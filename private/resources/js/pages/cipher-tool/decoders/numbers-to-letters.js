/**
 * Конвертер чисел в буквы / букв в числа.
 *
 * Поддерживаемые режимы кодирования (opts.encoding):
 *   positional-1 — позиция в алфавите с 1 (A=1, B=2…)
 *   positional-0 — позиция в алфавите с 0 (A=0, B=1…)
 *   ascii         — ASCII-код (десятичный)
 *   hex           — ASCII-код (шестнадцатеричный, без префикса)
 *   binary        — ASCII-код (8-битный двоичный)
 *
 * mode === 'encode': числа → буквы  (вкладка «Numbers to Letters»)
 * mode === 'decode': буквы → числа  (вкладка «Letters to Numbers»)
 */

/** @type {Record<string, string[]>} */
const ALPHABETS = {
  en: ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'],
  ru: ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я'],
  de: ['a','ä','b','c','d','e','f','g','h','i','j','k','l','m','n','o','ö','p','q','r','s','t','u','ü','v','w','x','y','z'],
  es: ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','ñ','o','p','q','r','s','t','u','v','w','x','y','z'],
  fr: ['a','à','â','b','c','ç','d','e','é','è','ê','ë','f','g','h','i','î','ï','j','k','l','m','n','o','ô','p','q','r','s','t','u','ù','û','ü','v','w','x','y','ÿ','z'],
  it: ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z'],
  pt: ['a','á','à','ã','b','c','ç','d','e','é','ê','f','g','h','i','í','j','k','l','m','n','o','ó','ô','p','q','r','s','t','u','ú','v','w','x','y','z'],
  tr: ['a','b','c','ç','d','e','f','g','ğ','h','ı','i','j','k','l','m','n','o','ö','p','r','s','ş','t','u','ü','v','y','z'],
}

/**
 * Определяет язык по наибольшему совпадению букв текста с алфавитом.
 *
 * @param {string} text
 * @returns {string}
 */
export function detectLanguage(text) {
  const lower = text.toLowerCase()
  let bestLang = 'en'
  let bestScore = -1
  for (const [lang, alpha] of Object.entries(ALPHABETS)) {
    const alphaSet = new Set(alpha)
    let score = 0
    let total = 0
    for (const ch of lower) {
      if (/\p{L}/u.test(ch)) {
        total++
        if (alphaSet.has(ch)) score++
      }
    }
    if (total > 0) {
      const ratio = score / total
      if (ratio > bestScore) {
        bestScore = ratio
        bestLang = lang
      }
    }
  }
  return bestLang
}

/**
 * Возвращает символ-разделитель по его названию.
 *
 * @param {string} name
 * @returns {string}
 */
function delimChar(name) {
  switch (name) {
    case 'dash':  return '-'
    case 'comma': return ','
    case 'slash': return '/'
    case 'dot':   return '.'
    default:      return ' '
  }
}

/**
 * Разбивает числовую строку на токены с учётом разделителя и пробелов-разделителей слов.
 * Пробел всегда считается границей слова; для space-режима — также разделителем чисел.
 *
 * @param {string} text
 * @param {string} delim
 * @returns {string[][]} Массив слов, каждое слово — массив числовых токенов.
 */
function tokenizeNumbers(text, delim) {
  const sep = delimChar(delim)
  const trimmed = text.trim()
  if (!trimmed) return []

  if (sep === ' ') {
    // В space-режиме всё разделено пробелами, слова не различаются — один массив.
    return [trimmed.split(/\s+/).filter(Boolean)]
  }

  // В остальных режимах слова разделяются пробелами, числа внутри слова — sep.
  return trimmed
    .split(/\s+/)
    .filter(Boolean)
    .map((word) => word.split(sep).filter(Boolean))
}

// ── Позиционные режимы ────────────────────────────────────────────────────

/**
 * Числа → буквы (позиционный режим).
 *
 * @param {string} text
 * @param {string} lang
 * @param {number} base  0 или 1
 * @param {string} delim
 * @returns {string}
 */
function positionalDecode(text, lang, base, delim) {
  const alpha = ALPHABETS[lang] || ALPHABETS.en
  const words = tokenizeNumbers(text, delim)
  const sep = delimChar(delim)

  return words.map((tokens) =>
    tokens.map((tok) => {
      if (!/^\d+$/.test(tok)) return tok
      const idx = parseInt(tok, 10) - base
      return (idx >= 0 && idx < alpha.length) ? alpha[idx] : tok
    }).join('')
  ).join(sep === ' ' ? ' ' : ' ')
}

/**
 * Буквы → числа (позиционный режим).
 *
 * @param {string} text
 * @param {string} lang
 * @param {number} base
 * @param {string} delim
 * @returns {string}
 */
function positionalEncode(text, lang, base, delim) {
  const alpha = ALPHABETS[lang] || ALPHABETS.en
  const indexMap = Object.fromEntries(alpha.map((ch, i) => [ch, i + base]))
  const sep = delimChar(delim)
  const lower = text.toLowerCase()
  const len = [...lower].length  // итерация по codepoints

  const chars = [...lower]
  const result = []
  let wordBuf = []

  const flushWord = () => {
    if (wordBuf.length > 0) {
      result.push(wordBuf.join(sep))
      wordBuf = []
    }
  }

  for (const ch of chars) {
    if (ch === ' ' || ch === '\n' || ch === '\r') {
      flushWord()
      result.push(' ')
    } else if (Object.prototype.hasOwnProperty.call(indexMap, ch)) {
      wordBuf.push(String(indexMap[ch]))
    } else {
      // Не-алфавитный символ передаётся как есть (без разделителя).
      wordBuf.push(ch)
    }
  }
  flushWord()

  return result.join('').trim()
}

// ── ASCII-режимы ──────────────────────────────────────────────────────────

/**
 * Форматирует код символа по типу кодировки.
 *
 * @param {number} code
 * @param {'ascii'|'hex'|'binary'} fmt
 * @returns {string}
 */
function formatCode(code, fmt) {
  if (fmt === 'hex') return code.toString(16).toUpperCase().padStart(2, '0')
  if (fmt === 'binary') return code.toString(2).padStart(8, '0')
  return String(code)
}

/**
 * Парсит токен в числовой код по типу кодировки.
 *
 * @param {string} tok
 * @param {'ascii'|'hex'|'binary'} fmt
 * @returns {number|null}
 */
function parseCode(tok, fmt) {
  if (!tok) return null
  if (fmt === 'hex') {
    if (!/^[0-9a-fA-F]+$/.test(tok)) return null
    return parseInt(tok, 16)
  }
  if (fmt === 'binary') {
    if (!/^[01]+$/.test(tok)) return null
    return parseInt(tok, 2)
  }
  if (!/^\d+$/.test(tok)) return null
  return parseInt(tok, 10)
}

/**
 * Числа → буквы (ASCII-режим).
 *
 * @param {string} text
 * @param {'ascii'|'hex'|'binary'} fmt
 * @param {string} delim
 * @returns {string}
 */
function asciiDecode(text, fmt, delim) {
  const words = tokenizeNumbers(text, delim)
  const sep = delimChar(delim)

  return words.map((tokens) =>
    tokens.map((tok) => {
      const code = parseCode(tok, fmt)
      if (code === null) return tok
      // Печатаем только printable Unicode (>= 32) или позволяем всё > 0
      if (code <= 0) return tok
      return String.fromCodePoint(code)
    }).join('')
  ).join(sep === ' ' ? ' ' : ' ')
}

/**
 * Буквы → числа (ASCII-режим).
 *
 * @param {string} text
 * @param {'ascii'|'hex'|'binary'} fmt
 * @param {string} delim
 * @returns {string}
 */
function asciiEncode(text, fmt, delim) {
  const sep = delimChar(delim)
  const result = []
  let wordBuf = []

  const flushWord = () => {
    if (wordBuf.length > 0) {
      result.push(wordBuf.join(sep))
      wordBuf = []
    }
  }

  for (const ch of text) {
    if (ch === '\n' || ch === '\r') {
      flushWord()
      result.push('\n')
    } else if (ch === ' ') {
      flushWord()
      result.push(' ')
    } else {
      const code = ch.codePointAt(0) ?? 0
      wordBuf.push(formatCode(code, fmt))
    }
  }
  flushWord()

  return result.join('').trim()
}

// ── Публичный API ──────────────────────────────────────────────────────────

/**
 * Преобразует значение: числа↔буквы в зависимости от mode.
 *
 * mode === 'encode': числа → буквы
 * mode === 'decode': буквы → числа
 *
 * @param {string} value
 * @param {'encode'|'decode'} mode
 * @param {Record<string, unknown>} [opts]
 * @returns {string}
 */
export function transformNumbersToLetters(value, mode, opts) {
  const encoding = String(opts?.encoding || 'positional-1')
  const delim    = String(opts?.delimiter || 'space')
  let   lang     = String(opts?.language  || 'auto')

  const isPositional = encoding === 'positional-1' || encoding === 'positional-0'
  const base = encoding === 'positional-0' ? 0 : 1

  if (mode === 'encode') {
    // Числа → буквы
    if (isPositional) {
      if (lang === 'auto') lang = 'en'  // при декоде позиций язык нужно задать явно
      return positionalDecode(value, lang, base, delim)
    }
    const fmt = /** @type {'ascii'|'hex'|'binary'} */ (encoding)
    return asciiDecode(value, fmt, delim)
  } else {
    // Буквы → числа
    if (isPositional) {
      if (lang === 'auto') lang = detectLanguage(value)
      return positionalEncode(value, lang, base, delim)
    }
    const fmt = /** @type {'ascii'|'hex'|'binary'} */ (encoding)
    return asciiEncode(value, fmt, delim)
  }
}

/**
 * Возвращает true для любого непустого значения.
 *
 * @param {string} value
 * @returns {boolean}
 */
export function looksLikeNumbersToLetters(value) {
  return Boolean(value && value.trim())
}
