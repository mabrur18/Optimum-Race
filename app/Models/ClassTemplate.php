<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'discipline_id',
        'name',
        'slug',
        'level_order',
    ];

    protected function casts(): array
    {
        return [
            'level_order' => 'integer',
        ];
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function raceClasses(): HasMany
    {
        return $this->hasMany(RaceClass::class, 'template_id');
    }
}
