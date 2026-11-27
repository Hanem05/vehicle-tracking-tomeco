@extends('layouts.app')
@section('title', 'Team accounts')
@section('content')
<div class="page-heading"><div><p class="eyebrow">ACCESS CONTROL</p><h1>Team accounts</h1><p class="muted">Give each staff member only the access their role requires.</p></div><a class="btn primary" href="{{ route('users.create') }}">+ Add account</a></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>State</th><th>Created</th><th></th></tr></thead><tbody>@foreach($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td><span class="role-chip">{{ strtoupper($user->role) }}</span></td><td><span class="badge {{ $user->is_active ? 'paid' : 'appealed' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td><td>{{ $user->created_at->format('M j, Y') }}</td><td><a class="link" href="{{ route('users.edit', $user) }}">Edit →</a></td></tr>@endforeach</tbody></table></div><div class="pagination">{{ $users->links() }}</div></section>
@endsection
