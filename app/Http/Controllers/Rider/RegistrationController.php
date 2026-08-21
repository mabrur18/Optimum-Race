<?php

namespace App\Http\Controllers\Rider;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }
        $rider = Rider::where('user_id', $user->id)->first();
        if (! $rider) {
            return Inertia::render('Rider/Registrations/Index', [
                'registrations' => [
                    'data' => [],
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                    'per_page' => 15,
                    'links' => [],
                    'from' => null,
                    'to' => null,
                ],
                'filters' => $request->only(['status', 'search']),
                'statuses_list' => $this->statusList(),
                'no_rider_profile' => true,
            ]);
        }

        $query = Registration::with(['event.discipline', 'items.raceClass'])
            ->where('rider_id', $rider->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->whereHas('event', function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('location', 'like', "%{$request->search}%");
            });
        }

        $paginated = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $data = $paginated->toArray();
        foreach ($data['data'] as $i => $row) {
            if (isset($paginated->items()[$i]) && isset($paginated->items()[$i]->status)) {
                $st = $paginated->items()[$i]->status;
                $statusValue = $st instanceof RegistrationStatus ? $st->value : (string) $st;
                $data['data'][$i]['status'] = $statusValue;
                $data['data'][$i]['status_label'] = $st instanceof RegistrationStatus ? $st->label() : $statusValue;
                $data['data'][$i]['status_color'] = $st instanceof RegistrationStatus ? $st->color() : 'gray';
                try {
                    $data['data'][$i]['total_amount'] = $paginated->items()[$i]->calculateTotal();
                } catch (\Throwable $e) {
                    $data['data'][$i]['total_amount'] = 0;
                }
            }
        }
        $data['links'] = $paginated->linkCollection()->toArray();
        $data['from'] = $paginated->firstItem();
        $data['to'] = $paginated->lastItem();

        return Inertia::render('Rider/Registrations/Index', [
            'registrations' => $data,
            'filters' => $request->only(['status', 'search']),
            'statuses_list' => $this->statusList(),
            'no_rider_profile' => false,
        ]);
    }

    public function show(Request $request, string $id)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }
        $rider = Rider::where('user_id', $user->id)->first();
        if (! $rider) {
            abort(404);
        }

        $registration = Registration::with(['rider.licenses', 'event.discipline', 'event.organizer', 'items.raceClass', 'paymentProofs', 'invoice', 'documents'])
            ->where('id', $id)
            ->where('rider_id', $rider->id)
            ->firstOrFail();

        $regData = $registration->toArray();
        $regData['status'] = $registration->status instanceof RegistrationStatus
            ? $registration->status->value
            : (string) $registration->status;
        $regData['status_label'] = $registration->status->label();
        $regData['status_color'] = $registration->status->color();
        $regData['can_generate_documents'] = $registration->status->canGenerateDocuments();
        $regData['is_paid'] = $registration->status->isPaid();
        try {
            $regData['total_amount'] = $registration->calculateTotal();
        } catch (\Throwable $e) {
            $regData['total_amount'] = 0;
        }

        return Inertia::render('Rider/Registrations/Show', [
            'registration' => $regData,
        ]);
    }

    protected function statusList(): array
    {
        return array_map(fn ($s) => [
            'value' => $s->value,
            'label' => $s->label(),
            'color' => $s->color(),
        ], RegistrationStatus::cases());
    }
}
