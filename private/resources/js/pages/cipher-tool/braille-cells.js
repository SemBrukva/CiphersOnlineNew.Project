import { encodeToCells } from './decoders/braille.js'

// Порядок точек при построчном рендере сетки 2×3: [1,4,2,5,3,6].
const DOT_ORDER = [1, 4, 2, 5, 3, 6]

// Геометрия SVG-экспорта (light-тема для печати).
const EXP = {
  pad: 18,
  dotR: 6,
  colGap: 20, // расстояние между центрами столбцов
  rowGap: 20, // расстояние между центрами строк
  charGap: 14,
  wordGap: 26,
  capH: 18,
  bg: '#ffffff',
  on: '#1a2035',
  off: '#d4d8e2',
  cap: 'rgba(26,32,53,0.55)',
}

/**
 * Строит DOM одной клетки Брайля (сетка 2×3 точек) для экрана.
 *
 * @param {number} mask Битовая маска клетки.
 * @param {string} [srcChar] Исходный символ (подпись под клеткой).
 * @returns {HTMLElement}
 */
function buildCell(mask, srcChar) {
  const cell = document.createElement('div')
  cell.className = 'braille-cell'

  const grid = document.createElement('div')
  grid.className = 'braille-cell__grid'
  for (const dot of DOT_ORDER) {
    const el = document.createElement('span')
    const filled = (mask & (1 << (dot - 1))) !== 0
    el.className = filled ? 'braille-dot braille-dot--on' : 'braille-dot'
    grid.appendChild(el)
  }
  cell.appendChild(grid)

  if (srcChar) {
    const caption = document.createElement('span')
    caption.className = 'braille-cell__caption'
    caption.textContent = srcChar
    cell.appendChild(caption)
  }
  return cell
}

/**
 * Экранирует спецсимволы XML для безопасной вставки в SVG-подписи.
 *
 * @param {string} value
 * @returns {string}
 */
function escapeXml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

/**
 * Строит SVG-строку сетки клеток Брайля (для экспорта в PNG/SVG).
 *
 * @param {import('./decoders/braille.js').StreamCell[]} cells
 * @returns {string}
 */
export function buildCellsSvg(cells) {
  const cellW = EXP.colGap + EXP.dotR * 2
  const cellH = EXP.rowGap * 2 + EXP.dotR * 2

  // Ширина полотна.
  let width = EXP.pad
  for (const cell of cells) {
    width += 'space' in cell ? EXP.wordGap : cellW + EXP.charGap
  }
  width = Math.max(width + EXP.pad, cellW + EXP.pad * 2)
  const height = EXP.pad + cellH + EXP.capH + EXP.pad

  let svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${Math.round(width)}" height="${height}">`
  svg += `<rect width="${Math.round(width)}" height="${height}" fill="${EXP.bg}"/>`

  let x = EXP.pad
  for (const cell of cells) {
    if ('space' in cell) { x += EXP.wordGap; continue }

    const mask = 'unknown' in cell ? 0 : cell.mask
    const cx = [x + EXP.dotR, x + EXP.dotR + EXP.colGap]
    const cy = [EXP.pad + EXP.dotR, EXP.pad + EXP.dotR + EXP.rowGap, EXP.pad + EXP.dotR + EXP.rowGap * 2]
    // dot n → (столбец, строка): 1→(0,0) 2→(0,1) 3→(0,2) 4→(1,0) 5→(1,1) 6→(1,2).
    const pos = { 1: [0, 0], 2: [0, 1], 3: [0, 2], 4: [1, 0], 5: [1, 1], 6: [1, 2] }
    for (let d = 1; d <= 6; d++) {
      const [col, row] = pos[d]
      const filled = (mask & (1 << (d - 1))) !== 0
      svg += `<circle cx="${cx[col]}" cy="${cy[row]}" r="${EXP.dotR}" `
        + (filled ? `fill="${EXP.on}"/>` : `fill="none" stroke="${EXP.off}" stroke-width="1.5"/>`)
    }

    const caption = cell.src ?? ''
    if (caption) {
      svg += `<text x="${x + cellW / 2}" y="${EXP.pad + cellH + 13}" text-anchor="middle" `
        + `fill="${EXP.cap}" font-family="monospace" font-size="12">${escapeXml(caption)}</text>`
    }
    x += cellW + EXP.charGap
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
      if (!w || !h) { reject(new Error('SVG has zero dimensions')); return }
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
 * Инициализирует визуальную сетку клеток Брайля в контейнере visualOutput
 * с возможностью экспорта в PNG/SVG.
 *
 * @param {{ visualOutput: HTMLElement|null }} deps
 * @returns {{
 *   render: (text: string, opts: object) => void,
 *   clear: () => void,
 *   hasContent: () => boolean,
 *   downloadPng: () => Promise<void>,
 *   downloadSvg: () => void,
 * }}
 */
export function initBrailleCells({ visualOutput }) {
  /** @type {import('./decoders/braille.js').StreamCell[]} */
  let lastCells = []

  const clear = () => {
    lastCells = []
    if (visualOutput) {
      visualOutput.innerHTML = ''
      visualOutput.style.display = 'none'
    }
  }

  const render = (text, opts = {}) => {
    if (!visualOutput) return
    const cells = encodeToCells(text, opts)
    lastCells = cells
    if (cells.length === 0) { clear(); return }

    const wrap = document.createElement('div')
    wrap.className = 'braille-cells'
    for (const cell of cells) {
      if ('space' in cell) {
        const gap = document.createElement('div')
        gap.className = 'braille-cells__gap'
        wrap.appendChild(gap)
        continue
      }
      if ('unknown' in cell) {
        wrap.appendChild(buildCell(0, cell.src))
        continue
      }
      wrap.appendChild(buildCell(cell.mask, cell.src ?? ''))
    }

    visualOutput.innerHTML = ''
    visualOutput.appendChild(wrap)
    visualOutput.style.display = 'block'
  }

  const hasContent = () => lastCells.length > 0

  const downloadSvg = () => {
    if (!hasContent()) return
    const svg = buildCellsSvg(lastCells)
    downloadBlob(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }), 'braille.svg')
  }

  const downloadPng = async () => {
    if (!hasContent()) return
    const svg = buildCellsSvg(lastCells)
    try {
      downloadBlob(await svgToPngBlob(svg), 'braille.png')
    } catch {
      downloadBlob(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }), 'braille.svg')
    }
  }

  return { render, clear, hasContent, downloadPng, downloadSvg }
}
