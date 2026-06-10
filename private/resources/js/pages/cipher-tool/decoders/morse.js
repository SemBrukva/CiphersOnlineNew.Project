/**
 * Базовая таблица Морзе — Международный стандарт ITU-R M.1677-1.
 * @type {Record<string, string>}
 */
const MORSE_BASE = {
  A: '.-',    B: '-...',  C: '-.-.',  D: '-..',   E: '.',
  F: '..-.',  G: '--.',   H: '....',  I: '..',    J: '.---',
  K: '-.-',   L: '.-..',  M: '--',    N: '-.',    O: '---',
  P: '.--.',  Q: '--.-',  R: '.-.',   S: '...',   T: '-',
  U: '..-',   V: '...-',  W: '.--',   X: '-..-',  Y: '-.--',
  Z: '--..',
  '0': '-----', '1': '.----', '2': '..---', '3': '...--', '4': '....-',
  '5': '.....', '6': '-....', '7': '--...', '8': '---..', '9': '----.',
  '.': '.-.-.-', ',': '--..--', '?': '..--..', "'": '.----.',
  '!': '-.-.--', '/': '-..-.', '(': '-.--.', ')': '-.--.-',
  '&': '.-...', ':': '---...', ';': '-.-.-.', '=': '-...-',
  '+': '.-.-.', '-': '-....-', '_': '..--.-', '"': '.-..-.',
  '$': '...-..-', '@': '.--.-.',
}

/**
 * Немецкий: стандарт ITU + умлауты и CH.
 * Ä: .-.-  Ö: ---.  Ü: ..--  CH: ----
 */
const MORSE_DE = {
  ...MORSE_BASE,
  'Ä': '.-.-', 'ä': '.-.-',  // Ä (ä)
  'Ö': '---.', 'ö': '---.',  // Ö (ö)  — отличается от O: ---
  'Ü': '..--', 'ü': '..--',  // Ü (ü)  — отличается от U: ..-
  'CH': '----',               // CH
}

/**
 * Испанский: стандарт ITU + Ñ.
 * Ñ: --.--
 */
const MORSE_ES = {
  ...MORSE_BASE,
  'Ñ': '--.--', 'ñ': '--.--',  // Ñ (ñ)
}

/**
 * Русская азбука Морзе (советский стандарт ГОСТ).
 * Цифры и знаки препинания — те же, что в ITU.
 * Ё кодируется как Е (традиционная практика).
 */
const MORSE_RU = {
  '0': '-----', '1': '.----', '2': '..---', '3': '...--', '4': '....-',
  '5': '.....', '6': '-....', '7': '--...', '8': '---..', '9': '----.',
  '.': '.-.-.-', ',': '--..--', '?': '..--..', "'": '.----.',
  '!': '-.-.--', '/': '-..-.', '=': '-...-', '+': '.-.-.',
  '-': '-....-', '(': '-.--.', ')': '-.--.-',
  // Кириллица
  'А': '.-',    'Б': '-...',  'В': '.--',   'Г': '--.',
  'Д': '-..',   'Е': '.',     'Ё': '.',     'Ж': '...-',
  'З': '--..',  'И': '..',    'Й': '.---',  'К': '-.-',
  'Л': '.-..',  'М': '--',    'Н': '-.',    'О': '---',
  'П': '.--.',  'Р': '.-.',   'С': '...',   'Т': '-',
  'У': '..-',   'Ф': '..-.',  'Х': '....',  'Ц': '-.-.',
  'Ч': '---.',  'Ш': '----',  'Щ': '--.-',  'Ъ': '..--..',
  'Ы': '-.--',  'Ь': '-..-',  'Э': '..-..', 'Ю': '..--',
  'Я': '.-.-',
}

/**
 * Турецкий: стандарт ITU + спецсимволы.
 * Ç: -.-..  Ğ: --.-.  Ş: -.---.
 * Ö: ---.  Ü: ..--  (как в немецком, без конфликтов с ITU)
 */
const MORSE_TR = {
  ...MORSE_BASE,
  'Ç': '-.-..', 'ç': '-.-..', // Ç — уникальный код
  'Ğ': '--.-.',  'ğ': '--.-.',  // Ğ — уникальный код
  'Ş': '-.---', 'ş': '-.---',  // Ş — уникальный код
  'Ö': '---.', 'ö': '---.',    // Ö
  'Ü': '..--', 'ü': '..--',    // Ü
}

/** @type {Record<string, Record<string, string>>} */
const MAPS = {
  en: MORSE_BASE,
  de: MORSE_DE,
  es: MORSE_ES,
  fr: MORSE_BASE,
  it: MORSE_BASE,
  pt: MORSE_BASE,
  ru: MORSE_RU,
  tr: MORSE_TR,
}

/**
 * Возвращает карту Морзе для языка.
 *
 * @param {string} lang
 * @returns {Record<string, string>}
 */
function mapFor(lang) {
  return MAPS[lang] ?? MORSE_BASE
}

/** @type {Map<string, Record<string, string>>} */
const reverseCache = new Map()

/**
 * Строит и кэширует обратную карту (код → символ).
 *
 * @param {string} lang
 * @returns {Record<string, string>}
 */
function reverseFor(lang) {
  if (reverseCache.has(lang)) return reverseCache.get(lang)

  const map = mapFor(lang)
  const reverse = {}
  // Заполняем обратную карту (первый символ с данным кодом имеет приоритет)
  for (const [char, code] of Object.entries(map)) {
    if (!Object.prototype.hasOwnProperty.call(reverse, code)) {
      reverse[code] = char
    }
  }
  reverseCache.set(lang, reverse)
  return reverse
}

/**
 * Кодирует текст в азбуку Морзе.
 * Буквы слова разделяются пробелом, слова — ' / '.
 *
 * @param {string} text
 * @param {string} lang
 * @returns {string}
 */
function encodeMorse(text, lang) {
  const map = mapFor(lang)
  const upper = text.toUpperCase().trim()

  return upper
    .split(/\s+/)
    .map((word) => {
      const symbols = []
      let i = 0
      while (i < word.length) {
        // CH — двухбуквенная лигатура (немецкий)
        if (i + 1 < word.length && word[i] === 'C' && word[i + 1] === 'H' && map['CH']) {
          symbols.push(map['CH'])
          i += 2
        } else {
          symbols.push(map[word[i]] ?? '?')
          i++
        }
      }
      return symbols.join(' ')
    })
    .join(' / ')
}

/**
 * Декодирует азбуку Морзе в текст.
 * Слова разделяются ' / ', буквы — пробелом.
 *
 * @param {string} text
 * @param {string} lang
 * @returns {string}
 */
function decodeMorse(text, lang) {
  const reverse = reverseFor(lang)
  return text
    .trim()
    .split(/\s*\/\s*/)
    .map((word) =>
      word
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .map((code) => reverse[code] ?? '?')
        .join('')
    )
    .join(' ')
}

/**
 * Автоматически определяет язык по тексту.
 * При декодировании возвращает 'en' (из кода Морзе язык не определить).
 *
 * @param {string} text
 * @param {'encode'|'decode'} mode
 * @returns {string}
 */
export function detectLanguage(text, mode = 'encode') {
  if (mode !== 'encode') return 'en'
  const u = text.toUpperCase()
  if (/[А-ЯЁ]/.test(u)) return 'ru'
  if (/[ÄÖÜ]/.test(u)) return 'de'
  if (/[ÑÓ]/.test(u)) return 'es'
  if (/[ÇĞŞIÖ]/.test(u) && /[İĞŞÇ]/.test(text)) return 'tr'
  return 'en'
}

/**
 * Возвращает массив символов, которые не могут быть закодированы (неизвестны карте).
 * Пробелы, переносы строк не считаются ошибкой.
 *
 * @param {string} text
 * @param {string} lang
 * @returns {string[]}
 */
export function getUnknownChars(text, lang) {
  const map = mapFor(lang)
  const upper = text.toUpperCase()
  const unknown = new Set()
  let i = 0
  while (i < upper.length) {
    const ch = upper[i]
    if (/\s/.test(ch)) { i++; continue }
    if (i + 1 < upper.length && upper[i] === 'C' && upper[i + 1] === 'H' && map['CH']) {
      i += 2; continue
    }
    if (!Object.prototype.hasOwnProperty.call(map, ch)) {
      unknown.add(text[i]) // сохраняем оригинальный регистр для читаемости
    }
    i++
  }
  return [...unknown]
}

/**
 * Проверяет, соответствует ли строка формату азбуки Морзе.
 * Допустимы: точки, тире, пробелы, косые черты.
 *
 * @param {string} value
 * @returns {boolean}
 */
export function isValidMorseFormat(value) {
  const s = value.trim()
  if (!s) return false
  return /^[.\-\s/]+$/.test(s) && /[.\-]/.test(s)
}

/**
 * Преобразует значение в зависимости от режима и языка.
 *
 * @param {string} value
 * @param {'encode'|'decode'} mode
 * @param {Record<string, unknown>} [opts]
 * @returns {string}
 */
export function transformMorse(value, mode, opts) {
  const lang = String(opts?.language || 'en')
  return mode === 'encode' ? encodeMorse(value, lang) : decodeMorse(value, lang)
}

/**
 * Эвристика: похожа ли строка на азбуку Морзе.
 *
 * @param {string} value
 * @returns {boolean}
 */
export function looksLikeMorse(value) {
  const s = value.trim()
  if (!s) return false
  return /^[.\- /]+$/.test(s) && /[.\-]/.test(s)
}

export { MORSE_BASE }
