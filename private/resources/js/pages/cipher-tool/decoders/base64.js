/**
 * Преобразование для инструмента Base64.
 */
export function transformBase64(value, mode) {
  return mode === 'encode'
    ? btoa(unescape(encodeURIComponent(value)))
    : decodeURIComponent(escape(atob(value.replace(/\s+/g, ''))))
}

/**
 * Эвристика определения закодированного значения Base64.
 */
export function looksLikeBase64(value) {
  return /^[A-Za-z0-9+/]+={0,2}$/.test(value.replace(/\s+/g, ''))
}
