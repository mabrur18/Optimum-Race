<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\RaceClass;
use App\Models\Registration;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Throwable;

class EntryListExportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $scope = $this->organizerScope($user);

        $singleRaceClass = $request->string('race_class_id', '')->toString();
        $raceClassIdsRaw = $request->input('race_class_ids', []);
        if ($singleRaceClass !== '') {
            $raceClassIdsRaw = [$singleRaceClass];
        }
        $filters = [
            'event_id' => $request->string('event_id', '')->toString() ?: null,
            'race_class_ids' => is_array($raceClassIdsRaw) ? $raceClassIdsRaw : [],
            'statuses' => $request->input('statuses', [RegistrationStatus::EntryList->value]),
            'search' => $request->string('search', '')->toString() ?: null,
            'per_page' => (int) $request->input('per_page', 15),
        ];
        if (! is_array($filters['race_class_ids'])) {
            $filters['race_class_ids'] = array_values(array_filter(array_map('strval', (array) explode(',', (string) $filters['race_class_ids']))));
        }
        if (! is_array($filters['statuses'])) {
            $filters['statuses'] = array_values(array_filter(array_map('strval', (array) explode(',', (string) $filters['statuses']))));
        }
        $filters['race_class_ids'] = array_values(array_filter($filters['race_class_ids'], 'is_string'));
        $filters['statuses'] = array_values(array_filter($filters['statuses'], 'is_string'));
        if ($filters['per_page'] < 1 || $filters['per_page'] > 200) {
            $filters['per_page'] = 15;
        }

        $events = Event::withoutGlobalScope(\App\Scopes\OrganizerScope::class)
            ->where($scope)
            ->orderByDesc('start_date')
            ->get(['id', 'title', 'start_date'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'start_date' => is_object($e->start_date) ? $e->start_date->toDateString() : (string) $e->start_date,
            ])
            ->toArray();

        $raceClassesQuery = RaceClass::query()
            ->with('event:id,title')
            ->whereHas('event', function ($q) use ($scope) {
                $q->withoutGlobalScope(\App\Scopes\OrganizerScope::class)->where($scope);
            })
            ->orderBy('name');
        if ($filters['event_id']) {
            $raceClassesQuery->where('event_id', $filters['event_id']);
        }
        $raceClasses = $raceClassesQuery->get(['id', 'event_id', 'name', 'code', 'quota', 'price'])
            ->map(fn ($rc) => [
                'id' => $rc->id,
                'event_id' => $rc->event_id,
                'name' => $rc->name,
                'code' => $rc->code,
                'quota' => $rc->quota,
                'price' => $rc->price,
                'event_title' => $rc->event?->title ?? '',
            ])
            ->toArray();

        $query = Registration::with(['event:id,title', 'rider:id,full_name,jersey_name,id_number,phone,email,province,regency', 'items.raceClass:id,name,code'])
            ->whereHas('event', function ($q) use ($scope) {
                $q->withoutGlobalScope(\App\Scopes\OrganizerScope::class)->where($scope);
            });

        if ($filters['event_id']) {
            $query->where('event_id', $filters['event_id']);
        }
        if (! empty($filters['race_class_ids'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->whereIn('race_class_id', $filters['race_class_ids']);
            });
        }
        if (! empty($filters['statuses'])) {
            $query->whereIn('status', $filters['statuses']);
        }
        if ($filters['search']) {
            $needle = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($needle) {
                $q->whereHas('rider', function ($sub) use ($needle) {
                    $sub->where('full_name', 'like', $needle)
                        ->orWhere('jersey_name', 'like', $needle)
                        ->orWhere('id_number', 'like', $needle)
                        ->orWhere('phone', 'like', $needle)
                        ->orWhere('email', 'like', $needle);
                })->orWhere('full_name', 'like', $needle)
                  ->orWhere('jersey_name', 'like', $needle)
                  ->orWhere('id_number', 'like', $needle)
                  ->orWhere('email', 'like', $needle)
                  ->orWhere('team_name', 'like', $needle)
                  ->orWhere('start_number_main', 'like', $needle);
            });
        }

        $paginated = $query->orderByDesc('entry_list_published_at')->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        $rows = [];
        foreach ($paginated->items() as $reg) {
            /** @var Registration $reg */
            $classes = $reg->items->map(fn ($i) => [
                'race_class_id' => $i->raceClass?->id,
                'name' => $i->raceClass?->name ?? '-',
                'code' => $i->raceClass?->code ?? '-',
                'price' => $i->raceClass?->price ?? 0,
            ])->values()->toArray();

            $statusRaw = $reg->status;
            $statusValue = $statusRaw instanceof RegistrationStatus
                ? $statusRaw->value
                : (string) $statusRaw;
            $statusEnum = RegistrationStatus::tryFrom($statusValue);
            $fullName = $reg->full_name ?? ($reg->rider?->full_name ?? '-');
            $jerseyName = $reg->jersey_name ?? ($reg->rider?->jersey_name ?? null);
            $idNumber = $reg->id_number ?? ($reg->rider?->id_number ?? '-');
            $phone = $reg->phone ?? ($reg->rider?->phone ?? '-');
            $email = $reg->email ?? ($reg->rider?->email ?? '-');
            $province = $reg->province ?? ($reg->rider?->province ?? '-');
            $regency = $reg->regency ?? ($reg->rider?->regency ?? '-');

            $rows[] = [
                'registration_id' => $reg->id,
                'event_id' => $reg->event_id,
                'event_title' => $reg->event?->title ?? '-',
                'status' => $statusValue,
                'status_label' => $statusEnum?->label() ?? $statusValue,
                'full_name' => $fullName,
                'jersey_name' => $jerseyName,
                'id_number' => $idNumber,
                'phone' => $phone,
                'email' => $email,
                'province' => $province,
                'regency' => $regency,
                'team_name' => $reg->team_name ?? '-',
                'start_number_main' => $reg->start_number_main,
                'classes' => $classes,
                'entry_list_published_at' => is_object($reg->entry_list_published_at)
                    ? $reg->entry_list_published_at->toIso8601String()
                    : ($reg->entry_list_published_at ? (string) $reg->entry_list_published_at : null),
                'created_at' => is_object($reg->created_at) ? $reg->created_at->toIso8601String() : (string) $reg->created_at,
            ];
        }

        $data = $paginated->toArray();
        $data['data'] = $rows;
        $data['links'] = $paginated->linkCollection()->toArray();
        $data['from'] = $paginated->firstItem();
        $data['to'] = $paginated->lastItem();

        $statusesList = array_map(fn ($s) => [
            'value' => $s->value,
            'label' => $s->label(),
        ], RegistrationStatus::cases());

        $summary = [
            'total_filtered' => $paginated->total(),
            'by_race_class' => $this->summaryByRaceClass($filters, $scope),
        ];

        return Inertia::render('Admin/EntryList', [
            'entries' => $data,
            'filters' => $filters,
            'events' => $events,
            'race_classes' => $raceClasses,
            'statuses_list' => $statusesList,
            'summary' => $summary,
        ]);
    }

    public function export(Request $request, string $format)
    {
        if (! in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            return redirect()
                ->back(fallback: route('admin.entry-list.index'))
                ->with('error', 'Format ekspor tidak valid. Pilih format: Excel, CSV, atau PDF.');
        }
        $user = Auth::user();
        $scope = $this->organizerScope($user);

        $singleRaceClass = $request->string('race_class_id', '')->toString();
        $raceClassIds = $request->input('race_class_ids', []);
        if ($singleRaceClass !== '') {
            $raceClassIds = [$singleRaceClass];
        }
        if (! is_array($raceClassIds)) {
            $raceClassIds = array_values(array_filter(array_map('strval', (array) explode(',', (string) $raceClassIds))));
        }
        $raceClassIds = array_values(array_filter($raceClassIds, 'is_string'));
        $eventId = $request->string('event_id', '')->toString() ?: null;
        $search = $request->string('search', '')->toString() ?: null;
        $statuses = $request->input('statuses', [RegistrationStatus::EntryList->value]);
        if (! is_array($statuses)) {
            $statuses = array_values(array_filter(array_map('strval', (array) explode(',', (string) $statuses))));
        }
        $statuses = array_values(array_filter($statuses, 'is_string'));

        try {
            $rows = $this->collectForExport($scope, $eventId, $raceClassIds, $statuses, $search);
            $filenameBase = 'entry-list-'.now()->format('Ymd-His');

            return match ($format) {
                'csv' => $this->exportCsv($rows, $filenameBase),
                'xlsx' => $this->exportXlsx($rows, $filenameBase),
                'pdf' => $this->exportPdf($rows, $filenameBase),
            };
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->back(fallback: route('admin.entry-list.index'))
                ->with('error', 'Gagal mengekspor file. Silakan coba beberapa saat lagi. ('.$e->getMessage().')');
        }
    }

    protected function collectForExport(callable $scope, ?string $eventId, array $raceClassIds, array $statuses, ?string $search): array
    {
        $query = Registration::with(['rider:id,full_name,regency', 'items.raceClass:id,name,code,price'])
            ->whereHas('event', function ($q) use ($scope) {
                $q->withoutGlobalScope(\App\Scopes\OrganizerScope::class)->where($scope);
            });

        if ($eventId) {
            $query->where('event_id', $eventId);
        }
        if (! empty($raceClassIds)) {
            $query->whereHas('items', function ($q) use ($raceClassIds) {
                $q->whereIn('race_class_id', $raceClassIds);
            });
        }
        if (! empty($statuses)) {
            $query->whereIn('status', $statuses);
        }
        if ($search) {
            $needle = '%'.$search.'%';
            $query->where(function ($q) use ($needle) {
                $q->whereHas('rider', function ($sub) use ($needle) {
                    $sub->where('full_name', 'like', $needle)
                        ->orWhere('id_number', 'like', $needle);
                })->orWhere('full_name', 'like', $needle)
                  ->orWhere('team_name', 'like', $needle)
                  ->orWhere('start_number_main', 'like', $needle);
            });
        }

        $ordered = $query->orderByDesc('entry_list_published_at')->orderBy('full_name')->limit(10000)->get();

        $result = [];
        foreach ($ordered as $reg) {
            /** @var Registration $reg */
            $fullName = trim((string) ($reg->full_name ?? ($reg->rider?->full_name ?? '')));
            $startNo = trim((string) ($reg->start_number_main ?? ''));
            $teamName = trim((string) ($reg->team_name ?? ''));
            $cityName = trim((string) ($reg->regency ?? ($reg->rider?->regency ?? '')));

            $result[] = [
                'Nama Peserta' => $fullName !== '' ? $fullName : '-',
                'Nomor Start' => $startNo !== '' ? $startNo : '-',
                'Nama Tim' => $teamName !== '' ? $teamName : '-',
                'Kota Asal' => $cityName !== '' ? $cityName : '-',
            ];
        }

        return $result;
    }

    protected function exportCsv(array $rows, string $filenameBase): Response
    {
        $headers = [];
        if ($rows) {
            $headers = array_keys($rows[0]);
        }

        $stream = fopen('php://temp', 'r+b');
        if ($stream === false) {
            abort(500, 'Gagal menyiapkan stream CSV.');
        }
        // BOM UTF-8 agar Excel mengenali karakter
        fwrite($stream, (string) "\xEF\xBB\xBF");
        if ($headers) {
            fputcsv($stream, $headers, ',', '"');
        }
        foreach ($rows as $row) {
            fputcsv($stream, array_values($row), ',', '"');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return response((string) $csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filenameBase.'.csv"',
        ]);
    }

    protected function exportXlsx(array $rows, string $filenameBase): Response
    {
        $headers = [];
        if ($rows) {
            $headers = array_keys($rows[0]);
        }

        $esc = static fn ($v): string => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $rowsHtml = '';
        if ($headers) {
            $rowsHtml .= '<Row>';
            foreach ($headers as $h) {
                $rowsHtml .= '<Cell ss:StyleID="sHeader"><Data ss:Type="String">'.$esc($h).'</Data></Cell>';
            }
            $rowsHtml .= '</Row>';
        }
        foreach ($rows as $r) {
            $rowsHtml .= '<Row>';
            foreach (array_values($r) as $cell) {
                $type = 'String';
                if (is_numeric($cell) && (string) $cell !== '') {
                    $type = 'Number';
                }
                $rowsHtml .= '<Cell><Data ss:Type="'.$type.'">'.$esc((string) $cell).'</Data></Cell>';
            }
            $rowsHtml .= '</Row>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>'."\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">'."\n";
        $xml .= '<Styles><Style ss:ID="sHeader"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#F97316" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style></Styles>'."\n";
        $xml .= '<Worksheet ss:Name="Entry List"><Table>'.$rowsHtml.'</Table></Worksheet>'."\n";
        $xml .= '</Workbook>'."\n";

        return response($xml, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filenameBase.'.xls"',
        ]);
    }

    protected function exportPdf(array $rows, string $filenameBase): Response
    {
        $headers = [];
        if ($rows) {
            $headers = array_keys($rows[0]);
        }
        $generatedAt = now()->format('d M Y H:i:s');
        $pdf = Pdf::loadView('exports.entry-list-pdf', compact('rows', 'headers', 'generatedAt'))
            ->setPaper('a4', 'landscape')
            ->setWarnings(false);

        return $pdf->download($filenameBase.'.pdf');
    }

    protected function summaryByRaceClass(array $filters, callable $scope): array
    {
        try {
            $subQuery = DB::table('registrations')
                ->select([
                    'registration_items.race_class_id',
                    DB::raw('COUNT(DISTINCT registrations.id) AS cnt'),
                ])
                ->join('registration_items', 'registration_items.registration_id', '=', 'registrations.id')
                ->join('events', 'events.id', '=', 'registrations.event_id')
                ->where($scope)
                ->when(! empty($filters['event_id']), fn ($q) => $q->where('events.id', $filters['event_id']))
                ->when(! empty($filters['race_class_ids']), fn ($q) => $q->whereIn('registration_items.race_class_id', $filters['race_class_ids']))
                ->when(! empty($filters['statuses']), fn ($q) => $q->whereIn('registrations.status', $filters['statuses']))
                ->groupBy('registration_items.race_class_id');

            $agg = DB::table(DB::raw('('.$subQuery->toSql().') AS agg'))
                ->mergeBindings($subQuery)
                ->join('race_classes', 'race_classes.id', '=', 'agg.race_class_id')
                ->select(['race_classes.id', 'race_classes.name', 'race_classes.code', DB::raw('agg.cnt AS count')])
                ->orderBy('race_classes.name')
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'code' => $r->code,
                    'count' => (int) $r->count,
                ])
                ->toArray();

            return $agg;
        } catch (Throwable) {
            return [];
        }
    }

    protected function organizerScope(?User $user): callable
    {
        if (! $user) {
            abort(403, 'Login diperlukan.');
        }
        $role = $user->role;
        $roleValue = is_object($role) ? ($role->value ?? (string) $role) : (string) $role;
        $isSuperadmin = $roleValue === UserRole::Superadmin->value;
        if (! in_array($roleValue, ['admin', 'staff', 'superadmin'], true)) {
            abort(403, 'Anda tidak memiliki izin mengakses halaman ini.');
        }
        $organizerId = $user->organizer_id;

        return static function ($query) use ($isSuperadmin, $organizerId) {
            if (! $isSuperadmin) {
                $query->where('organizer_id', $organizerId);
            }
        };
    }
}
