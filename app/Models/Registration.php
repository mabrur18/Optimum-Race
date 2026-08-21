<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    use HasUuids;

    protected $fillable = [
        'rider_id',
        'event_id',
        'status',
        'full_name',
        'jersey_name',
        'id_number',
        'phone',
        'email',
        'regency',
        'province',
        'team_name',
        'start_number_main',
        'agree_waiver',
        'approved_by',
        'approved_at',
        'entry_list_published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'agree_waiver' => 'boolean',
            'approved_at' => 'datetime',
            'entry_list_published_at' => 'datetime',
        ];
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RegistrationItem::class);
    }

    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(RegistrationStatusLog::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function scopeForRider($query, string $riderId)
    {
        return $query->where('rider_id', $riderId);
    }

    public function scopeForEvent($query, string $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeWithStatus($query, RegistrationStatus|array $status)
    {
        return $query->whereIn('status', is_array($status) ? $status : [$status]);
    }

    public function calculateTotal(): float
    {
        return $this->items->sum(fn ($item) => $item->raceClass->price ?? 0);
    }
}
