<?php

namespace App\Concerns;

use App\Models\Organizer;
use App\Scopes\OrganizerScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait untuk model yang terikat dengan organizer (tenant).
 * Otomatis menambahkan global scope agar data organizer terisolasi.
 */
trait BelongsToOrganizer
{
    public static function bootBelongsToOrganizer(): void
    {
        static::addGlobalScope(new OrganizerScope);

        static::creating(function ($model) {
            if (auth()->check() && blank($model->organizer_id)) {
                $user = auth()->user();
                if ($user->organizer_id) {
                    $model->organizer_id = $user->organizer_id;
                }
            }
        });
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Scope tanpa filter organizer (hanya untuk superadmin).
     */
    public function scopeWithoutOrganizerScope($query)
    {
        return $query->withoutGlobalScope(OrganizerScope::class);
    }
}
