import { diffText } from './cipher-tool/diff-core.js'
import { initCustomSelects } from './cipher-tool/custom-selects.js'

/**
 * Безопасно разбирает JSON конфигурации инструмента из data-атрибута.
 *
 * @param  {string|null} raw
 * @return {Record<string, *>}
 */
function parseUi(raw) {
  if (!raw) return {}
  try {
    const parsed = JSON.parse(raw)
    return parsed && typeof parsed === 'object' ? parsed : {}
  } catch {
    return {}
  }
}

/**
 * Инициализирует клиентский инструмент сравнения текстов (Text Diff).
 * Страница помечена data-page="text-diff"; на других страницах инициализатор ничего не делает.
 */
export function initTextDiffPage() {
  const root = document.querySelector('[data-page="text-diff"]')
  if (!root) return

  const ui = parseUi(root.getAttribute('data-cipher-ui'))

  const inputA = document.getElementById('diff-input-a')
  const inputB = document.getElementById('diff-input-b')
  const result = document.getElementById('diff-result')
  const emptyEl = document.getElementById('diff-empty')
  const statusbar = document.getElementById('diff-statusbar')
  const statsEl = document.getElementById('diff-stats')
  const countA = document.getElementById('diff-count-a')
  const countB = document.getElementById('diff-count-b')
  const feedback = document.getElementById('ciphers-feedback')

  if (!inputA || !inputB || !result) return

  const granularitySelect = document.getElementById('diff-granularity')
  const viewSelect = document.getElementById('diff-view')
  const optIgnoreCase = document.getElementById('diff-ignore-case')
  const optIgnoreWs = document.getElementById('diff-ignore-ws')
  const optTrim = document.getElementById('diff-trim')
  const optIgnoreEmpty = document.getElementById('diff-ignore-empty')
  const optSort = document.getElementById('diff-sort')
  const optOnlyChanges = document.getElementById('diff-only-changes')

  const swapBtn = document.getElementById('diff-swap')
  const clearBtn = document.getElementById('diff-clear')
  const copyBtn = document.getElementById('diff-copy')
  const shareBtn = document.getElementById('ciphers-share')
  const nav = document.getElementById('diff-nav')
  const prevBtn = document.getElementById('diff-prev')
  const nextBtn = document.getElementById('diff-next')
  const hunkPos = document.getElementById('diff-hunk-pos')

  const CONTEXT = 3
  const STORAGE_KEY = 'text-diff:options'

  let anchors = []
  let currentAnchor = -1
  let debounceTimer = null

  const esc = (s) => String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')

  const t = (key, fallback) => (ui[key] != null && ui[key] !== '' ? ui[key] : fallback)

  const readOptions = () => ({
    granularity: granularitySelect?.value || 'word',
    view: viewSelect?.value || 'split',
    ignoreCase: Boolean(optIgnoreCase?.checked),
    ignoreWhitespace: Boolean(optIgnoreWs?.checked),
    trim: Boolean(optTrim?.checked),
    ignoreEmptyLines: Boolean(optIgnoreEmpty?.checked),
    sortLines: Boolean(optSort?.checked),
    onlyChanges: Boolean(optOnlyChanges?.checked),
  })

  const saveOptions = () => {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(readOptions()))
    } catch {
      // ignore storage errors
    }
  }

  const loadOptions = () => {
    let saved = null
    try {
      saved = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || 'null')
    } catch {
      saved = null
    }
    if (!saved || typeof saved !== 'object') return
    if (granularitySelect && saved.granularity) granularitySelect.value = saved.granularity
    if (viewSelect && saved.view) viewSelect.value = saved.view
    if (optIgnoreCase) optIgnoreCase.checked = Boolean(saved.ignoreCase)
    if (optIgnoreWs) optIgnoreWs.checked = Boolean(saved.ignoreWhitespace)
    if (optTrim) optTrim.checked = Boolean(saved.trim)
    if (optIgnoreEmpty) optIgnoreEmpty.checked = Boolean(saved.ignoreEmptyLines)
    if (optSort) optSort.checked = Boolean(saved.sortLines)
    if (optOnlyChanges) optOnlyChanges.checked = Boolean(saved.onlyChanges)
  }

  const setFeedback = (message, isError = false) => {
    if (!feedback) return
    feedback.textContent = message
    feedback.classList.toggle('error', isError)
  }

  const updateCounts = () => {
    const countLines = (val) => (val === '' ? 0 : val.split('\n').length)
    const label = t('diffLinesLabel', 'lines')
    if (countA) countA.textContent = `${countLines(inputA.value)} ${label}`
    if (countB) countB.textContent = `${countLines(inputB.value)} ${label}`
  }

  const renderSegments = (segments, side) => {
    if (!segments) return esc(side === 'left' ? '' : '')
    return segments.map((seg) => {
      const text = esc(seg.text)
      if (seg.type === 'removed') return `<mark class="diff__chunk diff__chunk--del">${text}</mark>`
      if (seg.type === 'added') return `<mark class="diff__chunk diff__chunk--ins">${text}</mark>`
      return text
    }).join('')
  }

  const cellContent = (row, side) => {
    if (side === 'left') {
      if (row.left == null) return ''
      return row.leftSeg ? renderSegments(row.leftSeg, 'left') : esc(row.left)
    }
    if (row.right == null) return ''
    return row.rightSeg ? renderSegments(row.rightSeg, 'right') : esc(row.right)
  }

  // Сворачивает длинные участки одинаковых строк в раскрываемую «полосу».
  const collapseRows = (rows, onlyChanges) => {
    const groups = []
    let run = []
    const flush = (isLast) => {
      if (run.length === 0) return
      const isFirst = groups.length === 0
      const head = onlyChanges ? 0 : (isFirst ? 0 : CONTEXT)
      const tail = onlyChanges ? 0 : (isLast ? 0 : CONTEXT)
      if (run.length > head + tail + 1) {
        for (let i = 0; i < head; i++) groups.push({ kind: 'row', row: run[i] })
        groups.push({ kind: 'gap', count: run.length - head - tail })
        for (let i = run.length - tail; i < run.length; i++) groups.push({ kind: 'row', row: run[i] })
      } else {
        run.forEach((row) => groups.push({ kind: 'row', row }))
      }
      run = []
    }

    rows.forEach((row) => {
      if (row.type === 'equal') {
        run.push(row)
      } else {
        flush(false)
        groups.push({ kind: 'row', row })
      }
    })
    flush(true)
    return groups
  }

  const rowClass = (type) => `diff__row diff__row--${type}`

  const marker = (type, side) => {
    if (type === 'modified') return side === 'left' ? '-' : '+'
    if (type === 'delete') return '-'
    if (type === 'insert') return '+'
    return ''
  }

  const renderSplit = (groups) => {
    const parts = ['<table class="diff__table diff__table--split"><tbody>']
    groups.forEach((g) => {
      if (g.kind === 'gap') {
        parts.push(`<tr class="diff__gap"><td colspan="4">${gapLabel(g.count)}</td></tr>`)
        return
      }
      const row = g.row
      const anchorAttr = row.type !== 'equal' ? ' data-diff-anchor="1"' : ''
      parts.push(`<tr class="${rowClass(row.type)}"${anchorAttr}>`
        + `<td class="diff__ln">${row.leftNo ?? ''}</td>`
        + `<td class="diff__cell diff__cell--left"><span class="diff__sign">${marker(row.type, 'left')}</span><span class="diff__code">${cellContent(row, 'left')}</span></td>`
        + `<td class="diff__ln">${row.rightNo ?? ''}</td>`
        + `<td class="diff__cell diff__cell--right"><span class="diff__sign">${marker(row.type, 'right')}</span><span class="diff__code">${cellContent(row, 'right')}</span></td>`
        + '</tr>')
    })
    parts.push('</tbody></table>')
    return parts.join('')
  }

  const renderInline = (groups) => {
    const parts = ['<table class="diff__table diff__table--inline"><tbody>']
    groups.forEach((g) => {
      if (g.kind === 'gap') {
        parts.push(`<tr class="diff__gap"><td colspan="3">${gapLabel(g.count)}</td></tr>`)
        return
      }
      const row = g.row
      const anchorAttr = row.type !== 'equal' ? ' data-diff-anchor="1"' : ''
      if (row.type === 'modified') {
        parts.push(`<tr class="diff__row diff__row--delete" data-diff-anchor="1">`
          + `<td class="diff__ln">${row.leftNo ?? ''}</td><td class="diff__ln"></td>`
          + `<td class="diff__cell diff__cell--left"><span class="diff__sign">-</span><span class="diff__code">${cellContent(row, 'left')}</span></td></tr>`)
        parts.push(`<tr class="diff__row diff__row--insert">`
          + `<td class="diff__ln"></td><td class="diff__ln">${row.rightNo ?? ''}</td>`
          + `<td class="diff__cell diff__cell--right"><span class="diff__sign">+</span><span class="diff__code">${cellContent(row, 'right')}</span></td></tr>`)
        return
      }
      const side = row.type === 'insert' ? 'right' : 'left'
      parts.push(`<tr class="${rowClass(row.type)}"${anchorAttr}>`
        + `<td class="diff__ln">${row.leftNo ?? ''}</td>`
        + `<td class="diff__ln">${row.rightNo ?? ''}</td>`
        + `<td class="diff__cell"><span class="diff__sign">${marker(row.type, side)}</span><span class="diff__code">${cellContent(row, side)}</span></td>`
        + '</tr>')
    })
    parts.push('</tbody></table>')
    return parts.join('')
  }

  const gapLabel = (count) => {
    const template = t('diffUnchangedGap', ':count unchanged lines')
    return esc(template.replace(':count', String(count)))
  }

  const pct = (value) => `${Math.round(value * 100)}%`

  const renderStats = (stats) => {
    if (!statsEl) return
    const items = [
      { cls: 'add', label: t('diffStatAdded', 'added'), value: stats.linesAdded },
      { cls: 'del', label: t('diffStatRemoved', 'removed'), value: stats.linesRemoved },
      { cls: 'mod', label: t('diffStatModified', 'modified'), value: stats.linesModified },
    ]
    const chips = items.map((it) =>
      `<span class="diff__stat diff__stat--${it.cls}"><b>${it.value}</b> ${esc(it.label)}</span>`).join('')

    const simPct = pct(stats.similarity)
    const meter = `<div class="diff__similarity" title="${esc(t('diffSimilarity', 'Similarity'))}">`
      + `<span class="diff__similarity-label">${esc(t('diffSimilarity', 'Similarity'))}</span>`
      + `<span class="diff__meter"><span class="diff__meter-fill" style="width:${simPct}"></span></span>`
      + `<b class="diff__similarity-val">${simPct}</b></div>`

    statsEl.innerHTML = chips + meter
  }

  const collectAnchors = () => {
    anchors = Array.from(result.querySelectorAll('[data-diff-anchor="1"]'))
    // Соседние строки одного изменения (modified в inline) считаем за один якорь по группам.
    currentAnchor = -1
    updateHunkPos()
  }

  const updateHunkPos = () => {
    if (!hunkPos) return
    const total = anchors.length
    if (nav) nav.hidden = total === 0
    if (total === 0) {
      hunkPos.textContent = ''
      return
    }
    const pos = currentAnchor >= 0 ? currentAnchor + 1 : 0
    const template = t('diffChangePos', ':pos / :total')
    hunkPos.textContent = template.replace(':pos', String(pos)).replace(':total', String(total))
  }

  const gotoAnchor = (step) => {
    if (anchors.length === 0) return
    currentAnchor = (currentAnchor + step + anchors.length) % anchors.length
    const el = anchors[currentAnchor]
    result.querySelectorAll('.diff__row--focus').forEach((r) => r.classList.remove('diff__row--focus'))
    el.classList.add('diff__row--focus')
    el.scrollIntoView({ block: 'center', behavior: 'smooth' })
    updateHunkPos()
  }

  const buildUnified = (rows) => {
    const lines = []
    rows.forEach((row) => {
      if (row.type === 'equal') lines.push('  ' + row.left)
      else if (row.type === 'delete') lines.push('- ' + row.left)
      else if (row.type === 'insert') lines.push('+ ' + row.right)
      else {
        lines.push('- ' + row.left)
        lines.push('+ ' + row.right)
      }
    })
    return lines.join('\n')
  }

  let lastRows = []

  const compute = () => {
    updateCounts()
    const a = inputA.value
    const b = inputB.value
    const opts = readOptions()

    if (a === '' && b === '') {
      lastRows = []
      statusbar.hidden = true
      anchors = []
      updateHunkPos()
      result.innerHTML = `<p class="diff__empty">${esc(t('diffEmptyLabel', 'Enter text in both panels to compare'))}</p>`
      return
    }

    const { rows, stats } = diffText(a, b, opts)
    lastRows = rows

    renderStats(stats)
    statusbar.hidden = false

    if (stats.identical) {
      result.innerHTML = `<p class="diff__identical">✓ ${esc(t('diffIdentical', 'The two texts are identical'))}</p>`
      anchors = []
      updateHunkPos()
      return
    }

    const groups = collapseRows(rows, opts.onlyChanges)
    result.innerHTML = opts.view === 'inline' ? renderInline(groups) : renderSplit(groups)
    collectAnchors()
  }

  const scheduleCompute = () => {
    window.clearTimeout(debounceTimer)
    debounceTimer = window.setTimeout(compute, 120)
  }

  // --- Обработчики ---

  inputA.addEventListener('input', scheduleCompute)
  inputB.addEventListener('input', scheduleCompute)

  ;[granularitySelect, viewSelect].forEach((el) => el?.addEventListener('change', () => {
    saveOptions()
    compute()
  }))

  ;[optIgnoreCase, optIgnoreWs, optTrim, optIgnoreEmpty, optSort, optOnlyChanges].forEach((el) =>
    el?.addEventListener('change', () => {
      saveOptions()
      compute()
    }))

  // Кнопки примеров: быстрые чипы в тулбаре + карточки в блоке примеров ниже по странице.
  document.querySelectorAll('.diff-example').forEach((chip) => {
    chip.addEventListener('click', () => {
      inputA.value = chip.getAttribute('data-diff-a') || ''
      inputB.value = chip.getAttribute('data-diff-b') || ''
      compute()
      if (!root.contains(chip)) {
        root.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
    })
  })

  swapBtn?.addEventListener('click', () => {
    const tmp = inputA.value
    inputA.value = inputB.value
    inputB.value = tmp
    compute()
  })

  clearBtn?.addEventListener('click', () => {
    inputA.value = ''
    inputB.value = ''
    setFeedback('')
    compute()
    inputA.focus()
  })

  copyBtn?.addEventListener('click', async () => {
    if (lastRows.length === 0) return
    try {
      await navigator.clipboard.writeText(buildUnified(lastRows))
      setFeedback(t('diffCopied', 'Diff copied to clipboard'))
    } catch {
      setFeedback(t('diffCopyFailed', 'Unable to copy'), true)
    }
  })

  shareBtn?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(window.location.href)
      setFeedback(t('feedbackUrlCopied', 'Page URL copied.'))
    } catch {
      setFeedback(t('feedbackUrlCopyFailed', 'Unable to copy page URL.'), true)
    }
  })

  prevBtn?.addEventListener('click', () => gotoAnchor(-1))
  nextBtn?.addEventListener('click', () => gotoAnchor(1))

  loadOptions()
  initCustomSelects()
  compute()
}
