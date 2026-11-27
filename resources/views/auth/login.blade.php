<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sign in · Parkwise</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="login-body">
<div class="login-wrap">
    <div class="login-intro"><div class="login-logo">P</div><p class="eyebrow light">TOMECO · TACLOBAN CITY</p><h1>Clear records.<br>Better streets.</h1><p>A single place to document parking violations, retrieve evidence, and understand enforcement patterns.</p><div class="login-stat"><span>01 / 03</span> Vehicle tag → Violation record → Trend insight</div></div>
    <div class="login-panel"><div class="login-card"><p class="eyebrow">SECURE ACCESS</p><h2>Welcome back</h2><p class="muted">Sign in with your office account to continue.</p>
        @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('login.submit') }}" class="form-stack">@csrf
            <label>Email address<input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="name@office.gov.ph"></label>
            <label>Password<input type="password" name="password" required autocomplete="current-password" placeholder="Your password"></label>
            <button class="btn primary full">Sign in <span>→</span></button>
        </form><p class="login-help">Need an account? Ask your system administrator.</p>
    </div></div>
</div>
</body></html>
