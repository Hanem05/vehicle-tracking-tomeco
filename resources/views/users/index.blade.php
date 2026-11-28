@extends('layouts.app')
@section('title', 'Team accounts')
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">ACCESS CONTROL</p>
        <h1>Team accounts</h1>
        <p class="muted">Create enforcer accounts and control staff access.</p>
    </div>
    <a class="btn primary" href="{{ route('users.create') }}">Add account</a>
</div>

<section class="panel">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>State</th><th>Created</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $user)
                @php($supportedRole = in_array($user->role, ['admin', 'enforcer'], true))
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><span class="role-chip">{{ $supportedRole ? strtoupper($user->role) : 'LEGACY ROLE' }}</span></td>
                    <td>
                        @if(! $supportedRole)
                            <span class="badge appealed">Role update needed</span>
                        @else
                            <span class="badge {{ $user->is_active ? 'paid' : 'appealed' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                        @endif
                    </td>
                    <td>{{ $user->created_at->format('M j, Y') }}</td>
                    <td><a class="link" href="{{ route('users.edit', $user) }}">Edit</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $users->links() }}</div>
</section>
@endsection
