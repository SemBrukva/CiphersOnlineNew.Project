// Ядро генерации паролей и парольных фраз.
//
// Чистый ESM-модуль без зависимостей от DOM: используется страницей
// pages/password-generator.js и проверяется node-скриптом. Вся случайность —
// криптостойкая (Web Crypto getRandomValues) с rejection sampling, чтобы
// исключить модуло-смещение (критично для генератора паролей).

// Наборы символов.
export const CHARSETS = {
    lower: 'abcdefghijklmnopqrstuvwxyz',
    upper: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    digits: '0123456789',
    symbols: '!@#$%^&*()-_=+[]{};:,.?/',
};

// Похожие друг на друга символы (буква «эль», единица, «о», ноль и т. п.).
export const SIMILAR_CHARS = 'iIlL1oO0';

// Неоднозначные символы (скобки, кавычки, слеши), которые легко перепутать
// или которые ломают копирование в некоторых контекстах.
export const AMBIGUOUS_CHARS = '{}[]()/\\\'"`~,;:.<>';

// Границы длины пароля и числа слов во фразе.
export const MIN_LENGTH = 4;
export const MAX_LENGTH = 128;
export const MIN_WORDS = 3;
export const MAX_WORDS = 20;
export const MAX_COUNT = 100;

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
 * Возвращает криптостойкое целое в диапазоне [0, max) без модуло-смещения
 * (rejection sampling). `max` должен быть в пределах (0, 2^32].
 */
export function secureRandomInt(max) {
    const m = Math.trunc(max);
    if (m <= 0) {
        throw new Error('bad-range');
    }
    // Отбрасываем «хвост» диапазона, не делящийся на m нацело.
    const limit = Math.floor(0x100000000 / m) * m;
    const buf = new Uint32Array(1);
    let x;
    do {
        webcrypto().getRandomValues(buf);
        [x] = buf;
    } while (x >= limit);
    return x % m;
}

/**
 * Перемешивает массив на месте (Фишер—Йейтс) на криптостойкой случайности.
 */
function shuffle(arr) {
    for (let i = arr.length - 1; i > 0; i -= 1) {
        const j = secureRandomInt(i + 1);
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
}

/**
 * Удаляет из строки все символы, входящие в `remove`.
 */
function stripChars(source, remove) {
    if (!remove) {
        return source;
    }
    const set = new Set(remove);
    let out = '';
    for (const ch of source) {
        if (!set.has(ch)) {
            out += ch;
        }
    }
    return out;
}

/**
 * Собирает активные наборы символов для пароля с учётом исключений.
 *
 * @param {object} opts
 * @returns {string[]} Массив наборов (каждый — строка непустых символов).
 */
export function activeSets(opts) {
    const wanted = [];
    if (opts.lower !== false) wanted.push(CHARSETS.lower);
    if (opts.upper) wanted.push(CHARSETS.upper);
    if (opts.digits) wanted.push(CHARSETS.digits);
    if (opts.symbols) wanted.push(CHARSETS.symbols);

    let remove = '';
    if (opts.excludeSimilar) remove += SIMILAR_CHARS;
    if (opts.excludeAmbiguous) remove += AMBIGUOUS_CHARS;

    return wanted.map((s) => stripChars(s, remove)).filter((s) => s.length > 0);
}

/**
 * Размер общего пула символов пароля.
 */
export function poolSize(opts) {
    return activeSets(opts).reduce((n, s) => n + s.length, 0);
}

/**
 * Генерирует один пароль.
 *
 * По умолчанию гарантирует присутствие хотя бы одного символа из каждого
 * активного набора (если длина это позволяет). При `noRepeats` символы не
 * повторяются (длина не может превышать размер пула).
 *
 * @param {object} opts
 * @param {number} [opts.length=16]
 * @param {boolean} [opts.lower=true]
 * @param {boolean} [opts.upper]
 * @param {boolean} [opts.digits]
 * @param {boolean} [opts.symbols]
 * @param {boolean} [opts.excludeSimilar]
 * @param {boolean} [opts.excludeAmbiguous]
 * @param {boolean} [opts.noRepeats]
 * @returns {string}
 */
export function generatePassword(opts = {}) {
    const length = Math.max(MIN_LENGTH, Math.min(MAX_LENGTH, Math.trunc(opts.length ?? 16) || 16));
    const sets = activeSets(opts);
    if (sets.length === 0) {
        throw new Error('no-charset');
    }

    const pool = sets.join('');
    const noRepeats = Boolean(opts.noRepeats);
    if (noRepeats && length > pool.length) {
        throw new Error('too-long-norepeat');
    }

    const chars = [];
    const used = new Set();

    const pick = (source) => {
        if (!noRepeats) {
            return source[secureRandomInt(source.length)];
        }
        // Без повторов: выбираем из ещё не использованных символов набора.
        const avail = [];
        for (const ch of source) {
            if (!used.has(ch)) avail.push(ch);
        }
        if (avail.length === 0) {
            return null;
        }
        const ch = avail[secureRandomInt(avail.length)];
        used.add(ch);
        return ch;
    };

    // Гарантируем по одному символу из каждого набора, если длина позволяет.
    if (length >= sets.length) {
        for (const set of sets) {
            const ch = pick(set);
            if (ch !== null) chars.push(ch);
        }
    }

    // Добираем остаток из общего пула.
    while (chars.length < length) {
        const ch = pick(pool);
        if (ch === null) break;
        chars.push(ch);
    }

    return shuffle(chars).join('');
}

/**
 * Приводит слово к нужному регистру.
 */
function applyWordCase(word, mode) {
    if (mode === 'upper') return word.toUpperCase();
    if (mode === 'capitalize') return word.charAt(0).toUpperCase() + word.slice(1);
    return word;
}

/**
 * Генерирует одну парольную фразу из переданного словаря.
 *
 * @param {object} opts
 * @param {number} [opts.words=6]
 * @param {string} [opts.separator='-']
 * @param {'lower'|'capitalize'|'upper'} [opts.wordCase='lower']
 * @param {boolean} [opts.addNumber]  Дописать случайную цифру к случайному слову.
 * @param {string[]} wordlist        Словарь (например EFF_WORDLIST).
 * @returns {string}
 */
export function generatePassphrase(opts = {}, wordlist = []) {
    if (!Array.isArray(wordlist) || wordlist.length === 0) {
        throw new Error('no-wordlist');
    }
    const count = Math.max(MIN_WORDS, Math.min(MAX_WORDS, Math.trunc(opts.words ?? 6) || 6));
    const separator = typeof opts.separator === 'string' ? opts.separator : '-';
    const wordCase = opts.wordCase ?? 'lower';

    const words = [];
    for (let i = 0; i < count; i += 1) {
        words.push(applyWordCase(wordlist[secureRandomInt(wordlist.length)], wordCase));
    }

    if (opts.addNumber) {
        const idx = secureRandomInt(count);
        words[idx] += String(secureRandomInt(10));
    }

    return words.join(separator);
}

/**
 * Энтропия пароля в битах: length × log2(poolSize).
 */
export function passwordEntropy(opts = {}) {
    const length = Math.max(MIN_LENGTH, Math.min(MAX_LENGTH, Math.trunc(opts.length ?? 16) || 16));
    const size = poolSize(opts);
    if (size <= 1) return 0;
    return length * Math.log2(size);
}

/**
 * Энтропия парольной фразы в битах: words × log2(dictSize) (+ ~3.3 бита за цифру).
 */
export function passphraseEntropy(opts = {}, dictSize = 7776) {
    const count = Math.max(MIN_WORDS, Math.min(MAX_WORDS, Math.trunc(opts.words ?? 6) || 6));
    let bits = count * Math.log2(dictSize);
    if (opts.addNumber) bits += Math.log2(10 * count);
    return bits;
}

/**
 * Классифицирует энтропию в полосу надёжности 0–4.
 * 0 — очень слабый, 1 — слабый, 2 — средний, 3 — надёжный, 4 — очень надёжный.
 */
export function strengthBand(bits) {
    if (bits < 28) return 0;
    if (bits < 40) return 1;
    if (bits < 60) return 2;
    if (bits < 100) return 3;
    return 4;
}

/**
 * Генерирует партию паролей.
 *
 * @returns {string[]}
 */
export function generatePasswordBatch(opts = {}) {
    const count = Math.max(1, Math.min(MAX_COUNT, Math.trunc(opts.count ?? 1) || 1));
    const list = [];
    for (let i = 0; i < count; i += 1) {
        list.push(generatePassword(opts));
    }
    return list;
}

/**
 * Генерирует партию парольных фраз.
 *
 * @returns {string[]}
 */
export function generatePassphraseBatch(opts = {}, wordlist = []) {
    const count = Math.max(1, Math.min(MAX_COUNT, Math.trunc(opts.count ?? 1) || 1));
    const list = [];
    for (let i = 0; i < count; i += 1) {
        list.push(generatePassphrase(opts, wordlist));
    }
    return list;
}
