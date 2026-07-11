/**
 * Алфавит Base45 (RFC 9285) — 45 символов.
 */
const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ $%*+-./:'

/**
 * Преобразование для инструмента Base45 (RFC 9285).
 */
export function transformBase45(value, mode) {
  return mode === 'encode'
    ? encodeBase45(new TextEncoder().encode(value))
    : new TextDecoder().decode(decodeBase45(value))
}

/**
 * Эвристика определения закодированного значения Base45.
 */
export function looksLikeBase45(value) {
  const clean = value.trim()
  if (!clean) return false
  for (const ch of clean) {
    if (ALPHABET.indexOf(ch) === -1) return false
  }
  return true
}

/**
 * Кодирует байты в строку Base45: пара байт → 3 символа, одиночный байт → 2.
 */
function encodeBase45(bytes) {
  let out = ''
  for (let i = 0; i < bytes.length; i += 2) {
    if (i + 1 < bytes.length) {
      const n = bytes[i] * 256 + bytes[i + 1]
      const c = n % 45
      const d = Math.floor(n / 45) % 45
      const e = Math.floor(n / 45 / 45)
      out += ALPHABET[c] + ALPHABET[d] + ALPHABET[e]
    } else {
      const n = bytes[i]
      const c = n % 45
      const d = Math.floor(n / 45)
      out += ALPHABET[c] + ALPHABET[d]
    }
  }
  return out
}

/**
 * Декодирует строку Base45: 3 символа → пара байт, 2 символа → одиночный байт.
 */
function decodeBase45(value) {
  const clean = value.replace(/\n/g, '')
  if (!clean) throw new Error('base45')
  if (clean.length % 3 === 1) throw new Error('base45-length')

  const out = []
  for (let i = 0; i < clean.length; i += 3) {
    if (i + 2 < clean.length) {
      const n = digit(clean[i]) + digit(clean[i + 1]) * 45 + digit(clean[i + 2]) * 45 * 45
      if (n > 0xffff) throw new Error('base45-range')
      out.push(Math.floor(n / 256), n % 256)
    } else {
      const n = digit(clean[i]) + digit(clean[i + 1]) * 45
      if (n > 0xff) throw new Error('base45-range')
      out.push(n)
    }
  }
  return new Uint8Array(out)
}

function digit(ch) {
  const idx = ALPHABET.indexOf(ch)
  if (idx === -1) throw new Error('base45')
  return idx
}
