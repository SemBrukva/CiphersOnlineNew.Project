/**
 * Преобразование для инструмента Punycode (RFC 3492 / IDNA).
 *
 * Два варианта (opts.variant):
 *   domain — обработка по меткам доменного имени: метки с не-ASCII кодируются
 *            с префиксом `xn--`, чистые ASCII-метки остаются как есть.
 *   raw    — «сырой» bootstring RFC 3492 без префикса и разбивки по точкам.
 */

// Параметры bootstring из RFC 3492.
const BASE = 36
const TMIN = 1
const TMAX = 26
const SKEW = 38
const DAMP = 700
const INITIAL_BIAS = 72
const INITIAL_N = 128
const DELIMITER = '-'
const MAX_INT = 0x7fffffff

/**
 * Преобразование значения инструмента Punycode.
 */
export function transformPunycode(value, mode, opts) {
  const variant = opts?.variant === 'raw' ? 'raw' : 'domain'
  if (mode === 'encode') {
    return variant === 'raw' ? encodeRaw(value) : encodeDomain(value)
  }
  return variant === 'raw' ? decodeRaw(value) : decodeDomain(value)
}

/**
 * Эвристика определения закодированного значения (только фолбэк — примеры задают direction явно).
 */
export function looksLikePunycode(value) {
  const v = value.trim()
  // Доменная форма: любая метка с префиксом xn--.
  if (/(^|\.)xn--/i.test(v)) return true
  // Сырая форма: basic-часть + дефис + «расширенная» часть из a-z0-9.
  return /^[\x00-\x7f]*-[a-z0-9]+$/i.test(v) && !/\s/.test(v)
}

/**
 * Кодирует доменное имя в ASCII-совместимую форму (по меткам).
 */
function encodeDomain(value) {
  return value
    .split('.')
    .map((label) => {
      if (label === '' || !hasNonAscii(label)) return label
      return 'xn--' + punycodeEncode(label.normalize('NFC').toLowerCase())
    })
    .join('.')
}

/**
 * Декодирует доменное имя из ASCII-совместимой формы (по меткам).
 */
function decodeDomain(value) {
  return value
    .split('.')
    .map((label) => {
      if (/^xn--/i.test(label)) return punycodeDecode(label.slice(4))
      return label
    })
    .join('.')
}

/**
 * Кодирует всю строку «сырым» bootstring (без xn-- и без точек).
 */
function encodeRaw(value) {
  return punycodeEncode(value)
}

/**
 * Декодирует «сырой» bootstring в Unicode.
 */
function decodeRaw(value) {
  return punycodeDecode(value)
}

/**
 * Проверяет наличие не-ASCII символов.
 */
function hasNonAscii(str) {
  return /[^\x00-\x7f]/.test(str)
}

/**
 * Адаптация смещения (bias) согласно RFC 3492.
 */
function adapt(delta, numPoints, firstTime) {
  let d = firstTime ? Math.floor(delta / DAMP) : delta >> 1
  d += Math.floor(d / numPoints)
  let k = 0
  while (d > ((BASE - TMIN) * TMAX) >> 1) {
    d = Math.floor(d / (BASE - TMIN))
    k += BASE
  }
  return k + Math.floor(((BASE - TMIN + 1) * d) / (d + SKEW))
}

/**
 * Отображает значение цифры (0..35) в символ ASCII.
 */
function digitToChar(digit) {
  // 0..25 → a..z, 26..35 → 0..9
  return String.fromCharCode(digit + 22 + (digit < 26 ? 75 : 0))
}

/**
 * Отображает символ ASCII в значение цифры (0..35) либо бросает ошибку.
 */
function charToDigit(code) {
  if (code - 48 < 10) return code - 22 // 0..9 → 26..35
  if (code - 65 < 26) return code - 65 // A..Z → 0..25
  if (code - 97 < 26) return code - 97 // a..z → 0..25
  throw new Error('punycode')
}

/**
 * Кодирует строку Unicode в bootstring (без префикса).
 */
function punycodeEncode(input) {
  const codePoints = Array.from(input, (ch) => ch.codePointAt(0))
  const output = []

  // Базовые (ASCII) code points сразу в вывод.
  for (const cp of codePoints) {
    if (cp < 0x80) output.push(String.fromCharCode(cp))
  }

  const basicLength = output.length
  let handled = basicLength
  if (basicLength > 0) output.push(DELIMITER)

  let n = INITIAL_N
  let delta = 0
  let bias = INITIAL_BIAS

  while (handled < codePoints.length) {
    // Наименьший code point >= n среди оставшихся.
    let m = MAX_INT
    for (const cp of codePoints) {
      if (cp >= n && cp < m) m = cp
    }

    if (m - n > Math.floor((MAX_INT - delta) / (handled + 1))) {
      throw new Error('punycode')
    }
    delta += (m - n) * (handled + 1)
    n = m

    for (const cp of codePoints) {
      if (cp < n && ++delta > MAX_INT) throw new Error('punycode')
      if (cp === n) {
        let q = delta
        for (let k = BASE; ; k += BASE) {
          const t = k <= bias ? TMIN : k >= bias + TMAX ? TMAX : k - bias
          if (q < t) break
          const qMinusT = q - t
          const baseMinusT = BASE - t
          output.push(digitToChar(t + (qMinusT % baseMinusT)))
          q = Math.floor(qMinusT / baseMinusT)
        }
        output.push(digitToChar(q))
        bias = adapt(delta, handled + 1, handled === basicLength)
        delta = 0
        handled++
      }
    }

    delta++
    n++
  }

  return output.join('')
}

/**
 * Декодирует bootstring (без префикса) в строку Unicode.
 */
function punycodeDecode(input) {
  const output = []
  const lastDelimiter = input.lastIndexOf(DELIMITER)
  const basicLength = lastDelimiter < 0 ? 0 : lastDelimiter

  // Базовая часть — только ASCII.
  for (let j = 0; j < basicLength; j++) {
    const code = input.charCodeAt(j)
    if (code >= 0x80) throw new Error('punycode')
    output.push(code)
  }

  let n = INITIAL_N
  let bias = INITIAL_BIAS
  let i = 0
  let index = basicLength > 0 ? basicLength + 1 : 0

  while (index < input.length) {
    const oldi = i
    let w = 1
    for (let k = BASE; ; k += BASE) {
      if (index >= input.length) throw new Error('punycode')
      const digit = charToDigit(input.charCodeAt(index++))
      if (digit > Math.floor((MAX_INT - i) / w)) throw new Error('punycode')
      i += digit * w
      const t = k <= bias ? TMIN : k >= bias + TMAX ? TMAX : k - bias
      if (digit < t) break
      const baseMinusT = BASE - t
      if (w > Math.floor(MAX_INT / baseMinusT)) throw new Error('punycode')
      w *= baseMinusT
    }

    const outLength = output.length + 1
    bias = adapt(i - oldi, outLength, oldi === 0)

    if (Math.floor(i / outLength) > MAX_INT - n) throw new Error('punycode')
    n += Math.floor(i / outLength)
    i %= outLength

    output.splice(i, 0, n)
    i++
  }

  return String.fromCodePoint(...output)
}
