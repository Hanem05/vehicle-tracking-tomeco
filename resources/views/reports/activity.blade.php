@extends('layouts.app')
@section('title', 'Activity log')
@section('content')
<div class="page-heading"><div><p class="eyebrow">ACCOUNTABILITY</p><h1>Activity log</h1><p class="muted">Important changes and sign-ins recorded by the system.</p></div></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>When</th><th>User</th><th>Action</th><th>Record</th><th>Details</th><th>IP address</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ $log->created_at->format('M j, Y g:i A') }}</td><td>{{ $log->user?->name ?: 'System' }}</td><td>{{ str_replace('.', ' · ', $log->action) }}</td><td>{{ $log->subject_type }} {{ $log->subject_id }}</td><td>{{ $log->details ?: '—' }}</td><td>{{ $log->ip_address }}</td></tr>@empty<tr><td colspan="6" class="empty-cell">No activity yet.</td></tr>@endforelse</tbody></table></div><div class="pagination">{{ $logs->links() }}</div></section>
@endsection
