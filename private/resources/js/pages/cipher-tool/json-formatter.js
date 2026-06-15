/**
 * Токенизирует JSON-строку и оборачивает лексемы в span-теги для подсветки синтаксиса.
 * Вызывается только на уже провалидированном и отформатированном JSON.
 */
function highlightJson(str) {
  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  return str.replace(
    /("(?:\\u[0-9a-fA-F]{4}|\\[^u]|[^"\\])*")(\s*:)?|(true|false)|(null)|(-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)|([{}[\],:])/g,
    (match, str, colon, bool, nil, num, punct) => {
      if (str !== undefined) {
        return colon !== undefined
          ? `<span class="json-hl-key">${esc(str)}</span>${colon}`
          : `<span class="json-hl-string">${esc(str)}</span>`
      }
      if (bool  !== undefined) return `<span class="json-hl-boolean">${bool}</span>`
      if (nil   !== undefined) return `<span class="json-hl-null">${nil}</span>`
      if (num   !== undefined) return `<span class="json-hl-number">${num}</span>`
      if (punct !== undefined) return `<span class="json-hl-punct">${punct}</span>`
      return esc(match)
    }
  )
}

/**
 * Извлекает позицию (строка, столбец) из нативной ошибки JSON.parse.
 */
function parseJsonError(err, rawText) {
  const msg = err.message || ''

  // Firefox: "JSON.parse: ... at line L column C of the JSON data"
  const ffMatch = msg.match(/at line (\d+) column (\d+)/)
  if (ffMatch) {
    return {
      line: parseInt(ffMatch[1], 10),
      col:  parseInt(ffMatch[2], 10),
      description: msg
        .replace(/^JSON\.parse:\s*/, '')
        .replace(/\s*at line \d+ column \d+ of the JSON data.*/, ''),
    }
  }

  // Chrome/V8: "... at position N" or "in JSON at position N"
  const chromeMatch = msg.match(/at position (\d+)/)
  if (chromeMatch) {
    const pos    = Math.min(parseInt(chromeMatch[1], 10), rawText.length)
    const before = rawText.slice(0, pos)
    const line   = (before.match(/\n/g) || []).length + 1
    const lastNl = before.lastIndexOf('\n')
    const col    = pos - lastNl
    return {
      line,
      col,
      description: msg
        .replace(/\s+in JSON at position \d+$/, '')
        .replace(/\s+at position \d+$/, ''),
    }
  }

  return { line: null, col: null, description: msg }
}

/**
 * Обнаруживает дублирующиеся ключи внутри объектов JSON без полноценного парсера.
 *
 * @param  {string} rawJson Сырая JSON-строка.
 * @return {{ key: string, line: number, count: number }[]}
 */
function findDuplicateJsonKeys(rawJson) {
  const warnings = []
  let i = 0

  const lineStarts = [0]
  for (let j = 0; j < rawJson.length; j++) {
    if (rawJson[j] === '\n') lineStarts.push(j + 1)
  }

  const offsetToLine = (offset) => {
    let lo = 0, hi = lineStarts.length - 1
    while (lo < hi) {
      const mid = (lo + hi + 1) >> 1
      if (lineStarts[mid] <= offset) lo = mid; else hi = mid - 1
    }
    return lo + 1
  }

  const skip    = () => { while (i < rawJson.length && /\s/.test(rawJson[i])) i++ }
  const readStr = () => {
    i++ // пропустить открывающую "
    let result = ''
    while (i < rawJson.length) {
      if (rawJson[i] === '\\') {
        const c   = rawJson[i + 1]
        const map = { '"': '"', '\\': '\\', '/': '/', b: '\b', f: '\f', n: '\n', r: '\r', t: '\t' }
        if (c === 'u') {
          result += String.fromCharCode(parseInt(rawJson.slice(i + 2, i + 6), 16))
          i += 6
        } else {
          result += map[c] ?? c
          i += 2
        }
        continue
      }
      if (rawJson[i] === '"') { i++; break }
      result += rawJson[i++]
    }
    return result
  }

  function parseVal() {
    skip()
    if (i >= rawJson.length) return
    const c = rawJson[i]
    if (c === '{') parseObj()
    else if (c === '[') parseArr()
    else if (c === '"') readStr()
    else while (i < rawJson.length && !/[\s,\}\]]/.test(rawJson[i])) i++
  }

  function parseObj() {
    i++
    const seen = {}, firstLine = {}
    skip()
    if (rawJson[i] === '}') { i++; return }
    while (i < rawJson.length) {
      skip()
      if (rawJson[i] !== '"') break
      const ks  = i
      const key = readStr()
      const kl  = offsetToLine(ks)
      if (Object.prototype.hasOwnProperty.call(seen, key)) {
        seen[key]++
        const w = warnings.find((w) => w.key === key && w.line === firstLine[key])
        if (w) w.count = seen[key]; else warnings.push({ key, line: firstLine[key], count: seen[key] })
      } else {
        seen[key] = 1
        firstLine[key] = kl
      }
      skip()
      if (rawJson[i] === ':') i++
      parseVal()
      skip()
      if (rawJson[i] === ',') { i++; continue }
      if (rawJson[i] === '}') { i++; break }
      break
    }
  }

  function parseArr() {
    i++
    skip()
    if (rawJson[i] === ']') { i++; return }
    while (i < rawJson.length) {
      parseVal()
      skip()
      if (rawJson[i] === ',') { i++; continue }
      if (rawJson[i] === ']') { i++; break }
      break
    }
  }

  try { parseVal() } catch { /* игнорируем ошибки сканера */ }
  return warnings
}

/**
 * Рекурсивно анализирует структуру JSON: считает объекты, массивы, ключи и глубину.
 *
 * @param  {*} data Распарсенные данные.
 * @return {{ objects: number, arrays: number, keys: number, maxDepth: number }}
 */
function analyzeJson(data) {
  let objects = 0, arrays = 0, keys = 0, maxDepth = 0

  const walk = (node, depth) => {
    if (depth > maxDepth) maxDepth = depth
    if (Array.isArray(node)) {
      arrays++
      for (const item of node) walk(item, depth + 1)
    } else if (node !== null && typeof node === 'object') {
      objects++
      for (const v of Object.values(node)) { keys++; walk(v, depth + 1) }
    }
  }

  walk(data, 0)
  return { objects, arrays, keys, maxDepth }
}

/**
 * Рекурсивно сортирует ключи объектов JSON по алфавиту (массивы не изменяет).
 *
 * @param  {*} data Распарсенные данные.
 * @return {*}
 */
function sortJsonKeys(data) {
  if (Array.isArray(data)) return data.map(sortJsonKeys)
  if (data !== null && typeof data === 'object') {
    return Object.keys(data).sort().reduce((acc, k) => {
      acc[k] = sortJsonKeys(data[k])
      return acc
    }, {})
  }
  return data
}

/**
 * Рендерит узел JSON-дерева в виде HTML с возможностью сворачивания.
 *
 * @param  {*}      data  Любое JSON-значение.
 * @return {string} HTML-строка.
 */
function renderJsonTreeNode(data) {
  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

  if (data === null)           return `<span class="json-hl-null">null</span>`
  if (typeof data === 'boolean') return `<span class="json-hl-boolean">${data}</span>`
  if (typeof data === 'number')  return `<span class="json-hl-number">${data}</span>`
  if (typeof data === 'string')  return `<span class="json-hl-string">${esc(JSON.stringify(data))}</span>`

  if (Array.isArray(data)) {
    if (data.length === 0) return `<span class="json-hl-punct">[]</span>`
    const items = data.map((v, idx) =>
      `<li class="json-tree-item">` +
      `<span class="json-tree-idx">${idx}</span>` +
      `<span class="json-tree-sep">:</span>` +
      renderJsonTreeNode(v) +
      `</li>`
    ).join('')
    return (
      `<span class="json-tree-node" data-collapsible>` +
      `<button class="json-tree-caret" type="button" aria-label="toggle"></button>` +
      `<span class="json-hl-punct">[</span>` +
      `<span class="json-tree-ellipsis">… <span class="json-hl-punct">]</span></span>` +
      `<span class="json-tree-body"><ul class="json-tree-list">${items}</ul>` +
      `<span class="json-hl-punct">]</span></span>` +
      `</span>`
    )
  }

  const objKeys = Object.keys(data)
  if (objKeys.length === 0) return `<span class="json-hl-punct">{}</span>`
  const items = objKeys.map((k) =>
    `<li class="json-tree-item">` +
    `<span class="json-hl-key">${esc(JSON.stringify(k))}</span>` +
    `<span class="json-tree-sep">:</span>` +
    renderJsonTreeNode(data[k]) +
    `</li>`
  ).join('')
  return (
    `<span class="json-tree-node" data-collapsible>` +
    `<button class="json-tree-caret" type="button" aria-label="toggle"></button>` +
    `<span class="json-hl-punct">{</span>` +
    `<span class="json-tree-ellipsis">… <span class="json-hl-punct">}</span></span>` +
    `<span class="json-tree-body"><ul class="json-tree-list">${items}</ul>` +
    `<span class="json-hl-punct">}</span></span>` +
    `</span>`
  )
}

/**
 * Инициализирует JSON-форматтер: регистрирует обработчики событий.
 * Возвращает { run, showEmpty } для вызова из основного цикла обработки.
 *
 * @param {{
 *   input: HTMLTextAreaElement,
 *   output: HTMLTextAreaElement,
 *   visualOutput: HTMLElement|null,
 *   feedback: HTMLElement|null,
 *   labels: Record<string, string>,
 *   jsonIndentSelect: HTMLSelectElement|null,
 *   jsonSortKeysBtn: HTMLButtonElement|null,
 *   jsonDownloadBtn: HTMLButtonElement|null,
 *   setFeedback: (msg: string, isError?: boolean, isInfo?: boolean) => void,
 *   setOutputState: (hasContent: boolean) => void,
 *   highlightErrorInInput: (line: number, col: number|null) => void,
 *   sendAnalyticsBeacon: (slug: string, mode: string) => void,
 *   slug: string,
 *   getMode: () => string,
 *   onProcess: () => void,
 * }} ctx
 * @return {{ run: (value: string) => void, showEmpty: () => void }}
 */
export function initJsonFormatter({
  input, output, visualOutput, feedback,
  labels, jsonIndentSelect, jsonSortKeysBtn, jsonDownloadBtn,
  setFeedback, setOutputState, highlightErrorInInput,
  sendAnalyticsBeacon, slug, getMode, onProcess,
}) {
  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

  let lastParsed = null
  let lastDupes  = []
  let viewMode   = 'text'

  const showEmpty = () => {
    if (visualOutput) {
      visualOutput.style.display = 'none'
      visualOutput.innerHTML = ''
    }
    output.style.display = ''
    lastParsed = null
    lastDupes  = []
  }

  const setErrorFeedback = (primary, detail) => {
    if (!feedback) return
    const ep = esc(primary)
    const ed = detail ? esc(detail) : ''
    feedback.innerHTML = ed
      ? `${ep}<br><span class="json-err-detail">${ed}</span>`
      : ep
    feedback.classList.add('error')
    feedback.classList.remove('info')
  }

  const renderWarningsHtml = (dupes) => {
    if (!dupes.length) return ''
    return `<div class="json-warnings">${dupes.map((d) => {
      let msg = labels.jsonFormatterWarnDuplicate.replace(':key', esc(d.key))
      if (d.line) msg += ` <span class="json-warning-meta">(line ${d.line}${d.count > 2 ? `, ${d.count}×` : ''})</span>`
      return `<div class="json-warning-item"><span class="json-warning-icon">⚠</span> ${msg}</div>`
    }).join('')}</div>`
  }

  const renderStatsHtml = (stats, str) => {
    const bytes = new TextEncoder().encode(str).length
    const parts = [
      `${labels.jsonFormatterStatObjects}: ${stats.objects}`,
      `${labels.jsonFormatterStatArrays}: ${stats.arrays}`,
      `${labels.jsonFormatterStatKeys}: ${stats.keys}`,
      `${labels.jsonFormatterStatDepth}: ${stats.maxDepth}`,
      `${str.length} ${labels.chars}`,
      `${bytes} ${labels.bytes}`,
    ]
    return `<span class="json-stats">${parts.join(' · ')}</span>`
  }

  const renderFormatOutput = (parsed, formattedStr, dupes, view) => {
    if (!visualOutput) return
    const stats        = analyzeJson(parsed)
    const warningsHtml = renderWarningsHtml(dupes)
    const statsHtml    = renderStatsHtml(stats, formattedStr)
    const isText       = view === 'text'

    const barHtml = (
      `<div class="json-view-bar">` +
      `<div class="json-view-tabs">` +
      `<button class="json-view-tab${isText ? ' json-view-tab--active' : ''}" data-view="text">${labels.jsonFormatterViewText}</button>` +
      `<button class="json-view-tab${!isText ? ' json-view-tab--active' : ''}" data-view="tree">${labels.jsonFormatterViewTree}</button>` +
      `</div>` +
      statsHtml +
      `</div>`
    )
    const textPanel = (
      `<div class="json-panel json-panel--text"${!isText ? ' style="display:none"' : ''}>` +
      `<pre class="json-highlight">${highlightJson(formattedStr)}</pre></div>`
    )
    const treePanel = (
      `<div class="json-panel json-panel--tree"${isText ? ' style="display:none"' : ''}>` +
      `<div class="json-tree">${renderJsonTreeNode(parsed)}</div></div>`
    )

    output.style.display = 'none'
    visualOutput.style.display = 'block'
    visualOutput.innerHTML = barHtml + warningsHtml + textPanel + treePanel
  }

  const renderMiniOutput = (dupes) => {
    output.style.display = ''
    if (dupes.length > 0 && visualOutput) {
      visualOutput.style.display = 'block'
      visualOutput.innerHTML = renderWarningsHtml(dupes)
    } else if (visualOutput) {
      visualOutput.style.display = 'none'
      visualOutput.innerHTML = ''
    }
  }

  const run = (value) => {
    const dupes = findDuplicateJsonKeys(value)
    let parsed
    try {
      parsed = JSON.parse(value)
    } catch (e) {
      output.value = ''
      showEmpty()
      setOutputState(false)
      const errInfo = parseJsonError(e, value)
      if (errInfo.line) {
        setErrorFeedback(
          labels.jsonFormatterErrAt
            .replace(':line', errInfo.line)
            .replace(':col', errInfo.col ?? '?'),
          errInfo.description,
        )
        highlightErrorInInput(errInfo.line, errInfo.col)
      } else {
        setFeedback(labels.jsonFormatterErrInvalid.replace(':error', errInfo.description), true)
      }
      return
    }

    lastParsed = parsed
    lastDupes  = dupes

    const indent    = jsonIndentSelect?.value || '2'
    const indentArg = indent === 'tab' ? '\t' : Number(indent)
    const mode      = getMode()

    if (mode === 'encode') {
      output.value = JSON.stringify(parsed, null, indentArg)
      setOutputState(true)
      setFeedback('')
      renderFormatOutput(parsed, output.value, dupes, viewMode)
      sendAnalyticsBeacon(slug, 'format')
    } else {
      output.value = JSON.stringify(parsed)
      setOutputState(true)
      setFeedback('')
      renderMiniOutput(dupes)
      sendAnalyticsBeacon(slug, 'minify')
    }
  }

  jsonIndentSelect?.addEventListener('change', onProcess)

  visualOutput?.addEventListener('click', (e) => {
    const caret = e.target.closest('.json-tree-caret')
    if (caret) {
      const node = caret.closest('[data-collapsible]')
      if (node) node.classList.toggle('collapsed')
      return
    }
    const tab = e.target.closest('.json-view-tab')
    if (tab && lastParsed !== null) {
      const view = tab.dataset.view
      if (view) {
        viewMode = view
        renderFormatOutput(lastParsed, output.value, lastDupes, viewMode)
      }
    }
  })

  jsonSortKeysBtn?.addEventListener('click', () => {
    if (!lastParsed) return
    const sorted    = sortJsonKeys(lastParsed)
    const indent    = jsonIndentSelect?.value || '2'
    const indentArg = indent === 'tab' ? '\t' : Number(indent)
    input.value = JSON.stringify(sorted, null, indentArg)
    onProcess()
  })

  jsonDownloadBtn?.addEventListener('click', () => {
    if (!output.value) return
    const blob = new Blob([output.value], { type: 'application/json;charset=utf-8' })
    const url  = URL.createObjectURL(blob)
    const a    = document.createElement('a')
    a.href     = url
    a.download = 'formatted.json'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  })

  return { run, showEmpty }
}
