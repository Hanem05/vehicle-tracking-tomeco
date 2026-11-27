<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · TOMECO Parking Monitor</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand"><span class="brand-mark">P</span><span><strong>PARKWISE</strong><small>TOMECO OPERATIONS</small></span></a>
        <div class="nav-label">WORKSPACE</div>
        <nav class="nav">
            <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Overview</a>
            <a class="{{ request()->routeIs('vehicles.scan') ? 'active' : '' }}" href="{{ route('vehicles.scan') }}">Scan QR tag</a>
            <a class="{{ request()->routeIs('vehicles.*') && !request()->routeIs('vehicles.scan') ? 'active' : '' }}" href="{{ route('vehicles.index') }}">Vehicles</a>
            <a class="{{ request()->routeIs('violations.*') ? 'active' : '' }}" href="{{ route('violations.index') }}">Violations</a>
            <a class="{{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Reports</a>
        </nav>
        @if(auth()->user()->role === 'admin')
            <div class="nav-label">ADMINISTRATION</div>
            <nav class="nav">
                <a class="{{ request()->routeIs('types.*') ? 'active' : '' }}" href="{{ route('types.index') }}">Violation types</a>
                <a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Team accounts</a>
                <a class="{{ request()->routeIs('reports.activity') ? 'active' : '' }}" href="{{ route('reports.activity') }}">Activity log</a>
            </nav>
        @endif
        <div class="sidebar-foot"><span class="status-dot"></span> Office system <small>Local database</small></div>
    </aside>
    <div class="main-area">
        <header class="topbar">
            <div class="topbar-title">Parking violation monitoring</div>
            <div class="topbar-right"><span class="role-chip">{{ strtoupper(auth()->user()->role) }}</span><span class="topbar-user">{{ auth()->user()->name }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="text-button">Sign out</button></form></div>
        </header>
        <main class="content">
            @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert error"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
