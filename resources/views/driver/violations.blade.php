<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Vehicle tickets · TOMECO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/tomeco-theme.css'])
</head>
<body class="driver-portal-body">
<main class="driver-portal driver-results">
    <a href="{{ route('driver.scan') }}" class="driver-portal-brand">
        <img src="{{ asset('images/tomeco-emblem.png') }}" alt="TOMECO Traffic Operations emblem">
        <span><strong>TOMECO</strong><small>TRAFFIC OPERATIONS · TACLOBAN CITY</small></span>
    </a>

    <div class="page-heading driver-results-heading">
        <div><p class="eyebrow">VEHICLE TICKET SUMMARY</p><h1>{{ $vehicle->plate_number }}</h1><p class="muted">Recorded violations and fees for this vehicle.</p></div>
        <a href="{{ route('driver.scan') }}" class="btn secondary">Scan another tag</a>
    </div>

    <section class="metrics driver-summary-metrics" aria-label="Ticket totals">
        <div class="metric"><span>Fees due</span><strong>₱{{ number_format($outstandingFees, 2) }}</strong><small>Unpaid issued tickets</small></div>
        <div class="metric"><span>Recorded tickets</span><strong>{{ number_format($violations->count()) }}</strong><small>All statuses</small></div>
    </section>

    <section class="panel driver-ticket-panel">
        <div class="panel-head"><div><p class="eyebrow">TICKET HISTORY</p><h2>Violations and fees</h2></div></div>
        @if($violations->isEmpty())
            <p class="empty-cell">No violations are currently recorded for this vehicle.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Ticket</th><th>Violation</th><th>Location</th><th>Date</th><th>Fee</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($violations as $violation)
                        <tr>
                            <td>{{ $violation->ticket_number }}</td>
                            <td>{{ $violation->type?->name ?? 'Violation' }}</td>
                            <td>{{ $violation->location }}</td>
                            <td>{{ $violation->occurred_at->format('M j, Y') }}</td>
                            <td>₱{{ number_format((float) $violation->penalty_amount, 2) }}</td>
                            <td><span class="badge {{ $violation->status }}">{{ ucfirst($violation->status) }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p class="chart-note">Fees due include issued tickets only. Paid tickets are settled; appealed tickets are under review.</p>
        @endif
    </section>

    <p class="driver-privacy-note">This page shows ticket information for the QR tag scanned. For questions about a record or fee, contact TOMECO Traffic Operations.</p>
</main>
</body>
</html>
