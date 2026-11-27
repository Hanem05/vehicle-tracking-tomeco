import './bootstrap';
import { parseLicensePayload } from './license-scanner.js';

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

const googleMapNodes = [...document.querySelectorAll('[data-google-map]')];
if (googleMapNodes.length) {
    let mapsPromise;
    const loadGoogleMaps = (key) => {
        if (window.google?.maps?.importLibrary) return Promise.resolve();
        if (mapsPromise) return mapsPromise;

        mapsPromise = new Promise((resolve, reject) => {
            window.tomecoGoogleMapsLoaded = resolve;
            const script = document.createElement('script');
            const parameters = new URLSearchParams({ key, loading: 'async', callback: 'tomecoGoogleMapsLoaded', v: 'weekly', region: 'PH' });
            script.src = `https://maps.googleapis.com/maps/api/js?${parameters}`;
            script.async = true;
            script.onerror = () => reject(new Error('Google Maps could not be loaded.'));
            document.head.append(script);
        });

        return mapsPromise;
    };

    const initializeMap = async (node) => {
        const key = node.dataset.mapsKey;
        const mapStatus = document.getElementById('report-map-status');
        try {
            await loadGoogleMaps(key);
            const { Map, Circle, InfoWindow } = await window.google.maps.importLibrary('maps');
            const tacloban = { lat: 11.2443, lng: 125.0030 };

            if (node.dataset.googleMap === 'picker') {
                const latitude = document.getElementById('incident-latitude');
                const longitude = document.getElementById('incident-longitude');
                const status = document.getElementById('incident-map-status');
                const hasPoint = node.dataset.lat !== '' && node.dataset.lng !== '';
                const initial = hasPoint ? { lat: Number(node.dataset.lat), lng: Number(node.dataset.lng) } : tacloban;
                const map = new Map(node, { center: initial, zoom: hasPoint ? 16 : 14, mapTypeControl: false, streetViewControl: false, fullscreenControl: true });
                const selection = new Circle({ map: null, radius: 18, strokeColor: '#087f6b', strokeOpacity: 0.95, strokeWeight: 2, fillColor: '#19b79a', fillOpacity: 0.35 });

                const setPoint = (point) => {
                    const lat = point.lat().toFixed(7);
                    const lng = point.lng().toFixed(7);
                    latitude.value = lat;
                    longitude.value = lng;
                    selection.setCenter(point);
                    selection.setMap(map);
                    map.panTo(point);
                    status.textContent = `Selected ${lat}, ${lng}`;
                };

                if (hasPoint) setPoint({ lat: () => initial.lat, lng: () => initial.lng });
                map.addListener('click', (event) => setPoint(event.latLng));
                document.getElementById('clear-incident-map')?.addEventListener('click', () => {
                    latitude.value = '';
                    longitude.value = '';
                    selection.setMap(null);
                    status.textContent = 'No map point selected.';
                });
                return;
            }

            const points = window.tomecoReportMapPoints || [];
            const map = new Map(node, { center: tacloban, zoom: 12, mapTypeControl: true, streetViewControl: false, fullscreenControl: true });
            const info = new InfoWindow();
            const bounds = new window.google.maps.LatLngBounds();

            points.forEach((item) => {
                const center = item.position;
                const circle = new Circle({
                    map,
                    center,
                    radius: Math.min(90, 14 + item.count * 5),
                    strokeColor: '#087f6b',
                    strokeOpacity: 0.9,
                    strokeWeight: 2,
                    fillColor: '#19b79a',
                    fillOpacity: 0.34,
                });
                bounds.extend(center);
                circle.addListener('click', () => {
                    const content = document.createElement('div');
                    const heading = document.createElement('strong');
                    heading.textContent = `${item.count} ticket${item.count === 1 ? '' : 's'}`;
                    content.append(heading);
                    const place = document.createElement('p');
                    place.textContent = item.location;
                    content.append(place);
                    const list = document.createElement('ul');
                    item.tickets.forEach((ticket) => {
                        const line = document.createElement('li');
                        line.textContent = `${ticket.number} · ${ticket.date} · ${ticket.location}`;
                        list.append(line);
                    });
                    content.append(list);
                    if (item.count > item.tickets.length) {
                        const remaining = document.createElement('small');
                        remaining.textContent = `${item.count - item.tickets.length} more ticket(s) at this point`;
                        content.append(remaining);
                    }
                    info.setContent(content);
                    info.setPosition(center);
                    info.open({ map });
                });
            });

            if (points.length > 1) map.fitBounds(bounds, 36);
            if (points.length === 1) {
                map.setCenter(points[0].position);
                map.setZoom(15);
            }
            if (mapStatus) mapStatus.textContent = `${points.reduce((total, point) => total + point.count, 0)} geolocated ticket(s) shown.`;
        } catch (_) {
            if (mapStatus) mapStatus.textContent = 'Google Maps could not load. Check the API key and Maps JavaScript API settings.';
            const pickerStatus = document.getElementById('incident-map-status');
            if (pickerStatus) pickerStatus.textContent = 'Google Maps could not load. Check the API key and Maps JavaScript API settings.';
        }
    };

    googleMapNodes.forEach(initializeMap);
}
