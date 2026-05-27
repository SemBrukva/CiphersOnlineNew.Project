/**
 * Преобразование для инструмента URL encode/decode.
 */
export function transformUrl(value, mode) {
  return mode === 'encode' ? encodeURIComponent(value) : decodeURIComponent(value)
}

/**
 * Эвристика определения закодированного URL-значения.
 */
export function looksLikeUrl(value) {
  return /%[0-9A-Fa-f]{2}/.test(value) || value.includes('+')
}
