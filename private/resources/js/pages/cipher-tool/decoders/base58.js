/**
 * Алфавит Base58 (Bitcoin): без 0, O, I, l.
 */
const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz'
const BASE = 58n

/**
 * Преобразование для инструмента Base58.
 *
 * variant='base58check' добавляет version-байт и 4-байтовый контрольный код
 * (двойной SHA-256) — формат крипто-адресов. Возвращает Promise, т.к. хеш
 * считается асинхронно через crypto.subtle.
 */
export function transformBase58(value, mode, opts) {
  const check = opts?.variant === 'base58check'
  if (check) {
    return mode === 'encode' ? encodeCheck(value) : decodeCheck(value)
  }
  return mode === 'encode'
    ? encodeBase58(new TextEncoder().encode(value))
    : new TextDecoder().decode(decodeBase58(value))
}

/**
 * Эвристика определения закодированного значения Base58.
 */
export function looksLikeBase58(value) {
  const clean = value.replace(/\s+/g, '')
  return clean.length > 0 && !/[0OIl+/=]/.test(clean) && /^[1-9A-HJ-NP-Za-km-z]+$/.test(clean)
}

/**
 * Кодирует байты в строку Base58.
 */
function encodeBase58(bytes) {
  if (bytes.length === 0) return ''
  let num = bytesToBigInt(bytes)
  let out = ''
  while (num > 0n) {
    const rem = num % BASE
    num = num / BASE
    out = ALPHABET[Number(rem)] + out
  }
  // Ведущие нулевые байты → символы '1'.
  for (const byte of bytes) {
    if (byte === 0) out = '1' + out
    else break
  }
  return out
}

/**
 * Декодирует строку Base58 в байты.
 */
function decodeBase58(value) {
  const clean = value.replace(/\s+/g, '')
  if (!clean) throw new Error('base58')
  let num = 0n
  for (const ch of clean) {
    const idx = ALPHABET.indexOf(ch)
    if (idx === -1) throw new Error('base58')
    num = num * BASE + BigInt(idx)
  }
  let bytes = bigIntToBytes(num)
  // Восстанавливаем ведущие нулевые байты из префиксных '1'.
  let leadingZeros = 0
  for (const ch of clean) {
    if (ch === '1') leadingZeros++
    else break
  }
  if (leadingZeros > 0) {
    const prefix = new Uint8Array(leadingZeros)
    const merged = new Uint8Array(leadingZeros + bytes.length)
    merged.set(prefix, 0)
    merged.set(bytes, leadingZeros)
    bytes = merged
  }
  return bytes
}

/**
 * Кодирует текст как Base58Check: version(0x00) + payload + checksum(4).
 */
async function encodeCheck(value) {
  const payload = new TextEncoder().encode(value)
  const versioned = new Uint8Array(1 + payload.length)
  versioned[0] = 0x00
  versioned.set(payload, 1)
  const checksum = (await doubleSha256(versioned)).slice(0, 4)
  const full = new Uint8Array(versioned.length + 4)
  full.set(versioned, 0)
  full.set(checksum, versioned.length)
  return encodeBase58(full)
}

/**
 * Декодирует Base58Check, проверяет контрольную сумму, возвращает payload как текст.
 */
async function decodeCheck(value) {
  const full = decodeBase58(value)
  if (full.length < 5) throw new Error('base58check')
  const body = full.slice(0, full.length - 4)
  const checksum = full.slice(full.length - 4)
  const expected = (await doubleSha256(body)).slice(0, 4)
  for (let i = 0; i < 4; i++) {
    if (checksum[i] !== expected[i]) throw new Error('base58check-checksum')
  }
  return new TextDecoder().decode(body.slice(1))
}

/**
 * Двойной SHA-256 через Web Crypto.
 */
async function doubleSha256(bytes) {
  const first = await crypto.subtle.digest('SHA-256', bytes)
  const second = await crypto.subtle.digest('SHA-256', first)
  return new Uint8Array(second)
}

function bytesToBigInt(bytes) {
  let num = 0n
  for (const byte of bytes) num = (num << 8n) | BigInt(byte)
  return num
}

function bigIntToBytes(num) {
  if (num === 0n) return new Uint8Array(0)
  const out = []
  while (num > 0n) {
    out.unshift(Number(num & 0xffn))
    num >>= 8n
  }
  return new Uint8Array(out)
}
