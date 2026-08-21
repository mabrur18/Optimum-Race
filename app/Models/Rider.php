<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'full_name',
        'jersey_name',
        'id_number',
        'phone',
        'email',
        'regency',
        'province',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function scopeByIdNumber($query, string $idNumber)
    {
        return $query->where('id_number', $idNumber);
    }

    public function getFullNameAttribute($value): string
    {
        return strtoupper($value);
    }

    public function getDisplayName(): string
    {
        return $this->jersey_name ?? $this->full_name;
    }
}
