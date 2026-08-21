<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Role yang diizinkan (gunakan nama enum: superadmin, admin, staff, rider)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Pastikan role user diambil sebagai string value (handle enum cast)
        if ($user->role instanceof UserRole) {
            $userRoleValue = $user->role->value;
        } elseif (is_string($user->role)) {
            $userRoleValue = $user->role;
        } else {
            $userRoleValue = (string) $user->role;
        }

        // Normalisasi allowed roles ke string value
        $allowedRoleValues = [];
        foreach ($roles as $role) {
            $enum = UserRole::tryFrom($role);
            $allowedRoleValues[] = $enum ? $enum->value : $role;
        }

        if (! in_array($userRoleValue, $allowedRoleValues, true) && $userRoleValue !== UserRole::Superadmin->value) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
