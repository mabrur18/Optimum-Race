<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Dibuka',
            self::Closed => 'Ditutup',
            self::Archived => 'Diarsipkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Published => 'green',
            self::Closed => 'orange',
            self::Archived => 'slate',
        };
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::Published || $this === self::Closed;
    }
}
