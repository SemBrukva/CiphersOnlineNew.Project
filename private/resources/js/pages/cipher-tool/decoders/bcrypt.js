import bcrypt from 'bcryptjs'
import { parseIntInRange, verifyResult } from './kdf-util.js'

/**
 * bcrypt transform.
 * mode='encode' → генерирует bcrypt-хеш с указанным cost factor. Возвращает строку $2b$...
 * mode='decode' → сравнивает password с opts.verifyHash через bcrypt.compare.
 */
export function transformBcrypt(value, mode, opts = {}) {
  const password = String(value ?? '')
  const params = opts?.kdfParams || {}
  const cost = parseIntInRange(params.cost, 12, 4, 31)

  if (mode === 'decode') {
    const target = String(opts.verifyHash || '').trim()
    if (!target) {
      return Promise.reject(new Error('Hash to verify is required'))
    }
    return bcrypt.compare(password, target).then((ok) => verifyResult(ok))
  }

  return bcrypt.hash(password, cost)
}

/**
 * Эвристика — bcrypt hash начинается с $2a$, $2b$ или $2y$.
 */
export function looksLikeBcrypt(value) {
  return /^\$2[aby]\$\d{2}\$/.test(String(value || ''))
}
