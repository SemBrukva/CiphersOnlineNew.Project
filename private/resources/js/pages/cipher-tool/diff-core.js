/**
 * Ядро сравнения текстов: алгоритм Майерса (O(ND)) и построение выровненных строк
 * с внутристрочной подсветкой на уровне слов или символов. Без внешних зависимостей.
 */

/**
 * Алгоритм различий Майерса над двумя массивами.
 * Возвращает последовательность операций редактирования в прямом порядке.
 *
 * @param  {Array}  a  Массив «левых» элементов.
 * @param  {Array}  b  Массив «правых» элементов.
 * @param  {(x: *, y: *) => boolean} [eq] Функция сравнения элементов на равенство.
 * @return {Array<{type: 'equal'|'delete'|'insert', a: number|null, b: number|null}>}
 */
export function myersDiff(a, b, eq = (x, y) => x === y) {
  const n = a.length
  const m = b.length

  if (n === 0 && m === 0) return []
  if (n === 0) return b.map((_, j) => ({ type: 'insert', a: null, b: j }))
  if (m === 0) return a.map((_, i) => ({ type: 'delete', a: i, b: null }))

  const max = n + m
  const offset = max
  const v = new Array(2 * max + 1).fill(0)
  const trace = []

  let found = false
  for (let d = 0; d <= max && !found; d++) {
    trace.push(v.slice())
    for (let k = -d; k <= d; k += 2) {
      let x
      if (k === -d || (k !== d && v[offset + k - 1] < v[offset + k + 1])) {
        x = v[offset + k + 1]
      } else {
        x = v[offset + k - 1] + 1
      }
      let y = x - k
      while (x < n && y < m && eq(a[x], b[y])) {
        x++
        y++
      }
      v[offset + k] = x
      if (x >= n && y >= m) {
        found = true
        break
      }
    }
  }

  // Обратный проход по трассе для восстановления операций.
  const ops = []
  let x = n
  let y = m
  for (let d = trace.length - 1; d > 0; d--) {
    const vPrev = trace[d]
    const k = x - y
    let prevK
    if (k === -d || (k !== d && vPrev[offset + k - 1] < vPrev[offset + k + 1])) {
      prevK = k + 1
    } else {
      prevK = k - 1
    }
    const prevX = vPrev[offset + prevK]
    const prevY = prevX - prevK

    while (x > prevX && y > prevY) {
      ops.push({ type: 'equal', a: x - 1, b: y - 1 })
      x--
      y--
    }

    if (x === prevX) {
      ops.push({ type: 'insert', a: null, b: prevY })
    } else {
      ops.push({ type: 'delete', a: prevX, b: null })
    }

    x = prevX
    y = prevY
  }

  // Ведущая диагональ (совпадения из шага d = 0).
  while (x > 0 && y > 0) {
    ops.push({ type: 'equal', a: x - 1, b: y - 1 })
    x--
    y--
  }

  ops.reverse()
  return ops
}

const WORD_RE = /(\s+|[\p{L}\p{N}]+|[^\s\p{L}\p{N}]+)/gu

/**
 * Разбивает строку на токены-«слова» (буквенно-цифровые последовательности,
 * пробельные группы и группы пунктуации сохраняются как отдельные токены).
 *
 * @param  {string} str
 * @return {string[]}
 */
export function tokenizeWords(str) {
  return str.match(WORD_RE) || []
}

/**
 * Разбивает строку на массив символов с учётом суррогатных пар.
 *
 * @param  {string} str
 * @return {string[]}
 */
export function tokenizeChars(str) {
  return Array.from(str)
}

/**
 * Нормализует строку для сравнения согласно опциям (регистр, пробелы, обрезка).
 *
 * @param  {string} line
 * @param  {{ignoreCase?: boolean, ignoreWhitespace?: boolean, trim?: boolean}} opts
 * @return {string}
 */
export function normalizeLine(line, opts) {
  let value = line
  if (opts.ignoreCase) value = value.toLowerCase()
  if (opts.ignoreWhitespace) {
    value = value.replace(/\s+/g, '')
  } else if (opts.trim) {
    value = value.trim()
  }
  return value
}

/**
 * Считает внутристрочную разницу двух строк на уровне слов или символов.
 * Возвращает сегменты для левой и правой стороны с пометками added/removed.
 *
 * @param  {string} left
 * @param  {string} right
 * @param  {'word'|'char'} granularity
 * @param  {{ignoreCase?: boolean}} opts
 * @return {{left: Array<{text: string, type: 'equal'|'removed'}>, right: Array<{text: string, type: 'equal'|'added'}>}}
 */
export function diffInline(left, right, granularity, opts) {
  const tokenize = granularity === 'char' ? tokenizeChars : tokenizeWords
  const aTokens = tokenize(left)
  const bTokens = tokenize(right)

  const fold = (token) => (opts.ignoreCase ? token.toLowerCase() : token)
  const ops = myersDiff(aTokens, bTokens, (i, j) => fold(i) === fold(j))

  const leftSeg = []
  const rightSeg = []

  const pushLeft = (text, type) => {
    const last = leftSeg[leftSeg.length - 1]
    if (last && last.type === type) last.text += text
    else leftSeg.push({ text, type })
  }
  const pushRight = (text, type) => {
    const last = rightSeg[rightSeg.length - 1]
    if (last && last.type === type) last.text += text
    else rightSeg.push({ text, type })
  }

  for (const op of ops) {
    if (op.type === 'equal') {
      pushLeft(aTokens[op.a], 'equal')
      pushRight(bTokens[op.b], 'equal')
    } else if (op.type === 'delete') {
      pushLeft(aTokens[op.a], 'removed')
    } else {
      pushRight(bTokens[op.b], 'added')
    }
  }

  return { left: leftSeg, right: rightSeg }
}

/**
 * Готовит массивы строк к сравнению согласно опциям (пустые строки, сортировка).
 *
 * @param  {string} text
 * @param  {{ignoreEmptyLines?: boolean, sortLines?: boolean}} opts
 * @return {string[]}
 */
function prepareLines(text, opts) {
  if (text === '') return []
  let lines = text.split('\n')
  if (opts.ignoreEmptyLines) {
    lines = lines.filter((line) => line.trim() !== '')
  }
  if (opts.sortLines) {
    lines = [...lines].sort((x, y) => (x < y ? -1 : x > y ? 1 : 0))
  }
  return lines
}

/**
 * Полное сравнение двух текстов. Возвращает выровненные строки и статистику.
 *
 * Типы строк: 'equal' | 'delete' | 'insert' | 'modified'.
 * Для 'modified' при granularity ≠ 'line' заполняются сегменты leftSeg/rightSeg.
 *
 * @param  {string} aText
 * @param  {string} bText
 * @param  {{
 *   granularity?: 'line'|'word'|'char',
 *   ignoreCase?: boolean,
 *   ignoreWhitespace?: boolean,
 *   trim?: boolean,
 *   ignoreEmptyLines?: boolean,
 *   sortLines?: boolean
 * }} [options]
 * @return {{rows: Array, stats: Object}}
 */
export function diffText(aText, bText, options = {}) {
  const opts = {
    granularity: options.granularity || 'word',
    ignoreCase: Boolean(options.ignoreCase),
    ignoreWhitespace: Boolean(options.ignoreWhitespace),
    trim: Boolean(options.trim),
    ignoreEmptyLines: Boolean(options.ignoreEmptyLines),
    sortLines: Boolean(options.sortLines),
  }

  const aLines = prepareLines(aText, opts)
  const bLines = prepareLines(bText, opts)

  const aKeys = aLines.map((line) => normalizeLine(line, opts))
  const bKeys = bLines.map((line) => normalizeLine(line, opts))

  const ops = myersDiff(aKeys, bKeys)

  const rows = []
  let leftNo = 0
  let rightNo = 0
  let hunks = 0
  let inHunk = false

  const stats = {
    linesEqual: 0,
    linesAdded: 0,
    linesRemoved: 0,
    linesModified: 0,
    hunks: 0,
  }

  // Группируем идущие подряд delete/insert, чтобы спарить их как «изменённые» строки.
  let pendingDel = []
  let pendingIns = []

  const flushPending = () => {
    const pairs = Math.min(pendingDel.length, pendingIns.length)
    for (let i = 0; i < pairs; i++) {
      leftNo++
      rightNo++
      const leftText = aLines[pendingDel[i]]
      const rightText = bLines[pendingIns[i]]
      const row = {
        type: 'modified',
        leftNo,
        rightNo,
        left: leftText,
        right: rightText,
        leftSeg: null,
        rightSeg: null,
      }
      if (opts.granularity !== 'line') {
        const inline = diffInline(leftText, rightText, opts.granularity, opts)
        row.leftSeg = inline.left
        row.rightSeg = inline.right
      }
      rows.push(row)
      stats.linesModified++
    }
    for (let i = pairs; i < pendingDel.length; i++) {
      leftNo++
      rows.push({ type: 'delete', leftNo, rightNo: null, left: aLines[pendingDel[i]], right: null })
      stats.linesRemoved++
    }
    for (let i = pairs; i < pendingIns.length; i++) {
      rightNo++
      rows.push({ type: 'insert', leftNo: null, rightNo, left: null, right: bLines[pendingIns[i]] })
      stats.linesAdded++
    }
    pendingDel = []
    pendingIns = []
  }

  for (const op of ops) {
    if (op.type === 'delete') {
      pendingDel.push(op.a)
      if (!inHunk) { hunks++; inHunk = true }
    } else if (op.type === 'insert') {
      pendingIns.push(op.b)
      if (!inHunk) { hunks++; inHunk = true }
    } else {
      flushPending()
      inHunk = false
      leftNo++
      rightNo++
      rows.push({ type: 'equal', leftNo, rightNo, left: aLines[op.a], right: bLines[op.b] })
      stats.linesEqual++
    }
  }
  flushPending()

  stats.hunks = hunks
  stats.linesTotalLeft = aLines.length
  stats.linesTotalRight = bLines.length
  stats.charsLeft = Array.from(aText).length
  stats.charsRight = Array.from(bText).length
  stats.wordsLeft = (aText.match(/\S+/g) || []).length
  stats.wordsRight = (bText.match(/\S+/g) || []).length
  stats.identical = stats.linesAdded === 0 && stats.linesRemoved === 0 && stats.linesModified === 0
  stats.similarity = similarityRatio(aText, bText, opts)

  return { rows, stats }
}

/**
 * Длина наибольшей общей подпоследовательности символов (динамика с прокруткой строк).
 *
 * @param  {string[]} a
 * @param  {string[]} b
 * @return {number}
 */
function lcsLength(a, b) {
  const n = a.length
  const m = b.length
  let prev = new Array(m + 1).fill(0)
  let curr = new Array(m + 1).fill(0)
  for (let i = 1; i <= n; i++) {
    for (let j = 1; j <= m; j++) {
      if (a[i - 1] === b[j - 1]) curr[j] = prev[j - 1] + 1
      else curr[j] = prev[j] >= curr[j - 1] ? prev[j] : curr[j - 1]
    }
    const tmp = prev
    prev = curr
    curr = tmp
  }
  return prev[m]
}

/**
 * Оценивает подобие текстов в диапазоне 0..1 (2·LCS / (|a|+|b|) на уровне символов).
 * Для больших входов переходит к приближённой построчной оценке ради скорости.
 *
 * @param  {string} aText
 * @param  {string} bText
 * @param  {{ignoreCase?: boolean}} opts
 * @return {number}
 */
export function similarityRatio(aText, bText, opts = {}) {
  let a = aText
  let b = bText
  if (opts.ignoreCase) {
    a = a.toLowerCase()
    b = b.toLowerCase()
  }
  if (a.length === 0 && b.length === 0) return 1
  if (a.length === 0 || b.length === 0) return 0

  if (a.length * b.length <= 4_000_000) {
    const lcs = lcsLength(Array.from(a), Array.from(b))
    return (2 * lcs) / (Array.from(a).length + Array.from(b).length)
  }

  // Приближение по строкам для очень больших текстов.
  const aLines = a.split('\n')
  const bLines = b.split('\n')
  const lcs = lcsLength(aLines, bLines)
  return (2 * lcs) / (aLines.length + bLines.length)
}
