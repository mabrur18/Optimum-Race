<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property string|null $organizer_id
 * @property UserRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'organizer_id', 'role'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    // =========================================================================
    // Relations
    // =========================================================================

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function rider(): HasMany
    {
        return $this->hasMany(Rider::class);
    }

    // =========================================================================
    // Role Check Helpers
    // =========================================================================

    public function isSuperadmin(): bool
    {
        return $this->role === UserRole::Superadmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::Staff;
    }

    public function isRider(): bool
    {
        return $this->role === UserRole::Rider;
    }

    public function isOrganizerMember(): bool
    {
        return $this->role === UserRole::Admin || $this->role === UserRole::Staff;
    }

    public function hasOrganizerAccess(): bool
    {
        return $this->organizer_id !== null && $this->isOrganizerMember();
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeByRole($query, UserRole $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByOrganizer($query, string $organizerId)
    {
        return $query->where('organizer_id', $organizerId);
    }

    public function scopeOrganizerMembers($query)
    {
        return $query->whereIn('role', [UserRole::Admin, UserRole::Staff]);
    }
}
