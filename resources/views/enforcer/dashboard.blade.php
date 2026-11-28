@extends('layouts.app')
@section('title', 'Enforcer workspace')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">ENFORCER WORKSPACE</p>
        <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}.</h1>
        <p class="muted">Search the plate first. Register a vehicle only when it is not already in the registry.</p>
    </div>
</div>

<section class="panel enforcer-quick-search">
    <form class="filter-row" method="get" action="{{ route('vehicles.index') }}">
        <label for="enforcer-plate-search">Find a vehicle by plate number</label>
        <input id="enforcer-plate-search" name="search" placeholder="Enter the full plate number" required>
        <button class="btn primary">Search vehicles</button>
    </form>
</section>

<div class="actions enforcer-actions">
    <a class="btn secondary" href="{{ route('vehicles.scan') }}">Scan vehicle QR</a>
    <a class="btn primary" href="{{ route('violations.create') }}">Issue a ticket</a>
</div>

<div class="metrics dashboard-metrics">
    <a href="{{ route('vehicles.index') }}" class="metric"><span>Vehicles registered by you</span><strong>{{ number_format($vehicleCount) }}</strong><small>New vehicle records</small></a>
    <div class="metric"><span>Tickets issued by you</span><strong>{{ number_format($ticketCount) }}</strong><small>All your tickets</small></div>
    <div class="metric"><span>Open tickets</span><strong>{{ number_format($openCount) }}</strong><small>Awaiting resolution</small></div>
    <div class="metric"><span>Tickets today</span><strong>{{ number_format($todayCount) }}</strong><small>Created during this workday</small></div>
</div>

<section class="panel">
    <div class="panel-head"><div><p class="eyebrow">YOUR WORK</p><h2>Recent tickets</h2></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Ticket</th><th>Plate</th><th>Violation</th><th>Location</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            @forelse($recent as $violation)
                <tr>
                    <td><a class="table-link" href="{{ route('violations.show', $violation) }}">{{ $violation->ticket_number }}</a></td>
                    <td>{{ $violation->vehicle?->plate_number }}</td>
                    <td>{{ $violation->type?->name }}</td>
                    <td>{{ $violation->location }}</td>
                    <td><span class="badge {{ $violation->status }}">{{ ucfirst($violation->status) }}</span></td>
                    <td>{{ $violation->occurred_at->format('M j, Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-cell">No tickets yet. Search for a vehicle or issue your first ticket.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
