import { alphabetForVariant } from './decoders/pigpen.js'

// Грани ячейки решётки «крестики-нолики» по индексу 0..8: t=верх, r=право, b=низ, l=лево.
// Выводятся как внутренние линии решётки 3×3.
const GRID_EDGES = [
  { t: 0, r: 1, b: 1, l: 0 }, // 0
  { t: 0, r: 1, b: 1, l: 1 }, // 1
  { t: 0, r: 0, b: 1, l: 1 }, // 2
  { t: 1, r: 1, b: 1, l: 0 }, // 3
  { t: 1, r: 1, b: 1, l: 1 }, // 4
  { t: 1, r: 0, b: 1, l: 1 }, // 5
  { t: 1, r: 1, b: 0, l: 0 }, // 6
  { t: 1, r: 1, b: 0, l: 1 }, // 7
  { t: 1, r: 0, b: 0, l: 1 }, // 8
]

// Позиция точки внутри клина «икса» по ориентации (для вариантов с точкой).
const X_DOT_POS = { u: [15, 8], d: [15, 22], l: [8, 15], r: [22, 15] }

const FIG_STROKE_DL = '#1a2035'
const BG_DL = '#ffffff'
const FIG_W = 34
const FIG_H = 34
const CHAR_GAP = 4
const WORD_GAP = 18
const PAD = 16

// Буквы клавиатуры в порядке заполнения символов (для группировки по типу глифа).
const KEYBOARD_LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'

/**
 * Экранирует спецсимволы HTML в строке (для безопасной вставки меток перевода).
 *
 * @param {string} value
 * @returns {string}
 */
function escapeHtml(value) {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

/**
 * Строит SVG-содержимое одного символа (без обёртки <svg>).
 *
 * @param {{ k: string, s: number|string, d: string }} glyph
 * @param {string} stroke
 * @returns {string}
 */
function glyphInner(glyph, stroke) {
  const sw = '2'
  const line = (x1, y1, x2, y2) =>
    `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="${stroke}" stroke-width="${sw}" stroke-linecap="round"/>`
  const dot = (x, y) => `<circle cx="${x}" cy="${y}" r="2.4" fill="${stroke}"/>`

  let out = ''

  if (glyph.k === 'g') {
    const e = GRID_EDGES[glyph.s] ?? GRID_EDGES[0]
    if (e.t) out += line(3, 3, 27, 3)
    if (e.r) out += line(27, 3, 27, 27)
    if (e.b) out += line(3, 27, 27, 27)
    if (e.l) out += line(3, 3, 3, 27)
    if (glyph.d !== 'none') {
      const dx = glyph.d === 'l' ? 9 : glyph.d === 'r' ? 21 : 15
      out += dot(dx, 15)
    }
  } else {
    if (glyph.s === 'u') out += line(15, 15, 3, 3) + line(15, 15, 27, 3)
    else if (glyph.s === 'd') out += line(15, 15, 3, 27) + line(15, 15, 27, 27)
    else if (glyph.s === 'l') out += line(15, 15, 3, 3) + line(15, 15, 3, 27)
    else out += line(15, 15, 27, 3) + line(15, 15, 27, 27)
    if (glyph.d === 'c') {
      const [dx, dy] = X_DOT_POS[glyph.s] ?? [15, 15]
      out += dot(dx, dy)
    }
  }

  return out
}

/**
 * Генерирует inline-SVG символа для вставки в DOM (цвет — currentColor).
 *
 * @param {{ k: string, s: number|string, d: string }} glyph
 * @param {string} letter
 * @returns {string}
 */
function figureSvg(glyph, letter) {
  return (
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" class="pp-figure" role="img" aria-label="${letter}">`
    + `<title>${letter}</title>`
    + glyphInner(glyph, 'currentColor')
    + '</svg>'
  )
}

/**
 * Генерирует встроенный SVG-символ со смещением (для автономного документа).
 *
 * @param {{ k: string, s: number|string, d: string }} glyph
 * @param {number} x
 * @param {string} stroke
 * @returns {string}
 */
function figureEmbedded(glyph, x, stroke) {
  return (
    `<g transform="translate(${x},${PAD})">`
    + `<svg viewBox="0 0 30 30" width="${FIG_W}" height="${FIG_H}">`
    + glyphInner(glyph, stroke)
    + '</svg>'
    + '</g>'
  )
}

/**
 * Генерирует HTML-разметку одного слова.
 *
 * @param {string} word
 * @param {Record<string, { k: string, s: number|string, d: string }>} map
 * @returns {string}
 */
function wordHtml(word, map) {
  const upper = word.toUpperCase()
  let html = '<span class="pp-word">'
  for (const ch of upper) {
    const glyph = map[ch]
    if (glyph) {
      html += `<span class="pp-char">${figureSvg(glyph, ch)}</span>`
    } else {
      html += `<span class="pp-char pp-char--unknown" title="${ch}">?</span>`
    }
  }
  html += '</span>'
  return html
}

/**
 * Строит автономный SVG-документ со всеми символами текста (для экспорта в PNG).
 *
 * @param {string} text
 * @param {Record<string, { k: string, s: number|string, d: string }>} map
 * @returns {string}
 */
function buildCombinedSvg(text, map) {
  const chunks = text.split(/(\s+)/)

  let totalW = PAD
  for (const chunk of chunks) {
    if (/^\s+$/.test(chunk)) {
      totalW += WORD_GAP
    } else {
      totalW += [...chunk.toUpperCase()].length * (FIG_W + CHAR_GAP)
    }
  }
  totalW = Math.max(totalW + PAD, FIG_W + PAD * 2)

  const totalH = FIG_H + PAD * 2

  let svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${totalW}" height="${totalH}">`
  svg += `<rect width="${totalW}" height="${totalH}" fill="${BG_DL}"/>`

  let x = PAD
  for (const chunk of chunks) {
    if (/^\s+$/.test(chunk)) {
      x += WORD_GAP
    } else {
      for (const ch of [...chunk.toUpperCase()]) {
        const glyph = map[ch]
        if (glyph) {
          svg += figureEmbedded(glyph, x, FIG_STROKE_DL)
        } else {
          svg += `<text x="${x + FIG_W / 2}" y="${PAD + FIG_H / 2 + 6}" text-anchor="middle"`
               + ` fill="rgba(26,32,53,0.35)" font-family="monospace" font-size="18">?</text>`
        }
        x += FIG_W + CHAR_GAP
      }
    }
  }

  svg += '</svg>'
  return svg
}

/**
 * Конвертирует SVG-строку в PNG-Blob через Canvas (2× для чёткости на Retina).
 *
 * @param {string} svgString
 * @returns {Promise<Blob>}
 */
function svgToPngBlob(svgString) {
  return new Promise((resolve, reject) => {
    const dataUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgString)
    const img = new Image()

    img.onload = () => {
      const w = img.naturalWidth
      const h = img.naturalHeight
      if (!w || !h) {
        reject(new Error('SVG has zero dimensions'))
        return
      }
      const scale = 2
      const canvas = document.createElement('canvas')
      canvas.width = w * scale
      canvas.height = h * scale
      const ctx = canvas.getContext('2d')
      ctx.scale(scale, scale)
      ctx.drawImage(img, 0, 0)
      canvas.toBlob((pngBlob) => {
        if (pngBlob) resolve(pngBlob)
        else reject(new Error('canvas.toBlob returned null'))
      }, 'image/png')
    }

    img.onerror = () => reject(new Error('SVG image load failed'))
    img.src = dataUrl
  })
}

/**
 * Скачивает Blob как файл.
 *
 * @param {Blob} blob
 * @param {string} filename
 */
function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

/**
 * Инициализирует инструмент «Шифр масонов» (Pigpen).
 *
 * Скрывает вкладку decode и textarea вывода, показывает SVG-символы в visualOutput.
 * Кнопка downloadBtn скачивает символы как PNG-файл. Выбор варианта в variantSelect
 * меняет таблицу соответствия букв символам.
 *
 * Режим encode: вводимый текст рендерится символами в visualOutput, доступно
 * скачивание PNG. Режим decode: вместо ввода показывается клавиатура символов —
 * клик по символу дописывает соответствующую букву в output.
 *
 * @param {{
 *   input: HTMLTextAreaElement|null,
 *   output: HTMLTextAreaElement,
 *   visualOutput: HTMLElement,
 *   tabDecode: HTMLElement,
 *   downloadBtn: HTMLElement|null,
 *   ui: Record<string, string>,
 *   setFeedback: (msg: string, isError?: boolean, isInfo?: boolean) => void,
 *   setOutputState: (hasContent: boolean) => void,
 *   sendAnalyticsBeacon: (slug: string, mode: string) => void,
 *   slug: string,
 *   variantSelect: HTMLSelectElement|null,
 *   getMode: () => string,
 * }} ctx
 * @returns {{ showEmpty: () => void, run: (text: string) => void, setMode: (mode: string) => void }}
 */
export function initPigpen({
  input, output, visualOutput, tabDecode, downloadBtn,
  ui, setFeedback, setOutputState,
  sendAnalyticsBeacon, slug,
  variantSelect, getMode,
}) {
  const inputWrap = input?.closest('.ciphers-unified__input-wrap') ?? null
  const isDecode = () => (typeof getMode === 'function' ? getMode() === 'decode' : false)

  let lastText = ''
  let lastMap = alphabetForVariant('standard')

  // ── Клавиатура символов (режим decode) ───────────────────────────────────

  const keyboardWrap = document.createElement('div')
  keyboardWrap.className = 'pp-keyboard-wrap'
  keyboardWrap.style.display = 'none'
  keyboardWrap.innerHTML =
      `<p class="pp-keyboard-hint">${escapeHtml(String(ui.pigpenKeyboardHint || 'Tap the symbols to spell out the decoded text'))}</p>`
    + '<div class="pp-keyboard" role="group"></div>'
    + '<div class="pp-keyboard-controls">'
    + `<button type="button" class="pp-key-ctrl pp-key-ctrl--space" data-act="space">${escapeHtml(String(ui.pigpenSpaceLabel || 'Space'))}</button>`
    + `<button type="button" class="pp-key-ctrl" data-act="backspace" title="${escapeHtml(String(ui.pigpenBackspaceLabel || 'Backspace'))}" aria-label="${escapeHtml(String(ui.pigpenBackspaceLabel || 'Backspace'))}"><i class="bi bi-backspace"></i></button>`
    + `<button type="button" class="pp-key-ctrl" data-act="clear" title="${escapeHtml(String(ui.pigpenClearLabel || 'Clear'))}" aria-label="${escapeHtml(String(ui.pigpenClearLabel || 'Clear'))}"><i class="bi bi-x-lg"></i></button>`
    + '</div>'

  if (inputWrap?.parentNode) {
    inputWrap.parentNode.insertBefore(keyboardWrap, inputWrap)
  }

  const keysHost = keyboardWrap.querySelector('.pp-keyboard')

  const appendToOutput = (str) => {
    const wasEmpty = output.value === ''
    output.value += str
    setOutputState(Boolean(output.value))
    if (wasEmpty && output.value !== '') sendAnalyticsBeacon(slug, 'decode')
  }

  const rebuildKeyboard = () => {
    if (!keysHost) return
    const map = alphabetForVariant(String(variantSelect?.value || 'standard'))
    keysHost.innerHTML = ''
    for (const letter of KEYBOARD_LETTERS) {
      const glyph = map[letter]
      if (!glyph) continue
      const btn = document.createElement('button')
      btn.type = 'button'
      btn.className = 'pp-key'
      btn.dataset.letter = letter
      btn.title = letter
      btn.setAttribute('aria-label', letter)
      btn.innerHTML = figureSvg(glyph, letter) + `<span class="pp-key-letter">${letter}</span>`
      keysHost.appendChild(btn)
    }
  }

  keysHost?.addEventListener('click', (event) => {
    const key = event.target.closest('.pp-key')
    if (!key) return
    appendToOutput(String(key.dataset.letter || ''))
  })

  keyboardWrap.querySelector('.pp-keyboard-controls')?.addEventListener('click', (event) => {
    const btn = event.target.closest('.pp-key-ctrl')
    if (!btn) return
    if (btn.dataset.act === 'space') {
      appendToOutput(' ')
    } else if (btn.dataset.act === 'backspace') {
      output.value = output.value.slice(0, -1)
      setOutputState(Boolean(output.value))
    } else if (btn.dataset.act === 'clear') {
      output.value = ''
      setOutputState(false)
    }
  })

  variantSelect?.addEventListener('change', () => {
    if (isDecode()) rebuildKeyboard()
  })

  // ── Encode ───────────────────────────────────────────────────────────────

  const showEmpty = () => {
    if (isDecode()) return
    if (!visualOutput) return
    const msg = String(ui.pigpenEmptyLabel || 'Enter text to see the Pigpen symbols')
    visualOutput.innerHTML = `<p class="freq-empty">${msg}</p>`
  }

  const run = (text) => {
    if (isDecode()) return
    lastText = text
    lastMap = alphabetForVariant(String(variantSelect?.value || 'standard'))

    if (!text.trim()) {
      showEmpty()
      setOutputState(false)
      setFeedback('')
      return
    }

    const chunks = text.split(/(\s+)/)
    let hasUnknown = false
    const parts = chunks.map((chunk) => {
      if (/^\s+$/.test(chunk)) return '<span class="pp-space"></span>'
      const upper = chunk.toUpperCase()
      for (const ch of upper) {
        if (!lastMap[ch]) hasUnknown = true
      }
      return wordHtml(chunk, lastMap)
    })

    if (visualOutput) {
      visualOutput.innerHTML = `<div class="pp-output">${parts.join('')}</div>`
    }

    setOutputState(true)

    if (hasUnknown) {
      setFeedback(String(ui.pigpenWarnUnsupported || 'Only A–Z letters are supported; other characters are skipped'), false, true)
    } else {
      setFeedback('')
    }

    sendAnalyticsBeacon(slug, 'encode')
  }

  // ── Скачивание как PNG ───────────────────────────────────────────────────

  const setDownloadBtnState = (loading) => {
    if (!downloadBtn) return
    const iconEl = downloadBtn.querySelector('.bi')
    if (iconEl) iconEl.className = loading ? 'bi bi-hourglass-split' : 'bi bi-download'
    downloadBtn.disabled = loading
  }

  downloadBtn?.addEventListener('click', async () => {
    if (!lastText.trim()) return

    setDownloadBtnState(true)
    const svgString = buildCombinedSvg(lastText, lastMap)
    try {
      const pngBlob = await svgToPngBlob(svgString)
      downloadBlob(pngBlob, 'pigpen.png')
    } catch {
      const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' })
      downloadBlob(svgBlob, 'pigpen.svg')
    } finally {
      setDownloadBtnState(false)
    }
  })

  // ── Переключение режима encode/decode ────────────────────────────────────

  const setMode = (mode) => {
    const decode = mode === 'decode'
    if (inputWrap) inputWrap.style.display = decode ? 'none' : ''
    if (visualOutput) visualOutput.style.display = decode ? 'none' : 'block'
    output.style.display = decode ? '' : 'none'
    keyboardWrap.style.display = decode ? '' : 'none'
    if (decode) {
      rebuildKeyboard()
    } else {
      showEmpty()
    }
  }

  setMode('encode')
  return { showEmpty, run, setMode }
}
