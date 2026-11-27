<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use App\Services\TrendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, TrendService $trends): View
    {
        $query = $this->query($request);
        $hours = array_fill(0, 24, 0);
        foreach ((clone $query)->pluck('occurred_at') as $date) $hours[\Carbon\Carbon::parse($date)->hour]++;
        arsort($hours);
        $mapPoints = (clone $query)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('occurred_at')
            ->get(['ticket_number', 'location', 'latitude', 'longitude', 'occurred_at'])
            ->groupBy(fn ($violation) => number_format($violation->latitude, 5, '.', '').','.number_format($violation->longitude, 5, '.', ''))
            ->map(fn ($group) => [
                'position' => ['lat' => (float) $group->first()->latitude, 'lng' => (float) $group->first()->longitude],
                'count' => $group->count(),
                'location' => $group->first()->location,
                'tickets' => $group->take(5)->map(fn ($violation) => [
                    'number' => $violation->ticket_number,
                    'location' => $violation->location,
                    'date' => $violation->occurred_at->format('M j, Y'),
                ])->values(),
            ])->values();
        $mapPointCount = $mapPoints->sum('count');

        return view('reports.index', [
            'total' => (clone $query)->count(),
            'byStatus' => (clone $query)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status'),
            'byType' => (clone $query)->join('violation_types', 'violations.violation_type_id', '=', 'violation_types.id')->select('violation_types.name', DB::raw('COUNT(*) as total'))->groupBy('violation_types.name')->orderByDesc('total')->get(),
            'mapPoints' => $mapPoints,
            'mapPointCount' => $mapPointCount,
            'byVehicleType' => (clone $query)->join('vehicles', 'violations.vehicle_id', '=', 'vehicles.id')->select('vehicles.vehicle_type', DB::raw('COUNT(*) as total'))->groupBy('vehicles.vehicle_type')->orderByDesc('total')->get(),
            'topHours' => array_slice($hours, 0, 6, true),
            'types' => ViolationType::orderBy('name')->get(),
            'enforcers' => User::whereIn('role', ['admin', 'enforcer'])->orderBy('name')->get(['id', 'name']),
            'trend' => $trends->weekly(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->query($request)->with(['vehicle', 'type', 'creator'])->orderBy('occurred_at');
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Ticket', 'Plate', 'Vehicle type', 'Violation', 'Location', 'Occurred at', 'Penalty', 'Status', 'Enforcer', 'Description']);
            foreach ($query->cursor() as $v) {
                fputcsv($out, array_map($this->safeCsv(...), [$v->ticket_number, $v->vehicle?->plate_number, $v->vehicle?->vehicle_type, $v->type?->name, $v->location, $v->occurred_at?->format('Y-m-d H:i'), $v->penalty_amount, $v->status, $v->creator?->name, $v->description]));
            }
            fclose($out);
        }, 'violations-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function activity(): View
    {
        return view('reports.activity', ['logs' => ActivityLog::with('user')->latest()->paginate(30)]);
    }

    private function query(Request $request)
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'type' => ['nullable', 'integer', 'exists:violation_types,id'], 'enforcer' => ['nullable', 'integer', 'exists:users,id']]);
        return Violation::query()
            ->when($request->filled('from'), fn ($q) => $q->whereDate('occurred_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('occurred_at', '<=', $request->input('to')))
            ->when($request->filled('type'), fn ($q) => $q->where('violation_type_id', $request->integer('type')))
            ->when($request->filled('enforcer'), fn ($q) => $q->where('created_by', $request->integer('enforcer')));
    }

    private function safeCsv(?string $value): string
    {
        $value = (string) $value;
        return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'".$value : $value;
    }
}
