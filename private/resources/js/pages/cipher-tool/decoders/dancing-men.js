/**
 * Алфавиты шифра «Танцующие человечки».
 *
 * Каждая буква кодируется тройкой [левая рука, правая рука, ноги]:
 * - рука: 0 = вниз, 1 = горизонтально, 2 = вверх
 * - ноги: 0 = вместе, 1 = левая в сторону, 2 = правая в сторону, 3 = обе в стороны
 *
 * @type {Record<string, [number, number, number]>}
 */
export const ALPHABET_EN = {
  A: [0, 0, 0], B: [0, 0, 1], C: [0, 0, 2], D: [0, 0, 3],
  E: [0, 1, 0], F: [0, 1, 1], G: [0, 1, 2], H: [0, 1, 3],
  I: [0, 2, 0], J: [0, 2, 1], K: [0, 2, 2], L: [0, 2, 3],
  M: [1, 0, 0], N: [1, 0, 1], O: [1, 0, 2], P: [1, 0, 3],
  Q: [1, 1, 0], R: [1, 1, 1], S: [1, 1, 2], T: [1, 1, 3],
  U: [1, 2, 0], V: [1, 2, 1], W: [1, 2, 2], X: [1, 2, 3],
  Y: [2, 0, 0], Z: [2, 0, 1],
}

/**
 * Адаптация для русского алфавита (33 буквы).
 * @type {Record<string, [number, number, number]>}
 */
export const ALPHABET_RU = {
  А: [0, 0, 0], Б: [0, 0, 1], В: [0, 0, 2], Г: [0, 0, 3],
  Д: [0, 1, 0], Е: [0, 1, 1], Ё: [0, 1, 2], Ж: [0, 1, 3],
  З: [0, 2, 0], И: [0, 2, 1], Й: [0, 2, 2], К: [0, 2, 3],
  Л: [1, 0, 0], М: [1, 0, 1], Н: [1, 0, 2], О: [1, 0, 3],
  П: [1, 1, 0], Р: [1, 1, 1], С: [1, 1, 2], Т: [1, 1, 3],
  У: [1, 2, 0], Ф: [1, 2, 1], Х: [1, 2, 2], Ц: [1, 2, 3],
  Ч: [2, 0, 0], Ш: [2, 0, 1], Щ: [2, 0, 2], Ъ: [2, 0, 3],
  Ы: [2, 1, 0], Ь: [2, 1, 1], Э: [2, 1, 2], Ю: [2, 1, 3],
  Я: [2, 2, 0],
}

/** @type {Record<string, Record<string, [number, number, number]>>} */
const MAPS = { en: ALPHABET_EN, ru: ALPHABET_RU }

/**
 * Возвращает алфавит для указанного языка.
 *
 * @param {string} lang
 * @returns {Record<string, [number, number, number]>}
 */
export function alphabetFor(lang) {
  return MAPS[lang] ?? ALPHABET_EN
}

/**
 * Заглушка преобразования — используется только для совместимости с реестром декодеров.
 * Реальный рендеринг выполняется модулем dancing-men.js.
 *
 * @param {string} value
 * @param {'encode'|'decode'} mode
 * @param {Record<string, unknown>} [opts]
 * @returns {string}
 */
export function transformDancingMen(value, mode, opts) {
  if (mode !== 'encode') return value
  const lang = String(opts?.language || 'en')
  const map = alphabetFor(lang)
  return value
    .toUpperCase()
    .split('')
    .map((ch) => (/\s/.test(ch) ? ' ' : (map[ch] ? ch : '?')))
    .join('')
}

/**
 * Эвристика: всегда возвращает false — автоопределение decode не нужно.
 *
 * @returns {boolean}
 */
export function looksLikeDancingMen() {
  return false
}
