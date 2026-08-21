<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ClassTemplate;
use App\Models\Event;
use App\Models\RaceClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RaceClassController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperadmin = $this->isSuperadmin($user);
        $organizerId = $user->organizer_id;

        $scopeOrganizer = function ($query) use ($organizerId, $isSuperadmin) {
            if (! $isSuperadmin) {
                $query->where('organizer_id', $organizerId);
            }
        };

        $query = RaceClass::with(['event.discipline', 'template'])->whereHas('event', function ($q) use ($scopeOrganizer) {
            $q->withoutGlobalScope(\App\Scopes\OrganizerScope::class)->where($scopeOrganizer);
        });

        if ($request->event_id) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        $raceClassesPaginated = $query
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $raceClassesData = $raceClassesPaginated->toArray();
        $raceClassesData['links'] = $raceClassesPaginated->linkCollection()->toArray();
        $raceClassesData['from'] = $raceClassesPaginated->firstItem();
        $raceClassesData['to'] = $raceClassesPaginated->lastItem();

        $eventsList = Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
            ->where($scopeOrganizer)
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn ($e) => ['id' => $e->id, 'title' => $e->title]);

        return Inertia::render('Admin/RaceClasses/Index', [
            'race_classes' => $raceClassesData,
            'filters' => $request->only(['event_id', 'search']),
            'events_list' => $eventsList,
        ]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $isSuperadmin = $this->isSuperadmin($user);
        $organizerId = $user->organizer_id;

        $scopeOrganizer = function ($query) use ($organizerId, $isSuperadmin) {
            if (! $isSuperadmin) {
                $query->where('organizer_id', $organizerId);
            }
        };

        $events = Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
            ->where($scopeOrganizer)
            ->orderBy('title')
            ->get(['id', 'title']);

        $templates = ClassTemplate::orderBy('name')->get(['id', 'name', 'level_order']);

        $selectedEventId = $request->input('event_id');

        return Inertia::render('Admin/RaceClasses/Create', [
            'events' => $events,
            'templates' => $templates,
            'selected_event_id' => $selectedEventId,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'template_id' => ['nullable', 'exists:class_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', 'unique:race_classes,code'],
            'level_order' => ['nullable', 'integer', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $event = Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)->findOrFail($validated['event_id']);
        $this->ensureCanAccessEvent($event);

        RaceClass::create([
            ...$validated,
            'level_order' => $validated['level_order'] ?? 0,
        ]);

        $redirect = redirect()->route('admin.race-classes.index');
        if ($validated['event_id']) {
            $redirect = $redirect->with('success', 'Kelas race berhasil ditambahkan ke event.');
        }

        return $redirect;
    }

    public function edit(RaceClass $raceClass)
    {
        $user = Auth::user();
        $isSuperadmin = $this->isSuperadmin($user);
        $organizerId = $user->organizer_id;

        $scopeOrganizer = function ($query) use ($organizerId, $isSuperadmin) {
            if (! $isSuperadmin) {
                $query->where('organizer_id', $organizerId);
            }
        };

        $raceClass->load('event');
        $this->ensureCanAccessEvent($raceClass->event);

        $events = Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
            ->where($scopeOrganizer)
            ->orderBy('title')
            ->get(['id', 'title']);

        $templates = ClassTemplate::orderBy('name')->get(['id', 'name', 'level_order']);

        $raceClassData = $raceClass->toArray();

        return Inertia::render('Admin/RaceClasses/Edit', [
            'race_class' => $raceClassData,
            'events' => $events,
            'templates' => $templates,
        ]);
    }

    public function update(Request $request, RaceClass $raceClass)
    {
        $raceClass->load('event');
        $this->ensureCanAccessEvent($raceClass->event);

        $validated = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'template_id' => ['nullable', 'exists:class_templates,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', 'unique:race_classes,code,' . $raceClass->id],
            'level_order' => ['nullable', 'integer', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $raceClass->update([
            ...$validated,
            'level_order' => $validated['level_order'] ?? 0,
        ]);

        return redirect()->route('admin.race-classes.index')
            ->with('success', 'Kelas race berhasil diperbarui.');
    }

    public function destroy(RaceClass $raceClass)
    {
        $raceClass->load('event');
        $this->ensureCanAccessEvent($raceClass->event);

        if ($raceClass->registrationItems()->count() > 0) {
            return redirect()->route('admin.race-classes.index')
                ->with('error', 'Kelas tidak dapat dihapus karena sudah ada peserta yang mendaftar.');
        }

        $raceClass->delete();

        return redirect()->route('admin.race-classes.index')
            ->with('success', 'Kelas race berhasil dihapus.');
    }

    protected function isSuperadmin($user): bool
    {
        return ($user->role instanceof UserRole)
            ? $user->role === UserRole::Superadmin
            : $user->role === UserRole::Superadmin->value;
    }

    protected function ensureCanAccessEvent(Event $event): void
    {
        $user = Auth::user();
        if ($this->isSuperadmin($user)) {
            return;
        }
        if ((string) $event->organizer_id !== (string) $user->organizer_id) {
            abort(403, 'Anda tidak memiliki izin mengakses kelas ini.');
        }
    }
}
