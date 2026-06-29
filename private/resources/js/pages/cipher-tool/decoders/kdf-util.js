/**
 * Общие утилиты для KDF-декодеров.
 */

/**
 * Преобразует ArrayBuffer в hex-строку нижнего регистра.
 */
export function bufferToHex(buffer) {
  const bytes = new Uint8Array(buffer)
  let hex = ''
  for (let i = 0; i < bytes.length; i++) {
    hex += bytes[i].toString(16).padStart(2, '0')
  }
  return hex
}

/**
 * Генерирует случайные байты криптографическим RNG.
 */
export function randomBytes(length) {
  const arr = new Uint8Array(length)
  crypto.getRandomValues(arr)
  return arr
}

/**
 * Парсит целое число из строки с дефолтом и нижней/верхней границей.
 */
export function parseIntInRange(value, def, min, max) {
  const n = parseInt(String(value ?? '').trim(), 10)
  if (!Number.isFinite(n)) return def
  return Math.min(Math.max(n, min), max)
}

/**
 * Возвращает true, если результат — успешный verify (например, "✓ Match").
 */
export function verifyResult(ok, t) {
  return ok ? (t?.match || '✓ Match') : (t?.noMatch || '✗ Does not match')
}
