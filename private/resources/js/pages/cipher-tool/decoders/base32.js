/**
 * Алфавиты вариантов Base32.
 * rfc4648  — RFC 4648, паддинг '='.
 * base32hex — RFC 4648 «Extended Hex», паддинг '='.
 * crockford — Crockford Base32, без паддинга, регистронезависимый.
 */
const ALPHABETS = {
  rfc4648: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',
  base32hex: '0123456789ABCDEFGHIJKLMNOPQRSTUV',
  crockford: '0123456789ABCDEFGHJKMNPQRSTVWXYZ',
}

/**
 * Преобразование для инструмента Base32.
 */
export function transformBase32(value, mode, opts) {
  const variant = ALPHABETS[opts?.variant] ? opts.variant : 'rfc4648'
  return mode === 'encode'
    ? encodeBase32(value, variant)
    : decodeBase32(value, variant)
}

/**
 * Эвристика определения закодированного значения Base32.
 */
export function looksLikeBase32(value) {
  return /^[A-Za-z0-9=]+$/.test(value.replace(/[\s-]+/g, '')) && /[A-Za-z]/.test(value)
}

function encodeBase32(value, variant) {
  const alphabet = ALPHABETS[variant]
  const usePadding = variant !== 'crockford'
  const bytes = new TextEncoder().encode(value)
  let bits = 0
  let buffer = 0
  let output = ''

  for (const byte of bytes) {
    buffer = (buffer << 8) | byte
    bits += 8
    while (bits >= 5) {
      bits -= 5
      output += alphabet[(buffer >>> bits) & 0x1f]
    }
  }
  if (bits > 0) {
    output += alphabet[(buffer << (5 - bits)) & 0x1f]
  }
  if (usePadding) {
    while (output.length % 8 !== 0) output += '='
  }
  return output
}

function decodeBase32(value, variant) {
  const alphabet = ALPHABETS[variant]
  let clean = value.replace(/[\s=-]+/g, '')
  if (variant === 'crockford') {
    // Crockford: регистронезависимый, I/L→1, O→0.
    clean = clean.toUpperCase().replace(/O/g, '0').replace(/[IL]/g, '1')
  } else {
    clean = clean.toUpperCase()
  }
  if (!clean) throw new Error('base32')

  const lookup = buildLookup(alphabet)
  let bits = 0
  let buffer = 0
  const out = []

  for (const ch of clean) {
    const idx = lookup[ch]
    if (idx === undefined) throw new Error('base32')
    buffer = (buffer << 5) | idx
    bits += 5
    if (bits >= 8) {
      bits -= 8
      out.push((buffer >>> bits) & 0xff)
    }
  }
  return new TextDecoder().decode(new Uint8Array(out))
}

function buildLookup(alphabet) {
  const map = {}
  for (let i = 0; i < alphabet.length; i++) map[alphabet[i]] = i
  return map
}
