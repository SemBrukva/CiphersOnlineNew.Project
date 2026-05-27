/**
 * Преобразование для инструмента JWT Decoder.
 */
export function transformJwt(value, mode) {
  if (mode === 'decode') return ''
  return decodeJwtSummary(value)
}

/**
 * Эвристика определения JWT-токена.
 */
export function looksLikeJwt(value) {
  return value.split('.').length === 3
}

function decodeJwtSummary(token) {
  const parts = (token || '').trim().split('.')
  if (parts.length !== 3) throw new Error('jwt')

  const decodePart = (part) => {
    const normalized = part.replace(/-/g, '+').replace(/_/g, '/')
    const padding = '='.repeat((4 - (normalized.length % 4)) % 4)
    const raw = atob(normalized + padding)
    const bytes = Uint8Array.from(raw, (char) => char.charCodeAt(0))
    return new TextDecoder().decode(bytes)
  }

  const header = JSON.stringify(JSON.parse(decodePart(parts[0])), null, 2)
  const payload = JSON.stringify(JSON.parse(decodePart(parts[1])), null, 2)
  return `Header:\n${header}\n\nPayload:\n${payload}`
}
