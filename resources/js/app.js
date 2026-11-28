import './bootstrap';
import * as L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { parseLicensePayload } from './license-scanner.js';
import { initializeLeafletMaps } from './leaflet-maps.js';
import { resolveVehicleQr } from './vehicle-qr.js';

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
    const imageInput = document.getElementById('driver-qr-image');
    const imageMessage = document.getElementById('driver-qr-image-message');
    let controls;

    const acceptQrValue = (value) => {
        const qr = resolveVehicleQr(value, window.location.origin);
        if (!qr) return;

        if (qr.type === 'scan-url') {
            controls?.stop();
            window.location.assign(qr.url);
            return;
        }

        document.getElementById('lookup-code').value = qr.code;
        controls?.stop();
        document.getElementById('lookup-form').requestSubmit();
    };

    start.addEventListener('click', async () => {
        try {
            message.textContent = 'Starting camera…';
            const { BrowserQRCodeReader } = await import('@zxing/browser');
            const reader = new BrowserQRCodeReader();
            controls = await reader.decodeFromVideoDevice(undefined, video, (result) => {
                if (result) acceptQrValue(result.getText());
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

    if (imageInput && imageMessage) {
        imageInput.addEventListener('change', async () => {
            const file = imageInput.files?.[0];
            if (!file) return;

            if (file.size > 10 * 1024 * 1024) {
                imageMessage.textContent = 'Choose an image smaller than 10 MB.';
                imageInput.value = '';
                return;
            }

            controls?.stop();
            start.hidden = false;
            stop.hidden = true;
            const imageUrl = URL.createObjectURL(file);
            imageMessage.textContent = 'Reading the QR code from your image…';

            try {
                const { BrowserQRCodeReader } = await import('@zxing/browser');
                const reader = new BrowserQRCodeReader();
                const result = await reader.decodeFromImageUrl(imageUrl);
                imageMessage.textContent = 'QR code found. Opening the vehicle ticket summary…';
                acceptQrValue(result.getText());
            } catch (_) {
                imageMessage.textContent = 'No readable QR code found. Try a sharper image or enter the code manually.';
            } finally {
                URL.revokeObjectURL(imageUrl);
                imageInput.value = '';
            }
        });
    }
}

const licenseStart = document.getElementById('start-license-scanner');
if (licenseStart) {
    const licenseStop = document.getElementById('stop-license-scanner');
    const video = document.getElementById('license-video');
    const message = document.getElementById('license-scan-message');
    const imageInput = document.getElementById('license-image');
    const nameInput = document.getElementById('driver-name');
    const numberInput = document.getElementById('driver-license-number');
    const rawDetails = document.getElementById('license-raw-details');
    const rawText = document.getElementById('license-raw-text');
    let controls;
    let handled = false;

    const acceptLicenseCode = (raw) => {
        if (handled) return;
        handled = true;
        controls?.stop();
        licenseStart.hidden = false;
        licenseStop.hidden = true;

        const fields = parseLicensePayload(raw);
        if (fields.name) nameInput.value = fields.name;
        if (fields.number) numberInput.value = fields.number;
        rawText.textContent = raw;
        rawDetails.hidden = false;

        if (fields.name || fields.number) {
            message.textContent = 'Barcode read. Review the filled fields and correct them if needed.';
        } else {
            message.textContent = 'Barcode read, but this card format was not recognized. Review the decoded text below and enter the fields manually.';
        }
    };

    licenseStart.addEventListener('click', async () => {
        handled = false;
        try {
            message.textContent = 'Starting camera…';
            const { BrowserMultiFormatReader } = await import('@zxing/browser');
            const reader = new BrowserMultiFormatReader();
            controls = await reader.decodeFromVideoDevice(undefined, video, (result) => {
                if (result) acceptLicenseCode(result.getText());
            });
            licenseStart.hidden = true;
            licenseStop.hidden = false;
            message.textContent = 'Scanning for a QR or PDF417 barcode…';
        } catch (_) {
            message.textContent = 'Camera unavailable. Allow camera access and use HTTPS or localhost, or scan an image below.';
        }
    });

    licenseStop.addEventListener('click', () => {
        controls?.stop();
        licenseStart.hidden = false;
        licenseStop.hidden = true;
        message.textContent = 'Scanner stopped. You can enter the details manually.';
    });

    imageInput.addEventListener('change', async () => {
        const file = imageInput.files?.[0];
        if (!file) return;
        handled = false;
        const imageUrl = URL.createObjectURL(file);
        message.textContent = 'Reading barcode from the selected image…';
        try {
            const { BrowserMultiFormatReader } = await import('@zxing/browser');
            const reader = new BrowserMultiFormatReader();
            const result = await reader.decodeFromImageUrl(imageUrl);
            acceptLicenseCode(result.getText());
        } catch (_) {
            message.textContent = 'No supported QR or PDF417 barcode was found. Try a clearer image or enter the details manually.';
        } finally {
            URL.revokeObjectURL(imageUrl);
            imageInput.value = '';
        }
    });
}

initializeLeafletMaps(L);
