/**
 * Преобразование для инструмента HTML encode/decode.
 */
export function transformHtml(value, mode) {
  if (mode === 'encode') {
    return value
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;')
  }
  const el = document.createElement('textarea')
  el.innerHTML = value
  return el.value
}

/**
 * Эвристика определения HTML-закодированного значения.
 */
export function looksLikeHtml(value) {
  return /&(?:[a-z]{2,8}|#[0-9]{1,6}|#x[0-9a-fA-F]{1,5});/.test(value)
}
