<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use App\Models\Vehicle;
use App\Models\Violation;
use App\Models\ViolationType;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ViolationController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filtered($request);
        $violations = $query->with(['vehicle', 'type', 'creator'])->latest('occurred_at')->paginate(15)->withQueryString();
        return view('violations.index', compact('violations'));
    }

    public function create(Request $request): View
    {
        $selectedVehicle = $request->integer('vehicle') ? Vehicle::find($request->integer('vehicle')) : null;
        return view('violations.create', [
            'selectedVehicle' => $selectedVehicle,
            'vehicles' => Vehicle::orderBy('plate_number')->get(['id', 'plate_number']),
            'types' => ViolationType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'violation_type_id' => ['required', 'exists:violation_types,id'],
            'location' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
            'description' => ['nullable', 'string', 'max:3000'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_license_number' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9 -]+$/'],
            'notice_served_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'penalty_amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'evidence' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        if ($request->filled('latitude') !== $request->filled('longitude')) {
            return back()->withErrors(['latitude' => 'Select a point on the map so both coordinates are recorded.'])->withInput();
        }
        $type = ViolationType::findOrFail($data['violation_type_id']);
        abort_unless($type->is_active, 422, 'This violation type is inactive.');
        unset($data['evidence']);
        if ($request->hasFile('evidence')) $data['evidence_path'] = $request->file('evidence')->store('evidence', 'local');
        $data['ticket_number'] = 'TV-'.now()->format('Y').'-'.Str::upper(Str::random(10));
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'issued';
        $violation = Violation::create($data);
        Audit::record('violation.created', $violation, $violation->ticket_number);
        return redirect()->route('violations.show', $violation)->with('success', 'Ticket issued.');
    }

    public function updateTimeline(Request $request, Violation $violation): RedirectResponse
    {
        $data = $request->validate([
            'notice_served_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'appeared_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        if (filled($data['appeared_on'] ?? null) && blank($data['notice_served_on'] ?? null)) {
            return back()->withErrors(['appeared_on' => 'Record the notice service date before recording an appearance.'])->withInput();
        }

        if (filled($data['appeared_on'] ?? null)
            && CarbonImmutable::parse($data['appeared_on'])->lessThan(CarbonImmutable::parse($data['notice_served_on']))) {
            return back()->withErrors(['appeared_on' => 'The appearance date cannot be before the notice service date.'])->withInput();
        }

        $violation->update($data);
        Audit::record('violation.timeline_updated', $violation, 'served='.$violation->notice_served_on?->format('Y-m-d').'; appeared='.$violation->appeared_on?->format('Y-m-d'));

        return back()->with('success', 'Notice and appearance dates updated.');
    }

    public function recordSubpoenaAction(Request $request, Violation $violation): RedirectResponse
    {
        $timeline = $violation->complianceTimeline();
        if (! $timeline['subpoena_review_due']) {
            return back()->withErrors(['subpoena_action_on' => 'This ticket is not yet eligible for subpoena review under the recorded timeline.']);
        }

        $data = $request->validate([
            'subpoena_action_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'subpoena_reference' => ['nullable', 'string', 'max:100'],
        ]);

        if (CarbonImmutable::parse($data['subpoena_action_on'])->lessThanOrEqualTo($timeline['payment_due_on'])) {
            return back()->withErrors(['subpoena_action_on' => 'Record the action date only after the 45-day payment deadline.'])->withInput();
        }

        $violation->update($data + ['updated_by' => $request->user()->id]);
        Audit::record('violation.subpoena_action_recorded', $violation, $data['subpoena_reference'] ?? null);

        return back()->with('success', 'Subpoena action recorded.');
    }

    public function show(Violation $violation): View
    {
        $violation->load(['vehicle', 'type', 'creator', 'updater']);
        return view('violations.show', compact('violation'));
    }

    public function status(Request $request, Violation $violation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['issued', 'paid', 'appealed'])],
            'status_note' => ['nullable', 'string', 'max:3000'],
        ]);
        $old = $violation->status;
        $violation->update([
            'status' => $data['status'],
            'status_note' => $data['status_note'] ?? null,
            'resolved_at' => $data['status'] === 'paid' ? now() : null,
            'updated_by' => $request->user()->id,
        ]);
        Audit::record('violation.status_changed', $violation, $old.' → '.$violation->status);
        return back()->with('success', 'Ticket status updated.');
    }

    public function evidence(Violation $violation): BinaryFileResponse
    {
        abort_unless($violation->evidence_path && Storage::disk('local')->exists($violation->evidence_path), 404);
        return response()->file(Storage::disk('local')->path($violation->evidence_path), [
            'Content-Type' => Storage::disk('local')->mimeType($violation->evidence_path),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function filtered(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['issued', 'paid', 'appealed'])],
            'type' => ['nullable', 'integer', 'exists:violation_types,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        return Violation::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('violation_type_id', $request->integer('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('occurred_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('occurred_at', '<=', $request->input('to')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%'.strtolower(trim((string) $request->query('search'))).'%';
                $q->where(fn ($q) => $q->whereRaw('LOWER(ticket_number) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(location) LIKE ?', [$search])
                    ->orWhereHas('vehicle', fn ($v) => $v->whereRaw('LOWER(plate_number) LIKE ?', [$search])));
            });
    }
}
