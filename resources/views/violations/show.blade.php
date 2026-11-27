@extends('layouts.app')
@section('title', $violation->ticket_number)
@section('content')
@php($timeline = $violation->complianceTimeline())
<div class="page-heading">
    <div><p class="eyebrow">TICKET RECORD</p><h1>{{ $violation->ticket_number }}</h1><p class="muted">Issued {{ $violation->created_at->format('M j, Y g:i A') }} by {{ $violation->creator?->name ?: 'Unknown' }}</p></div>
    <span class="badge large {{ $violation->status }}">{{ ucfirst($violation->status) }}</span>
</div>

<div class="two-col">
    <section class="panel">
        <div class="panel-head"><h2>Violation details</h2></div>
        <dl class="detail-list">
            <div><dt>Vehicle</dt><dd><a class="table-link" href="{{ route('vehicles.show', $violation->vehicle) }}">{{ $violation->vehicle?->plate_number }}</a></dd></div>
            @if(auth()->user()->role !== 'viewer' && $violation->driver_name)<div><dt>Driver</dt><dd>{{ $violation->driver_name }}</dd></div>@endif
            @if(auth()->user()->role !== 'viewer' && $violation->driver_license_number)<div><dt>License number</dt><dd>{{ $violation->driver_license_number }}</dd></div>@endif
            <div><dt>Violation</dt><dd>{{ $violation->type?->name }}</dd></div>
            <div><dt>Location</dt><dd>{{ $violation->location }} @if($violation->latitude !== null && $violation->longitude !== null)<br><a class="link" href="https://www.openstreetmap.org/?mlat={{ $violation->latitude }}&amp;mlon={{ $violation->longitude }}#map=18/{{ $violation->latitude }}/{{ $violation->longitude }}" target="_blank" rel="noopener">Open coordinates in OpenStreetMap ↗</a>@endif</dd></div>
            <div><dt>Occurred</dt><dd>{{ $violation->occurred_at->format('M j, Y g:i A') }}</dd></div>
            <div><dt>Penalty</dt><dd>₱{{ number_format($violation->penalty_amount, 2) }}</dd></div>
            <div><dt>Description</dt><dd>{{ $violation->description ?: '—' }}</dd></div>
            <div><dt>Status note</dt><dd>{{ $violation->status_note ?: '—' }}</dd></div>
            @if($violation->resolved_at)<div><dt>Paid at</dt><dd>{{ $violation->resolved_at->format('M j, Y g:i A') }}</dd></div>@endif
        </dl>
    </section>

    <div class="stack">
        <section class="panel">
            <div class="panel-head"><h2>Photo evidence</h2></div>
            @if($violation->evidence_path)
                <a href="{{ route('violations.evidence', $violation) }}" target="_blank" rel="noopener"><img class="evidence-image" src="{{ route('violations.evidence', $violation) }}" alt="Evidence for {{ $violation->ticket_number }}"></a>
                <p class="muted">Open image to inspect full size.</p>
            @else
                <p class="empty-small">No photo was attached to this ticket.</p>
            @endif
        </section>
        @if(auth()->user()->role === 'admin')
            <section class="panel">
                <div class="panel-head"><h2>Update ticket status</h2></div>
                <form method="post" action="{{ route('violations.status', $violation) }}" class="form-stack">
                    @csrf @method('PATCH')
                    <label>Ticket status<select name="status">@foreach(['issued','paid','appealed'] as $status)<option value="{{ $status }}" @selected($violation->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
                    <label>Office note<textarea name="status_note" rows="3" placeholder="Reason, payment reference, or appeal note">{{ old('status_note', $violation->status_note) }}</textarea></label>
                    <button class="btn primary">Save status</button>
                </form>
            </section>
        @endif
    </div>
</div>

<section class="panel timeline-panel">
    <div class="panel-head"><div><p class="eyebrow">FOLLOW-UP SCHEDULE</p><h2>Appearance and fee timeline</h2></div><span class="timeline-state {{ $timeline['stage'] }}">{{ $timeline['label'] }}</span></div>
    @if($violation->notice_served_on)
        <div class="timeline-dates">
            <div><small>Notice served</small><strong>{{ $violation->notice_served_on->format('M j, Y') }}</strong></div>
            <div><small>Appear by</small><strong>{{ $timeline['appearance_due_on']->format('M j, Y') }}</strong>@if($violation->appeared_on)<span>Appearance recorded {{ $violation->appeared_on->format('M j, Y') }}</span>@endif</div>
            <div><small>Fee deadline if appearance is missed</small><strong>{{ $timeline['payment_due_on']->format('M j, Y') }}</strong></div>
        </div>
        @if($timeline['missed_appearance'] && $violation->status === 'issued')
            <p class="timeline-explainer">No appearance was recorded within 30 days. The fee payment window runs through the 45-day deadline. After that date, this ticket is flagged for staff subpoena review.</p>
        @elseif($timeline['stage'] === 'appeal_pending')
            <p class="timeline-explainer">This ticket is appealed and is not included in the subpoena review queue while that status remains active.</p>
        @endif
        @if($violation->subpoena_action_on)
            <p class="timeline-explainer action-recorded">Subpoena action recorded {{ $violation->subpoena_action_on->format('M j, Y') }}@if($violation->subpoena_reference) · Reference {{ $violation->subpoena_reference }}@endif.</p>
        @endif
    @else
        <p class="empty-small">Record the date the notice was served to start the 30-day appearance and 15-day payment windows.</p>
    @endif

    @if(in_array(auth()->user()->role, ['admin', 'enforcer'], true))
        <form method="post" action="{{ route('violations.timeline', $violation) }}" class="timeline-form">
            @csrf @method('PATCH')
            <label>Notice served on<input type="date" name="notice_served_on" value="{{ old('notice_served_on', $violation->notice_served_on?->toDateString()) }}" max="{{ now()->toDateString() }}"></label>
            <label>Driver appeared on<input type="date" name="appeared_on" value="{{ old('appeared_on', $violation->appeared_on?->toDateString()) }}" max="{{ now()->toDateString() }}"></label>
            <button class="btn secondary">Save timeline dates</button>
        </form>
    @endif

    @if($timeline['subpoena_review_due'] && auth()->user()->role === 'admin')
        <div class="subpoena-review-note"><strong>Subpoena review due</strong><p>More than 45 days have passed since notice service, and no timely appearance or payment/appeal is recorded. An administrator may record an action completed outside this app. This form does not create or serve a legal document.</p>
            <form method="post" action="{{ route('violations.subpoena-action', $violation) }}" class="timeline-form">
                @csrf @method('PATCH')
                <label>Action date<input type="date" name="subpoena_action_on" value="{{ old('subpoena_action_on', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></label>
                <label>Reference number <span class="optional">optional</span><input name="subpoena_reference" maxlength="100" value="{{ old('subpoena_reference') }}"></label>
                <button class="btn primary">Record action taken</button>
            </form>
        </div>
    @endif
</section>
@endsection
