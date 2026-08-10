/**
 * Обучающая визуализация шифра нигилистов: построенный квадрат Полибия
 * с числовыми координатами строк/столбцов и пошаговая таблица разложения
 * «буква + ключ = число» (или обратного вычитания при расшифровке).
 *
 * Рендерится в дополнительный блок #ciphers-visual-output ПОД основным
 * полем результата — само поле результата остаётся видимым (стандартный
 * поток вывода строки шифротекста).
 */

const esc = (s) => String(s)
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')

/**
 * Инициализирует панель визуализации шифра нигилистов.
 *
 * @param {{
 *   visualOutput: HTMLElement|null,
 *   ui: Record<string, string>,
 * }} ctx
 * @return {{ showResult: (response: object) => void, showEmpty: () => void }}
 */
export function initNihilistPanel({ visualOutput, ui }) {
  const labels = {
    squareTitle: ui.nihilistSquareTitle || 'Polybius square',
    stepsTitle:  ui.nihilistStepsTitle  || 'Step-by-step',
    colPlain:    ui.nihilistColPlain    || 'Letter',
    colCode:     ui.nihilistColCode     || 'Code',
    colKey:      ui.nihilistColKey      || 'Key',
    colKeyCode:  ui.nihilistColKeyCode  || 'Key code',
    colCipher:   ui.nihilistColCipher   || 'Cipher',
    empty:       ui.nihilistEmptyLabel  || 'Enter text and two keys to see the square and the calculation',
  }

  const showEmpty = () => {
    if (!visualOutput) return
    visualOutput.innerHTML = `<p class="nihilist-empty">${esc(labels.empty)}</p>`
  }

  /** Рисует HTML квадрата Полибия с числовыми метками строк/столбцов. */
  const renderSquare = (square, used) => {
    if (!Array.isArray(square) || square.length === 0) return ''
    const size = square.length

    const headCells = ['<span class="nihilist-grid__corner"></span>']
    for (let c = 0; c < size; c++) {
      headCells.push(`<span class="nihilist-grid__axis">${c + 1}</span>`)
    }

    const rowsHtml = square.map((row, r) => {
      const cells = [`<span class="nihilist-grid__axis">${r + 1}</span>`]
      row.forEach((ch) => {
        const isUsed = used.has(String(ch).toUpperCase())
        cells.push(`<span class="nihilist-grid__cell${isUsed ? ' nihilist-grid__cell--used' : ''}">${esc(ch)}</span>`)
      })
      return cells.join('')
    }).join('')

    return `<div class="nihilist-block">`
      + `<div class="nihilist-block__title">${esc(labels.squareTitle)}</div>`
      + `<div class="nihilist-grid" style="--nih-size:${size}">${headCells.join('')}${rowsHtml}</div>`
      + `</div>`
  }

  /** Рисует таблицу пошагового разложения. */
  const renderSteps = (steps) => {
    if (!Array.isArray(steps) || steps.length === 0) return ''

    const header = `<div class="nihilist-steps__row nihilist-steps__row--head">`
      + `<span>${esc(labels.colPlain)}</span>`
      + `<span>${esc(labels.colCode)}</span>`
      + `<span>${esc(labels.colKey)}</span>`
      + `<span>${esc(labels.colKeyCode)}</span>`
      + `<span>${esc(labels.colCipher)}</span>`
      + `</div>`

    const rows = steps.map((step) => `<div class="nihilist-steps__row">`
      + `<span class="nihilist-steps__sym">${esc(step.symbol ?? '')}</span>`
      + `<span>${esc(step.code ?? '')}</span>`
      + `<span class="nihilist-steps__sym">${esc(step.key_symbol ?? '')}</span>`
      + `<span>${esc(step.key_code ?? '')}</span>`
      + `<span class="nihilist-steps__cipher">${esc(step.cipher ?? '')}</span>`
      + `</div>`).join('')

    return `<div class="nihilist-block">`
      + `<div class="nihilist-block__title">${esc(labels.stepsTitle)}</div>`
      + `<div class="nihilist-steps">${header}${rows}</div>`
      + `</div>`
  }

  const showResult = (response) => {
    if (!visualOutput) return

    const square = Array.isArray(response?.square) ? response.square : []
    const steps  = Array.isArray(response?.steps) ? response.steps : []

    if (square.length === 0 && steps.length === 0) {
      showEmpty()
      return
    }

    // Подсвечиваем в квадрате буквы, участвующие в текущем разложении.
    const used = new Set()
    steps.forEach((step) => {
      if (step.symbol) used.add(String(step.symbol).toUpperCase())
    })

    visualOutput.innerHTML = renderSquare(square, used) + renderSteps(steps)
  }

  return { showResult, showEmpty }
}
