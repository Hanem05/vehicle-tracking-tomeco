<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Support\Audit;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $vehicles = Vehicle::withCount('violations')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->whereRaw('LOWER(plate_number) LIKE ?', ['%'.strtolower($search).'%'])->orWhereRaw('LOWER(owner_name) LIKE ?', ['%'.strtolower($search).'%'])))
            ->latest()->paginate(15)->withQueryString();
        return view('vehicles.index', compact('vehicles', 'search'));
    }

    public function create(): View { return view('vehicles.form', ['vehicle' => new Vehicle]); }
    public function edit(Vehicle $vehicle): View { return view('vehicles.form', compact('vehicle')); }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['plate_number'] = strtoupper(trim($data['plate_number']));
        $data['qr_token'] = (string) Str::uuid();
        $data['registered_by'] = $request->user()->id;
        $vehicle = Vehicle::create($data);
        Audit::record('vehicle.created', $vehicle, $vehicle->plate_number);
        return redirect()->route('vehicles.show', $vehicle)->with('success', 'Vehicle registered. Print its QR tag below.');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $this->validated($request, $vehicle);
        $data['plate_number'] = strtoupper(trim($data['plate_number']));
        $vehicle->update($data);
        Audit::record('vehicle.updated', $vehicle, $vehicle->plate_number);
        return redirect()->route('vehicles.show', $vehicle)->with('success', 'Vehicle updated.');
    }

    public function show(Vehicle $vehicle): View
    {
        $vehicle->load(['registrar', 'violations.type', 'violations.creator']);
        return view('vehicles.show', compact('vehicle'));
    }

    public function qr(Vehicle $vehicle): Response
    {
        $svg = (new SvgWriter)->write(new QrCode(url('/scan/'.$vehicle->qr_token)))->getString();
        return response($svg, 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function scan(): View { return view('vehicles.scan'); }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:500']]);
        $code = trim($data['code']);
        if (str_contains($code, '/scan/')) $code = basename(parse_url($code, PHP_URL_PATH) ?: '');
        return redirect()->route('vehicles.scanned', ['token' => $code]);
    }

    public function scanned(string $token): RedirectResponse
    {
        $vehicle = Vehicle::where('qr_token', $token)->first();
        if (! $vehicle) return redirect()->route('vehicles.scan')->withErrors(['code' => 'No vehicle matches that QR code.']);
        return redirect()->route(request()->user()->role === 'viewer' ? 'vehicles.show' : 'violations.create', request()->user()->role === 'viewer' ? $vehicle : ['vehicle' => $vehicle->id]);
    }

    private function validated(Request $request, ?Vehicle $vehicle = null): array
    {
        $request->merge(['plate_number' => strtoupper(trim((string) $request->input('plate_number')))]);
        return $request->validate([
            'plate_number' => ['required', 'string', 'max:30', Rule::unique('vehicles', 'plate_number')->ignore($vehicle?->id)],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_contact' => ['nullable', 'string', 'max:100'],
            'vehicle_type' => ['required', Rule::in(['Car', 'Motorcycle', 'Van', 'Truck', 'Bus', 'Other'])],
            'make_model' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
