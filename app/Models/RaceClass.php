<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaceClass extends Model
{
    use HasUuids;

    protected $table = 'race_classes';

    protected $fillable = [
        'event_id',
        'template_id',
        'code',
        'name',
        'level_order',
        'quota',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'level_order' => 'integer',
            'quota' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ClassTemplate::class, 'template_id');
    }

    public function registrationItems(): HasMany
    {
        return $this->hasMany(RegistrationItem::class);
    }

    public function eligibilityRules(): HasMany
    {
        return $this->hasMany(ClassEligibilityRule::class, 'race_class_id');
    }

    public function scopeForEvent($query, string $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function getRemainingQuota(): int
    {
        if ($this->quota === null) {
            return PHP_INT_MAX;
        }

        $registeredCount = $this->registrationItems()
            ->whereHas('registration', function ($q) {
                $q->whereNotIn('status', ['rejected', 'cancelled']);
            })
            ->count();

        return max(0, $this->quota - $registeredCount);
    }

    public function isFull(): bool
    {
        return $this->getRemainingQuota() === 0;
    }
}
