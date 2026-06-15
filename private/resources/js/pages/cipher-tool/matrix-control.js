/**
 * Инициализирует управление матрицей ключа (Hill cipher и аналоги).
 * Регистрирует обработчики ячеек, кнопок размера и keyInput.
 * Возвращает { renderMatrixGrid, setMatrixFromKeyValue, updateMatrixStatus }.
 *
 * @param {{
 *   matrixControl: HTMLElement|null,
 *   matrixGrid: HTMLElement|null,
 *   matrixStatus: HTMLElement|null,
 *   keyInput: HTMLInputElement|null,
 *   alphabetSelect: HTMLSelectElement|null,
 *   onSave: () => void,
 *   onScheduleRun: () => void,
 * }} ctx
 * @return {{
 *   renderMatrixGrid: (size: number, matrix?: number[][]) => void,
 *   setMatrixFromKeyValue: (value: string) => void,
 *   updateMatrixStatus: () => void,
 * }}
 */
export function initMatrixControl({
  matrixControl, matrixGrid, matrixStatus, keyInput, alphabetSelect,
  onSave, onScheduleRun,
}) {
  let isSyncing = false

  const positiveMod = (value, modulus) => ((value % modulus) + modulus) % modulus

  const gcd = (left, right) => {
    let a = Math.abs(Math.trunc(left))
    let b = Math.abs(Math.trunc(right))
    while (b !== 0) {
      const next = a % b
      a = b
      b = next
    }
    return a
  }

  const determinant = (matrix) => {
    const size = matrix.length
    if (size === 1) return Number(matrix[0]?.[0] ?? 0)
    if (size === 2) {
      return Number(matrix[0][0]) * Number(matrix[1][1]) - Number(matrix[0][1]) * Number(matrix[1][0])
    }
    return matrix[0].reduce((sum, value, col) => {
      const minor = matrix.slice(1).map((row) => row.filter((_, index) => index !== col))
      return sum + (col % 2 === 0 ? 1 : -1) * Number(value) * determinant(minor)
    }, 0)
  }

  const parseMatrixValue = (value) => {
    const rows = String(value || '')
      .trim()
      .split(/\s*;\s*/u)
      .map((row) => (row.match(/-?\d+/gu) ?? []).map((item) => Number.parseInt(item, 10)))
      .filter((row) => row.length > 0)

    if (rows.length === 1) {
      const flat = rows[0]
      const size = Math.sqrt(flat.length)
      if (Number.isInteger(size)) {
        return Array.from({ length: size }, (_, row) => flat.slice(row * size, row * size + size))
      }
    }

    return rows
  }

  const normalizeMatrixSize = (size) => {
    const numericSize = Number.parseInt(String(size), 10)
    return [2, 3, 4, 5].includes(numericSize) ? numericSize : 2
  }

  const getMatrixFromGrid = () => {
    if (!matrixGrid) return []
    const size = normalizeMatrixSize(matrixGrid.dataset.matrixSize || 2)
    return Array.from({ length: size }, (_, row) => {
      return Array.from({ length: size }, (_, col) => {
        const inputEl = matrixGrid.querySelector(`[data-matrix-cell="${row}:${col}"]`)
        const value = Number.parseInt(String(inputEl?.value ?? '0'), 10)
        return Number.isFinite(value) ? value : 0
      })
    })
  }

  const serializeMatrix = (matrix) =>
    matrix.map((row) => row.map((v) => String(Number.isFinite(v) ? Math.trunc(v) : 0)).join(' ')).join('; ')

  const selectedAlphabetSize = () => {
    if (!alphabetSelect) return 26
    const selected = alphabetSelect.options[alphabetSelect.selectedIndex]
    const size = Number.parseInt(String(selected?.dataset?.alphabetSize ?? ''), 10)
    return Number.isFinite(size) && size > 0 ? size : 26
  }

  const updateMatrixStatus = () => {
    if (!matrixControl || !matrixStatus) return
    const matrix = getMatrixFromGrid()
    const modulus = selectedAlphabetSize()
    const det = determinant(matrix)
    const normalizedDet = positiveMod(det, modulus)
    const isValid = gcd(normalizedDet, modulus) === 1
    const detLabel = matrixControl.dataset.matrixDeterminantLabel || 'det'
    const validLabel = matrixControl.dataset.matrixValidLabel || 'Valid key matrix'
    const invalidLabel = matrixControl.dataset.matrixInvalidLabel || 'Matrix is not invertible for this alphabet'

    matrixStatus.textContent = `${detLabel} = ${normalizedDet} (mod ${modulus}) · ${isValid ? validLabel : invalidLabel}`
    matrixStatus.classList.toggle('ciphers-settings-matrix__status--ok', isValid)
    matrixStatus.classList.toggle('ciphers-settings-matrix__status--error', !isValid)
  }

  const syncMatrixValueFromGrid = () => {
    if (!keyInput || !matrixGrid) return
    isSyncing = true
    keyInput.value = serializeMatrix(getMatrixFromGrid())
    isSyncing = false
    updateMatrixStatus()
  }

  const renderMatrixGrid = (size, matrix = []) => {
    if (!matrixControl || !matrixGrid) return
    const normalizedSize = normalizeMatrixSize(size)
    matrixGrid.dataset.matrixSize = String(normalizedSize)
    matrixGrid.style.setProperty('--matrix-size', String(normalizedSize))
    matrixGrid.innerHTML = ''

    for (let row = 0; row < normalizedSize; row++) {
      for (let col = 0; col < normalizedSize; col++) {
        const cellWrap = document.createElement('div')
        cellWrap.className = 'ciphers-matrix-cell-wrap'

        const inputEl = document.createElement('input')
        inputEl.type = 'number'
        inputEl.inputMode = 'numeric'
        inputEl.className = 'ciphers-settings-matrix__cell'
        inputEl.dataset.matrixCell = `${row}:${col}`
        inputEl.value = String(matrix[row]?.[col] ?? (row === col ? 1 : 0))
        inputEl.setAttribute('aria-label', `K ${row + 1},${col + 1}`)

        const handleCellChange = () => {
          syncMatrixValueFromGrid()
          onSave()
          onScheduleRun()
        }
        inputEl.addEventListener('input', handleCellChange)
        inputEl.addEventListener('change', handleCellChange)

        const spinners = document.createElement('div')
        spinners.className = 'ciphers-matrix-cell-spinners'
        spinners.setAttribute('aria-hidden', 'true')

        const upBtn = document.createElement('button')
        upBtn.type = 'button'
        upBtn.className = 'ciphers-matrix-cell-spinner'
        upBtn.tabIndex = -1
        upBtn.innerHTML = '<svg width="8" height="5" viewBox="0 0 8 5" fill="none"><path d="M1 4L4 1L7 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        upBtn.addEventListener('mousedown', (e) => {
          e.preventDefault()
          inputEl.value = String((Number.parseInt(inputEl.value || '0', 10) || 0) + 1)
          inputEl.dispatchEvent(new Event('input', { bubbles: true }))
        })

        const downBtn = document.createElement('button')
        downBtn.type = 'button'
        downBtn.className = 'ciphers-matrix-cell-spinner'
        downBtn.tabIndex = -1
        downBtn.innerHTML = '<svg width="8" height="5" viewBox="0 0 8 5" fill="none"><path d="M1 1L4 4L7 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        downBtn.addEventListener('mousedown', (e) => {
          e.preventDefault()
          inputEl.value = String((Number.parseInt(inputEl.value || '0', 10) || 0) - 1)
          inputEl.dispatchEvent(new Event('input', { bubbles: true }))
        })

        spinners.appendChild(upBtn)
        spinners.appendChild(downBtn)
        cellWrap.appendChild(inputEl)
        cellWrap.appendChild(spinners)
        matrixGrid.appendChild(cellWrap)
      }
    }

    matrixControl.querySelectorAll('button[data-matrix-size]').forEach((button) => {
      button.classList.toggle(
        'ciphers-settings-matrix__size--active',
        button.dataset.matrixSize === String(normalizedSize),
      )
    })

    syncMatrixValueFromGrid()
  }

  const setMatrixFromKeyValue = (value) => {
    if (!matrixControl || !keyInput) return
    const matrix = parseMatrixValue(value)
    const size = normalizeMatrixSize(matrix.length > 0 ? matrix.length : 2)
    renderMatrixGrid(size, matrix)
  }

  keyInput?.addEventListener('input', () => {
    if (!isSyncing) setMatrixFromKeyValue(keyInput.value)
  })

  matrixControl?.querySelectorAll('button[data-matrix-size]').forEach((button) => {
    button.addEventListener('click', () => {
      const currentMatrix = getMatrixFromGrid()
      renderMatrixGrid(normalizeMatrixSize(button.dataset.matrixSize), currentMatrix)
      onSave()
      onScheduleRun()
    })
  })

  return { renderMatrixGrid, setMatrixFromKeyValue, updateMatrixStatus }
}
