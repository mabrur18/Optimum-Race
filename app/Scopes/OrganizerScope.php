<?php

namespace App\Scopes;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (! $user) {
            // Guest: tidak ada scope organizer khusus, biarkan seperti biasa
            // (untuk halaman publik, visibility diatur oleh status event)
            return;
        }

        // Superadmin bisa lihat semua data
        if ($user->role === UserRole::Superadmin->value) {
            return;
        }

        // Admin / Staff hanya boleh lihat data organizer miliknya
        if ($user->organizer_id) {
            $builder->where($model->qualifyColumn('organizer_id'), $user->organizer_id);
        }
    }
}
