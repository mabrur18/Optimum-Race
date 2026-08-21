<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk memastikan request yang memerlukan organizer_id
 * secara eksplisit mendapatkan scope yang benar.
 * 
 * Pada praktiknya, global scope OrganizerScope sudah menangani ini,
 * tapi middleware ini berguna untuk:
 * 1. Validasi akses awal
 * 2. Logging/audit
 * 3. Penanganan kasus khusus
 */
class EnsureOrganizerScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Superadmin bisa bypass scope
        if ($user && $user->role === UserRole::Superadmin->value) {
            return $next($request);
        }

        // Admin/Staff harus punya organizer_id
        if ($user && $user->hasOrganizerAccess()) {
            // Set organizer_id ke request untuk kemudahan akses
            $request->merge(['_organizer_id' => $user->organizer_id]);
            
            return $next($request);
        }

        // Rider dan guest tidak perlu organizer scope
        // (mereka akan diarahkan ke halaman publik)
        return $next($request);
    }
}
