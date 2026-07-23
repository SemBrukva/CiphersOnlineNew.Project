// Ядро генерации UUID / GUID (RFC 4122 / 9562).
//
// Чистый ESM-модуль без зависимостей от DOM: используется страницей
// pages/uuid-generator.js и проверяется node-скриптом. Версии v1/v4/v7 и
// nil/max — синхронные; именные v3/v5 — асинхронные (v5 через нативный
// crypto.subtle SHA-1, v3 через динамически подгружаемый hash-wasm MD5,
// чтобы тяжёлый wasm не попадал в основной бандл).

// Предвычисленная таблица байт → двузначный hex.
const HEX = [];
for (let i = 0; i < 256; i += 1) {
    HEX.push((i + 0x100).toString(16).slice(1));
}

// Нулевой и максимальный UUID (спец-значения RFC 9562).
export const NIL = '00000000-0000-0000-0000-000000000000';
export const MAX = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

// Стандартные пространства имён для v3/v5 (RFC 4122, Appendix C).
export const NAMESPACES = {
    dns: '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
    url: '6ba7b811-9dad-11d1-80b4-00c04fd430c8',
    oid: '6ba7b812-9dad-11d1-80b4-00c04fd430c8',
    x500: '6ba7b814-9dad-11d1-80b4-00c04fd430c8',
};

// Версии, которым для генерации нужны namespace + name.
export const NAME_BASED = ['v3', 'v5'];

// Смещение между григорианской эпохой UUID (1582-10-15) и Unix-эпохой,
// в интервалах по 100 нс.
const GREGORIAN_OFFSET = 122192928000000000n;

/**
 * Возвращает объект Web Crypto (браузер или Node ≥ 19).
 */
function webcrypto() {
    const c = globalThis.crypto;
    if (!c || typeof c.getRandomValues !== 'function') {
        throw new Error('Web Crypto API is not available');
    }
    return c;
}

/**
 * Возвращает n криптостойких случайных байт.
 */
function randomBytes(n) {
    const bytes = new Uint8Array(n);
    webcrypto().getRandomValues(bytes);
    return bytes;
}

/**
 * Форматирует 16 байт в каноническую строку UUID (8-4-4-4-12).
 */
function bytesToUuid(b) {
    return (
        HEX[b[0]] + HEX[b[1]] + HEX[b[2]] + HEX[b[3]] + '-'
        + HEX[b[4]] + HEX[b[5]] + '-'
        + HEX[b[6]] + HEX[b[7]] + '-'
        + HEX[b[8]] + HEX[b[9]] + '-'
        + HEX[b[10]] + HEX[b[11]] + HEX[b[12]] + HEX[b[13]] + HEX[b[14]] + HEX[b[15]]
    );
}

/**
 * Разбирает строку UUID в 16 байт; бросает Error('bad-namespace') при неверном формате.
 */
function parseUuid(value) {
    const hex = String(value).replace(/urn:uuid:/i, '').replace(/[{}\s-]/g, '');
    if (!/^[0-9a-fA-F]{32}$/.test(hex)) {
        throw new Error('bad-namespace');
    }
    const bytes = new Uint8Array(16);
    for (let i = 0; i < 16; i += 1) {
        bytes[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16);
    }
    return bytes;
}

/**
 * Приводит идентификатор пространства имён (ключ dns/url/oid/x500 или сырой UUID)
 * к каноническому UUID пространства имён.
 */
export function resolveNamespace(namespace) {
    const key = String(namespace).toLowerCase();
    return NAMESPACES[key] ?? namespace;
}

/**
 * UUID версии 4 (полностью случайный).
 */
export function v4() {
    const b = randomBytes(16);
    b[6] = (b[6] & 0x0f) | 0x40;
    b[8] = (b[8] & 0x3f) | 0x80;
    return bytesToUuid(b);
}

/**
 * UUID версии 7 (48-битная Unix-метка времени в мс + случайный хвост, сортируется по времени).
 */
export function v7(now = Date.now()) {
    const b = randomBytes(16);
    const ts = BigInt(now);
    b[0] = Number((ts >> 40n) & 0xffn);
    b[1] = Number((ts >> 32n) & 0xffn);
    b[2] = Number((ts >> 24n) & 0xffn);
    b[3] = Number((ts >> 16n) & 0xffn);
    b[4] = Number((ts >> 8n) & 0xffn);
    b[5] = Number(ts & 0xffn);
    b[6] = (b[6] & 0x0f) | 0x70;
    b[8] = (b[8] & 0x3f) | 0x80;
    return bytesToUuid(b);
}

/**
 * UUID версии 1 (на основе времени). Node и clock sequence — случайные,
 * multicast-бит node выставлен согласно RFC (случайный, а не MAC, узел).
 * `tick` разносит метку времени при пакетной генерации в одну миллисекунду.
 */
export function v1(now = Date.now(), tick = 0) {
    const b = randomBytes(16);
    const ts = BigInt(now) * 10000n + GREGORIAN_OFFSET + BigInt(tick);
    const timeLow = ts & 0xffffffffn;
    const timeMid = (ts >> 32n) & 0xffffn;
    const timeHi = (ts >> 48n) & 0x0fffn;
    b[0] = Number((timeLow >> 24n) & 0xffn);
    b[1] = Number((timeLow >> 16n) & 0xffn);
    b[2] = Number((timeLow >> 8n) & 0xffn);
    b[3] = Number(timeLow & 0xffn);
    b[4] = Number((timeMid >> 8n) & 0xffn);
    b[5] = Number(timeMid & 0xffn);
    b[6] = Number((timeHi >> 8n) & 0x0fn) | 0x10;
    b[7] = Number(timeHi & 0xffn);
    b[8] = (b[8] & 0x3f) | 0x80;
    b[10] |= 0x01;
    return bytesToUuid(b);
}

/**
 * Собирает вход для именного UUID: байты пространства имён + UTF-8 байты имени.
 */
function nameData(name, namespace) {
    const ns = parseUuid(resolveNamespace(namespace));
    const nameBytes = new TextEncoder().encode(String(name));
    const data = new Uint8Array(ns.length + nameBytes.length);
    data.set(ns, 0);
    data.set(nameBytes, ns.length);
    return data;
}

/**
 * Собирает UUID из первых 16 байт хеша с указанной версией.
 */
function hashToUuid(hashBytes, version) {
    const b = hashBytes.slice(0, 16);
    b[6] = (b[6] & 0x0f) | (version << 4);
    b[8] = (b[8] & 0x3f) | 0x80;
    return bytesToUuid(b);
}

/**
 * UUID версии 5 (именной, SHA-1 через нативный crypto.subtle).
 */
export async function v5(name, namespace) {
    const data = nameData(name, namespace);
    const digest = new Uint8Array(await webcrypto().subtle.digest('SHA-1', data));
    return hashToUuid(digest, 5);
}

/**
 * UUID версии 3 (именной, MD5). hash-wasm подгружается динамически,
 * чтобы не утяжелять основной бандл.
 */
export async function v3(name, namespace) {
    const data = nameData(name, namespace);
    const { md5 } = await import('hash-wasm');
    const hex = await md5(data);
    const bytes = new Uint8Array(16);
    for (let i = 0; i < 16; i += 1) {
        bytes[i] = parseInt(hex.slice(i * 2, i * 2 + 2), 16);
    }
    return hashToUuid(bytes, 3);
}

/**
 * Применяет параметры форматирования к каноническому UUID.
 *
 * @param {string} uuid Канонический UUID (нижний регистр, с дефисами).
 * @param {{uppercase?: boolean, hyphens?: boolean, braces?: boolean, urn?: boolean}} opts
 */
export function format(uuid, opts = {}) {
    let s = uuid;
    if (opts.hyphens === false) {
        s = s.replace(/-/g, '');
    }
    if (opts.uppercase) {
        s = s.toUpperCase();
    }
    if (opts.braces) {
        s = `{${s}}`;
    }
    if (opts.urn) {
        s = `urn:uuid:${s}`;
    }
    return s;
}

/**
 * Генерирует один UUID указанной версии.
 */
async function generateOne(version, name, namespace, tick) {
    switch (version) {
        case 'v1': return v1(Date.now(), tick);
        case 'v4': return v4();
        case 'v7': return v7();
        case 'v3': return v3(name, namespace);
        case 'v5': return v5(name, namespace);
        case 'nil': return NIL;
        case 'max': return MAX;
        default: throw new Error(`unknown-version:${version}`);
    }
}

/**
 * Генерирует список UUID.
 *
 * @param {object} options
 * @param {string} options.version    Версия: v1|v3|v4|v5|v7|nil|max.
 * @param {number} [options.count]    Количество (1–100, зажимается).
 * @param {string} [options.name]     Имя (для v3/v5).
 * @param {string} [options.namespace] Пространство имён: ключ или UUID (для v3/v5).
 * @param {object} [options.format]   Параметры форматирования.
 * @returns {Promise<string[]>}       Отформатированные UUID.
 */
export async function generate(options) {
    const version = options.version;
    const count = Math.max(1, Math.min(100, Math.trunc(options.count ?? 1) || 1));
    const fmt = options.format ?? {};
    const results = [];
    for (let i = 0; i < count; i += 1) {
        // eslint-disable-next-line no-await-in-loop
        const uuid = await generateOne(version, options.name ?? '', options.namespace ?? '', i);
        results.push(format(uuid, fmt));
    }
    return results;
}
