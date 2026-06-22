/**
 * Визуализация диска Альберти: SVG-колесо + кнопки вращения + таблица Current Mapping.
 */

const OUTER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
const NS = 'http://www.w3.org/2000/svg'

/**
 * Генерирует внутренний алфавит из ключевого слова (зеркало PHP-логики).
 */
function buildInner(keyword) {
  const used = new Set()
  let result = ''
  for (const ch of (keyword || '').toUpperCase()) {
    if (/[A-Z]/.test(ch) && !used.has(ch)) { used.add(ch); result += ch }
  }
  for (const ch of OUTER) {
    if (!used.has(ch)) result += ch
  }
  return result
}

/**
 * Создаёт SVG-элемент с атрибутами.
 */
function svgEl(tag, attrs = {}, text = '') {
  const node = document.createElementNS(NS, tag)
  for (const [k, v] of Object.entries(attrs)) node.setAttribute(k, String(v))
  if (text) node.textContent = text
  return node
}

/**
 * Создаёт HTML-элемент с атрибутами и дочерними узлами.
 */
function hel(tag, attrs = {}, ...children) {
  const node = document.createElement(tag)
  for (const [k, v] of Object.entries(attrs)) {
    if (k === 'class') node.className = v
    else if (k === 'html') node.innerHTML = v
    else node.setAttribute(k, String(v))
  }
  for (const child of children) {
    if (child == null) continue
    node.appendChild(typeof child === 'string' ? document.createTextNode(child) : child)
  }
  return node
}

/**
 * Инициализирует панель диска Альберти: SVG-колесо с кнопками вращения
 * и таблица Current Mapping.
 *
 * @param {{ container: Element, keyInput: Element|null, indexSelect: Element|null, diskLabel: string, mappingLabel: string }} opts
 * @returns {{ update: (innerAlphabet: string, indexOffset: number) => void }}
 */
export function initAlbertiWheel({ container, keyInput, indexSelect, diskLabel = 'Alberti Cipher Disk', mappingLabel = 'Current Mapping' }) {
  /* ── SVG параметры ─────────────────────────────────────────────── */
  const W = 280
  const CX = W / 2
  const CY = W / 2
  const R_BG         = 136
  const R_OUTER_RING = 130
  const R_OUTER_TEXT = 115
  const R_SEP        = 92
  const R_INNER_RING = 90
  const R_INNER_TEXT = 75
  const R_HUB        = 30

  /* ── SVG ───────────────────────────────────────────────────────── */
  const svg = svgEl('svg', {
    viewBox: `0 0 ${W} ${W}`,
    class: 'aw-svg',
    role: 'img',
    'aria-label': 'Alberti cipher disk',
  })

  // Тени и засечки
  const defs = svgEl('defs')
  const filter = svgEl('filter', { id: 'aw-glow', x: '-20%', y: '-20%', width: '140%', height: '140%' })
  const feGaussian = svgEl('feGaussianBlur', { stdDeviation: '3', result: 'blur' })
  const feMerge = svgEl('feMerge')
  feMerge.appendChild(svgEl('feMergeNode', { in: 'blur' }))
  feMerge.appendChild(svgEl('feMergeNode', { in: 'SourceGraphic' }))
  filter.appendChild(feGaussian)
  filter.appendChild(feMerge)
  defs.appendChild(filter)
  svg.appendChild(defs)

  // Фоновый круг (тень-обёртка)
  svg.appendChild(svgEl('circle', { cx: CX, cy: CY, r: R_BG + 4, class: 'aw-shadow' }))
  // Внешний диск
  svg.appendChild(svgEl('circle', { cx: CX, cy: CY, r: R_BG, class: 'aw-bg' }))
  // Внешнее кольцо
  svg.appendChild(svgEl('circle', { cx: CX, cy: CY, r: R_OUTER_RING, class: 'aw-outer-ring' }))
  // Разделительная линия
  svg.appendChild(svgEl('circle', { cx: CX, cy: CY, r: R_SEP, class: 'aw-sep' }))
  // Внутренний диск
  svg.appendChild(svgEl('circle', { cx: CX, cy: CY, r: R_INNER_RING, class: 'aw-inner-ring' }))
  // Ступица
  svg.appendChild(svgEl('circle', { cx: CX, cy: CY, r: R_HUB, class: 'aw-hub' }))

  // Засечки по внешнему кольцу
  const ticksG = svgEl('g', { class: 'aw-ticks' })
  for (let i = 0; i < 26; i++) {
    const a = (i * 2 * Math.PI / 26) - Math.PI / 2
    const x1 = CX + (R_OUTER_RING - 2) * Math.cos(a)
    const y1 = CY + (R_OUTER_RING - 2) * Math.sin(a)
    const x2 = CX + (R_BG - 2) * Math.cos(a)
    const y2 = CY + (R_BG - 2) * Math.sin(a)
    ticksG.appendChild(svgEl('line', {
      x1: x1.toFixed(2), y1: y1.toFixed(2),
      x2: x2.toFixed(2), y2: y2.toFixed(2),
      class: 'aw-tick',
    }))
  }
  svg.appendChild(ticksG)

  // Буквы внешнего кольца (A-Z, неподвижны)
  const outerLettersG = svgEl('g', { class: 'aw-outer-letters' })
  const outerTexts = []
  for (let i = 0; i < 26; i++) {
    const a = (i * 2 * Math.PI / 26) - Math.PI / 2
    const t = svgEl('text', {
      x: (CX + R_OUTER_TEXT * Math.cos(a)).toFixed(2),
      y: (CY + R_OUTER_TEXT * Math.sin(a)).toFixed(2),
      class: 'aw-letter aw-letter--outer',
      'text-anchor': 'middle',
      'dominant-baseline': 'central',
      'data-pos': i,
    }, OUTER[i])
    outerLettersG.appendChild(t)
    outerTexts.push(t)
  }
  svg.appendChild(outerLettersG)

  // Буквы внутреннего кольца (обновляются)
  const innerLettersG = svgEl('g', { class: 'aw-inner-letters' })
  const innerTexts = []
  for (let i = 0; i < 26; i++) {
    const a = (i * 2 * Math.PI / 26) - Math.PI / 2
    const t = svgEl('text', {
      x: (CX + R_INNER_TEXT * Math.cos(a)).toFixed(2),
      y: (CY + R_INNER_TEXT * Math.sin(a)).toFixed(2),
      class: 'aw-letter aw-letter--inner',
      'text-anchor': 'middle',
      'dominant-baseline': 'central',
      'data-pos': i,
    }, '')
    innerLettersG.appendChild(t)
    innerTexts.push(t)
  }
  svg.appendChild(innerLettersG)

  // Указатель (треугольник) сверху
  svg.appendChild(svgEl('polygon', {
    points: `${CX},${CY - R_BG - 2} ${CX - 7},${CY - R_BG + 12} ${CX + 7},${CY - R_BG + 12}`,
    class: 'aw-pointer',
  }))

  // Текст текущего индекса в центре
  const centerLabel = svgEl('text', {
    x: CX, y: CY,
    class: 'aw-center-label',
    'text-anchor': 'middle',
    'dominant-baseline': 'central',
  }, 'A')
  svg.appendChild(centerLabel)

  /* ── Кнопки вращения ───────────────────────────────────────────── */
  const btnLeft = hel('button', {
    class: 'aw-rotate-btn',
    type: 'button',
    'aria-label': 'Rotate left',
    title: 'Rotate inner disk left',
    html: '<i class="bi bi-chevron-left"></i>',
  })
  const btnRight = hel('button', {
    class: 'aw-rotate-btn',
    type: 'button',
    'aria-label': 'Rotate right',
    title: 'Rotate inner disk right',
    html: '<i class="bi bi-chevron-right"></i>',
  })

  const svgWrap = hel('div', { class: 'aw-svg-wrap' }, svg)
  const wheelSection = hel('div', { class: 'aw-wheel-section' }, btnLeft, svgWrap, btnRight)

  /* ── Таблица Current Mapping ────────────────────────────────────── */
  const outerMapCells = []
  const innerMapCells = []
  const mapRow1 = hel('div', { class: 'aw-map__row aw-map__row--outer' })
  const mapRow2 = hel('div', { class: 'aw-map__row aw-map__row--inner' })

  for (let i = 0; i < 26; i++) {
    const oc = hel('div', { class: 'aw-map__cell' }, OUTER[i])
    const ic = hel('div', { class: 'aw-map__cell' }, '')
    mapRow1.appendChild(oc)
    mapRow2.appendChild(ic)
    outerMapCells.push(oc)
    innerMapCells.push(ic)
  }

  const mapSection = hel('div', { class: 'aw-map-section' },
    hel('div', { class: 'aw-map__label' }, mappingLabel),
    hel('div', { class: 'aw-map__scroll' },
      hel('div', { class: 'aw-map__table' }, mapRow1, mapRow2),
    ),
  )

  /* ── Заголовок панели ──────────────────────────────────────────── */
  const header = hel('div', { class: 'aw-header' })
  header.innerHTML = `<i class="bi bi-disc-fill"></i> ${diskLabel}`

  /* ── Сборка панели ─────────────────────────────────────────────── */
  const body = hel('div', { class: 'aw-body' }, wheelSection, mapSection)
  const panel = hel('div', { class: 'aw-panel' }, header, body)

  container.innerHTML = ''
  container.appendChild(panel)

  /* ── Состояние ─────────────────────────────────────────────────── */
  let currentInner  = buildInner(keyInput?.value || '')
  let currentOffset = letterToOffset(indexSelect?.value || 'A')

  /**
   * Перерисовывает диск и таблицу маппинга.
   */
  function render(innerAlphabet, offset) {
    const inner = (innerAlphabet || OUTER).toUpperCase().replace(/[^A-Z]/g, '').padEnd(26, OUTER).slice(0, 26)

    for (let pos = 0; pos < 26; pos++) {
      const innerIdx = (pos - offset + 26) % 26
      const letter   = inner[innerIdx] || ''
      const isActive = pos === offset

      // SVG — внутреннее кольцо
      innerTexts[pos].textContent = letter
      innerTexts[pos].classList.toggle('aw-letter--inner-active', isActive)

      // SVG — внешнее кольцо
      outerTexts[pos].classList.toggle('aw-letter--outer-active', isActive)

      // Таблица
      outerMapCells[pos].classList.toggle('aw-map__cell--active', isActive)
      innerMapCells[pos].textContent = letter
      innerMapCells[pos].classList.toggle('aw-map__cell--active', isActive)
    }

    centerLabel.textContent = OUTER[offset] || 'A'
  }

  render(currentInner, currentOffset)

  /* ── Реакция на поле ключа ─────────────────────────────────────── */
  keyInput?.addEventListener('input', () => {
    currentInner = buildInner(keyInput.value)
    render(currentInner, currentOffset)
  })

  /* ── Реакция на select индекса ─────────────────────────────────── */
  indexSelect?.addEventListener('change', () => {
    currentOffset = letterToOffset(indexSelect.value)
    render(currentInner, currentOffset)
  })

  /* ── Кнопки вращения ───────────────────────────────────────────── */
  function rotateBy(dir) {
    currentOffset = (currentOffset + dir + 26) % 26
    if (indexSelect) {
      indexSelect.value = OUTER[currentOffset]
      // Диспатчим событие — cipher-tool.js подхватит и запустит API
      indexSelect.dispatchEvent(new Event('change', { bubbles: true }))
    } else {
      render(currentInner, currentOffset)
    }
  }

  btnLeft.addEventListener('click',  () => rotateBy(-1))
  btnRight.addEventListener('click', () => rotateBy(1))

  return {
    /**
     * Синхронизирует диск с ответом API после шифрования.
     */
    update(innerAlphabet, indexOffset) {
      currentInner  = (innerAlphabet || OUTER).toUpperCase()
      currentOffset = Number.isFinite(indexOffset) ? indexOffset % 26 : 0
      render(currentInner, currentOffset)
    },
  }
}

function letterToOffset(letter) {
  const ch  = (letter || 'A').toUpperCase().charAt(0)
  const pos = OUTER.indexOf(ch)
  return pos >= 0 ? pos : 0
}
