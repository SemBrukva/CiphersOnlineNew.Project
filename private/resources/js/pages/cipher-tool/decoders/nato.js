/**
 * Декодер фонетического алфавита NATO и родственных spelling-алфавитов.
 * Кодирует текст в кодовые слова (Alfa, Bravo, …) и декодирует обратно.
 */

/**
 * Буквы фонетического алфавита NATO/ICAO (International Radiotelephony Spelling Alphabet).
 * @type {Record<string, string>}
 */
const NATO_LETTERS = {
  A: 'Alfa', B: 'Bravo', C: 'Charlie', D: 'Delta', E: 'Echo',
  F: 'Foxtrot', G: 'Golf', H: 'Hotel', I: 'India', J: 'Juliett',
  K: 'Kilo', L: 'Lima', M: 'Mike', N: 'November', O: 'Oscar',
  P: 'Papa', Q: 'Quebec', R: 'Romeo', S: 'Sierra', T: 'Tango',
  U: 'Uniform', V: 'Victor', W: 'Whiskey', X: 'X-ray', Y: 'Yankee',
  Z: 'Zulu',
}

/** Стандартные английские числительные (NATO/полиция). */
const NUMBERS_EN = {
  '0': 'Zero', '1': 'One', '2': 'Two', '3': 'Three', '4': 'Four',
  '5': 'Five', '6': 'Six', '7': 'Seven', '8': 'Eight', '9': 'Nine',
}

/** Авиационные числительные ICAO (radiotelephony): Tree, Fower, Fife, Niner. */
const NUMBERS_AVIATION = {
  '0': 'Zero', '1': 'One', '2': 'Two', '3': 'Tree', '4': 'Fower',
  '5': 'Fife', '6': 'Six', '7': 'Seven', '8': 'Eight', '9': 'Niner',
}

/** Немецкие числительные (DIN 5009). */
const NUMBERS_DE = {
  '0': 'Null', '1': 'Eins', '2': 'Zwei', '3': 'Drei', '4': 'Vier',
  '5': 'Fünf', '6': 'Sechs', '7': 'Sieben', '8': 'Acht', '9': 'Neun',
}

/**
 * Фонетический алфавит полиции США (LAPD / APCO старый вариант).
 * @type {Record<string, string>}
 */
const POLICE_LETTERS = {
  A: 'Adam', B: 'Boy', C: 'Charles', D: 'David', E: 'Edward',
  F: 'Frank', G: 'George', H: 'Henry', I: 'Ida', J: 'John',
  K: 'King', L: 'Lincoln', M: 'Mary', N: 'Nora', O: 'Ocean',
  P: 'Paul', Q: 'Queen', R: 'Robert', S: 'Sam', T: 'Tom',
  U: 'Union', V: 'Victor', W: 'William', X: 'X-ray', Y: 'Young',
  Z: 'Zebra',
}

/**
 * Немецкая таблица для передачи по буквам (DIN 5009, классическая).
 * @type {Record<string, string>}
 */
const GERMAN_LETTERS = {
  A: 'Anton', 'Ä': 'Ärger', B: 'Berta', C: 'Cäsar', D: 'Dora',
  E: 'Emil', F: 'Friedrich', G: 'Gustav', H: 'Heinrich', I: 'Ida',
  J: 'Julius', K: 'Kaufmann', L: 'Ludwig', M: 'Martha', N: 'Nordpol',
  O: 'Otto', 'Ö': 'Ökonom', P: 'Paula', Q: 'Quelle', R: 'Richard',
  S: 'Samuel', 'ß': 'Eszett', T: 'Theodor', U: 'Ulrich', 'Ü': 'Übermut',
  V: 'Viktor', W: 'Wilhelm', X: 'Xanthippe', Y: 'Ypsilon', Z: 'Zacharias',
}

/**
 * Определения вариантов: буквенная таблица + числительные.
 * @type {Record<string, {letters: Record<string,string>, numbers: Record<string,string>}>}
 */
const VARIANTS = {
  nato:     { letters: NATO_LETTERS,   numbers: NUMBERS_EN },
  aviation: { letters: NATO_LETTERS,   numbers: NUMBERS_AVIATION },
  police:   { letters: POLICE_LETTERS, numbers: NUMBERS_EN },
  german:   { letters: GERMAN_LETTERS, numbers: NUMBERS_DE },
}

/**
 * Возвращает определение варианта (по умолчанию — NATO).
 *
 * @param {string} variant
 * @returns {{letters: Record<string,string>, numbers: Record<string,string>}}
 */
function variantDef(variant) {
  return VARIANTS[variant] ?? VARIANTS.nato
}

/** Разделители кодовых слов внутри слова. */
const SEPARATORS = { space: ' ', hyphen: ' - ', comma: ', ', newline: '\n' }

/** Граница между исходными словами. */
const WORD_GAPS = { space: ' / ', hyphen: ' / ', comma: ' / ', newline: '\n/\n' }

/**
 * Возвращает кодовое слово для одного символа или null, если символ не поддержан.
 *
 * @param {string} ch    Один символ исходного текста.
 * @param {{letters: Record<string,string>, numbers: Record<string,string>}} def
 * @returns {string|null}
 */
function wordFor(ch, def) {
  if (ch === 'ß') return def.letters['ß'] ?? null
  const up = ch.toUpperCase()
  if (Object.prototype.hasOwnProperty.call(def.letters, up)) return def.letters[up]
  if (Object.prototype.hasOwnProperty.call(def.numbers, ch)) return def.numbers[ch]
  return null
}

/**
 * Кодирует текст в фонетический алфавит.
 *
 * @param {string} text
 * @param {{variant?: string, separator?: string, showLetter?: boolean}} opts
 * @returns {string}
 */
function encodeNato(text, opts) {
  const def = variantDef(opts.variant)
  const sep = SEPARATORS[opts.separator] ?? SEPARATORS.space
  const gap = WORD_GAPS[opts.separator] ?? WORD_GAPS.space
  const showLetter = Boolean(opts.showLetter)

  return text
    .trim()
    .split(/\s+/)
    .filter(Boolean)
    .map((word) =>
      [...word]
        .map((ch) => {
          const w = wordFor(ch, def)
          if (w === null) return null
          return showLetter ? `${ch.toUpperCase()} = ${w}` : w
        })
        .filter((w) => w !== null)
        .join(sep)
    )
    .filter(Boolean)
    .join(gap)
}

/** @type {Map<string, Record<string, string>>} */
const reverseCache = new Map()

/**
 * Строит и кэширует обратную карту (кодовое слово в нижнем регистре в символ)
 * для варианта. Всегда включает стандартный алфавит NATO и распространённые
 * альтернативные написания, чтобы декодирование было устойчивым.
 *
 * @param {string} variant
 * @returns {Record<string, string>}
 */
function reverseFor(variant) {
  if (reverseCache.has(variant)) return reverseCache.get(variant)

  const reverse = {}
  const add = (word, ch) => {
    const key = word.toLowerCase()
    if (!Object.prototype.hasOwnProperty.call(reverse, key)) reverse[key] = ch
  }

  // Приоритет — таблице выбранного варианта.
  const def = variantDef(variant)
  for (const [ch, word] of Object.entries(def.letters)) add(word, ch)
  for (const [digit, word] of Object.entries(def.numbers)) add(word, digit)

  // Затем — стандартный NATO как надёжный фолбэк.
  for (const [ch, word] of Object.entries(NATO_LETTERS)) add(word, ch)
  for (const [digit, word] of Object.entries(NUMBERS_EN)) add(word, digit)
  for (const [digit, word] of Object.entries(NUMBERS_AVIATION)) add(word, digit)

  // Распространённые альтернативные написания.
  const aliases = {
    alpha: 'A', juliet: 'J', xray: 'X', 'x ray': 'X', whisky: 'W',
    niner: '9', fife: '5', fower: '4', tree: '3',
  }
  for (const [word, ch] of Object.entries(aliases)) add(word, ch)

  reverseCache.set(variant, reverse)
  return reverse
}

/**
 * Декодирует фонетический алфавит обратно в текст.
 *
 * @param {string} text
 * @param {{variant?: string}} opts
 * @returns {string}
 */
function decodeNato(text, opts) {
  const reverse = reverseFor(opts.variant)

  // Формат «A = Alfa»: убираем префиксы «символ =», оставляя кодовые слова.
  text = text.replace(/[A-Za-zÄÖÜß0-9]\s*=\s*/g, '')

  const decodeToken = (token) => {
    let t = token.trim()
    if (!t) return ''
    // Формат «A = Alfa» — берём слово после знака равно, если оно есть.
    const eq = t.indexOf('=')
    if (eq !== -1) t = t.slice(eq + 1).trim()
    const key = t.toLowerCase().replace(/[.]/g, '')
    if (Object.prototype.hasOwnProperty.call(reverse, key)) return reverse[key]
    // Одиночная буква/цифра проходит как есть (устойчивость к смешанному вводу).
    if (/^[a-z0-9äöüß]$/i.test(t)) return t.toUpperCase()
    return '?'
  }

  return text
    .trim()
    .split(/\s*\/\s*|\n{2,}/)
    .map((chunk) =>
      chunk
        .trim()
        .split(/\s*,\s*|\s+|\s*-\s+/)
        .map((token) => token.trim())
        .filter(Boolean)
        .map(decodeToken)
        .join('')
    )
    .filter((w) => w !== '')
    .join(' ')
}

/**
 * Возвращает символы, для которых нет кодового слова в выбранном варианте
 * (пробелы игнорируются).
 *
 * @param {string} text
 * @param {string} variant
 * @returns {string[]}
 */
export function getUnknownChars(text, variant) {
  const def = variantDef(variant)
  const unknown = new Set()
  for (const ch of text) {
    if (/\s/.test(ch)) continue
    if (wordFor(ch, def) === null) unknown.add(ch)
  }
  return [...unknown]
}

/**
 * Преобразует значение согласно режиму.
 *
 * @param {string} value
 * @param {'encode'|'decode'} mode
 * @param {Record<string, unknown>} [opts]
 * @returns {string}
 */
export function transformNato(value, mode, opts) {
  const options = {
    variant: String(opts?.variant || 'nato'),
    separator: String(opts?.separator || 'space'),
    showLetter: Boolean(opts?.showLetter),
  }
  return mode === 'encode' ? encodeNato(value, options) : decodeNato(value, options)
}

/**
 * Эвристика: похожа ли строка на набор кодовых слов NATO.
 *
 * @param {string} value
 * @returns {boolean}
 */
export function looksLikeNato(value) {
  const s = value.trim().toLowerCase()
  if (!s) return false
  const tokens = s.split(/[\s,/-]+/).filter(Boolean)
  if (tokens.length === 0) return false
  const known = new Set(Object.values(NATO_LETTERS).map((w) => w.toLowerCase()))
  Object.values(NUMBERS_EN).forEach((w) => known.add(w.toLowerCase()))
  Object.values(POLICE_LETTERS).forEach((w) => known.add(w.toLowerCase()))
  const hits = tokens.filter((t) => known.has(t.replace(/[.]/g, ''))).length
  return hits / tokens.length >= 0.6
}

export { NATO_LETTERS, VARIANTS }
