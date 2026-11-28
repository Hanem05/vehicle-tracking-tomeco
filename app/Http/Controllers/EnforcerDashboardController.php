<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\Violation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnforcerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $enforcerId = $request->user()->id;
        $tickets = Violation::where('created_by', $enforcerId);

        return view('enforcer.dashboard', [
            'vehicleCount' => Vehicle::where('registered_by', $enforcerId)->count(),
            'ticketCount' => (clone $tickets)->count(),
            'openCount' => (clone $tickets)->where('status', 'issued')->count(),
            'todayCount' => (clone $tickets)->whereDate('created_at', today())->count(),
            'recent' => Violation::with(['vehicle', 'type'])
                ->where('created_by', $enforcerId)
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
