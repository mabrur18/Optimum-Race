<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Draft = 'draft';
    case PendingPayment = 'pending_payment';
    case PaymentReview = 'payment_review';
    case Rejected = 'rejected';
    case Approved = 'approved';
    case EntryList = 'entry_list';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingPayment => 'Menunggu Pembayaran',
            self::PaymentReview => 'Pembayaran Dicek',
            self::Rejected => 'Ditolak',
            self::Approved => 'Disetujui',
            self::EntryList => 'Entry List',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingPayment => 'yellow',
            self::PaymentReview => 'blue',
            self::Rejected => 'red',
            self::Approved => 'green',
            self::EntryList => 'emerald',
            self::Cancelled => 'slate',
        };
    }

    public function isPaid(): bool
    {
        return in_array($this, [self::Approved, self::EntryList]);
    }

    public function canGenerateDocuments(): bool
    {
        return $this->isPaid();
    }
}
