/**
 * Форматирование и минификация JSON.
 */
export function transformJson(value, mode, options = {}) {
  const parsed = JSON.parse(value)

  if (mode === 'encode') {
    const indent = options.indent === 'tab' ? '\t' : Number(options.indent || 2)
    return JSON.stringify(parsed, null, indent)
  }
  return JSON.stringify(parsed)
}

/**
 * Всегда возвращает false: для JSON-форматтера нет понятия "выглядит как закодированный вывод".
 * Направление определяется явным data-direction на чипе, а не авто-детектом.
 */
export function looksLikeJson(_value) {
  return false
}
