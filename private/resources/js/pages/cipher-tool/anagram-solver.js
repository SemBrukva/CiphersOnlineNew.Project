/**
 * Инициализирует инструмент поиска анаграмм.
 *
 * Скрывает стандартный output, показывает таблицу слов (или фраз для multi-word)
 * и панель «advanced» с фильтрами длины, префикса, суффикса и подстроки.
 *
 * @param {{
 *   output: HTMLTextAreaElement,
 *   visualOutput: HTMLElement,
 *   tabDecode: HTMLElement,
 *   ui: Record<string, any>,
 *   setFeedback: (msg: string, isError?: boolean) => void,
 *   setOutputState: (hasContent: boolean) => void,
 *   onChange: () => void,
 * }} ctx
 * @return {{
 *   showEmpty: () => void,
 *   handleApiResponse: (response: object) => void,
 *   collectSettings: () => Record<string, string|number>,
 * }}
 */
export function initAnagramSolver({
  output, visualOutput, tabDecode, ui,
  setFeedback, setOutputState, onChange,
}) {
  const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

  // ── Advanced-панель: поля фильтров ──────────────────────────────────────
  const advancedHtml = `
    <details class="anagram-advanced">
      <summary class="anagram-advanced__summary">${esc(ui.anagramAdvancedLabel || 'Advanced filters')}</summary>
      <div class="anagram-advanced__grid">
        <label class="anagram-advanced__field">
          <span>${esc(ui.anagramMinLengthLabel || 'Min length')}</span>
          <input type="number" min="1" max="32" data-anagram-field="min_length" value="2">
        </label>
        <label class="anagram-advanced__field">
          <span>${esc(ui.anagramMaxLengthLabel || 'Max length')}</span>
          <input type="number" min="0" max="32" data-anagram-field="max_length" value="0" placeholder="${esc(ui.anagramAnyLabel || 'any')}">
        </label>
        <label class="anagram-advanced__field">
          <span>${esc(ui.anagramStartsWithLabel || 'Starts with')}</span>
          <input type="text" maxlength="8" data-anagram-field="starts_with" placeholder="">
        </label>
        <label class="anagram-advanced__field">
          <span>${esc(ui.anagramEndsWithLabel || 'Ends with')}</span>
          <input type="text" maxlength="8" data-anagram-field="ends_with" placeholder="">
        </label>
        <label class="anagram-advanced__field">
          <span>${esc(ui.anagramContainsLabel || 'Contains')}</span>
          <input type="text" maxlength="8" data-anagram-field="contains" placeholder="">
        </label>
        <label class="anagram-advanced__field">
          <span>${esc(ui.anagramMaxResultsLabel || 'Max results')}</span>
          <input type="number" min="1" max="500" data-anagram-field="max_results" value="200">
        </label>
        <label class="anagram-advanced__field anagram-advanced__field--multi-only">
          <span>${esc(ui.anagramMaxWordsLabel || 'Max words')}</span>
          <select data-anagram-field="max_words">
            <option value="2" selected>2</option>
            <option value="3">3</option>
          </select>
        </label>
        <label class="anagram-advanced__field">
          <span>${esc(ui.anagramSortLabel || 'Sort')}</span>
          <select data-anagram-field="sort">
            <option value="length" selected>${esc(ui.anagramSortLength || 'Length')}</option>
            <option value="score">${esc(ui.anagramSortScore || 'Scrabble score')}</option>
            <option value="alpha">${esc(ui.anagramSortAlpha || 'Alphabetical')}</option>
          </select>
        </label>
      </div>
    </details>
  `

  const resultsContainer = document.createElement('div')
  resultsContainer.className = 'anagram-results'

  const showEmpty = () => {
    if (!visualOutput) return
    visualOutput.innerHTML = advancedHtml
    visualOutput.appendChild(resultsContainer)
    resultsContainer.innerHTML = `<p class="freq-empty">${esc(ui.anagramEmptyLabel || 'Enter letters or a pattern to find anagrams')}</p>`
    output.value = ''
    attachListeners()
  }

  const attachListeners = () => {
    visualOutput.querySelectorAll('[data-anagram-field]').forEach((el) => {
      el.addEventListener('change', () => onChange())
      el.addEventListener('input', () => onChange())
    })
  }

  const renderTable = (response) => {
    if (!resultsContainer) return

    const mode    = String(response?.mode || 'anagram')
    const lang    = String(response?.language || 'en')
    const items   = Array.isArray(response?.results) ? response.results : []
    const phrases = Array.isArray(response?.phrases) ? response.phrases : []

    if (mode === 'multi-word') {
      if (phrases.length === 0) {
        resultsContainer.innerHTML = `<p class="freq-empty">${esc(ui.anagramNoMatchesLabel || 'No matching phrases')}</p>`
        output.value = ''
        return
      }
      const headerHtml = `
        <div class="anagram-summary">
          <span class="anagram-summary__count">${esc(String(response.totalFound ?? phrases.length))} ${esc(ui.anagramFoundLabel || 'phrases')}</span>
          ${response.truncated ? `<span class="anagram-summary__truncated">${esc(ui.anagramTruncatedLabel || 'truncated')}</span>` : ''}
        </div>`
      const rowsHtml = phrases.map((p) => {
        const text  = (p.words ?? []).join(' ')
        const score = Number(p.score ?? 0)
        const length = Number(p.length ?? text.replace(/\s+/g, '').length)
        return `<div class="anagram-row">
          <span class="anagram-row__word">${esc(text)}</span>
          <span class="anagram-row__meta">${length} · ${score}</span>
          <button class="anagram-row__copy" type="button" data-anagram-copy="${esc(text)}">${esc(ui.anagramCopyLabel || 'Copy')}</button>
        </div>`
      }).join('')

      resultsContainer.innerHTML = headerHtml + `<div class="anagram-rows">${rowsHtml}</div>`
      output.value = phrases[0]?.words?.join(' ') ?? ''
      bindCopyButtons()
      return
    }

    if (items.length === 0) {
      resultsContainer.innerHTML = `<p class="freq-empty">${esc(ui.anagramNoMatchesLabel || 'No matching words')}</p>`
      output.value = ''
      return
    }

    const headerHtml = `
      <div class="anagram-summary">
        <span class="anagram-summary__count">${esc(String(response.totalFound ?? items.length))} ${esc(ui.anagramFoundLabel || 'words')} · ${esc(lang)}</span>
        ${response.truncated ? `<span class="anagram-summary__truncated">${esc(ui.anagramTruncatedLabel || 'truncated')}</span>` : ''}
      </div>`

    const rowsHtml = items.map((row) => {
      const word   = String(row.word ?? '')
      const length = Number(row.length ?? word.length)
      const score  = Number(row.score ?? 0)
      return `<div class="anagram-row">
        <span class="anagram-row__word">${esc(word)}</span>
        <span class="anagram-row__meta">${length} · ${score}</span>
        <button class="anagram-row__copy" type="button" data-anagram-copy="${esc(word)}">${esc(ui.anagramCopyLabel || 'Copy')}</button>
      </div>`
    }).join('')

    resultsContainer.innerHTML = headerHtml + `<div class="anagram-rows">${rowsHtml}</div>`
    output.value = items[0]?.word ?? ''
    bindCopyButtons()
  }

  const bindCopyButtons = () => {
    resultsContainer.querySelectorAll('[data-anagram-copy]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const text = btn.getAttribute('data-anagram-copy') || ''
        if (navigator.clipboard) {
          navigator.clipboard.writeText(text).catch(() => {})
        }
      })
    })
  }

  const handleApiResponse = (response) => {
    renderTable(response)
    const hasContent = (response?.results?.length ?? 0) > 0 || (response?.phrases?.length ?? 0) > 0
    setOutputState(hasContent)
    const warning = String(response?.warning || '')
    if (warning) {
      setFeedback(warning, true)
    } else {
      setFeedback('')
    }
  }

  const collectSettings = () => {
    const fields = visualOutput.querySelectorAll('[data-anagram-field]')
    const result = {}
    fields.forEach((el) => {
      const key = el.getAttribute('data-anagram-field')
      if (!key) return
      const value = el.value
      if (value === '' || value === null) return
      result[key] = value
    })
    return result
  }

  // Спрятать стандартные элементы decode и обычный output.
  tabDecode.style.display = 'none'
  output.style.display    = 'none'
  if (visualOutput) {
    visualOutput.style.display = 'block'
    showEmpty()
  }

  return { showEmpty, handleApiResponse, collectSettings }
}
