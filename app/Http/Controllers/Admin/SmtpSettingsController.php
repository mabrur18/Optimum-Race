<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSmtpSettingRequest;
use App\Http\Requests\Admin\TestSmtpConnectionRequest;
use App\Services\MailConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Symfony\Component\Mailer\Exception\TransportException;
use Throwable;

class SmtpSettingsController extends Controller
{
    public function __construct(
        protected MailConfigService $mailConfig,
    ) {
    }

    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $config = $this->mailConfig->all();
        $masked = $config;
        if (! empty($masked['smtp_password'])) {
            $masked['smtp_password'] = str_repeat('•', 16);
        }

        return Inertia::render('Admin/Settings/Smtp', [
            'config' => $masked,
            'is_configured' => $this->mailConfig->isConfigured(),
        ]);
    }

    public function store(StoreSmtpSettingRequest $request)
    {
        $this->ensureAdmin($request);

        try {
            $payload = $request->validated();
            if (empty($payload['smtp_encryption']) || $payload['smtp_encryption'] === 'none') {
                $payload['smtp_encryption'] = '';
            }
            if (! isset($payload['smtp_timeout']) || $payload['smtp_timeout'] === null) {
                $payload['smtp_timeout'] = 30;
            }

            // Jika password adalah mask (•), gunakan password tersimpan
            if (isset($payload['smtp_password']) && $payload['smtp_password'] !== null && trim($payload['smtp_password']) !== '' && ctype_punct((string) $payload['smtp_password'])) {
                $existing = $this->mailConfig->all();
                $payload['smtp_password'] = $existing['smtp_password'] ?? '';
            }

            $this->mailConfig->saveMany($payload);

            return redirect()->route('admin.settings.smtp.index')
                ->with('success', 'Pengaturan SMTP berhasil disimpan. Klik "Uji Koneksi & Kirim Email Uji" untuk memverifikasi.');
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.settings.smtp.index')
                ->withErrors(['save' => 'Gagal menyimpan pengaturan SMTP: '.$e->getMessage()]);
        }
    }

    public function test(TestSmtpConnectionRequest $request): JsonResponse
    {
        $this->ensureAdmin($request);

        try {
            $validated = $request->validated();

            // Gunakan payload dari request (bisa jadi belum disimpan)
            $tempPayload = [
                'smtp_host' => $validated['smtp_host'],
                'smtp_port' => (int) $validated['smtp_port'],
                'smtp_encryption' => $validated['smtp_encryption'] === 'none' ? '' : $validated['smtp_encryption'],
                'smtp_username' => $validated['smtp_username'] ?? '',
                'smtp_password' => ! empty($validated['smtp_password']) && ! ctype_punct((string) $validated['smtp_password'])
                    ? $validated['smtp_password']
                    : ($this->mailConfig->all()['smtp_password'] ?? ''),
                'smtp_from_address' => $validated['smtp_from_address'],
                'smtp_from_name' => $validated['smtp_from_name'] ?? 'Optimum Race',
                'smtp_timeout' => (int) ($validated['smtp_timeout'] ?? 30),
            ];

            $tempService = new MailConfigService();
            $tempService->saveMany($tempPayload);
            $tempService->applyRuntimeConfig();

            $toEmail = $validated['test_recipient'];
            $mailer = Mail::mailer('smtp');

            try {
                $mailer->raw(
                    "Ini adalah email uji dari Optimum Race System.\n\n".
                    "Waktu pengiriman: ".now()->format('d M Y H:i:s')."\n".
                    "Host SMTP: {$validated['smtp_host']}:{$validated['smtp_port']}\n".
                    "Enkripsi: ".strtoupper((string) $validated['smtp_encryption'])."\n".
                    "Pengirim: {$validated['smtp_from_name']} <{$validated['smtp_from_address']}>\n\n".
                    "Jika email ini sampai di kotak masuk Anda, berarti konfigurasi SMTP sudah BERHASIL.",
                    function ($message) use ($toEmail, $validated) {
                        $message
                            ->to($toEmail)
                            ->subject('[Uji Koneksi SMTP] Optimum Race — '.date('Y-m-d H:i'));
                    }
                );
            } catch (TransportException $te) {
                Log::warning('SMTP test transport failed: '.$te->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Koneksi SMTP GAGAL. Detail: '.$te->getMessage(),
                ], 422);
            } catch (Throwable $inner) {
                Log::warning('SMTP test send failed: '.$inner->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim email uji: '.$inner->getMessage(),
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Email uji BERHASIL dikirim ke '.$toEmail.'. Silakan cek Kotak Masuk atau folder Spam.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: '.$e->getMessage(),
            ], 500);
        }
    }

    protected function ensureAdmin(Request $request): void
    {
        $user = Auth::user() ?? $request->user();
        if (! $user) {
            abort(403, 'Login diperlukan.');
        }
        $role = $user->role;
        $roleValue = is_object($role) ? ($role->value ?? (string) $role) : (string) $role;
        if (! in_array($roleValue, ['admin', 'superadmin'], true)) {
            abort(403, 'Hanya Admin atau Superadmin yang dapat mengakses pengaturan ini.');
        }
    }
}
