<?php

namespace App\Jobs;

use App\Mail\RegistrationInvoice as RegistrationInvoiceMailable;
use App\Models\Invoice;
use App\Services\MailConfigService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendRegistrationInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $maxExceptions = 2;

    public function __construct(
        public Invoice $invoice,
    ) {
    }

    public function handle(): void
    {
        try {
            // TERAPKAN KONFIGURASI SMTP DINAMIS DARI DATABASE (jika tersedia)
            try {
                /** @var MailConfigService $mailConfigService */
                $mailConfigService = app(MailConfigService::class);
                $mailConfigService->applyRuntimeConfig();
            } catch (Throwable $e) {
                Log::warning('SendRegistrationInvoice: applyRuntimeConfig skipped - '.$e->getMessage());
            }

            $this->invoice->loadMissing(['registration.rider', 'registration.items.raceClass', 'registration.event']);

            $registration = $this->invoice->registration;
            if (! $registration) {
                Log::warning('SendRegistrationInvoice: registration not found for invoice '.$this->invoice->invoice_number);
                return;
            }

            $event = $registration?->event;
            $rider = $registration?->rider;
            $toEmail = $registration?->email ?? $rider?->email ?? null;
            if (! is_string($toEmail) || $toEmail === '' || ! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                Log::warning('SendRegistrationInvoice: email not valid for registration '.(string) $registration->id);
                $this->invoice->forceFill(['emailed_at' => now()])->save();
                return;
            }

            $pdfPath = null;
            try {
                $totalFormatted = 'Rp ' . number_format((float) $this->invoice->total, 0, ',', '.');
                $pdf = Pdf::loadView('mail.registration-invoice', [
                    'invoice' => $this->invoice,
                    'registration' => $registration,
                    'event' => $event,
                    'total_formatted' => $totalFormatted,
                    'payment_confirm_url' => route('payments.confirm.form.invoice', ['invoiceNumber' => $this->invoice->invoice_number]),
                ]);
                $relativePath = 'registrations/'.$registration->id.'/invoices/Invoice-'.$this->invoice->invoice_number.'.pdf';
                $put = Storage::disk('public')->put($relativePath, (string) $pdf->output());
                if ($put) {
                    $pdfPath = $relativePath;
                }
            } catch (Throwable $e) {
                report($e);
            }

            $mailable = new RegistrationInvoiceMailable($this->invoice, $pdfPath);
            // Pastikan menggunakan mailer 'smtp' yang sudah diterapkan konfigurasi dinamisnya
            Mail::mailer('smtp')->to($toEmail)->send($mailable);

            $this->invoice->forceFill(['emailed_at' => now()])->save();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
