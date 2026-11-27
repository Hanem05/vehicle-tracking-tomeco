import './bootstrap';

const typeSelect = document.getElementById('violation-type');
const penaltyInput = document.getElementById('penalty-amount');
if (typeSelect && penaltyInput) {
    typeSelect.addEventListener('change', () => {
        penaltyInput.value = typeSelect.selectedOptions[0]?.dataset.penalty ?? '';
    });
}

const start = document.getElementById('start-scanner');
if (start) {
    const stop = document.getElementById('stop-scanner');
    const video = document.getElementById('qr-video');
    const message = document.getElementById('scanner-message');
    let controls;
    start.addEventListener('click', async () => {
        try {
            message.textContent = 'Starting camera…';
            const { BrowserQRCodeReader } = await import('@zxing/browser');
            const reader = new BrowserQRCodeReader();
            controls = await reader.decodeFromVideoDevice(undefined, video, (result) => {
                if (!result) return;
                const value = result.getText();
                try {
                    const url = new URL(value);
                    if (url.origin === window.location.origin && url.pathname.startsWith('/scan/')) {
                        controls?.stop();
                        window.location.assign(url.href);
                        return;
                    }
                } catch (_) { /* A raw token can still be entered below. */ }
                document.getElementById('lookup-code').value = value;
                controls?.stop();
                document.getElementById('lookup-form').requestSubmit();
            });
            start.hidden = true;
            stop.hidden = false;
            message.textContent = 'Scanning… hold the QR tag steady.';
        } catch (error) {
            message.textContent = 'Camera unavailable. Use HTTPS or localhost, allow camera access, or enter the code manually.';
        }
    });
    stop.addEventListener('click', () => {
        controls?.stop();
        start.hidden = false;
        stop.hidden = true;
        message.textContent = 'Camera stopped.';
    });
}
