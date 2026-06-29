import { bufferToHex, parseIntInRange, verifyResult } from './kdf-util.js'

/**
 * Поддерживаемые hash-функции для PBKDF2.
 */
const VALID_HASHES = ['SHA-1', 'SHA-256', 'SHA-384', 'SHA-512']

/**
 * Вычисляет PBKDF2-ключ через SubtleCrypto.
 * Возвращает hex-строку производного ключа.
 */
async function derivePbkdf2(password, salt, iterations, hash, keyLengthBytes) {
  const enc = new TextEncoder()
  const cryptoKey = await crypto.subtle.importKey(
    'raw',
    enc.encode(password),
    { name: 'PBKDF2' },
    false,
    ['deriveBits']
  )
  const bits = await crypto.subtle.deriveBits(
    { name: 'PBKDF2', salt: enc.encode(salt), iterations, hash },
    cryptoKey,
    keyLengthBytes * 8
  )
  return bufferToHex(bits)
}

/**
 * Парсит KDF-параметры из объекта opts.
 */
function parseParams(opts) {
  const params = opts?.kdfParams || {}
  const hash = VALID_HASHES.includes(params.hash) ? params.hash : 'SHA-256'
  const iterations = parseIntInRange(params.iterations, 600000, 1, 10_000_000)
  const keyLength = parseIntInRange(params.keyLength, 32, 1, 1024)
  const salt = String(params.salt || '')
  return { hash, iterations, keyLength, salt }
}

/**
 * PBKDF2 transform.
 * mode='encode' → возвращает hex-производный ключ.
 * mode='decode' → сравнивает с opts.verifyHash и возвращает Match/No Match.
 */
export function transformPbkdf2(value, mode, opts = {}) {
  const { hash, iterations, keyLength, salt } = parseParams(opts)
  const password = String(value ?? '')

  if (mode === 'decode') {
    const target = String(opts.verifyHash || '').trim().toLowerCase()
    if (!target) {
      return Promise.reject(new Error('Hash to verify is required'))
    }
    return derivePbkdf2(password, salt, iterations, hash, keyLength)
      .then((computed) => verifyResult(computed === target))
  }

  return derivePbkdf2(password, salt, iterations, hash, keyLength)
}

/**
 * Для PBKDF2 авто-определение направления не нужно.
 */
export function looksLikePbkdf2(_value) {
  return false
}
