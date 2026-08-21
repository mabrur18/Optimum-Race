<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Display the landing page with public events.
     */
    public function index(Request $request)
    {
        $events = Event::with(['discipline', 'organizer'])
            ->whereIn('status', [EventStatus::Published, EventStatus::Closed])
            ->orderBy('start_date', 'asc')
            ->take(6)
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'location' => $event->location,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'eo_name' => $event->eo_name ?? optional($event->organizer)->name ?? '-',
                'discipline' => $event->discipline ? [
                    'name' => $event->discipline->name,
                    'slug' => $event->discipline->slug,
                ] : null,
            ]);

        return Inertia::render('Welcome', [
            'events' => $events,
        ]);
    }
}
