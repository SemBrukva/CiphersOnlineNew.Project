/**
 * Инициализирует инструмент brute-force расшифровки.
 * Скрывает вкладку decode, показывает пустое состояние.
 *
 * Для Caesar (ui.affineMode === false): плоская таблица всех 26 сдвигов.
 * Для Affine (ui.affineMode === true):  карточка лучшего варианта + список
 * топ-N кандидатов с возможностью переключить активный (как у vigenere-cracker).
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
  const isAffine = Boolean(ui.affineMode)
  const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

  let activeCandidates = []
  let activeIndex      = -1

  const showEmpty = () => {
    if (!visualOutput) return
    visualOutput.innerHTML = `<p class="freq-empty">${esc(ui.bruteEmptyLabel || 'Enter ciphertext to see all possible decryptions')}</p>`
    activeCandidates = []
    activeIndex      = -1
  }

  const copyToClipboard = (text) => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text)
        .then(() => setFeedback(labels.copied))
        .catch(() => setFeedback(labels.copyFailed, true))
      return
    }
    const ta = document.createElement('textarea')
    ta.value = text
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
    setFeedback(labels.copied)
  }

  // ── Caesar: плоская таблица сдвигов ────────────────────────────────────
  const renderCaesarTable = (results, bestShift, reliable) => {
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

    const rowsHtml = results.map((row) => {
      const { text, fitness, shift } = row
      const isBest  = (shift === bestShift)
      const pct     = typeof fitness === 'number' ? fitness : 0
      const rowCls  = isBest ? 'brute-row brute-row--best' : 'brute-row'
      const badge   = isBest ? `<span class="brute-best-badge">${bestBadge}</span>` : ''
      const barHtml = `<div class="brute-fitness-wrap">`
        + `<div class="brute-fitness-bar" style="width:${pct}%"></div>`
        + `<span class="brute-fitness-pct">${pct}%</span>`
        + `</div>`
      return `<div class="${rowCls}">`
        + `<span class="brute-shift">${esc(String(shift))}</span>`
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
        copyToClipboard(btn.getAttribute('data-brute-text') || '')
      })
    })
  }

  // ── Affine: карточка лучшего + компактный список топ-N ────────────────
  const applyActiveAffine = (idx) => {
    if (!visualOutput) return
    const cand = activeCandidates[idx]
    if (!cand) return

    activeIndex = idx

    const keyEl  = visualOutput.querySelector('.vc-summary-key')
    const textEl = visualOutput.querySelector('.vc-decrypted-text')
    if (keyEl)  keyEl.textContent  = `a=${cand.multiplier}, b=${cand.shift}`
    if (textEl) textEl.textContent = cand.text

    output.value = cand.text

    visualOutput.querySelectorAll('.vc-row').forEach((row) => {
      const rowIdx = Number(row.getAttribute('data-vc-idx'))
      row.classList.toggle('vc-row--active', rowIdx === idx)
    })
  }

  const renderAffine = (response) => {
    if (!visualOutput) return

    const candidates = Array.isArray(response?.results) ? response.results : []
    const reliable   = response?.reliable !== false

    if (candidates.length === 0) {
      showEmpty()
      return
    }

    activeCandidates = candidates
    activeIndex      = 0
    const best       = candidates[0]

    const title       = esc(ui.bruteTitle          || 'All possible decryptions')
    const colKey      = esc(ui.bruteColShift       || 'Key (a, b)')
    const colFitness  = esc(ui.bruteFitnessLabel   || 'Confidence')
    const colText     = esc(ui.bruteColText        || 'Decrypted text')
    const viewLabel   = esc(ui.bruteUseLabel       || 'View')
    const bestBadge   = esc(ui.bruteBestBadge      || 'Best')
    const likelyTpl   = String(ui.bruteLikelyKey   || 'Most likely key: a=:a, b=:b')
    const shortWarn   = esc(ui.bruteShortText      || 'Short text — add more characters for a reliable result')

    const likelyText  = esc(
      likelyTpl
        .replace(':a', String(best.multiplier ?? ''))
        .replace(':b', String(best.shift ?? ''))
        .replace(':shift', String(best.shift ?? ''))
    )

    const summaryHtml = `<div class="brute-summary">`
      + `<span class="brute-summary-icon">★</span>`
      + `<span class="vc-summary-key-wrap">${likelyText}</span>`
      + `</div>`
      + (reliable ? '' : `<div class="brute-short-text-warn">${shortWarn}</div>`)

    const decryptedHtml = `<div class="vc-decrypted-block">`
      + `<div class="vc-decrypted-label">${colText}</div>`
      + `<div class="vc-decrypted-text">${esc(best.text)}</div>`
      + `</div>`

    const headerHtml = `<div class="brute-header"><span class="brute-title">${title}</span></div>`

    const tableHeaderHtml = `<div class="brute-table-header vc-table-header">`
      + `<span>#</span>`
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
        + `<span class="vc-col-len"><span class="vc-col-len__num">${idx + 1}</span>${badge}</span>`
        + `<span class="vc-col-key"><span class="vc-col-key__text">a=${esc(c.multiplier)}, b=${esc(c.shift)}</span></span>`
        + `${barHtml}`
        + `<button class="brute-use-btn" type="button" data-vc-idx="${idx}">${viewLabel}</button>`
        + `</div>`
    }).join('')

    const tableHtml = headerHtml + tableHeaderHtml + `<div class="brute-rows">${rowsHtml}</div>`

    visualOutput.innerHTML = summaryHtml + decryptedHtml + tableHtml

    visualOutput.querySelectorAll('button.brute-use-btn[data-vc-idx]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation()
        applyActiveAffine(Number(btn.getAttribute('data-vc-idx')))
      })
    })

    visualOutput.querySelectorAll('.vc-row').forEach((row) => {
      row.addEventListener('click', () => {
        applyActiveAffine(Number(row.getAttribute('data-vc-idx')))
      })
    })
  }

  const handleApiResponse = (response, alphabetSelect) => {
    const detectedAlpha = response?.detected_alphabet
    if (detectedAlpha && alphabetSelect && alphabetSelect.value !== detectedAlpha) {
      alphabetSelect.value = detectedAlpha
    }
    if (isAffine) {
      if (response?.decrypted) output.value = response.decrypted
      renderAffine(response)
      setOutputState(Boolean(response?.results?.length))
    } else {
      renderCaesarTable(response?.results ?? [], response?.best_shift, response?.reliable)
      setOutputState(Boolean(response?.results?.length))
    }
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
