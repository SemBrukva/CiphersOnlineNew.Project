import { md5 } from './md5-impl.js'
import { crc32 } from './crc32-impl.js'
import { sha3_256, sha3_384, sha3_512 } from 'js-sha3'
import { blake2bHex, blake2sHex } from 'blakejs'

/**
 * Карта поддерживаемых SubtleCrypto-алгоритмов.
 * Ключ — slug из UI, значение — имя для crypto.subtle.digest().
 */
const SUBTLE_ALGORITHMS = {
  'sha-1':   'SHA-1',
  'sha-256': 'SHA-256',
  'sha-384': 'SHA-384',
  'sha-512': 'SHA-512',
}

/**
 * JS-реализации алгоритмов, которых нет в SubtleCrypto.
 * Каждый возвращает hex-строку.
 */
const SYNC_ALGORITHMS = {
  'md5':       (input) => md5(input),
  'crc32':     (input) => crc32(input),
  'sha3-256':  (input) => sha3_256(input),
  'sha3-384':  (input) => sha3_384(input),
  'sha3-512':  (input) => sha3_512(input),
  'blake2b':   (input) => blake2bHex(input),
  'blake2s':   (input) => blake2sHex(input),
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
 * Вычисление криптографического хеша. Возвращает Promise со строкой hex.
 * Поддерживает SubtleCrypto-алгоритмы (SHA-1/256/384/512) и JS-реализации
 * (MD5, SHA3-256/384/512, BLAKE2b, BLAKE2s).
 * mode игнорируется — хеширование одностороннее.
 */
export function transformHash(value, _mode, options = {}) {
  const algoSlug = String(options.algorithm || 'sha-256').toLowerCase()

  if (SYNC_ALGORITHMS[algoSlug]) {
    return Promise.resolve(SYNC_ALGORITHMS[algoSlug](String(value ?? '')))
  }

  const subtleName = SUBTLE_ALGORITHMS[algoSlug]
  if (!subtleName) {
    return Promise.reject(new Error(`Unsupported hash algorithm: ${algoSlug}`))
  }

  const data = new TextEncoder().encode(String(value ?? ''))
  return crypto.subtle.digest(subtleName, data).then(bufferToHex)
}

/**
 * Hash — односторонняя функция, авто-определение направления не имеет смысла.
 */
export function looksLikeHash(_value) {
  return false
}
