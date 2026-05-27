/**
 * Преобразование для инструмента Hex.
 */
export function transformHex(value, mode) {
  return mode === 'encode' ? textToHex(value) : hexToText(value)
}

/**
 * Эвристика определения закодированного значения Hex.
 */
export function looksLikeHex(value) {
  return /^[0-9a-fA-F\s]+$/.test(value)
}

function textToHex(value) {
  const bytes = new TextEncoder().encode(value)
  return Array.from(bytes).map((b) => b.toString(16).padStart(2, '0')).join('')
}

function hexToText(value) {
  const clean = value.replace(/\s+/g, '').toLowerCase()
  if (!clean || clean.length % 2 !== 0 || /[^0-9a-f]/.test(clean)) throw new Error('hex')
  const bytes = new Uint8Array(clean.match(/.{1,2}/g).map((part) => parseInt(part, 16)))
  return new TextDecoder().decode(bytes)
}
