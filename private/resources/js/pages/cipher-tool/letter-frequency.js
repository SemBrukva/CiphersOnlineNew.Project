/**
 * Инициализирует инструмент частоты букв алфавита (тепловая карта + таблица).
 * Скрывает вкладку decode, регистрирует обработчики фильтров.
 * Возвращает { run, showEmpty }.
 *
 * @param {{
 *   output: HTMLTextAreaElement,
 *   visualOutput: HTMLElement,
 *   tabDecode: HTMLElement,
 *   ui: Record<string, string>,
 *   decoder: object,
 *   lfreqLangSelect: HTMLSelectElement|null,
 *   lfreqSortSelect: HTMLSelectElement|null,
 *   labels: Record<string, string>,
 *   setFeedback: (msg: string, isError?: boolean) => void,
 *   setOutputState: (hasContent: boolean) => void,
 *   sendAnalyticsBeacon: (slug: string, mode: string) => void,
 *   slug: string,
 *   onProcess: () => void,
 * }} ctx
 * @return {{ run: (value: string) => void, showEmpty: () => void }}
 */
export function initLetterFrequency({
  output, visualOutput, tabDecode,
  ui, decoder,
  lfreqLangSelect, lfreqSortSelect,
  labels, setFeedback, setOutputState, sendAnalyticsBeacon, slug, onProcess,
}) {
  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

  const getLangName = (code) =>
    lfreqLangSelect?.querySelector(`option[value="${code}"]`)?.textContent?.trim() || code

  const showEmpty = () => {
    if (!visualOutput) return
    visualOutput.innerHTML = `<p class="freq-empty">${esc(ui.lfreqEmptyLabel || 'Enter text to see letter frequencies')}</p>`
  }

  const renderGrid = (data) => {
    if (!visualOutput) return
    const { stats, heatmapItems, tableItems, missingLetters, requestedLang, detectedLang, mismatch } = data

    if (!heatmapItems || heatmapItems.length === 0) {
      showEmpty()
      return
    }

    // Строка статистики + детекция
    let summaryHtml = `<div class="freq-summary">`
      + `${stats?.letters ?? 0} ${ui.lfreqStatLetters || 'letters'} · `
      + `${stats?.unique ?? 0} ${ui.lfreqStatUnique || 'unique'}`
    if (requestedLang === 'auto' && detectedLang) {
      const detectedTpl = ui.lfreqLangDetectedLabel || 'Detected: :lang'
      summaryHtml += ` · <span class="lfreq-detected">${esc(detectedTpl.replace(':lang', getLangName(detectedLang)))}</span>`
    }
    summaryHtml += `</div>`

    // Предупреждение о несоответствии языка
    let mismatchHtml = ''
    if (mismatch) {
      const tpl = ui.lfreqMismatchWarning || 'Text doesn\'t match selected language. Try: :lang.'
      mismatchHtml = `<div class="lfreq-mismatch">${esc(tpl.replace(':lang', getLangName(mismatch.detectedLang || '')))}</div>`
    }

    // Тепловая карта
    const maxPct = heatmapItems.reduce((m, it) => Math.max(m, it.pct), 0.01)
    const cells  = heatmapItems.map(({ char, count, pct }) => {
      const heat = pct / maxPct
      const cls  = count === 0 ? 'lfreq-cell lfreq-cell--zero' : 'lfreq-cell'
      const tip  = count === 0 ? `${char}: 0` : `${char}: ${count} (${pct.toFixed(1)}%)`
      return `<div class="${cls}" style="--lfreq-heat:${heat.toFixed(3)}" title="${esc(tip)}">`
        + `<span class="lfreq-cell__char">${esc(char)}</span>`
        + `<span class="lfreq-cell__pct">${count === 0 ? '—' : pct.toFixed(1) + '%'}</span>`
        + `</div>`
    }).join('')
    const heatmapHtml = `<div class="lfreq-section-title">${esc(ui.lfreqHeatmapTitle || 'Alphabet heatmap')}</div>`
      + `<div class="lfreq-heatmap">${cells}</div>`

    // Отсутствующие буквы
    let missingHtml = ''
    if (missingLetters && missingLetters.length > 0) {
      missingHtml = `<div class="lfreq-missing">`
        + `<span class="lfreq-missing__label">${esc(ui.lfreqMissingTitle || 'Not in text')}:</span> `
        + `<span class="lfreq-missing__chars">${esc(missingLetters.join(', '))}</span>`
        + `</div>`
    }

    // Таблица
    const tableMaxPct = tableItems.reduce((m, it) => Math.max(m, it.pct), 0.01)
    const header      = `<div class="lfreq-row lfreq-row--header">`
      + `<span>${esc(ui.lfreqColLetter || 'Letter')}</span>`
      + `<span></span>`
      + `<span class="lfreq-col-num">${esc(ui.lfreqColCount || 'Count')}</span>`
      + `<span class="lfreq-col-num">%</span>`
      + `<span class="lfreq-col-num">${esc(ui.lfreqColExpected || 'Exp %')}</span>`
      + `</div>`
    const rows = tableItems.map(({ char, count, pct, expected }) => {
      const barW   = Math.round((pct / tableMaxPct) * 100)
      const zeroCls = count === 0 ? ' lfreq-row--zero' : ''
      return `<div class="lfreq-row${zeroCls}">`
        + `<span class="freq-char">${esc(char)}</span>`
        + `<div class="freq-bar-track"><div class="freq-bar-actual" style="width:${barW}%"></div></div>`
        + `<span class="lfreq-col-num freq-count">${count}</span>`
        + `<span class="lfreq-col-num freq-pct">${pct.toFixed(1)}%</span>`
        + `<span class="lfreq-col-num freq-pct freq-pct--exp">${expected.toFixed(1)}%</span>`
        + `</div>`
    }).join('')
    const tableHtml = `<div class="lfreq-table">${header}${rows}</div>`

    output.value = tableItems.map(
      (it) => `${it.char}\t${it.count}\t${it.pct.toFixed(1)}%\t${it.expected.toFixed(1)}%`
    ).join('\n')

    visualOutput.innerHTML = summaryHtml + mismatchHtml + heatmapHtml + missingHtml + tableHtml
  }

  const run = (value) => {
    try {
      const json = decoder.transform(value, 'encode', {
        lang: lfreqLangSelect?.value || 'auto',
        sort: lfreqSortSelect?.value || 'alpha',
      })
      const data = JSON.parse(json)
      renderGrid(data)
      setOutputState(data.tableItems && data.tableItems.length > 0)
      setFeedback('')
      sendAnalyticsBeacon(slug, 'analyze')
    } catch {
      showEmpty()
      setOutputState(false)
      setFeedback(labels.invalid, true)
    }
  }

  tabDecode.style.display = 'none'
  output.style.display    = 'none'
  if (visualOutput) {
    visualOutput.style.display = 'block'
    showEmpty()
  }

  lfreqLangSelect?.addEventListener('change', onProcess)
  lfreqSortSelect?.addEventListener('change', onProcess)

  return { run, showEmpty }
}
