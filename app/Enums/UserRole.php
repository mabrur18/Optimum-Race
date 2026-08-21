<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Staff = 'staff';
    case Rider = 'rider';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Admin => 'Admin EO',
            self::Staff => 'Staff EO',
            self::Rider => 'Peserta / Rider',
        };
    }

    /**
     * Role yang terikat dengan satu organizer (tenant).
     */
    public function isOrganizerRole(): bool
    {
        return in_array($this, [self::Admin, self::Staff]);
    }
}
