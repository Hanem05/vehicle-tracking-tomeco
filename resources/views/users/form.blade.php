@extends('layouts.app')
@section('title', $user->exists ? 'Edit account' : 'Add account')
@section('content')
@php($selectedRole = old('role', $user->role))
<div class="page-heading">
    <div>
        <p class="eyebrow">TEAM ACCOUNT</p>
        <h1>{{ $user->exists ? 'Edit account' : 'Add account' }}</h1>
        <p class="muted">Admins create and manage all staff accounts.</p>
    </div>
    <a class="link" href="{{ route('users.index') }}">Back to accounts</a>
</div>

@if($user->exists && ! in_array($user->role, ['admin', 'enforcer'], true))
    <div class="hint-card role-transition-note">This legacy account has a retired role and cannot sign in. Choose Admin or Enforcer to restore access.</div>
@endif

<section class="panel form-panel">
    <form method="post" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
        @csrf
        @if($user->exists) @method('PUT') @endif
        <div class="form-grid">
            <label>Full name <em>*</em>
                <input name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">
            </label>
            <label>Email address <em>*</em>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
            </label>
            <label>Role <em>*</em>
                <select name="role" required>
                    <option value="" disabled @selected(! in_array($selectedRole, ['admin', 'enforcer'], true))>Choose a role</option>
                    <option value="admin" @selected($selectedRole === 'admin')>Admin — full system control</option>
                    <option value="enforcer" @selected($selectedRole === 'enforcer')>Enforcer — vehicle registration and ticketing</option>
                </select>
            </label>
            @if($user->exists)
                <label>Status
                    <select name="is_active">
                        <option value="1" @selected(old('is_active', (int) $user->is_active) == 1)>Active</option>
                        <option value="0" @selected(old('is_active', (int) $user->is_active) == 0)>Inactive</option>
                    </select>
                </label>
            @endif
            <label>Password {{ $user->exists ? '(leave blank to keep)' : '*' }}
                <input type="password" name="password" {{ $user->exists ? '' : 'required' }} minlength="12" autocomplete="new-password">
            </label>
            <label>Confirm password
                <input type="password" name="password_confirmation" {{ $user->exists ? '' : 'required' }} minlength="12" autocomplete="new-password">
            </label>
        </div>
        <div class="form-actions">
            <button class="btn primary">{{ $user->exists ? 'Save account' : 'Create account' }}</button>
            <a class="btn secondary" href="{{ route('users.index') }}">Cancel</a>
        </div>
    </form>
</section>
@endsection
