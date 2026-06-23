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

  const buildAutoResultCard = (autoAction, autoResult, cipherName) => {
    if (!autoResult) return ''

    const title       = esc(ui.cidAutoResultTitle || 'Auto-detected result')
    const decrypted   = extractDecryptedText(autoAction, autoResult)
    const keyLabel    = extractKeyLabel(autoAction, autoResult)

    const cipherBadge = cipherName
      ? `<span class="cid-auto-card__cipher">${esc(cipherName)}</span>`
      : ''

    const keyBadge = keyLabel
      ? `<span class="cid-auto-card__key">${esc(keyLabel)}</span>`
      : ''

    const metaHtml = (cipherBadge || keyBadge)
      ? `<div class="cid-auto-card__meta">${cipherBadge}${keyBadge}</div>`
      : ''

    const resultHtml = decrypted
      ? `<div class="cid-auto-result-text">${esc(decrypted)}</div>`
      : ''

    return `<div class="cid-auto-card">`
      + `<div class="cid-auto-card__title"><span class="brute-summary-icon">★</span>${title}</div>`
      + metaHtml
      + resultHtml
      + `</div>`
  }

  const buildCandidatesTable = (candidates, suppressCrackForAction) => {
    if (!Array.isArray(candidates) || candidates.length === 0) return ''

    const titleHtml = `<div class="brute-header">`
      + `<span class="brute-title">${esc(ui.cidCandidatesTitle || 'Cipher candidates')}</span>`
      + `</div>`

    const colCipher     = esc(ui.cidColCipher     || 'Cipher / Encoding')
    const colConfidence = esc(ui.cidColConfidence  || 'Confidence')
    const colEvidence   = esc(ui.cidColEvidence    || 'Evidence')
    const colAction     = esc(ui.cidColAction      || 'Action')
    const openTool      = esc(ui.cidOpenTool       || 'Open tool')
    const crackLabel    = esc(ui.cidCrackBtn       || 'Crack')

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
      const openBtn = toolUrl
        ? `<a class="cid-open-btn" href="${esc(toolUrl)}">${openTool}</a>`
        : ''

      const action = c.brute_force_action || ''
      const showCrack = action && action !== suppressCrackForAction
      const crackBtn = showCrack
        ? `<button type="button" class="cid-crack-btn" data-crack-action="${esc(action)}" data-alphabet="${esc(c.detected_alphabet ?? '')}">${crackLabel}</button>`
        : ''

      const rowHtml = `<div class="${cls}" data-row-action="${esc(action)}">`
        + `<span class="cid-col-name">${esc(cipherName)}</span>`
        + bar
        + `<span class="cid-col-evidence">${evidenceTags}</span>`
        + `<span class="cid-col-action">${crackBtn}${openBtn}</span>`
        + `</div>`

      const slotHtml = `<div class="cid-crack-slot" data-slot-for="${esc(action)}"></div>`
      return rowHtml + slotHtml
    }).join('')

    return titleHtml + headerHtml + `<div class="cid-rows">${rowsHtml}</div>`
  }

  // Извлекает читаемую метку ключа из ответа cracker'а (используется в
  // инлайн-блоке cid-crack-result).
  const formatKeyForResult = (action, result) => extractKeyLabel(action, result)

  const buildCrackResultHtml = (action, result) => {
    const decrypted = extractDecryptedText(action, result)
    const keyLabel  = formatKeyForResult(action, result)
    const keyTitle  = esc(ui.cidCrackKey || 'Key')

    const keyHtml = keyLabel
      ? `<div class="cid-crack-result__key"><span class="cid-crack-result__key-label">${keyTitle}:</span> ${esc(keyLabel)}</div>`
      : ''
    const textHtml = decrypted
      ? `<div class="cid-crack-result__text">${esc(decrypted)}</div>`
      : `<div class="cid-crack-result__text cid-crack-result__text--empty">—</div>`

    return `<div class="cid-crack-result">${keyHtml}${textHtml}</div>`
  }

  // Запускает cracker по клику и инлайн-рендерит результат в слот под строкой.
  const attachCrackHandlers = (currentText) => {
    visualOutput.querySelectorAll('.cid-crack-btn').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const action   = btn.getAttribute('data-crack-action') || ''
        const alphabet = btn.getAttribute('data-alphabet') || 'auto'
        const slot     = visualOutput.querySelector(`.cid-crack-slot[data-slot-for="${CSS.escape(action)}"]`)
        if (!action || !slot) return
        if (!window.api?.guest?.[action]) {
          slot.innerHTML = `<div class="cid-crack-result cid-crack-result--error">${esc(ui.cidCrackFailed || 'Failed to crack the text')}</div>`
          return
        }

        const originalLabel = btn.textContent
        btn.disabled = true
        btn.classList.add('cid-crack-btn--loading')
        btn.textContent = ui.cidCrackRunning || 'Cracking…'
        slot.innerHTML = `<div class="cid-crack-result cid-crack-result--loading">${esc(ui.cidCrackRunning || 'Cracking…')}</div>`

        try {
          const response = await window.api.guest[action]({
            text: currentText,
            settings: { alphabet: alphabet || 'auto' },
          })
          slot.innerHTML = buildCrackResultHtml(action, response)
        } catch {
          slot.innerHTML = `<div class="cid-crack-result cid-crack-result--error">${esc(ui.cidCrackFailed || 'Failed to crack the text')}</div>`
        } finally {
          btn.disabled = false
          btn.classList.remove('cid-crack-btn--loading')
          btn.textContent = originalLabel
        }
      })
    })
  }

  const handleApiResponse = (response) => {
    if (!visualOutput) return

    const candidates = response?.candidates ?? []
    const autoAction = response?.auto_action ?? null
    const autoResult = response?.auto_result ?? null

    if (candidates.length === 0) {
      showEmpty()
      setOutputState(false)
      setFeedback(ui.cidNoCandidatesMsg || 'No cipher types could be identified. Try entering more text.', false)
      return
    }

    const autoCandidate  = autoAction
      ? (candidates.find((c) => c.brute_force_action === autoAction) ?? candidates[0])
      : candidates[0]
    const autoCipherName = autoResult && autoCandidate
      ? (t(autoCandidate.cipher_key) || autoCandidate.cipher_key || '')
      : ''
    const autoCard       = buildAutoResultCard(autoAction, autoResult, autoCipherName)
    // Если для лидера уже отработал auto-result — не дублируем кнопку «Взломать»
    // на той же строке (карточка сверху и так показывает результат).
    const suppressAction = autoResult ? (autoAction || '') : ''
    const candidatesHtml = buildCandidatesTable(candidates, suppressAction)
    visualOutput.innerHTML = autoCard + candidatesHtml

    const currentText = input ? input.value : ''
    visualOutput.querySelectorAll('.cid-open-btn').forEach((btn) => {
      btn.addEventListener('click', () => saveCarryOver(currentText))
    })
    attachCrackHandlers(currentText)

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
