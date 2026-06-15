/**
 * Инициализирует инструмент brute-force расшифровки.
 * Скрывает вкладку decode, показывает пустое состояние.
 * Возвращает { showEmpty, handleApiResponse }.
 *
 * @param {{
 *   output: HTMLTextAreaElement,
 *   visualOutput: HTMLElement,
 *   tabDecode: HTMLElement,
 *   ui: Record<string, string>,
 *   labels: Record<string, string>,
 *   setFeedback: (msg: string, isError?: boolean) => void,
 *   setOutputState: (hasContent: boolean) => void,
 * }} ctx
 * @return {{
 *   showEmpty: () => void,
 *   handleApiResponse: (response: object, alphabetSelect: HTMLSelectElement|null) => void,
 * }}
 */
export function initBruteForce({
  output, visualOutput, tabDecode,
  ui, labels,
  setFeedback, setOutputState,
}) {
  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

  const showEmpty = () => {
    if (!visualOutput) return
    visualOutput.innerHTML = `<p class="freq-empty">${esc(ui.bruteEmptyLabel || 'Enter ciphertext to see all possible decryptions')}</p>`
  }

  const renderTable = (results, bestShift, reliable) => {
    if (!visualOutput) return
    if (!results || results.length === 0) {
      showEmpty()
      return
    }

    const title        = esc(ui.bruteTitle        || 'All possible decryptions')
    const colShift     = esc(ui.bruteColShift     || 'Shift')
    const colText      = esc(ui.bruteColText      || 'Decrypted text')
    const useLabel     = esc(ui.bruteUseLabel     || 'Use')
    const bestBadge    = esc(ui.bruteBestBadge    || 'Best')
    const fitLabel     = esc(ui.bruteFitnessLabel || 'Confidence')
    const likelyKeyTpl = String(ui.bruteLikelyKey || 'Most likely key: Shift :shift')
    const likelyKeyText = esc(likelyKeyTpl.replace(':shift', String(bestShift ?? '')))

    const shortTextWarning = reliable === false
      ? `<div class="brute-short-text-warn">${esc(ui.bruteShortText || 'Short text — add more characters for a reliable prediction')}</div>`
      : ''
    const summaryHtml = bestShift !== undefined
      ? `<div class="brute-summary"><span class="brute-summary-icon">★</span>${likelyKeyText}</div>${shortTextWarning}`
      : shortTextWarning

    const headerHtml = `<div class="brute-header">`
      + `<span class="brute-title">${title}</span>`
      + `<span class="brute-fitness-col-label">${fitLabel}</span>`
      + `</div>`

    const rowsHtml = results.map(({ shift, text, fitness }) => {
      const isBest  = shift === bestShift
      const pct     = typeof fitness === 'number' ? fitness : 0
      const rowCls  = isBest ? 'brute-row brute-row--best' : 'brute-row'
      const badge   = isBest ? `<span class="brute-best-badge">${bestBadge}</span>` : ''
      const barHtml = `<div class="brute-fitness-wrap">`
        + `<div class="brute-fitness-bar" style="width:${pct}%"></div>`
        + `<span class="brute-fitness-pct">${pct}%</span>`
        + `</div>`
      return `<div class="${rowCls}">`
        + `<span class="brute-shift">${shift}</span>`
        + `<span class="brute-text">${esc(text)}${badge}</span>`
        + `${barHtml}`
        + `<button class="brute-use-btn" data-brute-text="${esc(text)}">${useLabel}</button>`
        + `</div>`
    }).join('')

    const tableHtml = `<div class="brute-table-header">`
      + `<span>${colShift}</span>`
      + `<span>${colText}</span>`
      + `<span>${fitLabel}</span>`
      + `<span></span>`
      + `</div>`
      + `<div class="brute-rows">${rowsHtml}</div>`

    visualOutput.innerHTML = summaryHtml + headerHtml + tableHtml

    if (bestShift !== undefined) {
      visualOutput.querySelector('.brute-row--best')?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
    }

    visualOutput.querySelectorAll('.brute-use-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const text = btn.getAttribute('data-brute-text') || ''
        if (navigator.clipboard) {
          navigator.clipboard
            .writeText(text)
            .then(() => setFeedback(labels.copied))
            .catch(() => setFeedback(labels.copyFailed, true))
        } else {
          const ta = document.createElement('textarea')
          ta.value = text
          document.body.appendChild(ta)
          ta.select()
          document.execCommand('copy')
          document.body.removeChild(ta)
          setFeedback(labels.copied)
        }
      })
    })
  }

  const handleApiResponse = (response, alphabetSelect) => {
    const detectedAlpha = response?.detected_alphabet
    if (detectedAlpha && alphabetSelect && alphabetSelect.value !== detectedAlpha) {
      alphabetSelect.value = detectedAlpha
    }
    renderTable(response?.results ?? [], response?.best_shift, response?.reliable)
    setOutputState(Boolean(response?.results?.length))
    setFeedback('')
  }

  tabDecode.style.display = 'none'
  output.style.display    = 'none'
  if (visualOutput) {
    visualOutput.style.display = 'block'
    showEmpty()
  }

  return { showEmpty, handleApiResponse }
}
