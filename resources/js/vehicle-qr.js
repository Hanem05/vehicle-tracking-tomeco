export function resolveVehicleQr(value, origin) {
    const code = String(value ?? '').trim();
    if (!code) return null;

    try {
        const url = new URL(code);
        if (url.origin === origin && /^\/scan\/[^/]+\/?$/.test(url.pathname)) {
            return { type: 'scan-url', url: url.href };
        }
    } catch (_) {
        // Raw tokens and other QR payloads are submitted to the lookup form.
    }

    return { type: 'code', code };
}
