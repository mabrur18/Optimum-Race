<?php

namespace App\Enums;

enum PaymentVerificationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
