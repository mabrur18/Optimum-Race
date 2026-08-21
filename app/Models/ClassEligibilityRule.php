<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassEligibilityRule extends Model
{
    use HasUuids;

    protected $table = 'class_eligibility_rules';

    protected $fillable = [
        'event_id',
        'race_class_id',
        'allowed_class_id',
        'note',
    ];

    public function raceClass(): BelongsTo
    {
        return $this->belongsTo(RaceClass::class, 'race_class_id');
    }

    public function allowedClass(): BelongsTo
    {
        return $this->belongsTo(RaceClass::class, 'allowed_class_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
