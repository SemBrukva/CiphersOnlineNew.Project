// Ядро генерации случайных строк.
//
// Чистый ESM-модуль без зависимостей от DOM: используется страницей
// pages/randomstring-generator.js и проверяется node-скриптом. Вся случайность —
// криптостойкая (Web Crypto getRandomValues) с rejection sampling, чтобы
// исключить модуло-смещение.

// Базовые наборы символов.
export const CHARSETS = {
    lower: 'abcdefghijklmnopqrstuvwxyz',
    upper: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    digits: '0123456789',
    symbols: '!@#$%^&*()-_=+[]{};:,.?/',
}

// Похожие друг на друга символы латиницы (буква «эль», единица, «о», ноль и т. п.).
export const SIMILAR_CHARS = 'iIlL1oO0'

// Набор визуально похожих символов по алфавиту. Для латинских языков — латинский
// набор (+ цифры); для турецкого добавлены ı/İ; для кириллицы — о/О и з/З (похожи
// на 0 и 3). Коды без записи откатываются к SIMILAR_CHARS.
export const SIMILAR_BY_ALPHABET = {
    en: 'iIlL1oO0',
    de: 'iIlL1oO0',
    es: 'iIlL1oO0',
    fr: 'iIlL1oO0',
    it: 'iIlL1oO0',
    pt: 'iIlL1oO0',
    tr: 'iıIİlL1oO0',
    ru: 'оО0зЗ3',
}

/**
 * Возвращает набор визуально похожих символов для кода алфавита.
 *
 * @param {string} code
 * @returns {string}
 */
export function similarChars(code) {
    return SIMILAR_BY_ALPHABET[code] ?? SIMILAR_CHARS
}

// Границы длины строки и числа результатов в партии.
export const MIN_LENGTH = 1
export const MAX_LENGTH = 512
export const MAX_COUNT = 100

/**
 * Возвращает объект Web Crypto (браузер или Node ≥ 19).
 */
function webcrypto() {
    const c = globalThis.crypto
    if (!c || typeof c.getRandomValues !== 'function') {
        throw new Error('Web Crypto API is not available')
    }
    return c
}

/**
 * Возвращает криптостойкое целое в диапазоне [0, max) без модуло-смещения
 * (rejection sampling). `max` должен быть в пределах (0, 2^32].
 */
export function secureRandomInt(max) {
    const m = Math.trunc(max)
    if (m <= 0) {
        throw new Error('bad-range')
    }
    // Отбрасываем «хвост» диапазона, не делящийся на m нацело.
    const limit = Math.floor(0x100000000 / m) * m
    const buf = new Uint32Array(1)
    let x
    do {
        webcrypto().getRandomValues(buf)
        ;[x] = buf
    } while (x >= limit)
    return x % m
}

/**
 * Удаляет из строки повторяющиеся символы, сохраняя порядок первого вхождения.
 */
function dedupe(source) {
    const seen = new Set()
    let out = ''
    for (const ch of source) {
        if (!seen.has(ch)) {
            seen.add(ch)
            out += ch
        }
    }
    return out
}

/**
 * Удаляет из строки все символы, входящие в `remove`.
 */
function stripChars(source, remove) {
    if (!remove) {
        return source
    }
    const set = new Set(remove)
    let out = ''
    for (const ch of source) {
        if (!set.has(ch)) {
            out += ch
        }
    }
    return out
}

/**
 * Собирает пул символов из отмеченных базовых наборов и произвольного алфавита.
 * Дубликаты удаляются, при `excludeSimilar` вырезаются похожие символы.
 *
 * Строчные/прописные буквы берутся из `opts.lowerChars`/`opts.upperChars`, если
 * заданы (для алфавитов языков), иначе — из латинского набора по умолчанию.
 *
 * @param {object} opts
 * @param {boolean} [opts.lower]
 * @param {boolean} [opts.upper]
 * @param {boolean} [opts.digits]
 * @param {boolean} [opts.symbols]
 * @param {string}  [opts.lowerChars]       Строчные буквы алфавита (по умолчанию a–z).
 * @param {string}  [opts.upperChars]       Прописные буквы алфавита (по умолчанию A–Z).
 * @param {string}  [opts.custom]           Пользовательский набор символов.
 * @param {boolean} [opts.excludeSimilar]
 * @param {string}  [opts.similarChars]     Набор похожих символов для исключения (по умолчанию латинский).
 * @returns {string} Пул уникальных символов.
 */
export function buildPool(opts = {}) {
    let pool = ''
    if (opts.lower) pool += typeof opts.lowerChars === 'string' && opts.lowerChars !== '' ? opts.lowerChars : CHARSETS.lower
    if (opts.upper) pool += typeof opts.upperChars === 'string' && opts.upperChars !== '' ? opts.upperChars : CHARSETS.upper
    if (opts.digits) pool += CHARSETS.digits
    if (opts.symbols) pool += CHARSETS.symbols
    if (typeof opts.custom === 'string' && opts.custom !== '') pool += opts.custom

    if (opts.excludeSimilar) pool = stripChars(pool, opts.similarChars || SIMILAR_CHARS)

    return dedupe(pool)
}

/**
 * Размер пула символов.
 */
export function poolSize(opts = {}) {
    return buildPool(opts).length
}

/**
 * Генерирует одну случайную строку из собранного пула символов.
 *
 * @param {object} opts
 * @param {number}  [opts.length=32]
 * @param {boolean} [opts.noRepeats]  Символы не повторяются (длина ≤ размер пула).
 * @returns {string}
 * @throws {Error} `no-charset` — пул пуст; `too-long-norepeat` — длина > пула при noRepeats.
 */
export function generateRandomString(opts = {}) {
    const length = Math.max(MIN_LENGTH, Math.min(MAX_LENGTH, Math.trunc(opts.length ?? 32) || 32))
    const pool = buildPool(opts)
    if (pool.length === 0) {
        throw new Error('no-charset')
    }

    if (opts.noRepeats) {
        if (length > pool.length) {
            throw new Error('too-long-norepeat')
        }
        // Частичное перемешивание Фишера—Йейтса: берём первые `length` символов.
        const chars = [...pool]
        for (let i = 0; i < length; i += 1) {
            const j = i + secureRandomInt(chars.length - i)
            ;[chars[i], chars[j]] = [chars[j], chars[i]]
        }
        return chars.slice(0, length).join('')
    }

    let out = ''
    for (let i = 0; i < length; i += 1) {
        out += pool[secureRandomInt(pool.length)]
    }
    return out
}

/**
 * Генерирует партию случайных строк.
 *
 * @param {object} opts
 * @param {number} [opts.count=1]
 * @returns {string[]}
 */
export function generateBatch(opts = {}) {
    const count = Math.max(1, Math.min(MAX_COUNT, Math.trunc(opts.count ?? 1) || 1))
    const list = []
    for (let i = 0; i < count; i += 1) {
        list.push(generateRandomString(opts))
    }
    return list
}

/**
 * Энтропия одной строки в битах: length × log2(poolSize).
 */
export function stringEntropy(opts = {}) {
    const length = Math.max(MIN_LENGTH, Math.min(MAX_LENGTH, Math.trunc(opts.length ?? 32) || 32))
    const size = poolSize(opts)
    if (size <= 1) return 0
    return length * Math.log2(size)
}

/**
 * Форматирует список строк для вывода согласно выбранному разделителю.
 *
 * @param {string[]} list
 * @param {'newline'|'comma'|'space'|'quoted'} format
 * @returns {string}
 */
export function formatList(list, format) {
    if (!Array.isArray(list) || list.length === 0) return ''
    switch (format) {
        case 'comma':
            return list.join(', ')
        case 'space':
            return list.join(' ')
        case 'quoted':
            return list.map((s) => `"${s}"`).join(', ')
        case 'newline':
        default:
            return list.join('\n')
    }
}
