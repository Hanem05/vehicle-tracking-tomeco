<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · TOMECO Parking Monitor</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/tomeco-theme.css'])
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a href="{{ route('dashboard') }}" class="brand" aria-label="TOMECO operations dashboard">
            <span class="brand-emblem-wrap"><img class="brand-emblem" src="{{ asset('images/tomeco-emblem.png') }}" alt=""></span>
            <span class="brand-copy"><strong>TOMECO</strong><small>TRAFFIC OPERATIONS</small></span>
        </a>
        @if(auth()->user()->role === 'admin')
            <div class="nav-label">ADMIN WORKSPACE</div>
            <nav class="nav" aria-label="Admin workspace">
                <a class="{{ request()->routeIs('admin.dashboard', 'dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>Dashboard</a>
                <a class="{{ request()->routeIs('vehicles.scan') ? 'active' : '' }}" href="{{ route('vehicles.scan') }}" @if(request()->routeIs('vehicles.scan')) aria-current="page" @endif>Scan vehicle tag</a>
                <a class="{{ request()->routeIs('vehicles.*') && !request()->routeIs('vehicles.scan') ? 'active' : '' }}" href="{{ route('vehicles.index') }}" @if(request()->routeIs('vehicles.*') && !request()->routeIs('vehicles.scan')) aria-current="page" @endif>Vehicles</a>
                <a class="{{ request()->routeIs('violations.*') ? 'active' : '' }}" href="{{ route('violations.index') }}" @if(request()->routeIs('violations.*')) aria-current="page" @endif>Violations</a>
                <a class="{{ request()->routeIs('reports.index', 'reports.export') ? 'active' : '' }}" href="{{ route('reports.index') }}" @if(request()->routeIs('reports.index', 'reports.export')) aria-current="page" @endif>Reports</a>
            </nav>
            <div class="nav-label">ADMINISTRATION</div>
            <nav class="nav" aria-label="Administration">
                <a class="{{ request()->routeIs('types.*') ? 'active' : '' }}" href="{{ route('types.index') }}" @if(request()->routeIs('types.*')) aria-current="page" @endif>Violation types</a>
                <a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" @if(request()->routeIs('users.*')) aria-current="page" @endif>Team accounts</a>
                <a class="{{ request()->routeIs('reports.activity') ? 'active' : '' }}" href="{{ route('reports.activity') }}" @if(request()->routeIs('reports.activity')) aria-current="page" @endif>Activity log</a>
            </nav>
        @else
            <div class="nav-label">ENFORCER WORKSPACE</div>
            <nav class="nav" aria-label="Enforcer workspace">
                <a class="{{ request()->routeIs('enforcer.dashboard') ? 'active' : '' }}" href="{{ route('enforcer.dashboard') }}" @if(request()->routeIs('enforcer.dashboard')) aria-current="page" @endif>Home</a>
                <a class="{{ request()->routeIs('vehicles.index', 'vehicles.create', 'vehicles.show', 'vehicles.qr-card') ? 'active' : '' }}" href="{{ route('vehicles.index') }}" @if(request()->routeIs('vehicles.index', 'vehicles.create', 'vehicles.show', 'vehicles.qr-card')) aria-current="page" @endif>Find or register vehicle</a>
                <a class="{{ request()->routeIs('vehicles.scan') ? 'active' : '' }}" href="{{ route('vehicles.scan') }}" @if(request()->routeIs('vehicles.scan')) aria-current="page" @endif>Scan vehicle tag</a>
                <a class="{{ request()->routeIs('violations.create') ? 'active' : '' }}" href="{{ route('violations.create') }}" @if(request()->routeIs('violations.create')) aria-current="page" @endif>Issue ticket</a>
            </nav>
        @endif
        <div class="sidebar-foot"><span class="status-dot"></span> Office system <small>Tacloban City</small></div>
    </aside>
    <div class="main-area">
        <header class="topbar">
            <div class="topbar-title">TOMECO Traffic Operations</div>
            <div class="topbar-right"><span class="role-chip">{{ strtoupper(auth()->user()->role) }}</span><span class="topbar-user">{{ auth()->user()->name }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="text-button">Sign out</button></form></div>
        </header>
        <main class="content">
            @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
            @if(session('notice'))<div class="alert info" role="status">{{ session('notice') }}</div>@endif
            @if($errors->any())<div class="alert error"><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
