<?php

namespace App\Http\Controllers\Rider;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Rider;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $rider = Rider::where('user_id', $user->id)->first();

        if (! $rider) {
            return Inertia::render('Rider/Dashboard', [
                'stats' => [
                    'total_events' => 0,
                    'approved_registrations' => 0,
                    'pending_registrations' => 0,
                    'documents_ready' => 0,
                ],
                'recent_registrations' => [],
                'no_rider_profile' => true,
            ]);
        }

        $approvedValue = RegistrationStatus::Approved->value;
        $entryListValue = RegistrationStatus::EntryList->value;
        $pendingPaymentValue = RegistrationStatus::PendingPayment->value;
        $paymentReviewValue = RegistrationStatus::PaymentReview->value;

        $allRegs = Registration::with(['event.discipline'])->where('rider_id', $rider->id)->get();

        $stats = [
            'total_events' => $allRegs->count(),
            'approved_registrations' => $allRegs->filter(fn ($r) => in_array((string) ($r->status instanceof RegistrationStatus ? $r->status->value : $r->status), [$approvedValue, $entryListValue], true))->count(),
            'pending_registrations' => $allRegs->filter(fn ($r) => in_array((string) ($r->status instanceof RegistrationStatus ? $r->status->value : $r->status), [$pendingPaymentValue, $paymentReviewValue, 'draft'], true))->count(),
            'documents_ready' => 0,
        ];

        foreach ($allRegs as $reg) {
            try {
                if ($reg->status->canGenerateDocuments()) {
                    $stats['documents_ready']++;
                }
            } catch (\Throwable $e) {
            }
        }

        $recent = Registration::with(['event.discipline'])
            ->where('rider_id', $rider->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($reg) {
                $stValue = $reg->status instanceof RegistrationStatus
                    ? $reg->status->value
                    : (string) $reg->status;
                $stLabel = $reg->status instanceof RegistrationStatus
                    ? $reg->status->label()
                    : $stValue;
                $stColor = $reg->status instanceof RegistrationStatus
                    ? $reg->status->color()
                    : 'gray';

                $startDate = is_object($reg->event?->start_date)
                    ? $reg->event->start_date->toDateString()
                    : (string) ($reg->event?->start_date ?? '');

                return [
                    'id' => $reg->id,
                    'status' => $stValue,
                    'status_label' => $stLabel,
                    'status_color' => $stColor,
                    'created_at' => $reg->created_at ? $reg->created_at->toDateTimeString() : '',
                    'event' => [
                        'id' => $reg->event?->id,
                        'title' => $reg->event?->title ?? '-',
                        'location' => $reg->event?->location ?? '',
                        'start_date' => $startDate,
                        'discipline' => [
                            'name' => $reg->event?->discipline?->name ?? '-',
                        ],
                    ],
                ];
            });

        return Inertia::render('Rider/Dashboard', [
            'stats' => $stats,
            'recent_registrations' => $recent,
            'no_rider_profile' => false,
        ]);
    }
}
