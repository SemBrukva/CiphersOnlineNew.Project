/**
 * Конвертирует Unix-timestamp в читаемую дату и обратно.
 */

const UNITS = [
  { max: 60,          div: 1,          singular: 'second', plural: 'seconds' },
  { max: 3600,        div: 60,         singular: 'minute', plural: 'minutes' },
  { max: 86400,       div: 3600,       singular: 'hour',   plural: 'hours'   },
  { max: 2592000,     div: 86400,      singular: 'day',    plural: 'days'    },
  { max: 31536000,    div: 2592000,    singular: 'month',  plural: 'months'  },
  { max: Infinity,    div: 31536000,   singular: 'year',   plural: 'years'   },
]

/**
 * Возвращает относительное время (e.g. "3 hours ago", "in 5 minutes").
 *
 * @param {number} ms Unix-время в миллисекундах.
 * @param {string} locale Код локали (en, ru, …).
 * @returns {string}
 */
function relativeTime(ms, locale) {
  const diffSec = (ms - Date.now()) / 1000
  const absSec  = Math.abs(diffSec)

  let value, unit
  for (const u of UNITS) {
    if (absSec < u.max) {
      value = Math.round(absSec / u.div)
      unit  = u.singular
      break
    }
  }

  try {
    const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' })
    return rtf.format(diffSec > 0 ? value : -value, unit)
  } catch {
    if (Math.abs(diffSec) < 5) return 'just now'
    const sign = diffSec < 0 ? 'ago' : 'from now'
    return `${value} ${value === 1 ? unit : unit + 's'} ${sign}`
  }
}

/**
 * Определяет единицу метки времени по величине числа.
 *
 * @param {number} n
 * @returns {'ms'|'seconds'}
 */
function detectUnit(n) {
  return Math.abs(n) > 1e11 ? 'ms' : 'seconds'
}

/**
 * Возвращает строку с названием дня недели по объекту Date.
 *
 * @param {Date}   date
 * @param {string} locale
 * @returns {string}
 */
function dayOfWeek(date, locale) {
  try {
    return date.toLocaleDateString(locale, { weekday: 'long' })
  } catch {
    return date.toLocaleDateString('en', { weekday: 'long' })
  }
}

/**
 * Рендерит таблицу результатов в visualOutput.
 *
 * @param {HTMLElement} visualOutput
 * @param {HTMLTextAreaElement} output
 * @param {Array<{label:string, value:string}>} rows
 */
function renderRows(visualOutput, output, rows) {
  if (!visualOutput) return

  const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

  const html = rows.map((row) =>
    `<div class="ts-result-row">` +
    `<span class="ts-result-label">${esc(row.label)}</span>` +
    `<code class="ts-result-value">${esc(row.value)}</code>` +
    `<button class="ts-result-copy" type="button" data-copy="${esc(row.value)}" title="Copy">` +
    `<i class="bi bi-clipboard"></i></button>` +
    `</div>`
  ).join('')

  output.style.display = 'none'
  visualOutput.style.display = 'block'
  visualOutput.innerHTML = `<div class="ts-result-table">${html}</div>`
}

/**
 * Инициализирует Timestamp Converter.
 *
 * @param {{
 *   input: HTMLTextAreaElement,
 *   output: HTMLTextAreaElement,
 *   visualOutput: HTMLElement|null,
 *   labels: Record<string, string>,
 *   setFeedback: (msg: string, isError?: boolean) => void,
 *   setOutputState: (has: boolean) => void,
 *   sendAnalyticsBeacon: (slug: string, mode: string) => void,
 *   slug: string,
 *   locale: string,
 *   getMode: () => string,
 *   onProcess: () => void,
 *   tsUnitSelect: HTMLSelectElement|null,
 *   tsNowBtn: HTMLButtonElement|null,
 * }} ctx
 * @returns {{ run: (value: string) => void, showEmpty: () => void }}
 */
export function initTimestampConverter({
  input, output, visualOutput,
  labels, setFeedback, setOutputState,
  sendAnalyticsBeacon, slug, locale, getMode, onProcess,
  tsUnitSelect, tsNowBtn,
}) {
  const showEmpty = () => {
    if (visualOutput) {
      visualOutput.style.display = 'none'
      visualOutput.innerHTML = ''
    }
    output.style.display = ''
    output.value = ''
  }

  const runTimestampToDate = (value) => {
    const n = Number(value)
    if (!Number.isFinite(n)) {
      showEmpty()
      setFeedback(labels.tsErrInvalidTs, true)
      setOutputState(false)
      return
    }

    const unit   = tsUnitSelect?.value || 'auto'
    const effUnit = unit === 'auto' ? detectUnit(n) : unit
    const ms      = effUnit === 'ms' ? n : n * 1000
    const date    = new Date(ms)

    if (isNaN(date.getTime())) {
      showEmpty()
      setFeedback(labels.tsErrInvalidTs, true)
      setOutputState(false)
      return
    }

    const secStr = String(Math.trunc(ms / 1000))
    const msStr  = String(Math.trunc(ms))

    const rows = [
      { label: labels.tsLabelUtc,      value: date.toUTCString() },
      { label: labels.tsLabelLocal,    value: date.toLocaleString(locale) },
      { label: labels.tsLabelIso,      value: date.toISOString() },
      { label: labels.tsLabelRelative, value: relativeTime(ms, locale) },
      { label: labels.tsLabelDay,      value: dayOfWeek(date, locale) },
      { label: labels.tsLabelUnixSec,  value: secStr },
      { label: labels.tsLabelUnixMs,   value: msStr },
    ]

    output.value = date.toISOString()
    renderRows(visualOutput, output, rows)
    setOutputState(true)
    setFeedback('')
    sendAnalyticsBeacon(slug, 'encode')
  }

  const runDateToTimestamp = (value) => {
    const date = new Date(value)
    if (isNaN(date.getTime())) {
      showEmpty()
      setFeedback(labels.tsErrInvalidDate, true)
      setOutputState(false)
      return
    }

    const ms  = date.getTime()
    const sec = Math.trunc(ms / 1000)

    const rows = [
      { label: labels.tsResultSeconds, value: String(sec) },
      { label: labels.tsResultMs,      value: String(ms) },
      { label: labels.tsLabelIso,      value: date.toISOString() },
      { label: labels.tsLabelUtc,      value: date.toUTCString() },
      { label: labels.tsLabelLocal,    value: date.toLocaleString(locale) },
    ]

    output.value = String(sec)
    renderRows(visualOutput, output, rows)
    setOutputState(true)
    setFeedback('')
    sendAnalyticsBeacon(slug, 'decode')
  }

  const run = (value) => {
    const trimmed = (value || '').trim()
    if (!trimmed) {
      showEmpty()
      setOutputState(false)
      setFeedback('')
      return
    }
    if (getMode() === 'encode') {
      runTimestampToDate(trimmed)
    } else {
      runDateToTimestamp(trimmed)
    }
  }

  visualOutput?.addEventListener('click', (e) => {
    const btn = e.target.closest('.ts-result-copy')
    if (!btn) return
    const val = btn.dataset.copy
    if (!val) return
    navigator.clipboard.writeText(val).then(() => {
      const icon = btn.querySelector('.bi')
      if (icon) icon.className = 'bi bi-check-lg'
      setTimeout(() => { if (icon) icon.className = 'bi bi-clipboard' }, 1000)
    }).catch(() => {})
  })

  tsUnitSelect?.addEventListener('change', onProcess)

  tsNowBtn?.addEventListener('click', () => {
    if (getMode() === 'encode') {
      input.value = String(Math.trunc(Date.now() / 1000))
    } else {
      input.value = new Date().toISOString().slice(0, 19).replace('T', ' ')
    }
    onProcess()
    input.focus()
  })

  return { run, showEmpty }
}
