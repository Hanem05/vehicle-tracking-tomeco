@extends('layouts.app')
@section('title', 'Overview')
@section('content')
<div class="page-heading"><div><p class="eyebrow">OPERATIONS OVERVIEW</p><h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }}.</h1><p class="muted">Here is the current picture of your parking enforcement records.</p></div>@if(auth()->user()->role !== 'viewer')<a class="btn primary" href="{{ route('vehicles.scan') }}">Scan vehicle tag →</a>@endif</div>
<div class="metrics">
    <a href="{{ route('violations.index') }}" class="metric"><span>Total tickets</span><strong>{{ number_format($ticketCount) }}</strong><small>All recorded violations</small></a>
    <a href="{{ route('violations.index', ['status' => 'issued']) }}" class="metric"><span>Open tickets</span><strong>{{ number_format($openCount) }}</strong><small>Awaiting resolution</small></a>
    <a href="{{ route('vehicles.index') }}" class="metric"><span>Registered vehicles</span><strong>{{ number_format($vehicleCount) }}</strong><small>QR tagged records</small></a>
    <a href="{{ route('violations.index', ['status' => 'appealed']) }}" class="metric"><span>Appealed</span><strong>{{ number_format($appealCount) }}</strong><small>Require review</small></a>
</div>
<div class="two-col">
    <section class="panel"><div class="panel-head"><div><p class="eyebrow">WEEKLY ACTIVITY</p><h2>Violation trend</h2></div><a href="{{ route('reports.index') }}" class="link">Full report →</a></div>
        <div class="bar-chart">@php($max = max(1, max($trend['counts']))) @foreach($trend['counts'] as $i => $count)<div class="bar-col" title="{{ $trend['labels'][$i] }}: {{ $count }}"><span class="bar-value">{{ $count }}</span><div class="bar" style="height: {{ max(5, $count / $max * 130) }}px"></div><small>{{ $trend['labels'][$i] }}</small></div>@endforeach</div>
        <p class="chart-note">Recorded tickets per week over the last 12 weeks.</p>
    </section>
    <section class="panel"><div class="panel-head"><div><p class="eyebrow">WHERE IT HAPPENS</p><h2>Top locations</h2></div></div>
        @forelse($topLocations as $place)<div class="rank-row"><span class="rank-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span>{{ $place->location }}</span><strong>{{ $place->total }}</strong></div>@empty<p class="empty-small">Locations will appear as violations are recorded.</p>@endforelse
    </section>
</div>
<section class="panel"><div class="panel-head"><div><p class="eyebrow">LATEST RECORDS</p><h2>Recent violations</h2></div><a href="{{ route('violations.index') }}" class="link">View all →</a></div>
    <div class="table-wrap"><table><thead><tr><th>Ticket</th><th>Vehicle</th><th>Violation</th><th>Location</th><th>Status</th><th>Date</th></tr></thead><tbody>@forelse($recent as $v)<tr><td><a class="table-link" href="{{ route('violations.show', $v) }}">{{ $v->ticket_number }}</a></td><td>{{ $v->vehicle?->plate_number }}</td><td>{{ $v->type?->name }}</td><td>{{ $v->location }}</td><td><span class="badge {{ $v->status }}">{{ ucfirst($v->status) }}</span></td><td>{{ $v->occurred_at->format('M j, Y') }}</td></tr>@empty<tr><td colspan="6" class="empty-cell">No violations yet. Scan a vehicle tag to issue the first ticket.</td></tr>@endforelse</tbody></table></div>
</section>
@endsection
