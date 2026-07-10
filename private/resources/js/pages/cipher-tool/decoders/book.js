/**
 * Книжный шифр (Book cipher).
 *
 * Ключом служит целый «референсный текст» (книга). Каждый элемент открытого
 * текста заменяется ссылкой на позицию в книге. Поддерживаются четыре схемы
 * адресации (opts.scheme):
 *   word-index — номер = позиция слова в книге; шифруется словами
 *   beale      — номер = слово, чья первая буква = буква текста; по буквам
 *   line-word  — токен «строка.слово»; шифруется словами
 *   char-index — номер = позиция символа в книге; по символам
 *
 * mode === 'encode': открытый текст → числа
 * mode === 'decode': числа → открытый текст
 *
 * При кодировании, если элемент текста не покрыт книгой, бросается ошибка с
 * кодом 'book-uncovered' и списком непокрытых токенов; если книга пуста —
 * ошибка с кодом 'book-empty'.
 */

/**
 * Возвращает символ-разделитель вывода по его названию.
 *
 * @param {string} name
 * @returns {string}
 */
function delimChar(name) {
  switch (name) {
    case 'dash':  return '-'
    case 'comma': return ','
    case 'slash': return '/'
    case 'dot':   return '.'
    default:      return ' '
  }
}

/**
 * Быстрый детерминированный хэш строки (вариант cyrb53) для сидирования PRNG.
 * Нужен, чтобы случайный выбор кандидата при кодировании был стабильным для
 * одного и того же ввода (повторяемость, permalink, кеш).
 *
 * @param {string} str
 * @param {number} [seed]
 * @returns {number}
 */
function hashSeed(str, seed = 0) {
  let h1 = 0xdeadbeef ^ seed
  let h2 = 0x41c6ce57 ^ seed
  for (let i = 0; i < str.length; i++) {
    const ch = str.charCodeAt(i)
    h1 = Math.imul(h1 ^ ch, 2654435761)
    h2 = Math.imul(h2 ^ ch, 1597334677)
  }
  h1 = Math.imul(h1 ^ (h1 >>> 16), 2246822507) ^ Math.imul(h2 ^ (h2 >>> 13), 3266489909)
  h2 = Math.imul(h2 ^ (h2 >>> 16), 2246822507) ^ Math.imul(h1 ^ (h1 >>> 13), 3266489909)
  return (h2 >>> 0) * 4294967296 + (h1 >>> 0)
}

/**
 * Создаёт детерминированный PRNG (mulberry32) от числового сида.
 *
 * @param {number} seed
 * @returns {() => number}
 */
function makeRng(seed) {
  let a = seed >>> 0
  return function () {
    a |= 0
    a = (a + 0x6d2b79f5) | 0
    let t = Math.imul(a ^ (a >>> 15), 1 | a)
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296
  }
}

/**
 * Разбивает строку на слова (последовательности буквенных символов), сохраняя
 * их в нижнем регистре. Возвращает и сами слова (для декода), и карту
 * «слово → массив 1-based индексов» (для кодирования).
 *
 * @param {string} text
 * @returns {{ words: string[], index: Map<string, number[]> }}
 */
function buildWordList(text) {
  const words = []
  const index = new Map()
  const matches = text.toLowerCase().match(/[\p{L}\p{N}]+/gu) || []
  matches.forEach((word, i) => {
    words.push(word)
    const position = i + 1
    const bucket = index.get(word)
    if (bucket) bucket.push(position)
    else index.set(word, [position])
  })
  return { words, index }
}

/**
 * Выбирает случайный элемент массива через переданный PRNG.
 *
 * @template T
 * @param {T[]} arr
 * @param {() => number} rng
 * @returns {T}
 */
function pick(arr, rng) {
  return arr[Math.floor(rng() * arr.length)]
}

/**
 * Бросает ошибку о непокрытых токенах.
 *
 * @param {string[]} tokens
 * @returns {never}
 */
function throwUncovered(tokens) {
  const unique = [...new Set(tokens)]
  const error = new Error('book-uncovered: ' + unique.join(', '))
  error.code = 'book-uncovered'
  error.tokens = unique
  throw error
}

// ── word-index ──────────────────────────────────────────────────────────────

/**
 * Кодирует текст по индексам слов.
 *
 * @param {string} text
 * @param {string} book
 * @param {string} sep
 * @param {() => number} rng
 * @returns {string}
 */
function wordIndexEncode(text, book, sep, rng) {
  const { index } = buildWordList(book)
  const plainWords = (text.toLowerCase().match(/[\p{L}\p{N}]+/gu) || [])
  const uncovered = []
  const out = plainWords.map((word) => {
    const positions = index.get(word)
    if (!positions || positions.length === 0) {
      uncovered.push(word)
      return ''
    }
    return String(pick(positions, rng))
  })
  if (uncovered.length > 0) throwUncovered(uncovered)
  return out.join(sep)
}

/**
 * Декодирует индексы слов обратно в текст.
 *
 * @param {string} text
 * @param {string} book
 * @returns {string}
 */
function wordIndexDecode(text, book) {
  const { words } = buildWordList(book)
  const tokens = text.match(/\d+/g) || []
  return tokens.map((tok) => {
    const idx = parseInt(tok, 10) - 1
    return (idx >= 0 && idx < words.length) ? words[idx] : '?'
  }).join(' ')
}

// ── beale (первая буква слова) ────────────────────────────────────────────────

/**
 * Кодирует текст по схеме Билла: номер = слово, начинающееся на нужную букву.
 *
 * @param {string} text
 * @param {string} book
 * @param {string} sep
 * @param {() => number} rng
 * @returns {string}
 */
function bealeEncode(text, book, sep, rng) {
  const { words } = buildWordList(book)
  const byFirstLetter = new Map()
  words.forEach((word, i) => {
    const first = word[0]
    const bucket = byFirstLetter.get(first)
    if (bucket) bucket.push(i + 1)
    else byFirstLetter.set(first, [i + 1])
  })

  const letters = text.toLowerCase().match(/\p{L}/gu) || []
  const uncovered = []
  const out = letters.map((letter) => {
    const positions = byFirstLetter.get(letter)
    if (!positions || positions.length === 0) {
      uncovered.push(letter)
      return ''
    }
    return String(pick(positions, rng))
  })
  if (uncovered.length > 0) throwUncovered(uncovered)
  return out.join(sep)
}

/**
 * Декодирует номера схемы Билла обратно в буквы.
 *
 * @param {string} text
 * @param {string} book
 * @returns {string}
 */
function bealeDecode(text, book) {
  const { words } = buildWordList(book)
  const tokens = text.match(/\d+/g) || []
  return tokens.map((tok) => {
    const idx = parseInt(tok, 10) - 1
    return (idx >= 0 && idx < words.length) ? words[idx][0].toUpperCase() : '?'
  }).join('')
}

// ── line-word (координаты «строка.слово») ─────────────────────────────────────

/**
 * Строит список строк книги, где каждая строка — массив слов в нижнем регистре.
 *
 * @param {string} book
 * @returns {string[][]}
 */
function buildLineWords(book) {
  return book.split(/\r?\n/).map((line) => line.toLowerCase().match(/[\p{L}\p{N}]+/gu) || [])
}

/**
 * Кодирует текст координатами «строка.слово».
 *
 * @param {string} text
 * @param {string} book
 * @param {string} sep
 * @param {() => number} rng
 * @returns {string}
 */
function lineWordEncode(text, book, sep, rng) {
  const lines = buildLineWords(book)
  const index = new Map()
  lines.forEach((wordsInLine, lineIdx) => {
    wordsInLine.forEach((word, wordIdx) => {
      const coord = (lineIdx + 1) + '.' + (wordIdx + 1)
      const bucket = index.get(word)
      if (bucket) bucket.push(coord)
      else index.set(word, [coord])
    })
  })

  const plainWords = text.toLowerCase().match(/[\p{L}\p{N}]+/gu) || []
  const uncovered = []
  const out = plainWords.map((word) => {
    const coords = index.get(word)
    if (!coords || coords.length === 0) {
      uncovered.push(word)
      return ''
    }
    return pick(coords, rng)
  })
  if (uncovered.length > 0) throwUncovered(uncovered)
  return out.join(sep)
}

/**
 * Декодирует координаты «строка.слово» обратно в текст.
 *
 * @param {string} text
 * @param {string} book
 * @returns {string}
 */
function lineWordDecode(text, book) {
  const lines = buildLineWords(book)
  const tokens = text.match(/\d+\s*\.\s*\d+/g) || []
  return tokens.map((tok) => {
    const [l, w] = tok.split('.').map((n) => parseInt(n, 10) - 1)
    const line = lines[l]
    return (line && w >= 0 && w < line.length) ? line[w] : '?'
  }).join(' ')
}

// ── char-index ────────────────────────────────────────────────────────────────

/**
 * Кодирует текст по индексам символов книги (без учёта регистра для букв).
 *
 * @param {string} text
 * @param {string} book
 * @param {string} sep
 * @param {() => number} rng
 * @returns {string}
 */
function charIndexEncode(text, book, sep, rng) {
  const bookChars = [...book]
  const index = new Map()
  bookChars.forEach((ch, i) => {
    const key = ch.toLowerCase()
    const bucket = index.get(key)
    if (bucket) bucket.push(i + 1)
    else index.set(key, [i + 1])
  })

  const uncovered = []
  const out = [...text].map((ch) => {
    const positions = index.get(ch.toLowerCase())
    if (!positions || positions.length === 0) {
      uncovered.push(ch === ' ' ? '␣' : ch)
      return ''
    }
    return String(pick(positions, rng))
  })
  if (uncovered.length > 0) throwUncovered(uncovered)
  return out.join(sep)
}

/**
 * Декодирует индексы символов обратно в текст.
 *
 * @param {string} text
 * @param {string} book
 * @returns {string}
 */
function charIndexDecode(text, book) {
  const bookChars = [...book]
  const tokens = text.match(/\d+/g) || []
  return tokens.map((tok) => {
    const idx = parseInt(tok, 10) - 1
    return (idx >= 0 && idx < bookChars.length) ? bookChars[idx] : '?'
  }).join('')
}

// ── Публичный API ─────────────────────────────────────────────────────────────

/**
 * Преобразует значение книжным шифром в зависимости от mode и схемы.
 *
 * @param {string} value
 * @param {'encode'|'decode'} mode
 * @param {Record<string, unknown>} [opts]
 * @returns {string}
 */
export function transformBook(value, mode, opts) {
  const scheme = String(opts?.scheme || 'word-index')
  const book = String(opts?.book || '')
  const sep = delimChar(String(opts?.delimiter || 'space'))

  if (!value.trim()) return ''

  if (!book.trim()) {
    const error = new Error('book-empty')
    error.code = 'book-empty'
    throw error
  }

  if (mode === 'encode') {
    const rng = makeRng(hashSeed(book + ' ' + value + ' ' + scheme))
    switch (scheme) {
      case 'beale':      return bealeEncode(value, book, sep, rng)
      case 'line-word':  return lineWordEncode(value, book, sep, rng)
      case 'char-index': return charIndexEncode(value, book, sep, rng)
      default:           return wordIndexEncode(value, book, sep, rng)
    }
  }

  switch (scheme) {
    case 'beale':      return bealeDecode(value, book)
    case 'line-word':  return lineWordDecode(value, book)
    case 'char-index': return charIndexDecode(value, book)
    default:           return wordIndexDecode(value, book)
  }
}

/**
 * Эвристика автоопределения направления: ввод, состоящий преимущественно из
 * чисел и разделителей, считается зашифрованным (нужен режим decode).
 *
 * @param {string} value
 * @returns {boolean}
 */
export function looksLikeBook(value) {
  const trimmed = (value || '').trim()
  if (!trimmed) return false
  const digits = (trimmed.match(/\d/g) || []).length
  const letters = (trimmed.match(/\p{L}/gu) || []).length
  return digits > 0 && digits >= letters
}
