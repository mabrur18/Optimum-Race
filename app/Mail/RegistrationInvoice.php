<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class RegistrationInvoice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public ?string $pdfPath = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $fromAddress = Config::get('mail.from.address', 'hello@example.com');
        $fromName = Config::get('mail.from.name', 'Optimum Race');

        return new Envelope(
            from: new Address((string) $fromAddress, (string) $fromName),
            subject: 'Invoice Pendaftaran #'.$this->invoice->invoice_number.' | ORS (Optimum Racing System)',
        );
    }

    public function content(): Content
    {
        $registration = $this->invoice->registration;
        $event = $registration?->event;
        $formatRupiah = static fn ($amount) => 'Rp ' . number_format((float) $amount, 0, ',', '.');

        return new Content(
            view: 'mail.registration-invoice',
            with: [
                'invoice' => $this->invoice,
                'registration' => $registration,
                'event' => $event,
                'total_formatted' => $formatRupiah($this->invoice->total),
                'payment_confirm_url' => route('payments.confirm.form.invoice', ['invoiceNumber' => $this->invoice->invoice_number]),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->pdfPath && is_string($this->pdfPath) && $this->pdfPath !== '' && Storage::disk('public')->exists($this->pdfPath)) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->pdfPath)
                ->as('Invoice-'.$this->invoice->invoice_number.'.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
