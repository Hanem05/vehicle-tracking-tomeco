<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DriverPortalController extends Controller
{
    public function scan(): View
    {
        return view('driver.scan');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:500']]);
        $code = trim($data['code']);

        if (str_contains($code, '/scan/')) {
            $code = basename(parse_url($code, PHP_URL_PATH) ?: '');
        }

        $vehicle = Vehicle::where('qr_token', $code)->first();
        if (! $vehicle) {
            return back()->withErrors(['code' => 'No vehicle matches that QR code. Check the tag and try again.']);
        }

        return redirect()->route('vehicles.scanned', ['token' => $vehicle->qr_token]);
    }

    public function scanned(Request $request, string $token): Response
    {
        $vehicle = Vehicle::with(['violations.type'])
            ->where('qr_token', $token)
            ->first();

        if (! $vehicle) {
            $route = $request->user() ? 'vehicles.scan' : 'driver.scan';

            return redirect()->route($route)->withErrors(['code' => 'No vehicle matches that QR code. Check the tag and try again.']);
        }

        if ($request->user()) {
            return redirect()->route('violations.create', ['vehicle' => $vehicle->id]);
        }

        $violations = $vehicle->violations->sortByDesc('occurred_at')->values();
        $outstandingFees = $violations
            ->where('status', 'issued')
            ->sum(fn ($violation) => (float) $violation->penalty_amount);

        return response()->view('driver.violations', compact('vehicle', 'violations', 'outstandingFees'))
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
