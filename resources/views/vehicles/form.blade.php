@extends('layouts.app')
@section('title', $vehicle->exists ? 'Edit vehicle' : 'Register vehicle')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">VEHICLE REGISTRY</p>
        <h1>{{ $vehicle->exists ? 'Edit vehicle' : 'Register a vehicle' }}</h1>
        @if(! $vehicle->exists && auth()->user()->role === 'enforcer')
            <p class="muted">No vehicle matched your search. Confirm the plate number before registering; duplicate plates are blocked.</p>
        @else
            <p class="muted">A unique QR tag is created for every vehicle. Print the QR card and give it to the owner when they visit the TOMECO office.</p>
        @endif
    </div>
    <a class="link" href="{{ route('vehicles.index') }}">Back to vehicles</a>
</div>

<section class="panel form-panel">
    <form method="post" action="{{ $vehicle->exists ? route('vehicles.update', $vehicle) : route('vehicles.store') }}">
        @csrf
        @if($vehicle->exists) @method('PUT') @endif
        @if(! $vehicle->exists && auth()->user()->role === 'enforcer')
            <input type="hidden" name="searched_plate" value="{{ old('searched_plate', $vehicle->plate_number) }}">
        @endif
        <div class="form-grid">
            <label>Plate number <em>*</em>
                <input name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" required maxlength="30" placeholder="ABC 1234">
            </label>
            <label>Vehicle type <em>*</em>
                <select name="vehicle_type" required>
                    <option value="">Select type</option>
                    @foreach(['Car', 'Motorcycle', 'Van', 'Truck', 'Bus', 'Other'] as $type)
                        <option value="{{ $type }}" @selected(old('vehicle_type', $vehicle->vehicle_type) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <label>Owner name <em>*</em>
                <input name="owner_name" value="{{ old('owner_name', $vehicle->owner_name) }}" required maxlength="255" autocomplete="name">
            </label>
            <label>Contact number <span class="optional">optional</span>
                <input name="owner_contact" value="{{ old('owner_contact', $vehicle->owner_contact) }}" maxlength="100" autocomplete="tel">
            </label>
            <label>Make / model <span class="optional">optional</span>
                <input name="make_model" value="{{ old('make_model', $vehicle->make_model) }}" maxlength="255" placeholder="e.g. Toyota Vios">
            </label>
            <label>Color <span class="optional">optional</span>
                <input name="color" value="{{ old('color', $vehicle->color) }}" maxlength="50">
            </label>
        </div>
        <div class="form-actions">
            <button class="btn primary">{{ $vehicle->exists ? 'Save changes' : 'Register vehicle' }}</button>
            <a class="btn secondary" href="{{ route('vehicles.index') }}">Cancel</a>
        </div>
    </form>
</section>
@endsection
