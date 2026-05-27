/**
 * Преобразование для инструмента Unicode.
 */
export function transformUnicode(value, mode) {
  return mode === 'encode' ? textToUnicodeEscapes(value) : unicodeEscapesToText(value)
}

/**
 * Эвристика определения Unicode-эскейпов.
 */
export function looksLikeUnicode(value) {
  return /\\u[0-9a-fA-F]{4}|U\+[0-9a-fA-F]{4,6}|&#x?[0-9a-fA-F]+;/.test(value)
}

function textToUnicodeEscapes(value) {
  let result = ''
  for (let i = 0; i < value.length; i += 1) {
    result += `\\u${value.charCodeAt(i).toString(16).padStart(4, '0')}`
  }
  return result
}

function unicodeEscapesToText(value) {
  const inputText = value || ''
  if (!inputText.trim()) throw new Error('unicode')
  return inputText
    .replace(/\\u\{([0-9a-fA-F]{1,6})\}/g, (_, hex) => String.fromCodePoint(parseInt(hex, 16)))
    .replace(/U\+([0-9a-fA-F]{4,6})/g, (_, hex) => String.fromCodePoint(parseInt(hex, 16)))
    .replace(/\\u([0-9a-fA-F]{4})/g, (_, hex) => String.fromCharCode(parseInt(hex, 16)))
    .replace(/&#(x?[0-9a-fA-F]+);/g, (_, code) => {
      const cp = /^x/i.test(code) ? parseInt(code.slice(1), 16) : parseInt(code, 10)
      return String.fromCodePoint(cp)
    })
}
