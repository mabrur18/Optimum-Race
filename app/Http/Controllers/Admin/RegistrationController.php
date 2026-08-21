<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperadmin = $this->isSuperadmin($user);
        $organizerId = $user->organizer_id;

        $scopeOrganizer = function ($query) use ($organizerId, $isSuperadmin) {
            if (! $isSuperadmin) {
                $query->where('organizer_id', $organizerId);
            }
        };

        $query = Registration::with(['rider', 'event.discipline', 'items.raceClass'])->whereHas('event', function ($q) use ($scopeOrganizer) {
            $q->withoutGlobalScope(\App\Scopes\OrganizerScope::class)->where($scopeOrganizer);
        });

        if ($request->event_id) {
            $query->where('event_id', $request->event_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $needle = "%{$request->search}%";
            $query->where(function ($q) use ($needle) {
                $q->whereHas('rider', function ($sub) use ($needle) {
                    $sub->where('full_name', 'like', $needle)
                        ->orWhere('jersey_name', 'like', $needle)
                        ->orWhere('id_number', 'like', $needle);
                })->orWhere(function ($regQ) use ($needle) {
                    $regQ->where('full_name', 'like', $needle)
                        ->orWhere('jersey_name', 'like', $needle)
                        ->orWhere('id_number', 'like', $needle);
                });
            });
        }

        $paginated = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $data = $paginated->toArray();

        foreach ($data['data'] as $i => $row) {
            if (isset($paginated->items()[$i]) && isset($paginated->items()[$i]->status)) {
                $st = $paginated->items()[$i]->status;
                $data['data'][$i]['status'] = $st instanceof RegistrationStatus
                    ? $st->value
                    : (string) $st;
            }
        }

        $data['links'] = $paginated->linkCollection()->toArray();
        $data['from'] = $paginated->firstItem();
        $data['to'] = $paginated->lastItem();

        $eventsList = Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
            ->where($scopeOrganizer)
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn ($e) => ['id' => $e->id, 'title' => $e->title]);

        $statusesList = array_map(fn ($s) => [
            'value' => $s->value,
            'label' => $s->label(),
            'color' => $s->color(),
        ], RegistrationStatus::cases());

        return Inertia::render('Admin/Registrations/Index', [
            'registrations' => $data,
            'filters' => $request->only(['event_id', 'status', 'search']),
            'events_list' => $eventsList,
            'statuses_list' => $statusesList,
        ]);
    }

    public function show(Registration $registration)
    {
        $registration->load(['rider.licenses', 'rider.user', 'event.discipline', 'event.organizer', 'items.raceClass', 'paymentProofs', 'invoice', 'statusLogs.changer', 'documents']);
        $this->ensureCanAccessEvent($registration->event);

        $regData = $registration->toArray();
        $regData['status'] = $registration->status instanceof RegistrationStatus
            ? $registration->status->value
            : (string) $registration->status;
        $regData['status_label'] = $registration->status->label();
        $regData['status_color'] = $registration->status->color();
        $regData['can_generate_documents'] = $registration->status->canGenerateDocuments();
        $regData['is_paid'] = $registration->status->isPaid();
        try {
            $regData['total_amount'] = $registration->calculateTotal();
        } catch (\Throwable $e) {
            $regData['total_amount'] = 0;
        }

        $invoice = $registration->invoice;
        $latestApprovedProof = $registration->paymentProofs
            ->where('verification_status', \App\Enums\PaymentVerificationStatus::Approved)
            ->sortByDesc('verified_at')
            ->first();
        $latestAnyProof = $registration->paymentProofs->sortByDesc('uploaded_at')->first();

        $checks = [
            'invoice_exists' => (bool) $invoice,
            'invoice_number' => $invoice?->invoice_number,
            'invoice_emailed' => (bool) ($invoice?->emailed_at),
            'any_proof_submitted' => (bool) $latestAnyProof,
            'proof_approved' => (bool) $latestApprovedProof,
            'proof_uploaded_at' => $latestAnyProof?->uploaded_at?->toIso8601String(),
            'proof_verified_at' => $latestApprovedProof?->verified_at?->toIso8601String(),
        ];
        $checks['can_be_approved'] = $checks['invoice_exists'] && $checks['proof_approved'];

        $statusesList = array_map(fn ($s) => [
            'value' => $s->value,
            'label' => $s->label(),
        ], RegistrationStatus::cases());

        return Inertia::render('Admin/Registrations/Show', [
            'registration' => $regData,
            'statuses_list' => $statusesList,
            'approval_checks' => $checks,
        ]);
    }

    public function updateStatus(Request $request, Registration $registration)
    {
        $registration->load(['event', 'invoice', 'paymentProofs']);
        $this->ensureCanAccessEvent($registration->event);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:draft,pending_payment,payment_review,rejected,approved,entry_list,cancelled'],
            'note' => ['nullable', 'string', 'max:255'],
            'verification_status' => ['nullable', 'string', 'in:pending,approved,rejected'],
            'proof_id' => ['nullable', 'string', 'exists:payment_proofs,id'],
            'rejection_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $oldStatus = $registration->status;
        $newStatus = RegistrationStatus::from($validated['status']);

        // Flow Ringkas: Admin verifikasi sekali → langsung ke Entry List (Publik).
        $autoPromoted = false;
        if ($newStatus === RegistrationStatus::Approved) {
            $newStatus = RegistrationStatus::EntryList;
            $autoPromoted = true;
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // LANGKAH 1: PROSES DULU verifikasi bukti transfer SEBELUM gating (penting! agar admin bisa 1x klik save).
            $proofJustApproved = false;
            if (! empty($validated['proof_id']) && ! empty($validated['verification_status'])) {
                $proof = $registration->paymentProofs->firstWhere('id', $validated['proof_id']);
                if (! $proof) {
                    try {
                        $proof = \App\Models\PaymentProof::find((string) $validated['proof_id']);
                    } catch (\Throwable $e) {
                        $proof = null;
                    }
                }
                if ($proof) {
                    $statusEnum = \App\Enums\PaymentVerificationStatus::tryFrom($validated['verification_status']);
                    if ($statusEnum) {
                        $prev = $proof->verification_status;
                        $proof->verification_status = $statusEnum;
                        $proof->verified_by = Auth::id();
                        $proof->verified_at = now();
                        if ($statusEnum === \App\Enums\PaymentVerificationStatus::Rejected) {
                            $proof->rejection_reason = $validated['rejection_reason'] ?? $proof->rejection_reason ?? null;
                        }
                        $proof->save();
                        if ($prev !== \App\Enums\PaymentVerificationStatus::Approved && $statusEnum === \App\Enums\PaymentVerificationStatus::Approved) {
                            $proofJustApproved = true;
                        }
                        // Refresh paymentProofs collection agar gating di bawah membaca status TERBARU (tanpa query DB ulang).
                        foreach ($registration->paymentProofs as $idx => $p) {
                            if ((string) $p->id === (string) $proof->id) {
                                $registration->paymentProofs[$idx] = $proof;
                            }
                        }
                    }
                }
            }

            // LANGKAH 2: Gating cek SELESAI verifikasi (apakah sudah ada bukti yang approved).
            if (in_array($newStatus, [RegistrationStatus::Approved, RegistrationStatus::EntryList], true)) {
                $invoice = $registration->invoice;
                $approvedProof = $registration->paymentProofs
                    ->where('verification_status', \App\Enums\PaymentVerificationStatus::Approved)
                    ->first();
                if (! $approvedProof && $proofJustApproved && isset($proof)) {
                    $approvedProof = $proof;
                }
                if (! $invoice) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    return redirect()->route('admin.registrations.show', $registration->id)
                        ->withErrors(['status' => 'Invoice belum dibuat untuk pendaftaran ini. Klik tombol "✨ BUAT INVOICE SECARA MANUAL" di panel Checklist.']);
                }
                if (! $approvedProof) {
                    \Illuminate\Support\Facades\DB::rollBack();
                    return redirect()->route('admin.registrations.show', $registration->id)
                        ->withErrors(['status' => 'Belum ada bukti pembayaran yang statusnya APPROVED. CARA: (1) Buka Tab "Invoice & Bukti", (2) di row bukti transfer pilih verifikasi "✅ SETUJUI (APPROVED)", (3) KEMBALI tekan tombol SIMPAN PERUBAHAN STATUS ini.']);
                }
            }

            $registration->status = $newStatus;
            if ($newStatus === RegistrationStatus::Approved || $newStatus === RegistrationStatus::EntryList) {
                $registration->approved_by = Auth::id();
                $registration->approved_at = now();
            }
            if ($newStatus === RegistrationStatus::EntryList) {
                $registration->entry_list_published_at = now();
                if ($registration->invoice && $registration->invoice->paid_at === null) {
                    $registration->invoice->paid_at = now();
                    $registration->invoice->save();
                }
            } else {
                if ($registration->getAttribute('entry_list_published_at') !== null && ! in_array($newStatus, [RegistrationStatus::Approved, RegistrationStatus::EntryList], true)) {
                    $registration->entry_list_published_at = null;
                }
            }
            $registration->save();

            try {
                $note = $validated['note'] ?? null;
                if ($autoPromoted && $note === null) {
                    $note = '[Flow Ringkas] Otomatis promote dari Approved → Entry List (Publik) setelah verifikasi bukti pembayaran lolos.';
                }
                $registration->statusLogs()->create([
                    'from_status' => $oldStatus instanceof RegistrationStatus ? $oldStatus->value : (string) $oldStatus,
                    'to_status' => $newStatus->value,
                    'note' => $note,
                    'changed_by' => Auth::id(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->route('admin.registrations.show', $registration->id)
                ->with('error', 'Gagal memperbarui status: ' . $e->getMessage());
        }

        return redirect()->route('admin.registrations.show', $registration->id)
            ->with('success', 'Status pendaftaran berhasil diperbarui.');
    }

    public function generateManualInvoice(Request $request, Registration $registration)
    {
        $registration->loadMissing(['event', 'items.raceClass', 'invoice']);
        $this->ensureCanAccessEvent($registration->event);

        if ($registration->invoice) {
            return redirect()->route('admin.registrations.show', $registration->id)
                ->with('warning', 'Invoice untuk pendaftaran ini SUDAH ADA (No: '.$registration->invoice->invoice_number.'). Tidak perlu dibuat ulang.');
        }

        try {
            $registration->loadMissing('items.raceClass');
            try {
                $invoiceTotal = $registration->calculateTotal();
            } catch (\Throwable $e) {
                $invoiceTotal = 0;
                foreach (($registration->items ?? collect()) as $item) {
                    $price = $item->raceClass?->price ?? 0;
                    if (is_numeric($price)) $invoiceTotal += (float) $price;
                }
            }

            if ($invoiceTotal <= 0) {
                return redirect()->route('admin.registrations.show', $registration->id)
                    ->withErrors(['generate_invoice' => 'Total tagihan 0. Pastikan item kelas pendaftaran (items) terhubung dengan benar sebelum generate invoice.']);
            }

            do {
                $invoiceNumber = 'ORS-'.now()->format('Ym').'-'.strtoupper(\Illuminate\Support\Str::random(6));
            } while (\App\Models\Invoice::where('invoice_number', $invoiceNumber)->exists());

            $invoice = $registration->invoice()->create([
                'invoice_number' => $invoiceNumber,
                'total' => $invoiceTotal,
            ]);

            try {
                $statusRaw = ($registration->status instanceof RegistrationStatus)
                    ? $registration->status->value
                    : (string) $registration->status;

                $registration->statusLogs()->create([
                    'from_status' => (string) $statusRaw,
                    'to_status' => (string) $statusRaw,
                    'note' => '[Generate Manual Invoice] Dibuat oleh admin (id '.Auth::id().'). Nomor: '.$invoiceNumber.', Total Rp '.number_format((float)$invoiceTotal, 0, ',', '.'),
                    'changed_by' => Auth::id(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }

            try {
                \App\Jobs\SendRegistrationInvoiceJob::dispatch($invoice)->delay(now()->addSeconds(3));
            } catch (\Throwable $e) {
                report($e);
            }

            return redirect()->route('admin.registrations.show', $registration->id)
                ->with('success', 'Invoice BERHASIL dibuat secara manual: '.$invoiceNumber.' (Total Rp '.number_format((float)$invoiceTotal, 0, ',', '.').'). Email invoice akan dikirim otomatis.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.registrations.show', $registration->id)
                ->withErrors(['generate_invoice' => 'Gagal generate invoice: '.$e->getMessage()]);
        }
    }

    public function storeManualPaymentProof(Request $request, Registration $registration)
    {
        $this->ensureCanAccessEvent($registration->event);

        $validated = $request->validate([
            'proof_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $now = now();
        try {
            $file = $validated['proof_file'];
            $original = $file->getClientOriginalName();
            $safeName = sprintf(
                'bukti-transfer-admin-%s-%s.%s',
                $now->format('YmdHis'),
                substr(bin2hex(random_bytes(4)), 0, 8),
                strtolower($file->getClientOriginalExtension() ?: 'pdf')
            );
            $relDir = 'registrations/' . $registration->id . '/payments';
            $stored = $file->storeAs($relDir, $safeName, 'public');
            if (! $stored) {
                throw new \RuntimeException('Gagal menyimpan file bukti pembayaran.');
            }

            $registration->paymentProofs()->create([
                'file_path' => $stored,
                'file_name' => $original,
                'uploaded_at' => $now,
                'verification_status' => \App\Enums\PaymentVerificationStatus::Pending,
                'rejection_reason' => $validated['note'] ?? null,
            ]);

            $registration->statusLogs()->create([
                'from_status' => ($registration->status instanceof RegistrationStatus)
                    ? $registration->status->value
                    : (string) $registration->status,
                'to_status' => ($registration->status instanceof RegistrationStatus)
                    ? $registration->status->value
                    : (string) $registration->status,
                'note' => '[Admin Upload Bukti Bayar] ' . ($original ?? 'file') . (! empty($validated['note']) ? ' — ' . $validated['note'] : ''),
                'changed_by' => Auth::id(),
            ]);

            return redirect()->route('admin.registrations.show', $registration->id)
                ->with('success', 'Bukti pembayaran berhasil diunggah secara manual oleh admin.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.registrations.show', $registration->id)
                ->withErrors(['upload' => 'Gagal mengunggah bukti pembayaran: ' . $e->getMessage()]);
        }
    }

    public function storeManualDocument(Request $request, Registration $registration)
    {
        $this->ensureCanAccessEvent($registration->event);

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:kis,kta_iof,surat_rekomendasi'],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $now = now();
        try {
            $file = $validated['document_file'];
            $original = $file->getClientOriginalName();
            $typeLabel = \App\Enums\DocumentType::tryFrom($validated['document_type'])?->label() ?? $validated['document_type'];
            $safeName = sprintf(
                '%s-admin-%s-%s.%s',
                $validated['document_type'],
                $now->format('YmdHis'),
                substr(bin2hex(random_bytes(4)), 0, 8),
                strtolower($file->getClientOriginalExtension() ?: 'pdf')
            );
            $relDir = 'registrations/' . $registration->id . '/documents';
            $stored = $file->storeAs($relDir, $safeName, 'public');
            if (! $stored) {
                throw new \RuntimeException('Gagal menyimpan file dokumen.');
            }

            $registration->documents()->create([
                'type' => $validated['document_type'],
                'file_path' => $stored,
                'file_name' => $original,
                'uploaded_at' => $now,
                'uploaded_by' => Auth::id(),
            ]);

            $registration->statusLogs()->create([
                'from_status' => ($registration->status instanceof RegistrationStatus)
                    ? $registration->status->value
                    : (string) $registration->status,
                'to_status' => ($registration->status instanceof RegistrationStatus)
                    ? $registration->status->value
                    : (string) $registration->status,
                'note' => '[Admin Upload Dokumen] ' . $typeLabel . ' : ' . ($original ?? 'file') . (! empty($validated['note']) ? ' — ' . $validated['note'] : ''),
                'changed_by' => Auth::id(),
            ]);

            return redirect()->route('admin.registrations.show', $registration->id)
                ->with('success', 'Dokumen ' . $typeLabel . ' berhasil diunggah secara manual.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.registrations.show', $registration->id)
                ->withErrors(['upload' => 'Gagal mengunggah dokumen: ' . $e->getMessage()]);
        }
    }

    protected function isSuperadmin($user): bool
    {
        return ($user->role instanceof UserRole)
            ? $user->role === UserRole::Superadmin
            : $user->role === UserRole::Superadmin->value;
    }

    protected function ensureCanAccessEvent(Event $event): void
    {
        $user = Auth::user();
        if ($this->isSuperadmin($user)) {
            return;
        }
        if ((string) $event->organizer_id !== (string) $user->organizer_id) {
            abort(403, 'Anda tidak memiliki izin mengakses pendaftaran ini.');
        }
    }
}
