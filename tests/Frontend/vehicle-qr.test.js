import assert from 'node:assert/strict';
import test from 'node:test';
import { resolveVehicleQr } from '../../resources/js/vehicle-qr.js';

test('opens a same-origin vehicle scan link directly', () => {
    assert.deepEqual(
        resolveVehicleQr('https://tomeco.example/scan/123e4567-e89b-12d3-a456-426614174000', 'https://tomeco.example'),
        { type: 'scan-url', url: 'https://tomeco.example/scan/123e4567-e89b-12d3-a456-426614174000' },
    );
});

test('keeps raw tokens and external QR links in the local lookup flow', () => {
    assert.deepEqual(resolveVehicleQr('  123e4567-e89b-12d3-a456-426614174000  ', 'https://tomeco.example'), {
        type: 'code',
        code: '123e4567-e89b-12d3-a456-426614174000',
    });
    assert.deepEqual(resolveVehicleQr('https://other.example/scan/token', 'https://tomeco.example'), {
        type: 'code',
        code: 'https://other.example/scan/token',
    });
});

test('ignores empty QR payloads', () => {
    assert.equal(resolveVehicleQr('   ', 'https://tomeco.example'), null);
});
