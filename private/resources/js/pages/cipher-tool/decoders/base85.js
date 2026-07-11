/**
 * Алфавит Z85 (ZeroMQ RFC 32/Z85).
 */
const Z85 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.-:+=^!/*?&<>()[]{}@%$#'

/**
 * Преобразование для инструмента Base85 / Ascii85.
 *
 * variant='ascii85' (по умолчанию) — Adobe Ascii85 с рамками <~ ~> и сжатием 'z'.
 * variant='z85'     — ZeroMQ Z85 (длина входа кратна 4 байтам).
 */
export function transformBase85(value, mode, opts) {
  const variant = opts?.variant === 'z85' ? 'z85' : 'ascii85'
  if (variant === 'z85') {
    return mode === 'encode'
      ? encodeZ85(new TextEncoder().encode(value))
      : new TextDecoder().decode(decodeZ85(value))
  }
  return mode === 'encode'
    ? encodeAscii85(new TextEncoder().encode(value))
    : new TextDecoder().decode(decodeAscii85(value))
}

/**
 * Эвристика определения закодированного значения Base85.
 *
 * Признаётся только Adobe-обрамление <~ … ~>: без рамок алфавит Base85 покрывает
 * почти любой печатный ASCII, поэтому обычный текст ошибочно считался бы кодировкой
 * и пример «Hello, World!» декодировался бы в мусор вместо кодирования.
 */
export function looksLikeBase85(value) {
  return /^<~[\s\S]*~>$/.test(value.trim())
}

/* ---------- Adobe Ascii85 ---------- */

function encodeAscii85(bytes) {
  let out = '<~'
  for (let i = 0; i < bytes.length; i += 4) {
    const chunk = bytes.subarray(i, i + 4)
    const count = chunk.length
    let num = 0
    for (let j = 0; j < 4; j++) {
      num = (num * 256 + (j < count ? chunk[j] : 0)) >>> 0
    }
    if (count === 4 && num === 0) {
      out += 'z'
      continue
    }
    const group = []
    let n = num
    for (let j = 0; j < 5; j++) {
      group.unshift(n % 85)
      n = Math.floor(n / 85)
    }
    for (let j = 0; j < count + 1; j++) {
      out += String.fromCharCode(group[j] + 33)
    }
  }
  return out + '~>'
}

function decodeAscii85(value) {
  let clean = value.trim().replace(/\s+/g, '')
  if (clean.startsWith('<~')) clean = clean.slice(2)
  if (clean.endsWith('~>')) clean = clean.slice(0, -2)
  if (!clean) throw new Error('base85')

  const out = []
  let group = []
  for (const ch of clean) {
    if (ch === 'z') {
      if (group.length !== 0) throw new Error('base85')
      out.push(0, 0, 0, 0)
      continue
    }
    const code = ch.charCodeAt(0)
    if (code < 33 || code > 117) throw new Error('base85')
    group.push(code - 33)
    if (group.length === 5) {
      appendAscii85Group(out, group, 5)
      group = []
    }
  }
  if (group.length > 0) {
    if (group.length === 1) throw new Error('base85')
    const count = group.length
    while (group.length < 5) group.push(84)
    appendAscii85Group(out, group, count)
  }
  return new Uint8Array(out)
}

function appendAscii85Group(out, group, count) {
  let num = 0
  for (let j = 0; j < 5; j++) num = num * 85 + group[j]
  num = num >>> 0
  const bytes = [(num >>> 24) & 0xff, (num >>> 16) & 0xff, (num >>> 8) & 0xff, num & 0xff]
  for (let j = 0; j < count - 1; j++) out.push(bytes[j])
}

/* ---------- Z85 ---------- */

function encodeZ85(bytes) {
  if (bytes.length % 4 !== 0) throw new Error('z85-length')
  let out = ''
  for (let i = 0; i < bytes.length; i += 4) {
    let num = 0
    for (let j = 0; j < 4; j++) num = (num * 256 + bytes[i + j]) >>> 0
    const chars = []
    for (let j = 0; j < 5; j++) {
      chars.unshift(Z85[num % 85])
      num = Math.floor(num / 85)
    }
    out += chars.join('')
  }
  return out
}

function decodeZ85(value) {
  const clean = value.replace(/\s+/g, '')
  if (!clean) throw new Error('z85')
  if (clean.length % 5 !== 0) throw new Error('z85-length')
  const out = []
  for (let i = 0; i < clean.length; i += 5) {
    let num = 0
    for (let j = 0; j < 5; j++) {
      const idx = Z85.indexOf(clean[i + j])
      if (idx === -1) throw new Error('z85')
      num = num * 85 + idx
    }
    if (num > 0xffffffff) throw new Error('z85')
    out.push((num >>> 24) & 0xff, (num >>> 16) & 0xff, (num >>> 8) & 0xff, num & 0xff)
  }
  return new Uint8Array(out)
}
