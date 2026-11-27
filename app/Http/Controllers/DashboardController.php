<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Violation;
use App\Services\TrendService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(TrendService $trends): View
    {
        return view('dashboard', [
            'vehicleCount' => Vehicle::count(),
            'ticketCount' => Violation::count(),
            'openCount' => Violation::where('status', 'issued')->count(),
            'appealCount' => Violation::where('status', 'appealed')->count(),
            'recent' => Violation::with(['vehicle', 'type'])->latest()->limit(6)->get(),
            'topLocations' => Violation::select('location', DB::raw('COUNT(*) as total'))->groupBy('location')->orderByDesc('total')->limit(5)->get(),
            'trend' => $trends->weekly(),
        ]);
    }
}
