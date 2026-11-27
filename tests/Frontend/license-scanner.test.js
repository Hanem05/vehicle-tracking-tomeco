import assert from 'node:assert/strict';
import test from 'node:test';
import { parseLicensePayload } from '../../resources/js/license-scanner.js';

test('reads labeled name and license number from a plain barcode payload', () => {
    assert.deepEqual(
        parseLicensePayload('FULL NAME: Maria Cruz; DRIVER LICENSE NO: A1234567'),
        { name: 'Maria Cruz', number: 'A1234567' },
    );
});

test('reads common fields from JSON and nested barcode payloads', () => {
    assert.deepEqual(
        parseLicensePayload(JSON.stringify({ card: { DriverName: 'Juan Dela Cruz', DLNo: 'N01-234-567' } })),
        { name: 'Juan Dela Cruz', number: 'N01-234-567' },
    );
});

test('returns empty values when the card format has no recognized fields', () => {
    assert.deepEqual(parseLicensePayload('unstructured barcode text'), { name: '', number: '' });
});

test('falls back to labeled text when a payload is not valid JSON', () => {
    assert.deepEqual(
        parseLicensePayload('{broken JSON; NAME=Maria Cruz | LICENSE ID: ABCD1234'),
        { name: 'Maria Cruz', number: 'ABCD1234' },
    );
});
