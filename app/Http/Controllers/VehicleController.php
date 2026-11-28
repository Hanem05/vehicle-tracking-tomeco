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

    public function create(Request $request): View|RedirectResponse
    {
        $plate = strtoupper(trim((string) $request->query('plate', '')));

        if ($request->user()->role === 'enforcer' && $plate === '') {
            return redirect()->route('vehicles.index')->with(
                'notice',
                'Search for the plate first. Register it only if no vehicle appears in the results.',
            );
        }

        if ($request->user()->role === 'enforcer') {
            $existingVehicle = Vehicle::where('plate_number', $plate)->first();
            if ($existingVehicle) {
                return redirect()->route('vehicles.show', $existingVehicle)->with(
                    'notice',
                    'This plate is already registered. The existing vehicle record is open; no duplicate was created.',
                );
            }
        }

        $vehicle = new Vehicle;
        $vehicle->plate_number = $plate;

        return view('vehicles.form', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('vehicles.form', compact('vehicle'));
    }

    public function store(Request $request): RedirectResponse
    {
        $plate = strtoupper(trim((string) $request->input('plate_number')));
        if ($request->user()->role === 'enforcer'
            && strtoupper(trim((string) $request->input('searched_plate'))) !== $plate) {
            return back()->withErrors(['plate_number' => 'Search for this exact plate before registering the vehicle.'])->withInput();
        }

        $data = $this->validated($request);
        $data['plate_number'] = strtoupper(trim($data['plate_number']));
        $data['qr_token'] = (string) Str::uuid();
        $data['registered_by'] = $request->user()->id;
        $vehicle = Vehicle::create($data);
        Audit::record('vehicle.created', $vehicle, $vehicle->plate_number);

        return redirect()->route('vehicles.show', $vehicle)->with(
            'success',
            'Vehicle registered. The QR card is ready to print for the owner to collect at the TOMECO office.',
        );
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

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function qrCard(Vehicle $vehicle): View
    {
        return view('vehicles.qr-card', compact('vehicle'));
    }

    public function scan(): View
    {
        return view('vehicles.scan');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:500']]);
        $code = trim($data['code']);
        if (str_contains($code, '/scan/')) {
            $code = basename(parse_url($code, PHP_URL_PATH) ?: '');
        }

        return redirect()->route('vehicles.scanned', ['token' => $code]);
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
