<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Discipline;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $isSuperadmin = ($user->role instanceof UserRole)
            ? $user->role === UserRole::Superadmin
            : $user->role === UserRole::Superadmin->value;

        // Untuk admin: gunakan scope normal (sudah di-filter global scope)
        // Untuk superadmin: buang global scope agar lihat semua event
        $query = Event::query();
        if ($isSuperadmin) {
            $query->withoutGlobalScope(\App\Scopes\OrganizerScope::class);
        }

        $eventsPaginated = $query
            ->with(['discipline', 'organizer'])
            ->when(! $isSuperadmin, function ($query) use ($user) {
                // Tambahan filter organizer_id (double safe)
                $query->where('organizer_id', $user->organizer_id);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhere('eo_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Serialize events (enum -> string) + tambah `links`
        $eventsData = $eventsPaginated->toArray();
        foreach ($eventsData['data'] as $i => $ev) {
            if (isset($eventsPaginated->items()[$i]) && isset($eventsPaginated->items()[$i]->status)) {
                $st = $eventsPaginated->items()[$i]->status;
                $eventsData['data'][$i]['status'] = $st instanceof EventStatus
                    ? $st->value
                    : (string) $st;
            }
        }
        $eventsData['links'] = $eventsPaginated->linkCollection()->toArray();
        $eventsData['from'] = $eventsPaginated->firstItem();
        $eventsData['to'] = $eventsPaginated->lastItem();

        return Inertia::render('Admin/Events/Index', [
            'events' => $eventsData,
            'filters' => $request->only(['status', 'search']),
            'statuses' => array_map(fn ($status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ], EventStatus::cases()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $disciplines = Discipline::all()->map(fn ($d) => [
            'id' => $d->id,
            'name' => $d->name,
            'slug' => $d->slug,
        ]);

        $statuses = array_map(fn ($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], EventStatus::cases());

        return Inertia::render('Admin/Events/Create', [
            'disciplines' => $disciplines,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $isSuperadmin = ($user->role instanceof UserRole)
            ? $user->role === UserRole::Superadmin
            : $user->role === UserRole::Superadmin->value;

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'discipline_id' => ['required', 'exists:disciplines,id'],
            'location' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'eo_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:draft,published,closed,archived'],
            'description' => ['nullable', 'string'],
        ]);

        // Generate unique slug
        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;

        while (Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Tentukan organizer_id:
        // - Untuk Admin/Staff: pakai organizer_id user yang login
        // - Untuk Superadmin (tanpa organizer_id): fallback ke organizer pertama (default BKS Racing)
        $organizerId = $user->organizer_id;
        if ($isSuperadmin && empty($organizerId)) {
            $firstOrganizer = \App\Models\Organizer::first();
            if (! $firstOrganizer) {
                return redirect()->back()->withInput()->withErrors([
                    'organizer' => 'Tidak ada organizer terdaftar. Silakan buat organizer terlebih dahulu.',
                ]);
            }
            $organizerId = $firstOrganizer->id;
        }

        Event::create([
            ...$validated,
            'slug' => $slug,
            'organizer_id' => $organizerId,
        ]);

        return redirect()->route('admin.events.index')
            ->with('success', 'Event berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        $this->ensureCanAccessEvent($event);

        $event->load(['discipline', 'organizer', 'raceClasses', 'registrations.rider']);

        $eventArray = $event->toArray();

        // Convert enum status ke string
        if (isset($eventArray['status'])) {
            $eventArray['status'] = $event->status instanceof EventStatus
                ? $event->status->value
                : (string) $event->status;
        }

        // Serialize registrations status juga
        foreach ($eventArray['registrations'] ?? [] as $i => $reg) {
            if (isset($event->registrations[$i]) && isset($event->registrations[$i]->status)) {
                $st = $event->registrations[$i]->status;
                $eventArray['registrations'][$i]['status'] = $st instanceof \App\Enums\RegistrationStatus
                    ? $st->value
                    : (string) $st;
            }
        }

        return Inertia::render('Admin/Events/Show', [
            'event' => $eventArray,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event)
    {
        $this->ensureCanAccessEvent($event);

        $disciplines = Discipline::all()->map(fn ($d) => [
            'id' => $d->id,
            'name' => $d->name,
            'slug' => $d->slug,
        ]);

        $statuses = array_map(fn ($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], EventStatus::cases());

        $event->load(['raceClasses', 'classEligibilities']);

        $eligibilityMatrix = $event->buildEligibilityMatrix();

        $raceClasses = $eligibilityMatrix['race_classes']->toArray();
        $matrix = $eligibilityMatrix['matrix'];

        $allowedPairs = [];
        foreach ($matrix as $fromId => $cols) {
            foreach ($cols as $toId => $allowed) {
                if ($allowed) {
                    $allowedPairs[] = ['from' => $fromId, 'to' => $toId];
                }
            }
        }

        $eventData = $event->toArray();
        $eventData['status'] = $event->status instanceof EventStatus
            ? $event->status->value
            : (string) $event->status;

        return Inertia::render('Admin/Events/Edit', [
            'event' => $eventData,
            'disciplines' => $disciplines,
            'statuses' => $statuses,
            'race_classes' => $raceClasses,
            'default_eligible_pairs' => $allowedPairs,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event)
    {
        $this->ensureCanAccessEvent($event);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'discipline_id' => ['required', 'exists:disciplines,id'],
            'location' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'eo_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:draft,published,closed,archived'],
            'description' => ['nullable', 'string'],
            'eligible_pairs' => ['nullable', 'array'],
            'eligible_pairs.*.from' => ['required', 'string', 'exists:race_classes,id'],
            'eligible_pairs.*.to' => ['required', 'string', 'exists:race_classes,id'],
        ]);

        if ($event->title !== $validated['title']) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;

            while (Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
                ->where('slug', $slug)
                ->where('id', '!=', $event->id)
                ->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $validated['slug'] = $slug;
        }

        $event->update($validated);

        if (isset($validated['eligible_pairs'])) {
            $event->syncEligibilityMatrix($validated['eligible_pairs']);
        }

        return redirect()->route('admin.events.index')
            ->with('success', 'Event berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event)
    {
        $this->ensureCanAccessEvent($event);

        if ($event->registrations()->count() > 0) {
            return redirect()->route('admin.events.index')
                ->with('error', 'Event tidak dapat dihapus karena sudah ada pendaftaran.');
        }

        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', 'Event berhasil dihapus.');
    }

    /**
     * Pastikan user punya hak akses ke event ini.
     */
    protected function ensureCanAccessEvent(Event $event): void
    {
        $user = Auth::user();

        $isSuperadmin = ($user->role instanceof UserRole)
            ? $user->role === UserRole::Superadmin
            : $user->role === UserRole::Superadmin->value;

        if ($isSuperadmin) {
            return;
        }

        // Cek kepemilikan organizer_id (string comparison)
        if ((string) $event->organizer_id !== (string) $user->organizer_id) {
            abort(403, 'Anda tidak memiliki izin mengakses event ini.');
        }
    }
}
