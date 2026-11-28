<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vehicle QR card · {{ $vehicle->plate_number }}</title>
    <style>
        :root { color-scheme: light; font-family: Arial, Helvetica, sans-serif; color: #26333d; }
        * { box-sizing: border-box; }
        body { min-width: 320px; margin: 0; padding: 24px; background: #f1f3f4; }
        .print-actions { display: flex; justify-content: center; gap: 12px; margin: 0 auto 20px; }
        .print-actions a, .print-actions button { min-height: 44px; padding: 10px 16px; border: 1px solid #b9c2c8; border-radius: 8px; background: #fff; color: #26333d; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .print-actions button { border-color: #a83f49; background: #a83f49; color: #fff; }
        .url-warning { width: min(100%, 440px); margin: 0 auto 16px; padding: 12px 15px; border: 1px solid #e0b870; border-radius: 8px; background: #fff8e8; color: #634a1e; font-size: 13px; line-height: 1.45; }
        .qr-card { display: flex; width: min(100%, 440px); min-height: 600px; flex-direction: column; align-items: center; margin: 0 auto; padding: 30px 28px 24px; border: 1px solid #d6dcdf; border-radius: 16px; background: #fff; box-shadow: 0 12px 32px #1b293012; text-align: center; }
        .emblem { width: 66px; height: 66px; object-fit: contain; }
        .office-name { margin: 10px 0 0; color: #8e3740; font-size: 12px; font-weight: 700; letter-spacing: .12em; }
        h1 { margin: 20px 0 3px; font-size: 24px; line-height: 1.2; }
        .subtitle { margin: 0; color: #5e6b74; font-size: 13px; }
        .plate { margin: 22px 0 3px; color: #26333d; font-size: 25px; font-weight: 800; letter-spacing: .08em; }
        .owner { margin: 0 0 16px; color: #4e5a62; font-size: 14px; }
        .qr { display: block; width: 220px; height: 220px; padding: 9px; border: 1px solid #d9dfe3; border-radius: 8px; background: #fff; }
        .scan-instructions { max-width: 300px; margin: 18px 0 0; font-size: 15px; font-weight: 700; line-height: 1.45; }
        .keep-note { max-width: 300px; margin: 7px 0 0; color: #5e6b74; font-size: 12px; line-height: 1.45; }
        .card-footer { width: 100%; margin-top: auto; padding-top: 18px; border-top: 1px solid #e2e6e8; color: #5e6b74; font-size: 10px; letter-spacing: .04em; }
        @page { size: A6 portrait; margin: 8mm; }
        @media print {
            body { min-width: 0; padding: 0; background: #fff; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .print-actions, .url-warning { display: none !important; }
            .qr-card { width: 100%; min-height: 130mm; margin: 0; padding: 5mm 4mm; border: 0; border-radius: 0; box-shadow: none; }
            .emblem { width: 15mm; height: 15mm; }
            h1 { margin-top: 4mm; font-size: 16pt; }
            .plate { margin-top: 4mm; font-size: 18pt; }
            .owner { margin-bottom: 3mm; }
            .qr { width: 53mm; height: 53mm; padding: 2mm; }
            .scan-instructions { margin-top: 4mm; font-size: 10pt; }
            .keep-note { margin-top: 2mm; font-size: 8pt; }
            .card-footer { padding-top: 3mm; font-size: 7pt; }
        }
    </style>
</head>
<body>
    @if(in_array(parse_url(config('app.url'), PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true))
        <p class="url-warning" role="alert">This QR code currently links to localhost, which a driver's phone cannot reach. Set APP_URL to your office network address or public site before printing.</p>
    @endif
    <nav class="print-actions" aria-label="Print controls">
        <a href="{{ route('vehicles.show', $vehicle) }}">Back to vehicle</a>
        <button type="button" onclick="window.print()">Print QR card</button>
    </nav>
    <main class="qr-card">
        <img class="emblem" src="{{ asset('images/tomeco-emblem.png') }}" alt="TOMECO emblem">
        <p class="office-name">TACLOBAN CITY · TRAFFIC OPERATIONS</p>
        <h1>Vehicle QR Card</h1>
        <p class="subtitle">Keep this card with your vehicle</p>
        <p class="plate">{{ $vehicle->plate_number }}</p>
        <p class="owner">Registered to {{ $vehicle->owner_name }}</p>
        <img class="qr" src="{{ route('vehicles.qr', $vehicle) }}" alt="Scan code for vehicle {{ $vehicle->plate_number }}">
        <p class="scan-instructions">Scan with your phone camera to check recorded tickets and fees.</p>
        <p class="keep-note">If your QR card is lost or damaged, visit the TOMECO office for a replacement.</p>
        <footer class="card-footer">TOMECO · TACLOBAN CITY</footer>
    </main>
</body>
</html>
