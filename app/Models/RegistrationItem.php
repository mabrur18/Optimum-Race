<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationItem extends Model
{
    use HasUuids;

    protected $table = 'registration_items';

    protected $fillable = [
        'registration_id',
        'race_class_id',
        'team_id',
        'start_number',
        'is_main_class',
    ];

    protected function casts(): array
    {
        return [
            'is_main_class' => 'boolean',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function raceClass(): BelongsTo
    {
        return $this->belongsTo(RaceClass::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
