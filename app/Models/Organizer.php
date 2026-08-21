<?php

namespace App\Models;

use App\Enums\OrganizerPlan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organizer extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'plan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'plan' => OrganizerPlan::class,
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function waiverTemplates(): HasMany
    {
        return $this->hasMany(WaiverTemplate::class);
    }
}
