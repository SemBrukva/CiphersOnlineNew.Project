/**
 * Преобразование для инструмента ASCII.
 */
export function transformAscii(value, mode) {
  return mode === 'encode' ? textToAscii(value) : asciiToText(value)
}

/**
 * Эвристика определения ASCII-последовательности кодов.
 */
export function looksLikeAscii(value) {
  return /^\d+(?:\s+\d+)*$/.test(value)
}

function textToAscii(value) {
  return Array.from(value || '').map((char) => {
    const code = char.codePointAt(0)
    if (typeof code !== 'number' || code > 127) throw new Error('ascii')
    return String(code)
  }).join(' ')
}

function asciiToText(value) {
  const clean = (value || '').trim()
  if (!clean) throw new Error('ascii')
  return clean.split(/\s+/).map((part) => {
    if (!/^\d+$/.test(part)) throw new Error('ascii')
    const code = Number(part)
    if (code < 0 || code > 127) throw new Error('ascii')
    return String.fromCharCode(code)
  }).join('')
}
