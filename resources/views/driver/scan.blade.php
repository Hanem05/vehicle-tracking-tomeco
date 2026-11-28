<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Check tickets and fees · TOMECO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/tomeco-theme.css'])
</head>
<body class="driver-portal-body">
<main class="driver-portal">
    <a href="{{ route('login') }}" class="driver-portal-brand">
        <img src="{{ asset('images/tomeco-emblem.png') }}" alt="TOMECO Traffic Operations emblem">
        <span><strong>TOMECO</strong><small>TRAFFIC OPERATIONS · TACLOBAN CITY</small></span>
    </a>

    <section class="panel driver-scan-card">
        <p class="eyebrow">DRIVER SELF-SERVICE</p>
        <h1>Check tickets and fees</h1>
        <p class="muted">Scan the QR tag registered to your vehicle to view its recorded violations and fees.</p>

        @if($errors->any())
            <div class="alert error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="camera-box driver-camera-box">
            <video id="qr-video" playsinline muted></video>
            <div class="camera-overlay">Hold the vehicle QR code inside the frame</div>
        </div>
        <p id="scanner-message" class="muted driver-scan-message" role="status" aria-live="polite">Allow camera access when prompted. On a phone, camera access requires HTTPS.</p>
        <div class="driver-scan-actions">
            <button type="button" id="start-scanner" class="btn primary">Start QR scanner</button>
            <button type="button" id="stop-scanner" class="btn secondary" hidden>Stop scanner</button>
        </div>

        <div class="driver-qr-upload">
            <h2>Can’t use the camera?</h2>
            <p class="muted">Choose a clear photo or screenshot of the vehicle QR code.</p>
            <label class="image-scan-label driver-qr-upload-label">Upload a QR code image
                <input type="file" id="driver-qr-image" accept="image/*">
            </label>
            <p id="driver-qr-image-message" class="scan-message" role="status" aria-live="polite">The image is scanned in your browser and is not uploaded.</p>
        </div>

        <div class="driver-manual-lookup">
            <h2>Prefer to enter the code?</h2>
            <form method="get" action="{{ route('driver.lookup') }}" id="lookup-form" class="form-stack">
                <label>QR code or link
                    <input name="code" id="lookup-code" required maxlength="500" placeholder="Enter the code printed with your QR tag">
                </label>
                <button class="btn secondary">Check vehicle</button>
            </form>
        </div>
    </section>

    <p class="driver-privacy-note">The QR tag is a private link to this vehicle’s ticket summary. Keep it safe and share it only with the vehicle owner.</p>
    <a class="driver-staff-link" href="{{ route('login') }}">TOMECO staff sign in</a>
</main>
</body>
</html>
