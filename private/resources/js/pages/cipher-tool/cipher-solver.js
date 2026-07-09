/**
 * Инициализирует «умный» авто-солвер.
 * Скрывает стандартный output, показывает герой-карточку вероятного ответа,
 * ранжированный список расшифровок и сворачиваемый блок типов-кандидатов.
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
 *   handleApiResponse: (response: object) => void,
 * }}
 */
export function initCipherSolver({
  output, visualOutput, tabDecode,
  ui, input,
  setFeedback, setOutputState,
}) {
  const esc = (s) => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  const t   = (key) => (ui.solverTranslations?.[key]) ?? key

  const localePrefix = () => {
    const meta = document.querySelector('meta[name="locale-prefix"]')
    if (meta) return meta.getAttribute('content') || ''
    const m = window.location.pathname.match(/^\/(en|ru|de|es|fr|it|pt|tr)\//)
    return m ? '/' + m[1] : ''
  }

  const showEmpty = () => {
    if (!visualOutput) return
    visualOutput.innerHTML = `<p class="freq-empty">${esc(ui.solverEmptyLabel || 'Paste any ciphertext to get the answer')}</p>`
    output.value = ''
  }

  const cipherName = (key, fallback) => t(key) || key || fallback || ''

  // Строит бар читаемости 0..100%.
  const readabilityBar = (pct) => `<div class="brute-fitness-wrap">`
    + `<div class="brute-fitness-bar" style="width:${pct}%"></div>`
    + `<span class="brute-fitness-pct">${pct}%</span>`
    + `</div>`

  const keyBadge = (label) => label
    ? `<span class="cid-auto-card__key">${esc(label)}</span>`
    : ''

  // Герой-карточка вероятного ответа.
  const buildBestCard = (best) => {
    const title  = esc(ui.solverBestTitle || 'Most likely answer')
    const name   = cipherName(best.cipher_key, best.tool_slug)
    const pct    = best.readability_pct ?? 0

    const meta = `<div class="cid-auto-card__meta">`
      + `<span class="cid-auto-card__cipher">${esc(name)}</span>`
      + keyBadge(best.key_label)
      + `</div>`

    const copyBtn = `<button type="button" class="solver-copy" data-solver-copy>${esc(ui.solverCopyLabel || 'Copy')}</button>`

    return `<div class="cid-auto-card solver-best">`
      + `<div class="cid-auto-card__title"><span class="brute-summary-icon">★</span>${title}${copyBtn}</div>`
      + meta
      + readabilityBar(pct)
      + `<div class="cid-auto-result-text" data-solver-answer>${esc(best.plaintext)}</div>`
      + `</div>`
  }

  // Список остальных расшифровок (кроме лучшей).
  const buildAnswersList = (answers) => {
    if (answers.length === 0) return ''

    const title = `<div class="brute-header"><span class="brute-title">${esc(ui.solverAnswersTitle || 'Other decryptions')}</span></div>`

    const rows = answers.map((a) => {
      const name = cipherName(a.cipher_key, a.tool_slug)
      const pct  = a.readability_pct ?? 0
      return `<div class="solver-answer">`
        + `<div class="solver-answer__head">`
        + `<span class="cid-col-name">${esc(name)}</span>`
        + keyBadge(a.key_label)
        + readabilityBar(pct)
        + `</div>`
        + `<div class="solver-answer__text" data-solver-answer>${esc(a.plaintext)}</div>`
        + `</div>`
    }).join('')

    return title + `<div class="solver-answers">${rows}</div>`
  }

  // Сворачиваемый блок типов-кандидатов (вторичный уровень).
  const buildTypesBlock = (types) => {
    if (!Array.isArray(types) || types.length === 0) return ''

    const lp = localePrefix()
    const colCipher     = esc(ui.solverColCipher || 'Cipher / Encoding')
    const colConfidence = esc(ui.solverColConfidence || 'Confidence')
    const colEvidence   = esc(ui.solverColEvidence || 'Evidence')
    const openTool      = esc(ui.solverOpenTool || 'Open tool')

    const header = `<div class="cid-table-header">`
      + `<span>${colCipher}</span><span>${colConfidence}</span><span>${colEvidence}</span><span></span>`
      + `</div>`

    const rows = types.map((c) => {
      const name = cipherName(c.cipher_key, c.tool_slug)
      const pct  = c.confidence_pct ?? Math.round((c.confidence ?? 0) * 100)
      const evidence = (c.evidence_keys ?? []).map((k) => `<span class="cid-ev-tag">${esc(t(k))}</span>`).join('')
      const toolUrl = c.tool_slug ? `${lp}/${c.tool_slug}` : null
      const openBtn = toolUrl ? `<a class="cid-open-btn" href="${esc(toolUrl)}">${openTool}</a>` : ''

      return `<div class="cid-row">`
        + `<span class="cid-col-name">${esc(name)}</span>`
        + readabilityBar(pct)
        + `<span class="cid-col-evidence">${evidence}</span>`
        + `<span class="cid-col-action">${openBtn}</span>`
        + `</div>`
    }).join('')

    const toggle = esc(ui.solverTypesToggle || 'Show cipher candidates')
    return `<details class="solver-types">`
      + `<summary class="solver-types__toggle">${toggle}</summary>`
      + header + `<div class="cid-rows">${rows}</div>`
      + `</details>`
  }

  const attachCopyHandler = () => {
    const btn = visualOutput.querySelector('[data-solver-copy]')
    if (!btn) return
    btn.addEventListener('click', async () => {
      const text = output.value || ''
      try {
        await navigator.clipboard.writeText(text)
        const original = btn.textContent
        btn.textContent = ui.solverCopyLabel ? `${ui.solverCopyLabel} ✓` : '✓'
        setTimeout(() => { btn.textContent = original }, 1500)
      } catch {
        // ignore clipboard errors
      }
    })
  }

  const handleApiResponse = (response) => {
    if (!visualOutput) return

    const best    = response?.best ?? null
    const answers = Array.isArray(response?.answers) ? response.answers : []
    const types   = Array.isArray(response?.type_candidates) ? response.type_candidates : []

    if (!best) {
      showEmpty()
      setOutputState(false)
      setFeedback(ui.solverNoAnswersMsg || 'Could not decrypt this text automatically. Try the cipher candidates below.', false)
      // Показываем хотя бы типы-кандидаты, если они есть.
      if (types.length > 0) visualOutput.innerHTML = buildTypesBlock(types)
      return
    }

    // Остальные ответы = все, кроме первого (он же best).
    const others = answers.slice(1)

    visualOutput.innerHTML = buildBestCard(best)
      + buildAnswersList(others)
      + buildTypesBlock(types)

    attachCopyHandler()

    output.value = best.plaintext ?? ''
    setOutputState(Boolean(output.value))
    setFeedback('')
  }

  // Солвер не шифрует — режим один, поэтому прячем весь блок табов
  // (иначе остаётся нелогичный активный таб «Encode»).
  const tabGroup = tabDecode.closest('.ciphers-tabs')
  if (tabGroup) tabGroup.style.display = 'none'
  else tabDecode.style.display = 'none'
  output.style.display    = 'none'
  if (visualOutput) {
    visualOutput.style.display = 'block'
    showEmpty()
  }

  return { showEmpty, handleApiResponse }
}
