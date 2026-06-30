import { alphabetFor } from './decoders/dancing-men.js'

// Координаты концов рук от плечевой точки (15, 17).
// 0 = вниз, 1 = горизонтально, 2 = вверх.
const LEFT_ARM_PTS  = [[9, 24], [6, 17], [9, 10]]
const RIGHT_ARM_PTS = [[21, 24], [24, 17], [21, 10]]

// Координаты концов ног от бедренной точки (15, 28).
// 0 = вместе, 1 = лев. в сторону, 2 = прав. в сторону, 3 = обе в стороны.
const LEFT_LEG_PTS  = [[11, 42], [7, 42], [11, 42], [7, 42]]
const RIGHT_LEG_PTS = [[19, 42], [19, 42], [23, 42], [23, 42]]

const FIG_STROKE    = 'rgba(255,255,255,0.88)'
const FIG_STROKE_DL = '#1a2035'
const BG_DL        = '#ffffff'
const FIG_W = 36
const FIG_H = 58
const CHAR_GAP = 3
const WORD_GAP = 18
const PAD = 16

/**
 * Генерирует SVG-строку для одного человечка.
 *
 * @param {number} leftArm
 * @param {number} rightArm
 * @param {number} legs
 * @param {string} letter
 * @returns {string}
 */
function figureSvg(leftArm, rightArm, legs, letter) {
  const la = LEFT_ARM_PTS[leftArm]   ?? LEFT_ARM_PTS[0]
  const ra = RIGHT_ARM_PTS[rightArm] ?? RIGHT_ARM_PTS[0]
  const ll = LEFT_LEG_PTS[legs]      ?? LEFT_LEG_PTS[0]
  const rl = RIGHT_LEG_PTS[legs]     ?? RIGHT_LEG_PTS[0]

  return (
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 48" class="dm-figure" aria-label="${letter}" role="img">`
    + `<title>${letter}</title>`
    + `<circle cx="15" cy="6" r="4.5" fill="none" stroke="currentColor" stroke-width="1.8"/>`
    + `<line x1="15" y1="10.5" x2="15" y2="28" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>`
    + `<line x1="15" y1="17" x2="${la[0]}" y2="${la[1]}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>`
    + `<line x1="15" y1="17" x2="${ra[0]}" y2="${ra[1]}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>`
    + `<line x1="15" y1="28" x2="${ll[0]}" y2="${ll[1]}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>`
    + `<line x1="15" y1="28" x2="${rl[0]}" y2="${rl[1]}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>`
    + `</svg>`
  )
}

/**
 * Генерирует SVG-элемент одного человечка без DOM-классов (для standalone SVG).
 *
 * @param {number} leftArm
 * @param {number} rightArm
 * @param {number} legs
 * @param {number} x  Горизонтальное смещение внутри итогового SVG.
 * @returns {string}
 */
function figureEmbedded(leftArm, rightArm, legs, x, stroke = FIG_STROKE) {
  const la = LEFT_ARM_PTS[leftArm]   ?? LEFT_ARM_PTS[0]
  const ra = RIGHT_ARM_PTS[rightArm] ?? RIGHT_ARM_PTS[0]
  const ll = LEFT_LEG_PTS[legs]      ?? LEFT_LEG_PTS[0]
  const rl = RIGHT_LEG_PTS[legs]     ?? RIGHT_LEG_PTS[0]
  const sw = '1.8'
  const s = stroke

  return (
    `<g transform="translate(${x},${PAD})">`
    + `<svg viewBox="0 0 30 48" width="${FIG_W}" height="${FIG_H}">`
    + `<circle cx="15" cy="6" r="4.5" fill="none" stroke="${s}" stroke-width="${sw}"/>`
    + `<line x1="15" y1="10.5" x2="15" y2="28" stroke="${s}" stroke-width="${sw}" stroke-linecap="round"/>`
    + `<line x1="15" y1="17" x2="${la[0]}" y2="${la[1]}" stroke="${s}" stroke-width="${sw}" stroke-linecap="round"/>`
    + `<line x1="15" y1="17" x2="${ra[0]}" y2="${ra[1]}" stroke="${s}" stroke-width="${sw}" stroke-linecap="round"/>`
    + `<line x1="15" y1="28" x2="${ll[0]}" y2="${ll[1]}" stroke="${s}" stroke-width="${sw}" stroke-linecap="round"/>`
    + `<line x1="15" y1="28" x2="${rl[0]}" y2="${rl[1]}" stroke="${s}" stroke-width="${sw}" stroke-linecap="round"/>`
    + `</svg>`
    + `</g>`
  )
}

/**
 * Генерирует HTML-разметку для одного слова.
 *
 * @param {string} word
 * @param {Record<string, [number, number, number]>} map
 * @returns {string}
 */
function wordHtml(word, map) {
  const upper = word.toUpperCase()
  let html = '<span class="dm-word">'
  for (const ch of upper) {
    const pose = map[ch]
    if (pose) {
      html += `<span class="dm-char">${figureSvg(pose[0], pose[1], pose[2], ch)}</span>`
    } else {
      html += `<span class="dm-char dm-char--unknown" title="${ch}">?</span>`
    }
  }
  html += '</span>'
  return html
}

/**
 * Строит автономный SVG-документ с фигурками всех букв входного текста.
 * Используется при копировании как изображение.
 *
 * @param {string} text
 * @param {Record<string, [number, number, number]>} map
 * @returns {string}
 */
function buildCombinedSvg(text, map) {
  const chunks = text.split(/(\s+)/)

  // Считаем итоговую ширину
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
        const pose = map[ch]
        if (pose) {
          svg += figureEmbedded(pose[0], pose[1], pose[2], x, FIG_STROKE_DL)
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
 * Использует data URL вместо Blob URL, чтобы избежать проблем с CSP.
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
      const scale  = 2
      const canvas = document.createElement('canvas')
      canvas.width  = w * scale
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
  const a   = document.createElement('a')
  a.href     = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

/**
 * Инициализирует инструмент «Танцующие человечки».
 *
 * Скрывает вкладку decode и textarea вывода, показывает SVG-фигуры в visualOutput.
 * Кнопка downloadBtn скачивает фигурки как PNG-файл.
 *
 * @param {{
 *   output: HTMLTextAreaElement,
 *   visualOutput: HTMLElement,
 *   tabDecode: HTMLElement,
 *   downloadBtn: HTMLElement|null,
 *   ui: Record<string, string>,
 *   setFeedback: (msg: string, isError?: boolean, isInfo?: boolean) => void,
 *   setOutputState: (hasContent: boolean) => void,
 *   sendAnalyticsBeacon: (slug: string, mode: string) => void,
 *   slug: string,
 *   alphabetSelect: HTMLSelectElement|null,
 * }} ctx
 * @returns {{ showEmpty: () => void, run: (text: string) => void }}
 */
export function initDancingMen({
  output, visualOutput, tabDecode, downloadBtn,
  ui, setFeedback, setOutputState,
  sendAnalyticsBeacon, slug,
  alphabetSelect,
}) {
  tabDecode.style.display = 'none'
  output.style.display = 'none'
  if (visualOutput) {
    visualOutput.style.display = 'block'
  }

  let lastText = ''
  let lastMap  = alphabetFor('en')

  const showEmpty = () => {
    if (!visualOutput) return
    const msg = String(ui.dancingMenEmptyLabel || 'Enter text to see dancing men figures')
    visualOutput.innerHTML = `<p class="freq-empty">${msg}</p>`
  }

  const run = (text) => {
    lastText = text
    lastMap  = alphabetFor(String(alphabetSelect?.value || 'en'))

    if (!text.trim()) {
      showEmpty()
      setOutputState(false)
      setFeedback('')
      return
    }

    const chunks = text.split(/(\s+)/)
    let hasUnknown = false
    const parts = chunks.map((chunk) => {
      if (/^\s+$/.test(chunk)) return '<span class="dm-space"></span>'
      const upper = chunk.toUpperCase()
      for (const ch of upper) {
        if (!lastMap[ch]) hasUnknown = true
      }
      return wordHtml(chunk, lastMap)
    })

    if (visualOutput) {
      visualOutput.innerHTML = `<div class="dm-output">${parts.join('')}</div>`
    }

    setOutputState(true)

    if (hasUnknown) {
      setFeedback(String(ui.dancingMenWarnUnsupported || 'Some characters are not supported in this alphabet'), false, true)
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
      downloadBlob(pngBlob, 'dancing-men.png')
    } catch {
      // Canvas/CSP не позволяет конвертировать в PNG — скачиваем SVG напрямую.
      const svgBlob = new Blob([svgString], { type: 'image/svg+xml;charset=utf-8' })
      downloadBlob(svgBlob, 'dancing-men.svg')
    } finally {
      setDownloadBtnState(false)
    }
  })

  showEmpty()
  return { showEmpty, run }
}
