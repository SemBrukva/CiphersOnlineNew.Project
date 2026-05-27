/**
 * Преобразование для инструмента Binary.
 */
export function transformBinary(value, mode) {
  return mode === 'encode' ? textToBinary(value) : binaryToText(value)
}

/**
 * Эвристика определения закодированного значения Binary.
 */
export function looksLikeBinary(value) {
  return /^[01\s]+$/.test(value)
}

function textToBinary(value) {
  const bytes = new TextEncoder().encode(value)
  return Array.from(bytes).map((b) => b.toString(2).padStart(8, '0')).join(' ')
}

function binaryToText(value) {
  const clean = value.replace(/\s+/g, ' ').trim()
  if (!clean || /[^01\s]/.test(clean)) throw new Error('binary')
  const bytes = clean.split(' ').map((part) => {
    if (!part || part.length > 8 || /[^01]/.test(part)) throw new Error('binary')
    return parseInt(part.padStart(8, '0'), 2)
  })
  return new TextDecoder().decode(new Uint8Array(bytes))
}
