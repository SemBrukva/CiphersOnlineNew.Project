/**
 * Инициализирует инструмент определения шифра.
 * Скрывает стандартный output, показывает таблицу кандидатов и карточку авторезультата.
 * Возвращает { showEmpty, handleApiResponse }.
 *
 * @param {{
 *   output: HTMLTextAreaElement,
 *   visualOutput: HTMLElement,
 *   tabDecode: HTMLElement,
 *   ui: Record<string, any>,
 *   input: HTMLTextAreaElement,
 *   setFeedback: (msg: string, isError?: boolean) => void,
 *   setOutputState: (hasContent: boolean) => void,
 * }} ctx
 * @return {{
 *   showEmpty: () => void,
 *   handleApiResponse: (response: object, alphabetSelect: HTMLSelectElement|null) => void,
 * }}
 */
export function initCipherIdentifier({
  output, visualOutput, tabDecode,
  ui, input,
  setFeedback, setOutputState,
}) {
  const esc    = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const t      = (key) => (ui.cidTranslations?.[key]) ?? key

  const saveCarryOver = (text) => {
    try {
      window.localStorage.setItem('ciphers:carry-over', JSON.stringify({
        text,
        sourceSlug: ui.toolSlug || '',
        expiresAt: Date.now() + 60_000,
      }))
    } catch {
      // ignore storage errors
    }
  }

  const localePrefix = () => {
    const meta = document.querySelector('meta[name="locale-prefix"]')
    if (meta) return meta.getAttribute('content') || ''
    const m = window.location.pathname.match(/^\/(en|ru|de|es|fr|it|pt|tr)\//)
    return m ? '/' + m[1] : ''
  }

  const showEmpty = () => {
    if (!visualOutput) return
    visualOutput.innerHTML = `<p class="freq-empty">${esc(ui.cidEmptyLabel || 'Enter text to identify the cipher')}</p>`
    output.value = ''
  }

  // Выдаёт расшифрованный текст из любого формата ответа brute-force/cracker.
  const extractDecryptedText = (autoAction, autoResult) => {
    if (!autoResult) return ''
    if (typeof autoResult.decrypted === 'string') return autoResult.decrypted
    if (autoAction === 'caesar-brute-force') {
      const best = Number(autoResult.best_shift ?? -1)
      const results = Array.isArray(autoResult.results) ? autoResult.results : []
      if (best >= 0 && results[best]) return String(results[best].text ?? '')
    }
    if (autoAction === 'affine-brute-force') {
      const results = Array.isArray(autoResult.results) ? autoResult.results : []
      if (results[0]) return String(results[0].text ?? '')
    }
    return ''
  }

  // Выдаёт краткую метку ключа (например, "shift=3" или "a=5, b=8" или "KEY").
  const extractKeyLabel = (autoAction, autoResult) => {
    if (!autoResult) return ''
    if (autoAction === 'vigenere-cracker') {
      return autoResult.key ? `key=${autoResult.key}` : ''
    }
    if (autoAction === 'caesar-brute-force') {
      const best = autoResult.best_shift
      return Number.isFinite(Number(best)) ? `shift=${best}` : ''
    }
    if (autoAction === 'affine-brute-force') {
      return typeof autoResult.key === 'string' ? autoResult.key : ''
    }
    return autoResult.key ? String(autoResult.key) : ''
  }

  const buildAutoResultCard = (autoAction, autoResult) => {
    if (!autoResult) return ''

    const title       = esc(ui.cidAutoResultTitle || 'Auto-detected result')
    const decrypted   = extractDecryptedText(autoAction, autoResult)
    const keyLabel    = extractKeyLabel(autoAction, autoResult)

    const keyHtml = keyLabel
      ? `<div class="cid-auto-card__key">${esc(keyLabel)}</div>`
      : ''

    const resultHtml = decrypted
      ? `<div class="cid-auto-result-text">${esc(decrypted)}</div>`
      : ''

    return `<div class="cid-auto-card">`
      + `<div class="cid-auto-card__title"><span class="brute-summary-icon">★</span>${title}</div>`
      + keyHtml
      + resultHtml
      + `</div>`
  }

  const buildCandidatesTable = (candidates) => {
    if (!Array.isArray(candidates) || candidates.length === 0) return ''

    const titleHtml = `<div class="brute-header">`
      + `<span class="brute-title">${esc(ui.cidCandidatesTitle || 'Cipher candidates')}</span>`
      + `</div>`

    const colCipher     = esc(ui.cidColCipher     || 'Cipher / Encoding')
    const colConfidence = esc(ui.cidColConfidence  || 'Confidence')
    const colEvidence   = esc(ui.cidColEvidence    || 'Evidence')
    const colAction     = esc(ui.cidColAction      || 'Action')
    const openTool      = esc(ui.cidOpenTool       || 'Open tool')

    const headerHtml = `<div class="cid-table-header">`
      + `<span>${colCipher}</span>`
      + `<span>${colConfidence}</span>`
      + `<span>${colEvidence}</span>`
      + `<span>${colAction}</span>`
      + `</div>`

    const lp = localePrefix()

    const rowsHtml = candidates.map((c, idx) => {
      const isBest    = idx === 0
      const cipherName = t(c.cipher_key) || c.cipher_key || c.tool_slug
      const pct       = c.confidence_pct ?? Math.round((c.confidence ?? 0) * 100)
      const cls       = ['cid-row', isBest ? 'cid-row--best' : ''].filter(Boolean).join(' ')

      const bar = `<div class="brute-fitness-wrap">`
        + `<div class="brute-fitness-bar" style="width:${pct}%"></div>`
        + `<span class="brute-fitness-pct">${pct}%</span>`
        + `</div>`

      const evidenceTags = (c.evidence_keys ?? []).map(
        (k) => `<span class="cid-ev-tag">${esc(t(k))}</span>`
      ).join('')

      const toolUrl = c.tool_slug ? `${lp}/${c.tool_slug}` : null
      const actionBtn = toolUrl
        ? `<a class="cid-open-btn" href="${esc(toolUrl)}">${openTool}</a>`
        : ''

      return `<div class="${cls}">`
        + `<span class="cid-col-name">${esc(cipherName)}</span>`
        + bar
        + `<span class="cid-col-evidence">${evidenceTags}</span>`
        + `<span class="cid-col-action">${actionBtn}</span>`
        + `</div>`
    }).join('')

    return titleHtml + headerHtml + `<div class="cid-rows">${rowsHtml}</div>`
  }

  const handleApiResponse = (response) => {
    if (!visualOutput) return

    const candidates = response?.candidates ?? []
    const autoAction = response?.auto_action ?? null
    const autoResult = response?.auto_result ?? null

    if (candidates.length === 0) {
      showEmpty()
      setOutputState(false)
      setFeedback('')
      return
    }

    const autoCard       = buildAutoResultCard(autoAction, autoResult)
    const candidatesHtml = buildCandidatesTable(candidates)
    visualOutput.innerHTML = autoCard + candidatesHtml

    const currentText = input ? input.value : ''
    visualOutput.querySelectorAll('.cid-open-btn').forEach((btn) => {
      btn.addEventListener('click', () => saveCarryOver(currentText))
    })

    const bestResult = extractDecryptedText(autoAction, autoResult)
    output.value = bestResult
    setOutputState(Boolean(bestResult))
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
