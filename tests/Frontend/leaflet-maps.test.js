import assert from 'node:assert/strict';
import test from 'node:test';
import { initializeLeafletMaps } from '../../resources/js/leaflet-maps.js';

function mockMap() {
    return {
        events: {},
        fitBoundsCall: null,
        removedLayer: null,
        setViewCall: null,
        panToCall: null,
        setView(point, zoom) { this.setViewCall = { point, zoom }; return this; },
        panTo(point) { this.panToCall = point; return this; },
        on(name, callback) { this.events[name] = callback; return this; },
        fitBounds(bounds, options) { this.fitBoundsCall = { bounds, options }; return this; },
        removeLayer(layer) { this.removedLayer = layer; },
        invalidateSize() { return this; },
    };
}

function withBrowserMocks(t, elements = {}, reportPoints = []) {
    const previous = {
        document: Object.getOwnPropertyDescriptor(globalThis, 'document'),
        window: Object.getOwnPropertyDescriptor(globalThis, 'window'),
        requestAnimationFrame: Object.getOwnPropertyDescriptor(globalThis, 'requestAnimationFrame'),
    };

    globalThis.document = {
        getElementById: (id) => elements[id] ?? null,
        createElement: (tagName) => ({
            tagName,
            children: [],
            textContent: '',
            append(child) { this.children.push(child); },
        }),
    };
    globalThis.window = { tomecoReportMapPoints: reportPoints };
    globalThis.requestAnimationFrame = (callback) => callback();

    t.after(() => {
        for (const [name, descriptor] of Object.entries(previous)) {
            if (descriptor) Object.defineProperty(globalThis, name, descriptor);
            else delete globalThis[name];
        }
    });
}

function mockLeaflet() {
    const map = mockMap();
    const tileLayer = {
        handlers: {},
        on(name, callback) { this.handlers[name] = callback; return this; },
        addTo(target) { this.map = target; return this; },
    };
    const circles = [];
    const markers = [];
    const bounds = {
        points: [],
        extend(point) { this.points.push(point); return this; },
        pad(amount) { this.padding = amount; return this; },
    };

    return {
        map,
        tileLayer,
        circles,
        markers,
        bounds,
        L: {
            map: () => map,
            tileLayer: (url, options) => { tileLayer.url = url; tileLayer.options = options; return tileLayer; },
            circle: (point, options) => {
                const circle = {
                    point,
                    options,
                    addTo(target) { this.map = target; return this; },
                    setLatLng(next) { this.point = next; },
                };
                circles.push(circle);
                return circle;
            },
            latLng: (lat, lng) => ({ lat, lng }),
            latLngBounds: () => bounds,
            circleMarker: (point, options) => {
                const marker = {
                    point,
                    options,
                    bindPopup(popup) { this.popup = popup; return this; },
                    addTo(target) { this.map = target; return this; },
                };
                markers.push(marker);
                return marker;
            },
        },
    };
}

test('Leaflet picker uses OpenStreetMap tiles and writes or clears selected coordinates', (t) => {
    const latitude = { value: '' };
    const longitude = { value: '' };
    const selectionStatus = { textContent: '' };
    const mapStatus = { textContent: '' };
    const clearButton = { addEventListener: (name, callback) => { clearButton[name] = callback; } };
    const node = {
        dataset: {
            leafletMap: 'picker',
            mapTileUrl: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            mapAttribution: '© OpenStreetMap contributors',
            lat: '',
            lng: '',
        },
    };
    const mock = mockLeaflet();
    withBrowserMocks(t, {
        'incident-latitude': latitude,
        'incident-longitude': longitude,
        'incident-map-status': selectionStatus,
        'incident-map-load-status': mapStatus,
        'clear-incident-map': clearButton,
    });

    initializeLeafletMaps(mock.L, { querySelectorAll: () => [node] });

    assert.equal(mock.tileLayer.url, 'https://tile.openstreetmap.org/{z}/{x}/{y}.png');
    assert.equal(mock.tileLayer.options.attribution, '© OpenStreetMap contributors');
    assert.equal(mapStatus.textContent, 'Click the map to select an incident location.');

    const selectedPoint = { lat: 11.12345678, lng: 124.98765432 };
    mock.map.events.click({ latlng: selectedPoint });
    assert.equal(latitude.value, '11.1234568');
    assert.equal(longitude.value, '124.9876543');
    assert.equal(selectionStatus.textContent, 'Selected 11.1234568, 124.9876543');
    assert.equal(mock.circles[0].options.radius, 18);

    clearButton.click();
    assert.equal(latitude.value, '');
    assert.equal(longitude.value, '');
    assert.equal(mock.map.removedLayer, mock.circles[0]);
    assert.equal(selectionStatus.textContent, 'No map point selected.');
});

test('report map fits saved incident points and builds safe ticket popups', (t) => {
    const status = { textContent: '' };
    const node = {
        dataset: {
            leafletMap: 'report',
            mapTileUrl: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            mapAttribution: '© OpenStreetMap contributors',
        },
    };
    const mock = mockLeaflet();
    withBrowserMocks(t, { 'report-map-status': status }, [
        {
            position: { lat: 11.24, lng: 125.0 },
            count: 2,
            location: '<script>unsafe</script>',
            tickets: [{ number: 'TV-1', date: 'Nov 1, 2026', location: 'Market road' }],
        },
        { position: { lat: 11.25, lng: 125.01 }, count: 1, location: 'Downtown', tickets: [] },
    ]);

    initializeLeafletMaps(mock.L, { querySelectorAll: () => [node] });

    assert.equal(mock.markers.length, 2);
    assert.equal(mock.bounds.points.length, 2);
    assert.equal(mock.map.fitBoundsCall.options.maxZoom, 15);
    assert.equal(status.textContent, '3 geolocated ticket(s) shown.');
    const popup = mock.markers[0].popup;
    assert.equal(popup.children[1].textContent, '<script>unsafe</script>');
    assert.equal(popup.children[2].children[0].textContent, 'TV-1 · Nov 1, 2026 · Market road');
});
