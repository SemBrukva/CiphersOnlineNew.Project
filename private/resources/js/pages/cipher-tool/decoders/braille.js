/**
 * Шрифт Брайля (Grade 1, uncontracted) для 8 системных языков.
 *
 * Ядро — «клетка как битовая маска». Точки клетки нумеруются:
 *   1 4
 *   2 5
 *   3 6
 * Битовое значение точки n = 1 << (n - 1); сумма значений точек совпадает
 * со смещением символа от U+2800 в блоке Unicode Braille Patterns.
 * Поэтому одна маска (0..63) порождает все представления:
 *   - Unicode-глиф  = String.fromCodePoint(0x2800 + mask)
 *   - номера точек  = перечисление поднятых точек (напр. 'h' → '125')
 *   - Braille ASCII = фиксированная таблица из 64 клеток → ASCII
 *   - визуальная сетка 2×3 (см. braille-cells.js)
 *
 * @typedef {number} Cell Битовая маска клетки (0..63).
 */

/**
 * Преобразует строку номеров точек ('145') в битовую маску клетки.
 *
 * @param {string} dots Например '145'.
 * @returns {Cell}
 */
function D(dots) {
  let mask = 0
  for (const d of dots) mask |= 1 << (Number(d) - 1)
  return mask
}

/** Числовой знак ⠼ (точки 3456) — предваряет группу цифр. */
const NUMBER_SIGN = D('3456')
/** Буквенный знак ⠰ (точки 56) — возвращает буквенный режим после цифр. */
const LETTER_SIGN = D('56')
/** Знак заглавной ⠠ (точка 6). Удвоенный — заглавное слово. */
const CAPITAL_SIGN = D('6')

/**
 * Базовая латиница a–z (стандарт Луи Брайля, общий для всех латинских языков).
 *
 * @type {Record<string, string>}
 */
const LATIN = {
  a: '1', b: '12', c: '14', d: '145', e: '15', f: '124', g: '1245', h: '125', i: '24', j: '245',
  k: '13', l: '123', m: '134', n: '1345', o: '135', p: '1234', q: '12345', r: '1235', s: '234', t: '2345',
  u: '136', v: '1236', w: '2456', x: '1346', y: '13456', z: '1356',
}

/**
 * Цифры 0–9 в числовом режиме = буквы a–j (после числового знака ⠼).
 *
 * @type {Record<string, string>}
 */
const DIGITS = {
  1: '1', 2: '12', 3: '14', 4: '145', 5: '15', 6: '124', 7: '1245', 8: '125', 9: '24', 0: '245',
}

/**
 * Общая пунктуация (English Braille). Маски подобраны без коллизий,
 * чтобы decode был однозначным.
 *
 * @type {Record<string, string>}
 */
const PUNCT = {
  ',': '2', ';': '23', ':': '25', '.': '256', '!': '235', '?': '236',
  "'": '3', '-': '36', '(': '12356', ')': '23456',
}

/**
 * Диакритика по языкам (национальные таблицы Брайля).
 * Значения — из стандартных национальных алфавитов Брайля.
 */
const ACCENTS = {
  fr: {
    'é': '123456', 'à': '12356', 'è': '2346', 'ù': '23456', 'â': '16', 'ê': '126',
    'î': '146', 'ô': '1456', 'û': '156', 'ë': '1246', 'ï': '12456', 'ü': '1256',
    'ç': '12346', 'œ': '246',
  },
  de: {
    'ä': '345', 'ö': '246', 'ü': '1256', 'ß': '2346',
  },
  es: {
    'á': '12356', 'é': '2346', 'í': '34', 'ó': '346', 'ú': '23456', 'ü': '1256', 'ñ': '12456',
  },
  it: {
    'à': '12356', 'è': '2346', 'é': '123456', 'ì': '34', 'ò': '346', 'ù': '23456',
  },
  pt: {
    'á': '12356', 'à': '1246', 'â': '16', 'ã': '345', 'ç': '12346', 'é': '123456', 'ê': '126',
    'í': '34', 'ó': '346', 'ô': '1456', 'õ': '246', 'ú': '23456', 'ü': '1256',
  },
  tr: {
    // Согласные с диакритикой берут формы английских диграфов: ç→ch(16), ğ→gh(126), ş→sh(146).
    // Гласные ö/ü — французско-немецкие формы; ı (без точки) = i(24) со сдвигом вниз → 35.
    'ç': '16', 'ö': '246', 'ü': '1256', 'ş': '146', 'ğ': '126', 'ı': '35',
  },
}

/**
 * Русская таблица Брайля (кириллица). Цифры/пунктуация — общие.
 *
 * @type {Record<string, string>}
 */
const RUSSIAN = {
  'а': '1', 'б': '12', 'в': '2456', 'г': '1245', 'д': '145', 'е': '15', 'ё': '16',
  'ж': '245', 'з': '1356', 'и': '24', 'й': '12346', 'к': '13', 'л': '123', 'м': '134',
  'н': '1345', 'о': '135', 'п': '1234', 'р': '1235', 'с': '234', 'т': '2345', 'у': '136',
  'ф': '124', 'х': '125', 'ц': '14', 'ч': '12345', 'ш': '156', 'щ': '1346', 'ъ': '12356',
  'ы': '2346', 'ь': '23456', 'э': '246', 'ю': '1256', 'я': '1246',
}

/**
 * Строит буквенную карту (символ → маска) для языка.
 *
 * @param {string} lang
 * @returns {Record<string, Cell>}
 */
function buildLetterMap(lang) {
  const source = lang === 'ru'
    ? { ...RUSSIAN }
    : { ...LATIN, ...(ACCENTS[lang] ?? {}) }

  /** @type {Record<string, Cell>} */
  const map = {}
  // Буквы вставляем первыми — при коллизии масок обратная карта отдаёт приоритет букве,
  // а не пунктуации (напр. маска 23456 → 'ù'/'ú', а не ')').
  for (const [ch, dots] of Object.entries(source)) map[ch] = D(dots)
  for (const [ch, dots] of Object.entries(PUNCT)) map[ch] = D(dots)
  return map
}

/** @type {Map<string, Record<string, Cell>>} */
const letterMapCache = new Map()

/**
 * Возвращает (кэшируя) буквенную карту языка.
 *
 * @param {string} lang
 * @returns {Record<string, Cell>}
 */
function letterMapFor(lang) {
  const key = lang || 'en'
  if (!letterMapCache.has(key)) letterMapCache.set(key, buildLetterMap(key))
  return letterMapCache.get(key)
}

/** @type {Map<string, Map<Cell, string>>} */
const reverseCache = new Map()

/**
 * Возвращает обратную карту (маска → символ) для языка.
 * При коллизии приоритет у символа, определённого раньше.
 *
 * @param {string} lang
 * @returns {Map<Cell, string>}
 */
function reverseMapFor(lang) {
  const key = lang || 'en'
  if (reverseCache.has(key)) return reverseCache.get(key)

  const forward = letterMapFor(key)
  /** @type {Map<Cell, string>} */
  const reverse = new Map()
  for (const [ch, mask] of Object.entries(forward)) {
    if (!reverse.has(mask)) reverse.set(mask, ch)
  }
  reverseCache.set(key, reverse)
  return reverse
}

/**
 * Таблица Braille ASCII (North American Braille Computer Code): маска → ASCII.
 * Индекс = маска клетки (0..63). Не зависит от языка.
 *
 * @type {string}
 */
const BRAILLE_ASCII =
  ' A1B\'K2L@CIF/MSP' +
  '"E3H9O6R^DJG>NTQ' +
  ',*5<-U8V.%[$+X!&' +
  ';:4\\0Z7(_?W]#Y)='

/** @type {Map<string, Cell>} Обратная карта Braille ASCII (символ → маска). */
const asciiToCell = new Map()
for (let i = 0; i < BRAILLE_ASCII.length; i++) asciiToCell.set(BRAILLE_ASCII[i], i)

/**
 * Проверяет, является ли символ заглавной буквой (в т.ч. с диакритикой/кириллицей).
 *
 * @param {string} ch
 * @returns {boolean}
 */
function isUpper(ch) {
  const lower = ch.toLowerCase()
  return lower !== ch && lower === ch.toLowerCase() && ch === ch.toUpperCase()
}

/**
 * Описание одной клетки потока (или пробела/неизвестного символа).
 *
 * @typedef {{ mask: Cell, src?: string }
 *   | { space: true, src: string }
 *   | { unknown: true, src: string }} StreamCell
 */

/**
 * Кодирует текст в поток клеток Брайля.
 *
 * @param {string} text
 * @param {{ language?: string, keepCase?: boolean }} [opts]
 * @returns {StreamCell[]}
 */
export function encodeToCells(text, opts = {}) {
  const lang = String(opts.language || 'en')
  const keepCase = opts.keepCase !== false
  const map = letterMapFor(lang)

  /** @type {StreamCell[]} */
  const cells = []
  let numberMode = false

  for (const ch of Array.from(text)) {
    if (/\s/.test(ch)) {
      numberMode = false
      cells.push({ space: true, src: ch })
      continue
    }
    if (ch >= '0' && ch <= '9') {
      if (!numberMode) { cells.push({ mask: NUMBER_SIGN }); numberMode = true }
      cells.push({ mask: D(DIGITS[ch]), src: ch })
      continue
    }

    const lower = ch.toLowerCase()
    const mask = map[lower]
    if (mask === undefined) {
      numberMode = false
      cells.push({ unknown: true, src: ch })
      continue
    }
    // Буквенный знак нужен, если буква a–j идёт сразу после цифры без пробела.
    if (numberMode) { cells.push({ mask: LETTER_SIGN }); numberMode = false }
    if (keepCase && isUpper(ch)) cells.push({ mask: CAPITAL_SIGN })
    cells.push({ mask, src: ch })
  }
  return cells
}

/**
 * Рендерит поток клеток в строку заданного формата.
 *
 * @param {StreamCell[]} cells
 * @param {'unicode'|'dots'|'ascii'} format
 * @returns {string}
 */
export function renderCells(cells, format) {
  if (format === 'dots') {
    return cells
      .map((cell) => {
        if ('space' in cell) return '/'
        if ('unknown' in cell) return '?'
        return dotsOf(cell.mask) || '0'
      })
      .join(' ')
  }
  if (format === 'ascii') {
    return cells
      .map((cell) => {
        if ('space' in cell) return ' '
        if ('unknown' in cell) return '?'
        return BRAILLE_ASCII[cell.mask] ?? '?'
      })
      .join('')
  }
  // unicode
  return cells
    .map((cell) => {
      if ('space' in cell) return ' '
      if ('unknown' in cell) return '?'
      return String.fromCodePoint(0x2800 + cell.mask)
    })
    .join('')
}

/**
 * Возвращает строку номеров поднятых точек для маски ('h' → '125').
 *
 * @param {Cell} mask
 * @returns {string}
 */
export function dotsOf(mask) {
  let s = ''
  for (let d = 1; d <= 6; d++) if (mask & (1 << (d - 1))) s += String(d)
  return s
}

/**
 * Определяет формат ввода при декодировании.
 *
 * @param {string} value
 * @returns {'unicode'|'dots'|'ascii'}
 */
function detectInputFormat(value) {
  if (/[⠀-⣿]/.test(value)) return 'unicode'
  if (/^[\s0-9/]+$/.test(value.trim())) return 'dots'
  return 'ascii'
}

/**
 * Разбирает строку Брайля в массив клеток (маска | null для пробела).
 *
 * @param {string} value
 * @param {'unicode'|'dots'|'ascii'} format
 * @returns {(Cell|null)[]}
 */
function parseCells(value, format) {
  if (format === 'dots') {
    return value
      .trim()
      .split(/\s+/)
      .filter(Boolean)
      .map((token) => (token === '/' || token === '0' ? null : D(token)))
  }
  if (format === 'ascii') {
    return Array.from(value.toUpperCase()).map((ch) =>
      ch === ' ' ? null : (asciiToCell.get(ch) ?? -1)
    )
  }
  // unicode
  return Array.from(value).map((ch) => {
    const code = ch.codePointAt(0)
    if (code >= 0x2800 && code <= 0x28ff) {
      const mask = code - 0x2800
      return mask === 0 ? null : mask
    }
    if (/\s/.test(ch)) return null
    return -1
  })
}

/**
 * Декодирует поток клеток обратно в текст.
 *
 * @param {(Cell|null)[]} masks
 * @param {string} lang
 * @returns {string}
 */
function decodeCells(masks, lang) {
  const reverse = reverseMapFor(lang)
  let out = ''
  let numberMode = false
  let capitalNext = 0 // 0 — нет, 1 — следующая буква, 2 — слово до пробела

  for (const mask of masks) {
    if (mask === null) { out += ' '; numberMode = false; capitalNext = 0; continue }
    if (mask === -1) { out += '?'; continue }
    if (mask === NUMBER_SIGN) { numberMode = true; continue }
    if (mask === LETTER_SIGN) { numberMode = false; continue }
    if (mask === CAPITAL_SIGN) { capitalNext = capitalNext === 1 ? 2 : 1; continue }

    if (numberMode) {
      const digit = DIGIT_BY_MASK.get(mask)
      if (digit !== undefined) { out += digit; continue }
      numberMode = false
    }

    const ch = reverse.get(mask)
    if (ch === undefined) { out += '?'; continue }
    if (capitalNext) {
      out += ch.toUpperCase()
      if (capitalNext === 1) capitalNext = 0
    } else {
      out += ch
    }
  }
  return out
}

/** @type {Map<Cell, string>} Обратная карта цифр (маска → цифра). */
const DIGIT_BY_MASK = new Map()
for (const [digit, dots] of Object.entries(DIGITS)) DIGIT_BY_MASK.set(D(dots), digit)

/**
 * Кодирует текст в Брайль (в заданном формате).
 *
 * @param {string} value
 * @param {{ language?: string, keepCase?: boolean, format?: string }} [opts]
 * @returns {string}
 */
export function encodeBraille(value, opts = {}) {
  const cells = encodeToCells(value, opts)
  const format = /** @type {'unicode'|'dots'|'ascii'} */ (opts.format || 'unicode')
  return renderCells(cells, format)
}

/**
 * Декодирует Брайль обратно в текст (формат ввода определяется автоматически).
 *
 * @param {string} value
 * @param {{ language?: string }} [opts]
 * @returns {string}
 */
export function decodeBraille(value, opts = {}) {
  const format = detectInputFormat(value)
  const masks = parseCells(value, format)
  return decodeCells(masks, String(opts.language || 'en'))
}

/**
 * Преобразует значение в зависимости от режима.
 *
 * @param {string} value
 * @param {'encode'|'decode'} mode
 * @param {Record<string, unknown>} [opts]
 * @returns {string}
 */
export function transformBraille(value, mode, opts = {}) {
  const params = {
    language: String(opts.language || 'en'),
    keepCase: opts.keepCase !== false,
    format: String(opts.format || 'unicode'),
  }
  return mode === 'encode' ? encodeBraille(value, params) : decodeBraille(value, params)
}

/**
 * Автоопределение языка по тексту (только при encode; при decode — 'en').
 *
 * @param {string} text
 * @param {'encode'|'decode'} [mode]
 * @returns {string}
 */
export function detectBrailleLanguage(text, mode = 'encode') {
  if (mode !== 'encode') return 'en'
  if (/[а-яёА-ЯЁ]/.test(text)) return 'ru'
  if (/[şğıŞĞİ]/.test(text)) return 'tr'
  if (/[äöüßÄÖÜ]/.test(text)) return 'de'
  if (/[ñáíóúÑ]/.test(text)) return 'es'
  if (/[ãõçÃÕÇ]/.test(text)) return 'pt'
  if (/[àèìòùÀ]/.test(text)) return 'it'
  if (/[éêîôûëïœçÉ]/.test(text)) return 'fr'
  return 'en'
}

/**
 * Эвристика: похожа ли строка на Брайль (Unicode-глифы, номера точек или Braille ASCII).
 *
 * @param {string} value
 * @returns {boolean}
 */
export function looksLikeBraille(value) {
  const s = value.trim()
  if (!s) return false
  return /[⠀-⣿]/.test(s)
}

export { NUMBER_SIGN, LETTER_SIGN, CAPITAL_SIGN, BRAILLE_ASCII }
