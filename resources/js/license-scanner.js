const NAME_KEYS = new Set(['name', 'fullname', 'drivername', 'displayname']);
const LICENSE_KEYS = new Set(['licensenumber', 'licenseno', 'licenseid', 'dln', 'dlno']);

/** Extract the driver name and license number from common barcode payloads. */
export function parseLicensePayload(raw) {
    const text = String(raw ?? '');
    let name = '';
    let number = '';

    const readObject = (value) => {
        if (!value || typeof value !== 'object') return;
        for (const [key, item] of Object.entries(value)) {
            const normalized = key.toLowerCase().replace(/[^a-z]/g, '');
            if (typeof item === 'string' || typeof item === 'number') {
                if (!name && NAME_KEYS.has(normalized)) name = String(item).trim();
                if (!number && LICENSE_KEYS.has(normalized)) number = String(item).trim();
            } else if (item && typeof item === 'object') {
                readObject(item);
            }
        }
    };

    try {
        readObject(JSON.parse(text));
    } catch (_) {
        // Physical cards often use issuer-specific, non-JSON barcode payloads.
    }

    const nameMatch = text.match(/(?:FULL[ _-]*NAME|DRIVER[ _-]*NAME|NAME)\s*[:=]\s*([^\r\n;|]+)/i);
    const numberMatch = text.match(/(?:DRIVER[ _-]*)?(?:LICENSE|LICENCE)[ _-]*(?:NO\.?|NUMBER|ID|#)?\s*[:=#]\s*([A-Z0-9-]{4,50})|\b(?:DLN|DLNO)\s*[:=#]\s*([A-Z0-9-]{4,50})/i);

    return {
        name: name || nameMatch?.[1]?.trim() || '',
        number: number || numberMatch?.[1] || numberMatch?.[2] || '',
    };
}
