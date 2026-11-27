<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Violation;
use App\Services\TrendService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(TrendService $trends): View
    {
        $today = CarbonImmutable::today();
        $subpoenaReviewQueue = Violation::with('vehicle')
            ->where('status', 'issued')
            ->whereNotNull('notice_served_on')
            ->whereDate('notice_served_on', '<', $today->subDays(45)->toDateString())
            ->whereNull('subpoena_action_on')
            ->latest('notice_served_on')
            ->get(['id', 'vehicle_id', 'ticket_number', 'location', 'status', 'notice_served_on', 'appeared_on', 'subpoena_action_on'])
            ->filter(fn (Violation $violation) => $violation->complianceTimeline($today)['subpoena_review_due'])
            ->values();

        return view('dashboard', [
            'vehicleCount' => Vehicle::count(),
            'ticketCount' => Violation::count(),
            'openCount' => Violation::where('status', 'issued')->count(),
            'appealCount' => Violation::where('status', 'appealed')->count(),
            'subpoenaReviewCount' => $subpoenaReviewQueue->count(),
            'subpoenaReviewQueue' => $subpoenaReviewQueue->take(5),
            'recent' => Violation::with(['vehicle', 'type'])->latest()->limit(6)->get(),
            'topLocations' => Violation::select('location', DB::raw('COUNT(*) as total'))->groupBy('location')->orderByDesc('total')->limit(5)->get(),
            'trend' => $trends->weekly(),
        ]);
    }
}
