<?php

namespace App\Enums;

enum OrganizerPlan: string
{
    case Standard = 'standard';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Premium => 'Premium',
        };
    }
}
