@extends('layouts.app')
@section('title', 'Issue ticket')
@section('content')
<div class="page-heading">
    <div><p class="eyebrow">NEW VIOLATION</p><h1>Issue a parking ticket</h1><p class="muted">Capture the details while you are on site. Required fields are marked.</p></div>
    <a class="link" href="{{ route('vehicles.scan') }}">← Scan a tag</a>
</div>

<section class="panel form-panel">
    <form method="post" action="{{ route('violations.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-section">
            <h2>Incident details</h2>
            <div class="form-grid">
                <label>Vehicle <em>*</em>
                    <select name="vehicle_id" required>
                        <option value="">Select a registered vehicle</option>
                        @foreach($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected(old('vehicle_id', $selectedVehicle?->id) == $vehicle->id)>{{ $vehicle->plate_number }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Violation type <em>*</em>
                    <select name="violation_type_id" id="violation-type" required>
                        <option value="">Select a type</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" data-penalty="{{ $type->default_penalty }}" @selected(old('violation_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Location <em>*</em>
                    <input name="location" required maxlength="255" value="{{ old('location') }}" placeholder="Street, landmark or zone">
                </label>
                <label>Date and time <em>*</em>
                    <input type="datetime-local" name="occurred_at" value="{{ old('occurred_at', now()->format('Y-m-d\TH:i')) }}" required>
                </label>
                <label>Penalty amount (₱) <em>*</em>
                    <input type="number" name="penalty_amount" id="penalty-amount" value="{{ old('penalty_amount') }}" min="0" step="0.01" required>
                </label>
                <label>Notice served on <span class="optional">optional</span>
                    <input type="date" name="notice_served_on" value="{{ old('notice_served_on') }}" max="{{ now()->toDateString() }}">
                </label>
            </div>
            <label class="block-label">Additional details <span class="optional">optional</span>
                <textarea name="description" rows="4" placeholder="Describe what happened">{{ old('description') }}</textarea>
            </label>
            <label class="block-label">Photo evidence <span class="optional">optional · JPG/PNG/WebP, max 5 MB</span>
                <input type="file" name="evidence" accept="image/jpeg,image/png,image/webp">
            </label>
        </div>

        <div class="form-section feature-section">
            <div class="section-heading"><div><p class="eyebrow">INCIDENT LOCATION</p><h2>Pin it on OpenStreetMap</h2></div></div>
            <p class="muted">Click the incident location on the map. The written location remains required for the ticket.</p>
            <div id="incident-map" class="leaflet-map map-picker" data-leaflet-map="picker" data-map-tile-url="{{ config('services.map.tile_url') }}" data-map-attribution="{{ config('services.map.attribution') }}" data-lat="{{ old('latitude') }}" data-lng="{{ old('longitude') }}" aria-label="Select incident location on OpenStreetMap"></div>
            <p class="chart-note" id="incident-map-load-status" aria-live="polite">Loading OpenStreetMap tiles…</p>
            <div class="coordinate-row"><span id="incident-map-status">No map point selected.</span><button type="button" class="text-button" id="clear-incident-map">Clear map point</button></div>
            <input type="hidden" name="latitude" id="incident-latitude" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" id="incident-longitude" value="{{ old('longitude') }}">
        </div>

        <div class="form-section feature-section">
            <div class="section-heading"><div><p class="eyebrow">DRIVER DETAILS</p><h2>Scan a driver’s license</h2></div></div>
            <p class="muted">Scan a QR or PDF417 barcode, or enter the details manually. The camera image is processed in this browser and is not uploaded.</p>
            <div class="license-scan-tools">
                <div class="camera-box license-camera-box"><video id="license-video" playsinline muted></video><div class="camera-overlay">Position the license barcode in frame</div></div>
                <div class="license-scan-controls">
                    <button class="btn secondary" type="button" id="start-license-scanner">Start camera scanner</button>
                    <button class="btn secondary" type="button" id="stop-license-scanner" hidden>Stop scanner</button>
                    <label class="image-scan-label">Scan an image<input type="file" id="license-image" accept="image/*" capture="environment"></label>
                    <p class="scan-message" id="license-scan-message" role="status" aria-live="polite">Only the driver name and license number fields below are saved with this ticket.</p>
                    <details id="license-raw-details" hidden><summary>Review decoded barcode text</summary><pre id="license-raw-text"></pre></details>
                </div>
            </div>
            <div class="form-grid driver-fields">
                <label>Driver name <span class="optional">optional</span>
                    <input name="driver_name" id="driver-name" value="{{ old('driver_name') }}" maxlength="255" autocomplete="off">
                </label>
                <label>Driver’s license number <span class="optional">optional</span>
                    <input name="driver_license_number" id="driver-license-number" value="{{ old('driver_license_number') }}" maxlength="50" autocomplete="off" inputmode="text">
                </label>
            </div>
        </div>

        <div class="form-actions"><button class="btn primary">Issue ticket</button><a class="btn secondary" href="{{ auth()->user()->role === 'admin' ? route('violations.index') : route('enforcer.dashboard') }}">Cancel</a></div>
    </form>
</section>
@endsection
