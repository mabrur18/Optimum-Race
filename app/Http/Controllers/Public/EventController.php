<?php

namespace App\Http\Controllers\Public;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\RaceClass;
use App\Models\Rider;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EventController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $event = Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
            ->with(['discipline', 'organizer', 'raceClasses'])
            ->where('slug', $slug)
            ->whereIn('status', [EventStatus::Published->value, EventStatus::Closed->value])
            ->firstOrFail();

        $eventArr = $event->toArray();
        $eventArr['status'] = $event->status instanceof EventStatus
            ? $event->status->value
            : (string) $event->status;
        $eventArr['is_open_for_registration'] = $event->isOpenForRegistration();
        $eventArr['total_registrations'] = $event->getTotalRegistrationsCount();

        foreach ($eventArr['race_classes'] as $i => $rc) {
            $rcModel = $event->raceClasses[$i] ?? null;
            $eventArr['race_classes'][$i]['remaining_quota'] = $rcModel ? $rcModel->getRemainingQuota() : ($rc['quota'] ?? null);
            $eventArr['race_classes'][$i]['is_full'] = $rcModel ? $rcModel->isFull() : false;
        }

        $formatCurrency = function ($amount) {
            return 'Rp ' . number_format($amount, 0, ',', '.');
        };

        foreach ($eventArr['race_classes'] as $i => $rc) {
            $eventArr['race_classes'][$i]['price_formatted'] = $formatCurrency($rc['price'] ?? 0);
        }

        $currentUser = Auth::user();
        $hasExistingRegistration = false;
        if ($currentUser) {
            $rider = Rider::where('user_id', $currentUser->id)->first();
            if ($rider) {
                $hasExistingRegistration = Registration::where('rider_id', $rider->id)
                    ->where('event_id', $event->id)
                    ->exists();
            }
        }

        $entryListRaw = Registration::where('event_id', $event->id)
            ->where('status', RegistrationStatus::EntryList->value)
            ->with(['items.raceClass'])
            ->orderBy('entry_list_published_at')
            ->get();

        $entryList = [];
        foreach ($entryListRaw as $reg) {
            $startNumber = $reg->start_number_main ?? null;
            $perClassMap = [];
            foreach (($reg->items ?? collect([])) as $item) {
                $rcId = (string) ($item->race_class_id ?? '');
                if ($rcId === '') {
                    continue;
                }
                $sn = ! empty($item->start_number) ? $item->start_number : null;
                if (! empty($item->is_main_class) && $sn !== null) {
                    $startNumber = $sn;
                }
                $perClassMap[$rcId] = $sn;
            }

            $baseRow = [
                'full_name' => $reg->full_name ?? ($reg->rider?->full_name ?? null),
                'team_name' => $reg->team_name ?? ($reg->rider?->team_name ?? null),
            ];

            foreach (($reg->items ?? collect([])) as $item) {
                $rcId = (string) ($item->race_class_id ?? '');
                if ($rcId === '') {
                    continue;
                }
                $sn = $perClassMap[$rcId] ?? $startNumber;
                $row = [
                    'registration_id' => (string) $reg->id,
                    'full_name' => $baseRow['full_name'],
                    'team_name' => $baseRow['team_name'],
                    'start_number' => $sn,
                ];
                if (! isset($entryList[$rcId])) {
                    $entryList[$rcId] = [];
                }
                $entryList[$rcId][] = $row;
            }
        }

        return Inertia::render('Public/EventDetail', [
            'event' => $eventArr,
            'has_existing_registration' => $hasExistingRegistration,
            'entry_list_by_race_class' => $entryList,
        ]);
    }

    public function showRegistrationForm(Request $request, string $slug)
    {
        $event = Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
            ->with(['discipline', 'organizer', 'raceClasses'])
            ->where('slug', $slug)
            ->where('status', EventStatus::Published->value)
            ->firstOrFail();

        if (! $event->isOpenForRegistration()) {
            return redirect()->route('events.show', $slug)
                ->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        $eventArr = $event->toArray();
        $eventArr['status'] = $event->status instanceof EventStatus
            ? $event->status->value
            : (string) $event->status;

        $availableClasses = [];
        foreach ($event->raceClasses as $rc) {
            if (! $rc->isFull()) {
                $availableClasses[] = [
                    'id' => $rc->id,
                    'name' => $rc->name,
                    'code' => $rc->code,
                    'price' => (float) $rc->price,
                    'remaining_quota' => $rc->getRemainingQuota(),
                ];
            }
        }
        $eventArr['available_classes'] = $availableClasses;
        $eventArr['eligibility_allowed_map'] = $event->getEligibilityAllowedMap();

        $currentUser = Auth::user();
        $prefillRider = null;
        if ($currentUser) {
            $rider = Rider::where('user_id', $currentUser->id)->first();
            if ($rider) {
                $prefillRider = [
                    'full_name' => $rider->full_name,
                    'jersey_name' => $rider->jersey_name,
                    'id_number' => $rider->id_number,
                    'phone' => $rider->phone,
                    'email' => $rider->email ?? $currentUser->email,
                    'regency' => $rider->regency,
                    'province' => $rider->province,
                ];
            } elseif ($currentUser->name || $currentUser->email) {
                $prefillRider = [
                    'full_name' => $currentUser->name ?? '',
                    'jersey_name' => '',
                    'id_number' => '',
                    'phone' => '',
                    'email' => $currentUser->email ?? '',
                    'regency' => '',
                    'province' => '',
                ];
            }
        }

        return Inertia::render('Public/EventRegister', [
            'event' => $eventArr,
            'prefill_rider' => $prefillRider,
        ]);
    }

    public function submitRegistration(Request $request, string $slug)
    {
        $event = Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
            ->where('slug', $slug)
            ->where('status', EventStatus::Published->value)
            ->firstOrFail();

        if (! $event->isOpenForRegistration()) {
            return redirect()->route('events.show', $slug)
                ->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        $request->merge([
            'race_class_ids' => is_array($request->input('race_class_ids'))
                ? $request->input('race_class_ids')
                : [],
            'additional_class_ids' => is_array($request->input('additional_class_ids'))
                ? $request->input('additional_class_ids')
                : [],
        ]);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'jersey_name' => ['nullable', 'string', 'max:128'],
            'id_number' => ['required', 'string', 'max:64'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'regency' => ['required', 'string', 'max:128'],
            'province' => ['required', 'string', 'max:128'],
            'main_race_class_id' => ['required', 'string', 'exists:race_classes,id'],
            'additional_class_ids' => ['nullable', 'array'],
            'additional_class_ids.*' => ['exists:race_classes,id'],
            'race_class_ids' => ['nullable', 'array'],
            'race_class_ids.*' => ['exists:race_classes,id'],
            'team_name' => ['nullable', 'string', 'max:255'],
            'start_number_main' => ['nullable', 'string', 'max:16'],
            'agree_waiver' => ['required', 'accepted'],
            'document_type' => ['required', 'string', 'in:kis,kta_iof,surat_rekomendasi'],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'proof_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if (! is_array($validated['race_class_ids'] ?? null)) {
            $validated['race_class_ids'] = [];
        }
        if (! is_array($validated['additional_class_ids'] ?? null)) {
            $validated['additional_class_ids'] = [];
        }
        if ($validated['main_race_class_id'] && ! in_array($validated['main_race_class_id'], $validated['race_class_ids'], true)) {
            $validated['race_class_ids'][] = $validated['main_race_class_id'];
        }
        foreach ($validated['additional_class_ids'] as $addId) {
            if (is_string($addId) && $addId !== '' && ! in_array($addId, $validated['race_class_ids'], true)) {
                $validated['race_class_ids'][] = $addId;
            }
        }
        $validated['race_class_ids'] = array_values(array_filter(array_unique($validated['race_class_ids']), 'is_string'));
        $validated['additional_class_ids'] = array_values(array_filter(array_unique($validated['additional_class_ids']), 'is_string'));

        $classIds = array_values(array_unique($validated['race_class_ids']));
        $raceClasses = RaceClass::whereIn('id', $classIds)
            ->where('event_id', $event->id)
            ->get();

        if ($raceClasses->count() !== count($classIds)) {
            return redirect()->back()->withInput()
                ->withErrors(['race_class_ids' => 'Beberapa kelas tidak valid untuk event ini.']);
        }

        if ($validated['main_race_class_id'] && !in_array($validated['main_race_class_id'], $classIds, true)) {
            return redirect()->back()->withInput()
                ->withErrors(['main_race_class_id' => 'Kelas utama tidak valid atau tidak tersedia.']);
        }

        foreach ($validated['additional_class_ids'] ?? [] as $addId) {
            if (!in_array($addId, $classIds, true)) continue;
            if ($event->isEligibleCombination([$validated['main_race_class_id'], $addId])) continue;
            $pair = $event->getFirstInvalidEligibilityPair([$validated['main_race_class_id'], $addId]);
            return redirect()->back()->withInput()
                ->withErrors([
                    'additional_class_ids' => $pair ? $pair['message'] : "Kelas tambahan tidak dapat digabungkan dengan kelas utama.",
                ]);
        }

        $invalidPair = $event->getFirstInvalidEligibilityPair($classIds);
        if ($invalidPair) {
            return redirect()->back()->withInput()
                ->withErrors(['race_class_ids' => $invalidPair['message']]);
        }

        foreach ($raceClasses as $rc) {
            if ($rc->isFull()) {
                return redirect()->back()->withInput()
                    ->withErrors(['race_class_ids' => "Kelas {$rc->name} sudah penuh."]);
            }
        }

        $currentUser = Auth::user();
        $riderUserId = $currentUser?->id;

        DB::beginTransaction();
        try {
            $rider = Rider::firstOrCreate(
                ['id_number' => $validated['id_number']],
                [
                    'user_id' => $riderUserId,
                    'full_name' => $validated['full_name'],
                    'jersey_name' => $validated['jersey_name'],
                    'phone' => $validated['phone'],
                    'email' => $validated['email'],
                    'regency' => $validated['regency'],
                    'province' => $validated['province'],
                ]
            );

            if (! $rider->user_id && $riderUserId) {
                $rider->user_id = $riderUserId;
                $rider->save();
            }

            $existing = Registration::where('rider_id', $rider->id)
                ->where('event_id', $event->id)
                ->first();
            if ($existing) {
                DB::rollBack();
                return redirect()->route('events.show', $slug)
                    ->with('error', 'Anda sudah terdaftar di event ini. Silakan cek dashboard rider Anda.');
            }

            $registration = Registration::create([
                'rider_id' => $rider->id,
                'event_id' => $event->id,
                'status' => RegistrationStatus::PendingPayment,
                'full_name' => $validated['full_name'],
                'jersey_name' => $validated['jersey_name'] ?? null,
                'id_number' => $validated['id_number'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'regency' => $validated['regency'],
                'province' => $validated['province'],
                'team_name' => $validated['team_name'] ?? null,
                'start_number_main' => $validated['start_number_main'] ?? null,
                'agree_waiver' => (bool) ($validated['agree_waiver'] ?? false),
            ]);

            $mainClassId = $validated['main_race_class_id'] ?: $raceClasses->first()->id;
            foreach ($raceClasses as $rc) {
                $registration->items()->create([
                    'race_class_id' => $rc->id,
                    'start_number' => (string)$rc->id === (string)$mainClassId ? ($validated['start_number_main'] ?? null) : null,
                    'is_main_class' => (string)$rc->id === (string)$mainClassId,
                ]);
            }

            try {
                $registration->statusLogs()->create([
                    'from_status' => RegistrationStatus::Draft->value,
                    'to_status' => RegistrationStatus::PendingPayment->value,
                    'note' => 'Pendaftaran awal oleh rider',
                    'changed_by' => $riderUserId,
                ]);
            } catch (\Throwable $e) {
            }

            try {
                $docFile = $request->file('document_file');
                if ($docFile && $docFile->isValid()) {
                    $docName = sprintf(
                        '%s_%s_%s.%s',
                        (string) $registration->id,
                        (string) $validated['document_type'],
                        substr(md5((string) microtime(true)), 0, 6),
                        $docFile->getClientOriginalExtension() ?: 'bin'
                    );
                    $stored = $docFile->storeAs('registrations/'.$registration->id.'/documents', $docName, 'public');
                    if ($stored) {
                        $docTypeRaw = (string) $validated['document_type'];
                        $docType = match ($docTypeRaw) {
                            'kis' => \App\Enums\DocumentType::Kis,
                            'kta_iof' => \App\Enums\DocumentType::KtaIof,
                            'surat_rekomendasi' => \App\Enums\DocumentType::SuratRekomendasi,
                            default => \App\Enums\DocumentType::RegistrationForm,
                        };
                        $registration->documents()->create([
                            'type' => $docType,
                            'document_uuid' => \Illuminate\Support\Str::uuid()->toString(),
                            'file_path' => $stored,
                            'version' => 1,
                            'generated_at' => now(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }

            try {
                $proofFile = $request->file('proof_file');
                if ($proofFile && $proofFile->isValid()) {
                    $proofName = sprintf(
                        'bukti-transfer-registrasi-%s-%s.%s',
                        (string) $registration->id,
                        substr(bin2hex(random_bytes(4)), 0, 8),
                        $proofFile->getClientOriginalExtension() ?: 'bin'
                    );
                    $proofStored = $proofFile->storeAs('registrations/'.$registration->id.'/payments', $proofName, 'public');
                    if ($proofStored) {
                        $registration->paymentProofs()->create([
                            'file_path' => $proofStored,
                            'uploaded_at' => now(),
                            'verification_status' => \App\Enums\PaymentVerificationStatus::Pending,
                            'rejection_reason' => null,
                        ]);

                        try {
                            $registration->statusLogs()->create([
                                'from_status' => RegistrationStatus::PendingPayment->value,
                                'to_status' => RegistrationStatus::PaymentReview->value,
                                'note' => '[Submit Form] Bukti transfer diupload bersamaan dengan pengiriman form pendaftaran',
                                'changed_by' => $riderUserId,
                            ]);
                        } catch (\Throwable $e) {
                        }

                        $registration->status = RegistrationStatus::PaymentReview;
                        $registration->save();
                    }
                }
            } catch (\Throwable $e) {
                report($e);
            }

            $registration->loadMissing('items.raceClass');
            $invoiceTotal = $registration->calculateTotal();
            do {
                $invoiceNumber = 'ORS-'.now()->format('Ym').'-'.strtoupper(\Illuminate\Support\Str::random(6));
            } while (\App\Models\Invoice::where('invoice_number', $invoiceNumber)->exists());
            $invoice = $registration->invoice()->create([
                'invoice_number' => $invoiceNumber,
                'total' => $invoiceTotal,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', 'Gagal menyimpan pendaftaran: ' . $e->getMessage());
        }

        try {
            if (isset($invoice)) {
                \App\Jobs\SendRegistrationInvoiceJob::dispatch($invoice)->delay(now()->addSeconds(5));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $redirect = redirect()->route('home');
        if ($currentUser) {
            $isRider = ($currentUser->role instanceof UserRole)
                ? $currentUser->role === UserRole::Rider
                : $currentUser->role === UserRole::Rider->value;
            if ($isRider) {
                $redirect = redirect()->route('rider.dashboard');
            }
        }

        return $redirect->with(
            'toast',
            [
                'type' => 'success',
                'message' => 'Pendaftaran berhasil dikirim! Invoice Anda '.$invoiceNumber.' sedang diproses. Cek email (jika tidak ada, cek spam) atau buka halaman konfirmasi pembayaran untuk upload bukti transfer.',
            ]
        );
    }

    public function showPaymentConfirmForm(Request $request, ?string $invoiceNumber = null)
    {
        $prefillInvoice = (string) ($invoiceNumber ?? $request->query('invoice') ?? $request->old('invoice_number') ?? '');
        $invoice = null;
        $registration = null;
        $event = null;

        if ($prefillInvoice !== '') {
            $invoice = \App\Models\Invoice::where('invoice_number', $prefillInvoice)
                ->with(['registration.event'])
                ->first();
            if ($invoice) {
                $registration = $invoice->registration;
                $event = $registration?->event;
            }
        }

        return Inertia::render('Public/PaymentConfirm', [
            'prefill_invoice' => $prefillInvoice,
            'invoice' => $invoice ? [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total' => (float) $invoice->total,
                'total_formatted' => 'Rp ' . number_format((float) $invoice->total, 0, ',', '.'),
                'paid_at' => $invoice->paid_at?->toIso8601String(),
                'emailed_at' => $invoice->emailed_at?->toIso8601String(),
            ] : null,
            'registration' => $registration ? [
                'id' => $registration->id,
                'status' => ($registration->status instanceof RegistrationStatus)
                    ? $registration->status->value
                    : (string) $registration->status,
                'status_label' => ($registration->status instanceof RegistrationStatus)
                    ? $registration->status->label()
                    : (string) $registration->status,
                'full_name' => $registration->full_name,
                'team_name' => $registration->team_name,
                'email' => $registration->email,
            ] : null,
            'event' => $event ? [
                'slug' => $event->slug,
                'title' => $event->title,
            ] : null,
            'submitted' => (bool) $request->session()->get('payment_submitted', false),
        ]);
    }

    public function submitPaymentProof(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => ['required', 'string', 'max:64', 'exists:invoices,invoice_number'],
            'proof_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $invoice = \App\Models\Invoice::where('invoice_number', $validated['invoice_number'])
            ->with(['registration'])
            ->firstOrFail();

        $registration = $invoice->registration;
        if (! $registration) {
            return redirect()->back()->withInput()
                ->withErrors(['invoice_number' => 'Data registrasi untuk invoice ini tidak ditemukan.']);
        }

        $allowedStatuses = [RegistrationStatus::PendingPayment, RegistrationStatus::PaymentReview, RegistrationStatus::Rejected];
        $statusOk = false;
        foreach ($allowedStatuses as $s) {
            $check = ($registration->status instanceof RegistrationStatus)
                ? $registration->status->value
                : (string) $registration->status;
            if ((string) $check === (string) $s->value) {
                $statusOk = true;
                break;
            }
        }
        if (! $statusOk) {
            return redirect()->back()->withInput()
                ->withErrors(['invoice_number' => 'Pendaftaran dengan invoice ini tidak dapat diunggah bukti pembayaran saat ini (status: '.($registration->status instanceof RegistrationStatus ? $registration->status->label() : (string) $registration->status).').']);
        }

        $proofFile = $request->file('proof_file');
        if (! $proofFile || ! $proofFile->isValid()) {
            return redirect()->back()->withInput()
                ->withErrors(['proof_file' => 'Bukti pembayaran tidak dapat dibaca.']);
        }

        DB::beginTransaction();
        try {
            $proofName = sprintf(
                'proof_%s_%s.%s',
                (string) $registration->id,
                substr(md5((string) microtime(true)), 0, 6),
                $proofFile->getClientOriginalExtension() ?: 'bin'
            );
            $storedPath = $proofFile->storeAs('registrations/'.$registration->id.'/payments', $proofName, 'public');

            $registration->paymentProofs()->create([
                'file_path' => $storedPath,
                'uploaded_at' => now(),
                'verification_status' => \App\Enums\PaymentVerificationStatus::Pending,
                'rejection_reason' => null,
            ]);

            $oldStatus = ($registration->status instanceof RegistrationStatus)
                ? $registration->status->value
                : (string) $registration->status;

            if ((string) $oldStatus !== (string) RegistrationStatus::PaymentReview->value) {
                $registration->status = RegistrationStatus::PaymentReview;
                $registration->save();

                try {
                    $registration->statusLogs()->create([
                        'from_status' => (string) $oldStatus,
                        'to_status' => RegistrationStatus::PaymentReview->value,
                        'note' => 'Unggah bukti pembayaran via halaman konfirmasi',
                        'changed_by' => null,
                    ]);
                } catch (\Throwable $e) {
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', 'Gagal mengunggah bukti pembayaran: ' . $e->getMessage());
        }

        return redirect()->route('payments.confirm.form.invoice', ['invoiceNumber' => $invoice->invoice_number])
            ->with('payment_submitted', true)
            ->with(
                'toast',
                [
                    'type' => 'success',
                    'message' => 'Bukti pembayaran berhasil diunggah! Nomor invoice telah diverifikasi sistem dan sedang menunggu review admin. Harap menunggu email balasan dalam 1x24 jam.',
                ]
            );
    }
}