@extends('layouts.app')
@section('title', 'Vehicles')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">REGISTRY</p>
        <h1>Vehicles</h1>
        <p class="muted">Search the plate before registering a vehicle.</p>
    </div>
    @if(auth()->user()->role === 'admin')
        <a class="btn primary" href="{{ route('vehicles.create') }}">Register vehicle</a>
    @endif
</div>

<section class="panel">
    <form class="filter-row" method="get" action="{{ route('vehicles.index') }}">
        <input id="vehicle-search" name="search" value="{{ $search }}" aria-label="Search by plate or owner" placeholder="{{ auth()->user()->role === 'enforcer' ? 'Search exact plate number' : 'Search plate number or owner name' }}">
        <button class="btn secondary">Search vehicles</button>
        @if($search)
            <a class="link" href="{{ route('vehicles.index') }}">Clear search</a>
        @endif
    </form>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Plate number</th><th>Owner</th><th>Vehicle</th><th>Violations</th><th>Registered</th><th></th></tr></thead>
            <tbody>
            @forelse($vehicles as $vehicle)
                <tr>
                    <td><a class="table-link" href="{{ route('vehicles.show', $vehicle) }}">{{ $vehicle->plate_number }}</a></td>
                    <td>{{ $vehicle->owner_name }}</td>
                    <td>{{ $vehicle->vehicle_type }} @if($vehicle->make_model) · {{ $vehicle->make_model }}@endif</td>
                    <td>{{ $vehicle->violations_count }}</td>
                    <td>{{ $vehicle->created_at->format('M j, Y') }}</td>
                    <td><a class="link" href="{{ route('vehicles.show', $vehicle) }}">Open</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-cell">
                        @if($search)
                            <div class="empty-search">
                                <p>No vehicle matched “{{ $search }}”. If this is the correct plate, register the vehicle now.</p>
                                <a class="btn primary" href="{{ route('vehicles.create', ['plate' => $search]) }}">Register this vehicle</a>
                            </div>
                        @else
                            No vehicles found. Search for a plate to check the registry.
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $vehicles->links() }}</div>
</section>
@endsection
