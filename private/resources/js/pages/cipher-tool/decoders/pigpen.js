/**
 * Алфавиты шифра масонов (Pigpen) и его вариантов.
 *
 * Каждая буква кодируется дескриптором символа:
 * - { k: 'g', s: <0..8>, d: <'none'|'c'|'l'|'r'> } — ячейка решётки «крестики-нолики»
 *   с набором граней по индексу s и точкой d (нет / центр / слева / справа).
 * - { k: 'x', s: <'u'|'d'|'l'|'r'>, d: <'none'|'c'> } — клин «икса» (вверх/вниз/влево/вправо)
 *   с точкой d (нет / в центре клина).
 *
 * @typedef {{ k: 'g'|'x', s: number|string, d: string }} PigpenGlyph
 */

const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'

/** Ориентации клиньев «икса» в порядке заполнения. */
const X_ORDER = ['u', 'l', 'r', 'd']

/**
 * Стандартный (масонский) вариант: A–I решётка, J–R решётка с точкой,
 * S–V «икс», W–Z «икс» с точкой.
 *
 * @returns {Record<string, PigpenGlyph>}
 */
function buildStandard() {
  const map = {}
  for (let i = 0; i < 9; i++) map[LETTERS[i]] = { k: 'g', s: i, d: 'none' }
  for (let i = 0; i < 9; i++) map[LETTERS[9 + i]] = { k: 'g', s: i, d: 'c' }
  for (let i = 0; i < 4; i++) map[LETTERS[18 + i]] = { k: 'x', s: X_ORDER[i], d: 'none' }
  for (let i = 0; i < 4; i++) map[LETTERS[22 + i]] = { k: 'x', s: X_ORDER[i], d: 'c' }
  return map
}

/**
 * Чередующийся вариант: A–I решётка, J–M «икс», N–V решётка с точкой,
 * W–Z «икс» с точкой.
 *
 * @returns {Record<string, PigpenGlyph>}
 */
function buildVariant() {
  const map = {}
  for (let i = 0; i < 9; i++) map[LETTERS[i]] = { k: 'g', s: i, d: 'none' }
  for (let i = 0; i < 4; i++) map[LETTERS[9 + i]] = { k: 'x', s: X_ORDER[i], d: 'none' }
  for (let i = 0; i < 9; i++) map[LETTERS[13 + i]] = { k: 'g', s: i, d: 'c' }
  for (let i = 0; i < 4; i++) map[LETTERS[22 + i]] = { k: 'x', s: X_ORDER[i], d: 'c' }
  return map
}

/**
 * Розенкрейцерский вариант: одна решётка, по три буквы в ячейке,
 * положение точки (слева / центр / справа) задаёт порядковый номер буквы.
 *
 * @returns {Record<string, PigpenGlyph>}
 */
function buildRosicrucian() {
  const map = {}
  const dots = ['l', 'c', 'r']
  for (let i = 0; i < 26; i++) {
    map[LETTERS[i]] = { k: 'g', s: Math.floor(i / 3), d: dots[i % 3] }
  }
  return map
}

/** @type {Record<string, Record<string, PigpenGlyph>>} */
const VARIANTS = {
  standard: buildStandard(),
  variant: buildVariant(),
  rosicrucian: buildRosicrucian(),
}

/**
 * Возвращает таблицу соответствия букв символам для указанного варианта.
 *
 * @param {string} variant
 * @returns {Record<string, PigpenGlyph>}
 */
export function alphabetForVariant(variant) {
  return VARIANTS[variant] ?? VARIANTS.standard
}

/**
 * Заглушка преобразования — нужна только для совместимости с реестром декодеров.
 * Реальный рендеринг выполняется модулем pigpen.js.
 *
 * @param {string} value
 * @param {'encode'|'decode'} mode
 * @param {Record<string, unknown>} [opts]
 * @returns {string}
 */
export function transformPigpen(value, mode, opts) {
  if (mode !== 'encode') return value
  const map = alphabetForVariant(String(opts?.variant || 'standard'))
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
export function looksLikePigpen() {
  return false
}
