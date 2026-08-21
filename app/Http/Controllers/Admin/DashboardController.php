<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\RaceClass;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $isSuperadmin = ($user->role instanceof UserRole)
            ? $user->role === UserRole::Superadmin
            : $user->role === UserRole::Superadmin->value;

        // Pastikan organizer_id ada (kecuali superadmin, bisa akses admin panel)
        if (empty($user->organizer_id) && ! $isSuperadmin) {
            return redirect()->route('home')->with(
                'error',
                'Akun admin Anda tidak terikat dengan Organizer manapun.'
            );
        }

        $organizerId = $user->organizer_id;

        // Untuk superadmin: tidak filter organizer_id, pakai global NULL
        // (tampil seluruh events)
        $scopeOrganizer = function ($query) use ($organizerId, $isSuperadmin) {
            if (! $isSuperadmin) {
                $query->where('organizer_id', $organizerId);
            }
        };

        // Status published disesuaikan dengan string value-nya
        // (hindari perbandingan enum dengan string di query builder)
        $publishedValue = EventStatus::Published->value;

        // Stats: Gunakan whereRaw organizer_id agar tidak bergantung pada global scope
        $stats = [
            'total_events' => Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
                ->where($scopeOrganizer)
                ->count(),
            'published_events' => Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
                ->where($scopeOrganizer)
                ->where('status', $publishedValue)
                ->count(),
            'total_race_classes' => RaceClass::whereHas('event', function ($query) use ($scopeOrganizer) {
                $query->withoutGlobalScope(\App\Scopes\OrganizerScope::class)
                    ->where($scopeOrganizer);
            })->count(),
            'total_registrations' => Registration::whereHas('event', function ($query) use ($scopeOrganizer) {
                $query->withoutGlobalScope(\App\Scopes\OrganizerScope::class)
                    ->where($scopeOrganizer);
            })->count(),
        ];

        // Recent events
        $recentEvents = Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
            ->with('discipline')
            ->where($scopeOrganizer)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($event) {
                // Pastikan status serialize sebagai string
                $statusValue = $event->status instanceof EventStatus
                    ? $event->status->value
                    : (string) $event->status;

                // Pastikan tanggal serialize sebagai string
                $startDateValue = is_object($event->start_date)
                    ? $event->start_date->toDateString()
                    : (string) $event->start_date;

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'status' => $statusValue,
                    'start_date' => $startDateValue,
                    'discipline' => [
                        'name' => $event->discipline->name ?? '-',
                    ],
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recent_events' => $recentEvents,
        ]);
    }
}
