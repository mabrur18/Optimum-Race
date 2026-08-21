<?php

namespace App\Models;

use App\Enums\LicenseType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    use HasUuids;

    protected $fillable = [
        'rider_id',
        'type',
        'number',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => LicenseType::class,
            'expires_at' => 'date',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function scopeByType($query, LicenseType $type)
    {
        return $query->where('type', $type);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
