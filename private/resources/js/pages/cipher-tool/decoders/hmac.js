/**
 * Карта slug → SubtleCrypto hash для HMAC.
 */
const HMAC_HASH = {
  'hmac-sha-1':   'SHA-1',
  'hmac-sha-256': 'SHA-256',
  'hmac-sha-384': 'SHA-384',
  'hmac-sha-512': 'SHA-512',
}

/**
 * Преобразует ArrayBuffer в hex-строку нижнего регистра.
 */
function bufferToHex(buffer) {
  const bytes = new Uint8Array(buffer)
  let hex = ''
  for (let i = 0; i < bytes.length; i++) {
    hex += bytes[i].toString(16).padStart(2, '0')
  }
  return hex
}

/**
 * Декодирует hex-строку в Uint8Array. Игнорирует пробелы.
 * Возвращает null при ошибке формата.
 */
function hexToBytes(hex) {
  const clean = hex.replace(/\s+/g, '')
  if (clean.length % 2 !== 0 || !/^[0-9a-fA-F]*$/.test(clean)) {
    return null
  }
  const bytes = new Uint8Array(clean.length / 2)
  for (let i = 0; i < clean.length; i += 2) {
    bytes[i / 2] = parseInt(clean.substr(i, 2), 16)
  }
  return bytes
}

/**
 * Декодирует base64-строку в Uint8Array. Возвращает null при ошибке.
 */
function base64ToBytes(base64) {
  try {
    const binary = atob(base64.replace(/\s+/g, ''))
    const bytes = new Uint8Array(binary.length)
    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i)
    }
    return bytes
  } catch {
    return null
  }
}

/**
 * Готовит ключ из строки в зависимости от формата (text/hex/base64).
 */
function prepareKey(key, format) {
  if (format === 'hex') {
    const bytes = hexToBytes(key)
    if (bytes === null) {
      throw new Error('Invalid hex key')
    }
    return bytes
  }
  if (format === 'base64') {
    const bytes = base64ToBytes(key)
    if (bytes === null) {
      throw new Error('Invalid base64 key')
    }
    return bytes
  }
  return new TextEncoder().encode(key)
}

/**
 * Вычисляет HMAC через SubtleCrypto. Возвращает Promise со строкой hex.
 * mode игнорируется — HMAC одностороннее.
 */
export function transformHmac(value, _mode, options = {}) {
  const algoSlug = String(options.algorithm || 'hmac-sha-256').toLowerCase()
  const hashName = HMAC_HASH[algoSlug]
  if (!hashName) {
    return Promise.reject(new Error(`Unsupported HMAC algorithm: ${algoSlug}`))
  }

  const keyText = String(options.key ?? '')
  const keyFormat = String(options.keyFormat || 'text').toLowerCase()

  let keyBytes
  try {
    keyBytes = prepareKey(keyText, keyFormat)
  } catch (err) {
    return Promise.reject(err)
  }

  const data = new TextEncoder().encode(String(value ?? ''))

  return crypto.subtle.importKey(
    'raw',
    keyBytes,
    { name: 'HMAC', hash: { name: hashName } },
    false,
    ['sign']
  ).then((cryptoKey) => crypto.subtle.sign('HMAC', cryptoKey, data))
   .then(bufferToHex)
}

/**
 * HMAC — односторонний MAC, авто-определение направления не имеет смысла.
 */
export function looksLikeHmac(_value) {
  return false
}
