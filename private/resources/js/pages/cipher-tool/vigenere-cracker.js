/**
 * Инициализирует инструмент взлома шифра Виженера.
 * Скрывает вкладку decode и textarea, показывает визуальный вывод.
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
export function initVigenereCracker({
  output, visualOutput, tabDecode,
  ui, labels,
  setFeedback, setOutputState,
}) {
  const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

  let activeCandidates = []
  let activeIndex      = -1

  const showEmpty = () => {
    if (!visualOutput) return
    visualOutput.innerHTML = `<p class="freq-empty">${esc(ui.vcEmptyLabel || 'Enter ciphertext to crack the Vigenère cipher')}</p>`
    activeCandidates = []
    activeIndex      = -1
  }

  const applyActive = (idx) => {
    if (!visualOutput) return
    const candidate = activeCandidates[idx]
    if (!candidate) return

    activeIndex = idx

    const keyEl  = visualOutput.querySelector('.vc-summary-key')
    const textEl = visualOutput.querySelector('.vc-decrypted-text')
    if (keyEl)  keyEl.textContent  = candidate.key
    if (textEl) textEl.textContent = candidate.text

    output.value = candidate.text

    visualOutput.querySelectorAll('.vc-row').forEach((row) => {
      const rowIdx = Number(row.getAttribute('data-vc-idx'))
      row.classList.toggle('vc-row--active', rowIdx === idx)
    })
  }

  const renderResults = (response) => {
    if (!visualOutput) return

    const fallbackKey  = response?.key ?? ''
    const fallbackText = response?.decrypted ?? ''
    const candidates   = Array.isArray(response?.candidates) ? response.candidates : []
    const reliable     = response?.reliable !== false

    if (candidates.length === 0 && !fallbackKey) {
      showEmpty()
      return
    }

    activeCandidates = candidates.length > 0
      ? candidates
      : [{ key: fallbackKey, text: fallbackText, length: fallbackKey.length, ic: 0, fitness: 0 }]
    activeIndex = 0

    const initial = activeCandidates[0]

    const title       = esc(ui.vcTitle       || 'Crack results')
    const keyLabel    = esc(ui.vcKeyLabel     || 'Found key')
    const textLabel   = esc(ui.vcTextLabel    || 'Decrypted text')
    const colLen      = esc(ui.vcColLen       || 'Key length')
    const colKey      = esc(ui.vcColKey       || 'Key')
    const colFitness  = esc(ui.vcColFitness   || 'Confidence')
    const viewLabel   = esc(ui.vcViewLabel    || 'View')
    const bestBadge   = esc(ui.vcBestBadge    || 'Best')
    const shortWarn   = esc(ui.vcShortText    || 'Short text — add more characters for a reliable result')

    const summaryHtml = `<div class="brute-summary">`
      + `<span class="brute-summary-icon">★</span>`
      + `${keyLabel}: <strong class="vc-summary-key">${esc(initial.key)}</strong>`
      + `</div>`
      + (reliable ? '' : `<div class="brute-short-text-warn">${shortWarn}</div>`)

    const decryptedHtml = `<div class="vc-decrypted-block">`
      + `<div class="vc-decrypted-label">${textLabel}</div>`
      + `<div class="vc-decrypted-text">${esc(initial.text)}</div>`
      + `</div>`

    let tableHtml = ''
    if (candidates.length > 0) {
      const headerHtml = `<div class="brute-header">`
        + `<span class="brute-title">${title}</span>`
        + `</div>`

      const tableHeaderHtml = `<div class="brute-table-header vc-table-header">`
        + `<span>${colLen}</span>`
        + `<span>${colKey}</span>`
        + `<span>${colFitness}</span>`
        + `<span></span>`
        + `</div>`

      const rowsHtml = candidates.map((c, idx) => {
        const isBest  = idx === 0
        const pct     = typeof c.fitness === 'number' ? c.fitness : 0
        const rowCls  = [
          'brute-row',
          'vc-row',
          isBest ? 'brute-row--best' : '',
          idx === 0 ? 'vc-row--active' : '',
        ].filter(Boolean).join(' ')
        const badge   = isBest ? `<span class="brute-best-badge">${bestBadge}</span>` : ''
        const barHtml = `<div class="brute-fitness-wrap">`
          + `<div class="brute-fitness-bar" style="width:${pct}%"></div>`
          + `<span class="brute-fitness-pct">${pct}%</span>`
          + `</div>`
        return `<div class="${rowCls}" data-vc-idx="${idx}">`
          + `<span class="vc-col-len"><span class="vc-col-len__num">${esc(c.length)}</span>${badge}</span>`
          + `<span class="vc-col-key"><span class="vc-col-key__text">${esc(c.key)}</span><span class="vc-col-ic">IC ${esc(c.ic)}</span></span>`
          + `${barHtml}`
          + `<button class="brute-use-btn" type="button" data-vc-idx="${idx}">${viewLabel}</button>`
          + `</div>`
      }).join('')

      tableHtml = headerHtml + tableHeaderHtml + `<div class="brute-rows">${rowsHtml}</div>`
    }

    visualOutput.innerHTML = summaryHtml + decryptedHtml + tableHtml

    visualOutput.querySelectorAll('button.brute-use-btn[data-vc-idx]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation()
        applyActive(Number(btn.getAttribute('data-vc-idx')))
      })
    })
  }

  const handleApiResponse = (response, alphabetSelect) => {
    const detectedAlpha = response?.detected_alphabet
    if (detectedAlpha && alphabetSelect && alphabetSelect.value !== detectedAlpha) {
      alphabetSelect.value = detectedAlpha
    }

    if (response?.key) {
      output.value = response.decrypted ?? ''
    }

    renderResults(response)
    setOutputState(Boolean(response?.key))

    if (response?.warning) {
      setFeedback(response.warning, true)
    } else {
      setFeedback('')
    }
  }

  tabDecode.style.display = 'none'
  output.style.display    = 'none'
  if (visualOutput) {
    visualOutput.style.display = 'block'
    showEmpty()
  }

  return { showEmpty, handleApiResponse }
}
