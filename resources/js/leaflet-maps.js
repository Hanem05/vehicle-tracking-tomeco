const TACLOBAN = [11.2443, 125.003];
const OSM_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

export function initializeLeafletMaps(L, root = document) {
    const nodes = root.querySelectorAll('[data-leaflet-map]');

    nodes.forEach((node) => {
        const mapType = node.dataset.leafletMap;
        const isPicker = mapType === 'picker';
        const status = document.getElementById(isPicker ? 'incident-map-load-status' : 'report-map-status');

        try {
            const tileUrl = node.dataset.mapTileUrl;
            const attribution = node.dataset.mapAttribution || OSM_ATTRIBUTION;
            const map = L.map(node, { scrollWheelZoom: true });
            const tileLayer = L.tileLayer(tileUrl, { maxZoom: 19, attribution });

            tileLayer.on('tileerror', () => {
                if (status) status.textContent = 'Map tiles could not load. Check the browser’s internet connection.';
            });
            tileLayer.addTo(map);

            if (isPicker) {
                initializePicker(L, map, node, status);
            } else if (mapType === 'report') {
                initializeReportMap(L, map, window.tomecoReportMapPoints || [], status);
            }

            requestAnimationFrame(() => map.invalidateSize({ pan: false }));
        } catch (_) {
            if (status) status.textContent = 'The map could not be initialized. Refresh the page and try again.';
        }
    });
}

function initializePicker(L, map, node, mapStatus) {
    const latitude = document.getElementById('incident-latitude');
    const longitude = document.getElementById('incident-longitude');
    const selectionStatus = document.getElementById('incident-map-status');
    const parsedLat = Number(node.dataset.lat);
    const parsedLng = Number(node.dataset.lng);
    const hasPoint = node.dataset.lat !== ''
        && node.dataset.lng !== ''
        && Number.isFinite(parsedLat)
        && Number.isFinite(parsedLng)
        && Math.abs(parsedLat) <= 90
        && Math.abs(parsedLng) <= 180;
    let selection = null;

    map.setView(hasPoint ? [parsedLat, parsedLng] : TACLOBAN, hasPoint ? 16 : 14);

    const setPoint = (point) => {
        const lat = Number(point.lat).toFixed(7);
        const lng = Number(point.lng).toFixed(7);
        latitude.value = lat;
        longitude.value = lng;

        if (selection) {
            selection.setLatLng(point);
        } else {
            selection = L.circle(point, {
                radius: 18,
                color: '#087f6b',
                opacity: 0.95,
                weight: 2,
                fillColor: '#19b79a',
                fillOpacity: 0.35,
            }).addTo(map);
        }

        map.panTo(point);
        if (selectionStatus) selectionStatus.textContent = `Selected ${lat}, ${lng}`;
    };

    if (hasPoint) setPoint({ lat: parsedLat, lng: parsedLng });
    map.on('click', (event) => setPoint(event.latlng));
    document.getElementById('clear-incident-map')?.addEventListener('click', () => {
        latitude.value = '';
        longitude.value = '';
        if (selection) {
            map.removeLayer(selection);
            selection = null;
        }
        if (selectionStatus) selectionStatus.textContent = 'No map point selected.';
    });

    if (mapStatus) mapStatus.textContent = 'Click the map to select an incident location.';
}

function initializeReportMap(L, map, points, status) {
    const bounds = L.latLngBounds();
    let ticketCount = 0;
    let pointCount = 0;

    points.forEach((item) => {
        const lat = Number(item.position?.lat);
        const lng = Number(item.position?.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) return;

        const center = L.latLng(lat, lng);
        const count = Math.max(1, Number(item.count) || 1);
        const marker = L.circleMarker(center, {
            radius: Math.min(24, 8 + Math.sqrt(count) * 3),
            color: '#087f6b',
            opacity: 0.9,
            weight: 2,
            fillColor: '#19b79a',
            fillOpacity: 0.75,
        }).addTo(map);

        marker.bindPopup(createTicketPopup(item, count));
        bounds.extend(center);
        pointCount++;
        ticketCount += count;
    });

    if (pointCount > 1) {
        map.fitBounds(bounds.pad(0.15), { maxZoom: 15 });
    } else if (pointCount === 1) {
        map.fitBounds(bounds, { maxZoom: 15 });
    } else {
        map.setView(TACLOBAN, 12);
    }

    if (status) status.textContent = `${ticketCount} geolocated ticket(s) shown.`;
}

function createTicketPopup(item, count) {
    const content = document.createElement('div');
    const heading = document.createElement('strong');
    heading.textContent = `${count} ticket${count === 1 ? '' : 's'}`;
    content.append(heading);

    const place = document.createElement('p');
    place.textContent = item.location || 'Recorded incident location';
    content.append(place);

    const tickets = Array.isArray(item.tickets) ? item.tickets : [];
    if (tickets.length) {
        const list = document.createElement('ul');
        tickets.forEach((ticket) => {
            const line = document.createElement('li');
            line.textContent = `${ticket.number} · ${ticket.date} · ${ticket.location}`;
            list.append(line);
        });
        content.append(list);
    }

    if (count > tickets.length) {
        const remaining = document.createElement('small');
        remaining.textContent = `${count - tickets.length} more ticket(s) at this point`;
        content.append(remaining);
    }

    return content;
}
