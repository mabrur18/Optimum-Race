<?php

namespace App\Enums;

enum LicenseType: string
{
    case KtaIof = 'kta_iof';
    case KisImi = 'kis_imi';
    case Sim = 'sim';

    public function label(): string
    {
        return match ($this) {
            self::KtaIof => 'KTA IOF',
            self::KisImi => 'KIS IMI',
            self::Sim => 'SIM',
        };
    }
}
