/**
 * Инициализирует инструмент частотного анализа символов.
 * Скрывает вкладку decode, регистрирует обработчики фильтров.
 * Возвращает { run, showEmpty }.
 *
 * @param {{
 *   output: HTMLTextAreaElement,
 *   visualOutput: HTMLElement,
 *   tabDecode: HTMLElement,
 *   ui: Record<string, string>,
 *   decoder: object,
 *   freqScopeSelect: HTMLSelectElement|null,
 *   freqSortSelect: HTMLSelectElement|null,
 *   freqLangSelect: HTMLSelectElement|null,
 *   labels: Record<string, string>,
 *   setFeedback: (msg: string, isError?: boolean) => void,
 *   setOutputState: (hasContent: boolean) => void,
 *   sendAnalyticsBeacon: (slug: string, mode: string) => void,
 *   slug: string,
 *   onProcess: () => void,
 * }} ctx
 * @return {{ run: (value: string) => void, showEmpty: () => void }}
 */
export function initFrequencyAnalysis({
  output, visualOutput, tabDecode,
  ui, decoder,
  freqScopeSelect, freqSortSelect, freqLangSelect,
  labels, setFeedback, setOutputState, sendAnalyticsBeacon, slug, onProcess,
}) {
  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

  const showEmpty = () => {
    if (!visualOutput) return
    visualOutput.innerHTML = `<p class="freq-empty">${ui.freqEmptyLabel || 'Enter text to analyze'}</p>`
  }

  const renderChart = (data) => {
    if (!visualOutput) return
    const { scope, lang: dataLang, stats, items, mismatch, langMatch } = data
    if (!items || items.length === 0) {
      showEmpty()
      return
    }

    const maxCount = items[0]?.count || 1
    const maxPct   = items.reduce((m, it) => Math.max(m, it.pct, it.expected ?? 0), 0.1)

    // Строка статистики
    const charsWord  = (ui.freqStatsCharsLabel || ':count chars').replace(':count', '').trim()
    const summaryHtml = `<div class="freq-summary">`
      + `${stats?.totalChars ?? 0} ${charsWord} · `
      + `${stats?.letters ?? 0} ${ui.freqStatLetters || 'letters'} · `
      + `${stats?.words ?? 0} ${ui.freqStatWords || 'words'}`
      + `</div>`

    // Блок IC
    const icVal    = typeof stats?.ic === 'number' ? stats.ic.toFixed(4) : '0.0000'
    const icInterp = stats?.icInterpretation || 'short'
    const icMap    = { natural: ui.freqIcNatural, polyalpha: ui.freqIcPolyalpha, random: ui.freqIcRandom, short: ui.freqIcShort }
    const icText   = icMap[icInterp] || icInterp
    const icHtml   = `<div class="freq-ic">`
      + `<span class="freq-ic-meta">${esc(ui.freqIcLabel || 'IC')} = ${icVal}</span>`
      + `<span class="freq-ic-badge freq-ic-badge--${esc(icInterp)}">${esc(icText)}</span>`
      + `</div>`

    // Предупреждение о несовпадении языкового профиля
    let mismatchHtml = ''
    if (scope === 'letters' && mismatch && mismatch.outsideLetters?.length > 0) {
      const template   = ui.freqMismatchWarning || 'Characters outside selected language profile: :chars. Consider switching to :lang.'
      const chars      = mismatch.outsideLetters.join(', ')
      const suggestName = mismatch.suggestions?.[0]?.name || ''
      mismatchHtml = `<div class="freq-mismatch">${esc(template.replace(':chars', chars).replace(':lang', suggestName))}</div>`
    }

    // Вертикальный столбчатый график (только Letters)
    let chartHtml = ''
    if (scope === 'letters') {
      const CHART_H   = 80
      const chartMaxF = items.reduce((m, it) => Math.max(m, it.pct, it.expected ?? 0), 0.1)
      const groups    = items.map(({ char, pct, expected = 0 }) => {
        const aH  = Math.max(2, Math.round((pct / chartMaxF) * CHART_H))
        const eH  = Math.max(2, Math.round((expected / chartMaxF) * CHART_H))
        const tip = `${char}: ${pct.toFixed(1)}% vs ${expected.toFixed(1)}%`
        return `<div class="freq-chart-group" title="${esc(tip)}">`
          + `<div class="freq-chart-cols">`
          + `<div class="freq-chart-col freq-chart-col--actual" style="height:${aH}px"></div>`
          + `<div class="freq-chart-col freq-chart-col--expected" style="height:${eH}px"></div>`
          + `</div>`
          + `<span class="freq-chart-label">${esc(char)}</span>`
          + `</div>`
      }).join('')
      chartHtml = `<div class="freq-chart-area">${groups}</div>`
    }

    // Таблица
    let tableHtml
    if (scope === 'letters') {
      const diffTip = esc(ui.freqColDiffTooltip || 'Actual %− Expected %')
      const header  = `<div class="freq-row freq-row--header freq-row--letters">`
        + `<span>${esc(ui.freqColLetter || 'Letter')}</span>`
        + `<span class="freq-col-num">${esc(ui.freqColCount || 'Count')}</span>`
        + `<span></span>`
        + `<span class="freq-col-num">${esc(ui.freqColActualPct || 'Actual%')}</span>`
        + `<span class="freq-col-num">${esc(ui.freqColExpectedPct || 'Exp%')}</span>`
        + `<span class="freq-col-num freq-diff-header" title="${diffTip}">${esc(ui.freqColDiff || 'Diff')}</span>`
        + `</div>`
      const rows = items.map(({ char, count, pct, expected = 0, diff = 0 }) => {
        const aW      = Math.round((pct / maxPct) * 100)
        const eW      = Math.round((expected / maxPct) * 100)
        const diffStr = diff >= 0 ? `+${diff.toFixed(1)}` : diff.toFixed(1)
        const diffCls = diff > 0.5 ? 'freq-diff--pos' : diff < -0.5 ? 'freq-diff--neg' : ''
        return `<div class="freq-row freq-row--letters">`
          + `<span class="freq-char">${esc(char)}</span>`
          + `<span class="freq-col-num freq-count">${count}</span>`
          + `<div class="freq-bar-track">`
          + `<div class="freq-bar-actual" style="width:${aW}%"></div>`
          + `<div class="freq-bar-expected" style="width:${eW}%"></div>`
          + `</div>`
          + `<span class="freq-col-num freq-pct">${pct.toFixed(1)}%</span>`
          + `<span class="freq-col-num freq-pct freq-pct--exp">${expected.toFixed(1)}%</span>`
          + `<span class="freq-col-num freq-diff ${diffCls}">${esc(diffStr)}</span>`
          + `</div>`
      }).join('')
      tableHtml = `<div class="freq-table">${header}${rows}</div>`
    } else {
      const colLabel = scope === 'words'    ? (ui.freqColWord    || 'Word')
        : scope === 'bigrams'  ? (ui.freqColBigram  || 'Bigram')
        : scope === 'trigrams' ? (ui.freqColTrigram || 'Trigram')
        : (ui.freqColLetter || 'Char')
      const header = `<div class="freq-row freq-row--header freq-row--simple">`
        + `<span>${esc(colLabel)}</span>`
        + `<span></span>`
        + `<span class="freq-col-num">${esc(ui.freqColCount || 'Count')}</span>`
        + `<span class="freq-col-num">%</span>`
        + `</div>`
      const rows = items.map(({ char, count, pct }) => {
        const bW = Math.round((count / maxCount) * 100)
        return `<div class="freq-row freq-row--simple">`
          + `<span class="freq-char">${esc(char)}</span>`
          + `<div class="freq-bar-track"><div class="freq-bar-actual" style="width:${bW}%"></div></div>`
          + `<span class="freq-col-num freq-count">${count}</span>`
          + `<span class="freq-col-num freq-pct">${pct.toFixed(1)}%</span>`
          + `</div>`
      }).join('')
      tableHtml = `<div class="freq-table">${header}${rows}</div>`
    }

    // Соответствие языкам
    let langMatchHtml = ''
    if (langMatch && langMatch.length > 0) {
      const title = esc(ui.freqLangMatchTitle || 'Language Match')
      const rows  = langMatch.map(({ lang: lCode, name, score }) => {
        const isSel = lCode === dataLang
        const cls   = isSel ? 'freq-lang-match__row freq-lang-match__row--selected' : 'freq-lang-match__row'
        return `<div class="${cls}">`
          + `<span class="freq-lang-match__name">${esc(name)}</span>`
          + `<div class="freq-lang-match__bar-wrap"><div class="freq-lang-match__bar" style="width:${score}%"></div></div>`
          + `<span class="freq-lang-match__score">${score}%</span>`
          + `</div>`
      }).join('')
      langMatchHtml = `<div class="freq-lang-match"><div class="freq-lang-match__title">${title}</div>${rows}</div>`
    }

    // Текст для буфера обмена
    output.value = items.map((it) => {
      if (scope === 'letters') {
        return `${it.char}\t${it.count}\t${it.pct.toFixed(1)}%\t${(it.expected ?? 0).toFixed(1)}%\t${(it.diff ?? 0).toFixed(1)}`
      }
      return `${it.char}\t${it.count}\t${it.pct.toFixed(1)}%`
    }).join('\n')

    visualOutput.innerHTML = summaryHtml + icHtml + mismatchHtml + chartHtml + tableHtml + langMatchHtml
  }

  const run = (value) => {
    try {
      const json = decoder.transform(value, 'encode', {
        scope: freqScopeSelect?.value || 'letters',
        sort:  freqSortSelect?.value  || 'frequency',
        lang:  freqLangSelect?.value  || 'en',
      })
      const data = JSON.parse(json)
      renderChart(data)
      setOutputState(data.items && data.items.length > 0)
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

  freqScopeSelect?.addEventListener('change', onProcess)
  freqSortSelect?.addEventListener('change', onProcess)
  freqLangSelect?.addEventListener('change', onProcess)

  return { run, showEmpty }
}
