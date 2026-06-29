import { argon2id, argon2i, argon2d, argon2Verify } from 'hash-wasm'
import { parseIntInRange, randomBytes, verifyResult } from './kdf-util.js'

const VARIANTS = {
  argon2id,
  argon2i,
  argon2d,
}

/**
 * Argon2 transform.
 * mode='encode' → возвращает encoded PHC-строку $argon2id$v=19$m=...$...$...
 * mode='decode' → сравнивает password с opts.verifyHash через argon2Verify.
 */
export function transformArgon2(value, mode, opts = {}) {
  const password = String(value ?? '')
  const params = opts?.kdfParams || {}
  const variant = VARIANTS[params.variant] ? params.variant : 'argon2id'
  const memorySize = parseIntInRange(params.memory, 19456, 8, 1024 * 1024)
  const iterations = parseIntInRange(params.iterations, 2, 1, 100)
  const parallelism = parseIntInRange(params.parallelism, 1, 1, 16)
  const hashLength = parseIntInRange(params.keyLength, 32, 4, 1024)

  if (mode === 'decode') {
    const target = String(opts.verifyHash || '').trim()
    if (!target) {
      return Promise.reject(new Error('Hash to verify is required'))
    }
    return argon2Verify({ password, hash: target }).then((ok) => verifyResult(ok))
  }

  const saltText = String(params.salt || '')
  const salt = saltText !== ''
    ? new TextEncoder().encode(saltText)
    : randomBytes(16)

  return VARIANTS[variant]({
    password,
    salt,
    parallelism,
    iterations,
    memorySize,
    hashLength,
    outputType: 'encoded',
  })
}

/**
 * Эвристика — Argon2 PHC encoded hash начинается с $argon2{id|i|d}$.
 */
export function looksLikeArgon2(value) {
  return /^\$argon2(id|i|d)\$/.test(String(value || ''))
}
