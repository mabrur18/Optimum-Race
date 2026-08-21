<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discipline extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function classTemplates(): HasMany
    {
        return $this->hasMany(ClassTemplate::class);
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
