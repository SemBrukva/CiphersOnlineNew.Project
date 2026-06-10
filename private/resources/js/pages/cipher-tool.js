import { getDecoderBySlug } from './cipher-tool/decoder-registry.js'
import { playMorse, stopMorse, isMorsePlaying, downloadMorseWav } from './cipher-tool/morse-audio.js'
import { detectLanguage, getUnknownChars, isValidMorseFormat } from './cipher-tool/decoders/morse.js'

function canUsePreferenceStorage() {
  return window.CiphersOnlineConsent?.has('preferences') === true
}

/**
 * Инициализирует универсальную рабочую область инструмента на странице шифра.
 */
export function initCipherToolPage() {
  const root = document.querySelector('[data-page="cipher-tool"][data-cipher-tool]')
  if (!root) return

  const slug = root.getAttribute('data-cipher-tool') || ''
  const ui = parseJson(root.getAttribute('data-cipher-ui') || '{}')

  const input = document.getElementById('ciphers-input')
  const output = document.getElementById('ciphers-output')
  const tabEncode = document.getElementById('tab-encode')
  const tabDecode = document.getElementById('tab-decode')
  const inputLabel = document.getElementById('ciphers-input-label')
  const counter = document.getElementById('ciphers-counter')
  const feedback = document.getElementById('ciphers-feedback')
  const copyBtn = document.getElementById('ciphers-copy')
  const shareBtn = document.getElementById('ciphers-share')
  const resultCard = document.getElementById('ciphers-result-card')
  const resultLabel = document.querySelector('.ciphers-unified__field-label--result')
  const runBtn = document.getElementById('ciphers-primary')
  const liveModeInput = document.getElementById('ciphers-live-mode')
  const shiftInput = document.getElementById('ciphers-shift')
  const shiftDecBtn = document.getElementById('ciphers-shift-dec')
  const shiftIncBtn = document.getElementById('ciphers-shift-inc')
  const alphabetSelect = document.getElementById('ciphers-alphabet')
  const delimiterSelect = document.getElementById('ciphers-delimiter')
  const keyInput = document.getElementById('ciphers-key')
  const matrixControl = document.querySelector('[data-matrix-control]')
  const matrixGrid = matrixControl?.querySelector('[data-matrix-grid]') ?? null
  const matrixStatus = matrixControl?.querySelector('[data-matrix-status]') ?? null
  const generateKeyBtn = document.getElementById('ciphers-generate-key')
  const clearBtn = document.getElementById('ciphers-clear')
  const coverInput = document.getElementById('ciphers-cover')
  const coverCapacityEl = document.getElementById('ciphers-cover-capacity')

  if (!input || !output || !tabEncode || !tabDecode || !inputLabel || !counter) return

  let mode = 'encode'
  const isEncodingTool = slug.startsWith('encoding/')
  const calculationMode = String(ui.calculationMode || 'client').toLowerCase()
  const isApiMode = calculationMode === 'api'
  const isMorseTool = slug === 'codes-and-alphabets/morse-code'
  const apiAction = String(ui.apiAction || '').trim()
  const stateStorageKey = `cipher-tool:state:${slug}`
  let liveModeDebounceTimer = null
  const decoder = getDecoderBySlug(slug)
  const isClientTool = isEncodingTool || decoder !== null
  let matrixIsSyncing = false

  const labels = {
    chars: ui.charsLabel || 'chars',
    bytes: ui.bytesLabel || 'bytes',
    inputEncode: ui.inputLabelEncode || 'Input',
    inputDecode: ui.inputLabelDecode || 'Encoded input',
    placeholderEncode: ui.placeholderEncode || 'Enter text...',
    placeholderDecode: ui.placeholderDecode || 'Paste encoded value...',
    invalid: ui.feedbackInvalidInput || 'Invalid input for current mode.',
    notJson: ui.feedbackNotJson || 'Input must be valid JSON.',
    copied: ui.feedbackResultCopied || 'Result copied.',
    copyFailed: ui.feedbackResultCopyFailed || 'Unable to copy result.',
    urlCopied: ui.feedbackUrlCopied || 'Page URL copied.',
    urlCopyFailed: ui.feedbackUrlCopyFailed || 'Unable to copy page URL.',
    runFailed: ui.feedbackInvalidInput || 'Unable to process request.',
    morseErrInvalidFormat: ui.morseErrInvalidFormat || 'Invalid Morse code format.',
    morseWarnUnknownChars: ui.morseWarnUnknownChars || 'Unknown characters skipped: :chars.',
    morseInfoDecodedUnknown: ui.morseInfoDecodedUnknown || 'Some codes could not be decoded (shown as ?).',
  }

  const setFeedback = (message, isError = false, isInfo = false) => {
    if (!feedback) return
    feedback.textContent = message
    feedback.classList.toggle('error', isError)
    feedback.classList.toggle('info', isInfo && !isError)
  }

  const countUnicodeLetters = (text) => (text.match(/\p{L}/gu) ?? []).length

  const updateCoverCapacity = () => {
    if (!coverCapacityEl) return

    const secretLetters = countUnicodeLetters(input?.value ?? '')

    if (secretLetters === 0 || mode !== 'encode' || !(coverInput?.value ?? '').trim()) {
      coverCapacityEl.textContent = ''
      coverCapacityEl.className = 'ciphers-cover-capacity'
      return
    }

    const needed    = secretLetters * 5 + 10
    const available = countUnicodeLetters(coverInput?.value ?? '')

    coverCapacityEl.textContent = `${available} / ${needed}`
    coverCapacityEl.classList.toggle('ciphers-cover-capacity--ok',   available >= needed)
    coverCapacityEl.classList.toggle('ciphers-cover-capacity--warn', available < needed)
  }

  const saveState = () => {
    if (!slug || !canUsePreferenceStorage()) return

    try {
      const state = {
        alphabet: String(alphabetSelect?.value ?? 'auto'),
        delimiter: String(delimiterSelect?.value ?? 'dash'),
        shift: Number(shiftInput?.value ?? 0),
        key: String(keyInput?.value ?? ''),
        liveMode: Boolean(liveModeInput?.checked),
      }
      window.localStorage.setItem(stateStorageKey, JSON.stringify(state))
    } catch {
      // ignore storage errors
    }
  }

  const loadState = () => {
    if (!slug || !canUsePreferenceStorage()) return null

    try {
      const rawState = window.localStorage.getItem(stateStorageKey)
      if (!rawState) return null

      const parsedState = JSON.parse(rawState)
      if (!parsedState || typeof parsedState !== 'object') return null
      return parsedState
    } catch {
      return null
    }
  }

  const updateCounter = () => {
    const val = input.value || ''
    const chars = val.length

    if (slug === 'encoding/jwt-decoder') {
      const segments = val.trim() ? val.trim().split('.').length : 0
      counter.textContent = segments > 0
        ? `${chars} ${labels.chars} · ${segments} segments`
        : `${chars} ${labels.chars}`
      return
    }

    const bytes = new TextEncoder().encode(val).length
    counter.textContent = `${chars} ${labels.chars} · ${bytes} ${labels.bytes}`
  }

  const setMode = (nextMode) => {
    mode = nextMode
    const isEncode = mode === 'encode'
    tabEncode.classList.toggle('ciphers-tab--active', isEncode)
    tabEncode.setAttribute('aria-selected', isEncode ? 'true' : 'false')
    tabDecode.classList.toggle('ciphers-tab--active', !isEncode)
    tabDecode.setAttribute('aria-selected', !isEncode ? 'true' : 'false')
    inputLabel.textContent = isEncode ? labels.inputEncode : labels.inputDecode
    input.placeholder = isEncode ? labels.placeholderEncode : labels.placeholderDecode
    document.querySelectorAll('[data-encode-only]').forEach((el) => {
      el.style.display = isEncode ? '' : 'none'
    })
    process()
  }

  const setOutputState = (hasContent) => {
    output.classList.remove('ciphers-output--flash')
    if (hasContent) {
      void output.offsetWidth
      output.classList.add('ciphers-output--flash')
      resultCard?.classList.add('ciphers-result-card--live')
      resultLabel?.classList.add('ciphers-unified__field-label--result-live')
    } else {
      resultCard?.classList.remove('ciphers-result-card--live')
      resultLabel?.classList.remove('ciphers-unified__field-label--result-live')
    }
  }

  const getMaxShift = () => {
    const inputMax = Number(shiftInput?.max ?? 39)
    const fallbackMax = Number.isFinite(inputMax) ? inputMax : 39
    if (!alphabetSelect) return fallbackMax
    const selected = alphabetSelect.options[alphabetSelect.selectedIndex]
    const rawValue = Number(selected?.dataset?.maxShift ?? fallbackMax)
    return Number.isFinite(rawValue) && rawValue >= 0 ? rawValue : fallbackMax
  }

  const getMinShift = () => {
    const rawValue = Number(shiftInput?.min ?? 0)
    return Number.isFinite(rawValue) ? rawValue : 0
  }

  const normalizeShiftInput = () => {
    if (!shiftInput) return 0
    const numericValue = Number(shiftInput.value)
    return Number.isFinite(numericValue) ? Math.trunc(numericValue) : 0
  }

  const setShiftValue = (nextValue) => {
    if (!shiftInput) return
    const maxShift = getMaxShift()
    const minShift = getMinShift()
    const clamped = Math.min(Math.max(minShift, Math.trunc(nextValue)), maxShift)
    shiftInput.max = String(maxShift)
    shiftInput.value = String(clamped)
  }

  const syncShiftWithAlphabet = () => {
    setShiftValue(normalizeShiftInput())
  }

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

  const serializeMatrix = (matrix) => {
    return matrix.map((row) => row.map((value) => String(Number.isFinite(value) ? Math.trunc(value) : 0)).join(' ')).join('; ')
  }

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
    matrixIsSyncing = true
    keyInput.value = serializeMatrix(getMatrixFromGrid())
    matrixIsSyncing = false
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

        const handleMatrixCellChange = () => {
          syncMatrixValueFromGrid()
          saveState()
          scheduleApiRun()
        }
        inputEl.addEventListener('input', handleMatrixCellChange)
        inputEl.addEventListener('change', handleMatrixCellChange)

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
      button.classList.toggle('ciphers-settings-matrix__size--active', button.dataset.matrixSize === String(normalizedSize))
    })

    syncMatrixValueFromGrid()
  }

  const setMatrixFromKeyValue = (value) => {
    if (!matrixControl || !keyInput) return
    const matrix = parseMatrixValue(value)
    const size = normalizeMatrixSize(matrix.length > 0 ? matrix.length : 2)
    renderMatrixGrid(size, matrix)
  }

  const applySavedState = () => {
    const savedState = loadState()
    if (!savedState) return

    if (alphabetSelect && typeof savedState.alphabet === 'string' && savedState.alphabet !== '') {
      const hasOption = Array.from(alphabetSelect.options).some((option) => option.value === savedState.alphabet)
      if (hasOption) {
        alphabetSelect.value = savedState.alphabet
      }
    }

    if (delimiterSelect && typeof savedState.delimiter === 'string' && savedState.delimiter !== '') {
      const hasOption = Array.from(delimiterSelect.options).some((option) => option.value === savedState.delimiter)
      if (hasOption) {
        delimiterSelect.value = savedState.delimiter
      }
    }

    if (shiftInput && Number.isFinite(Number(savedState.shift))) {
      shiftInput.value = String(Math.trunc(Number(savedState.shift)))
    }

    if (keyInput && typeof savedState.key === 'string') {
      keyInput.value = savedState.key
    }

    if (matrixControl && keyInput) {
      setMatrixFromKeyValue(keyInput.value)
    }

    syncShiftWithAlphabet()

    if (liveModeInput && typeof savedState.liveMode === 'boolean') {
      liveModeInput.checked = savedState.liveMode
    }
  }

  const process = () => {
    const value = input.value || ''
    updateCounter()
    updateCoverCapacity()

    if (!value.trim()) {
      output.value = ''
      setOutputState(false)
      setFeedback('')
      return
    }

    if (!isClientTool || isApiMode) {
      if (isApiMode) {
        if (!value.trim()) {
          output.value = ''
          setOutputState(false)
          setFeedback('')
          return
        }

        scheduleApiRun()
        return
      }

      output.value = ''
      setOutputState(false)
      setFeedback('')
      return
    }

    try {
      const rawLang = alphabetSelect?.value || 'en'
      const effectiveLang = (isMorseTool && rawLang === 'auto')
        ? detectLanguage(value, mode)
        : rawLang

      if (isMorseTool && mode === 'decode' && !isValidMorseFormat(value)) {
        output.value = ''
        setOutputState(false)
        setFeedback(labels.morseErrInvalidFormat, true)
        return
      }

      output.value = transform(value, mode, decoder, { language: effectiveLang })
      setOutputState(true)

      if (isMorseTool) {
        if (mode === 'encode') {
          const unknown = getUnknownChars(value, effectiveLang)
          if (unknown.length > 0) {
            setFeedback(labels.morseWarnUnknownChars.replace(':chars', unknown.join(', ')), false, true)
          } else {
            setFeedback('')
          }
        } else if (output.value.includes('?')) {
          setFeedback(labels.morseInfoDecodedUnknown, false, true)
        } else {
          setFeedback('')
        }
      } else {
        setFeedback('')
      }

      sendAnalyticsBeacon(slug, mode)
    } catch (error) {
      output.value = ''
      setOutputState(false)
      setFeedback(error?.code === 'not-json' ? labels.notJson : labels.invalid, true)
    }
  }

  const runApiRequest = async () => {
    const text = input.value || ''
    if (!text.trim()) {
      output.value = ''
      setOutputState(false)
      setFeedback('')
      return
    }

    if (!isApiMode || apiAction === '') {
      setFeedback(labels.runFailed, true)
      return
    }

    const shift = Number(shiftInput?.value ?? 3)
    const alphabet = String(alphabetSelect?.value ?? 'auto')
    const delimiter = String(delimiterSelect?.value ?? 'dash')
    const key = String(keyInput?.value ?? '')
    const coverText = String(coverInput?.value ?? '')
    const direction = mode === 'decode' ? 'decrypt' : 'encrypt'

    if (runBtn) {
      runBtn.disabled = true
    }

    try {
      const apiMethod = window.api?.guest?.[apiAction]
      if (typeof apiMethod !== 'function') {
        throw new Error(`Unknown API action: ${apiAction}`)
      }

      const response = await apiMethod({
        text,
        direction,
        locale: ui.locale ?? 'en',
        settings: Object.fromEntries(
          Object.entries({
            shift,
            alphabet,
            delimiter,
            key,
            cover_text: coverText,
          }).filter(([, value]) => value !== '')
        ),
      })

      output.value = String(response?.result ?? '')
      setOutputState(Boolean(output.value))
      if (output.value && response?.warning) {
        setFeedback(String(response.warning), false, true)
      } else if (output.value && mode === 'decode' && ui.decodeNote) {
        setFeedback(ui.decodeNote, false, true)
      } else {
        setFeedback('')
      }
    } catch (error) {
      const fieldErrors = error?.response?.error?.details?.errors
      const firstFieldError = fieldErrors && typeof fieldErrors === 'object'
        ? Object.values(fieldErrors).flat()[0]
        : null
      const message = String(firstFieldError ?? error?.message ?? error?.response?.error?.message ?? labels.runFailed)
      output.value = ''
      setOutputState(false)
      setFeedback(message, true)
    } finally {
      if (runBtn) {
        runBtn.disabled = false
      }
    }
  }

  const isLiveModeEnabled = () => Boolean(liveModeInput?.checked)

  const scheduleApiRun = () => {
    if (!isApiMode || !isLiveModeEnabled()) return
    if (liveModeDebounceTimer !== null) {
      clearTimeout(liveModeDebounceTimer)
    }

    liveModeDebounceTimer = window.setTimeout(() => {
      liveModeDebounceTimer = null
      void runApiRequest()
    }, 350)
  }

  input.addEventListener('input', process)

  alphabetSelect?.addEventListener('change', () => {
    syncShiftWithAlphabet()
    updateMatrixStatus()
    saveState()
    if (isApiMode) {
      scheduleApiRun()
    } else if (isClientTool) {
      process()
    }
  })

  delimiterSelect?.addEventListener('change', () => {
    saveState()
    scheduleApiRun()
  })

  shiftInput?.addEventListener('input', () => {
    setShiftValue(normalizeShiftInput())
    saveState()
    scheduleApiRun()
  })

  shiftDecBtn?.addEventListener('click', () => {
    setShiftValue(normalizeShiftInput() - 1)
    saveState()
    scheduleApiRun()
  })

  shiftIncBtn?.addEventListener('click', () => {
    setShiftValue(normalizeShiftInput() + 1)
    saveState()
    scheduleApiRun()
  })

  keyInput?.addEventListener('input', () => {
    if (matrixControl && !matrixIsSyncing) {
      setMatrixFromKeyValue(keyInput.value)
    }
    saveState()
    scheduleApiRun()
  })

  matrixControl?.querySelectorAll('button[data-matrix-size]').forEach((button) => {
    button.addEventListener('click', () => {
      const currentMatrix = getMatrixFromGrid()
      renderMatrixGrid(normalizeMatrixSize(button.dataset.matrixSize), currentMatrix)
      saveState()
      scheduleApiRun()
    })
  })

  coverInput?.addEventListener('input', () => {
    updateCoverCapacity()
    scheduleApiRun()
  })

  generateKeyBtn?.addEventListener('click', () => {
    const text = input.value || ''
    if (!text || !keyInput) return
    const codepoints = Array.from(text)
    const randomKey = codepoints.map(() => {
      return String.fromCharCode(33 + Math.floor(Math.random() * 94))
    }).join('')
    keyInput.value = randomKey
    keyInput.dispatchEvent(new Event('input', { bubbles: true }))
  })

  document.querySelectorAll('.ciphers-example-chip').forEach((chip) => {
    chip.addEventListener('click', () => {
      const text = chip.getAttribute('data-example') || ''
      const alphabet = chip.getAttribute('data-alphabet') || ''
      const delimiter = chip.getAttribute('data-delimiter') || ''
      const key = chip.getAttribute('data-key')
      const keyInputId = chip.getAttribute('data-key-input') || 'ciphers-key'
      const shift = chip.getAttribute('data-shift')
      if (alphabet && alphabetSelect && alphabetSelect.value !== alphabet) {
        alphabetSelect.value = alphabet
        alphabetSelect.dispatchEvent(new Event('change', { bubbles: true }))
      }
      if (delimiter && delimiterSelect && delimiterSelect.value !== delimiter) {
        delimiterSelect.value = delimiter
        delimiterSelect.dispatchEvent(new Event('change', { bubbles: true }))
      }
      const targetKeyInput = document.getElementById(keyInputId)
      if (targetKeyInput) {
        targetKeyInput.value = key ?? ''
        targetKeyInput.dispatchEvent(new Event('input', { bubbles: true }))
      }
      if (shift !== null && shiftInput) {
        setShiftValue(Number(shift))
      }
      input.value = text
      const chipDirection = chip.getAttribute('data-direction') || ''
      if (chipDirection === 'decrypt') {
        setMode('decode')
      } else if (chipDirection === 'encrypt') {
        setMode('encode')
      } else if (looksLikeEncoded(text, decoder)) {
        setMode('decode')
      } else {
        setMode('encode')
      }
      if (isApiMode && liveModeInput && !liveModeInput.checked) {
        liveModeInput.checked = true
        saveState()
      }
      scheduleApiRun()
      input.focus()
    })
  })

  document.querySelectorAll('.ciphers-example-use').forEach((btn) => {
    btn.addEventListener('click', () => {
      const text = btn.getAttribute('data-example-text') || ''
      const alphabet = btn.getAttribute('data-alphabet') || ''
      const delimiter = btn.getAttribute('data-delimiter') || ''
      const key = btn.getAttribute('data-key')
      const keyInputId = btn.getAttribute('data-key-input') || 'ciphers-key'
      const shift = btn.getAttribute('data-shift')
      const direction = btn.getAttribute('data-direction') || ''
      if (alphabet && alphabetSelect && alphabetSelect.value !== alphabet) {
        alphabetSelect.value = alphabet
        alphabetSelect.dispatchEvent(new Event('change', { bubbles: true }))
      }
      if (delimiter && delimiterSelect && delimiterSelect.value !== delimiter) {
        delimiterSelect.value = delimiter
        delimiterSelect.dispatchEvent(new Event('change', { bubbles: true }))
      }
      const targetKeyInput = document.getElementById(keyInputId)
      if (targetKeyInput) {
        targetKeyInput.value = key ?? ''
        targetKeyInput.dispatchEvent(new Event('input', { bubbles: true }))
      }
      if (shift !== null && shiftInput) {
        setShiftValue(Number(shift))
      }
      input.value = text
      if (direction === 'decrypt') {
        setMode('decode')
      } else if (direction === 'encrypt') {
        setMode('encode')
      } else if (looksLikeEncoded(text, decoder)) {
        setMode('decode')
      } else {
        setMode('encode')
      }
      if (isApiMode && liveModeInput && !liveModeInput.checked) {
        liveModeInput.checked = true
        saveState()
      }
      scheduleApiRun()
      document.getElementById('ciphers-tool-shell')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
      input.focus()
    })
  })

  clearBtn?.addEventListener('click', () => {
    input.value = ''
    if (coverInput) coverInput.value = ''
    if (coverCapacityEl) { coverCapacityEl.textContent = ''; coverCapacityEl.className = 'ciphers-cover-capacity' }
    output.value = ''
    updateCounter()
    setOutputState(false)
    setFeedback('')
    const iconEl = clearBtn.querySelector('.bi')
    if (iconEl) iconEl.className = 'bi bi-check-lg'
    clearBtn.classList.add('ciphers-unified__btn-ghost--copied')
    window.setTimeout(() => {
      if (iconEl) iconEl.className = 'bi bi-x-lg'
      clearBtn.classList.remove('ciphers-unified__btn-ghost--copied')
    }, 800)
    input.focus()
  })

  copyBtn?.addEventListener('click', async () => {
    if (!output.value) return
    try {
      await navigator.clipboard.writeText(output.value)
      const iconEl = copyBtn.querySelector('.bi')
      if (iconEl) iconEl.className = 'bi bi-check-lg'
      copyBtn.classList.add('ciphers-unified__btn-ghost--copied')
      setFeedback(labels.copied)
      window.setTimeout(() => {
        if (iconEl) iconEl.className = 'bi bi-clipboard'
        copyBtn.classList.remove('ciphers-unified__btn-ghost--copied')
        setFeedback('')
      }, 1200)
    } catch {
      setFeedback(labels.copyFailed, true)
    }
  })

  shareBtn?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(window.location.href)
      const iconEl = shareBtn.querySelector('.bi')
      if (iconEl) iconEl.className = 'bi bi-check-lg'
      shareBtn.classList.add('ciphers-unified__btn-ghost--copied')
      setFeedback(labels.urlCopied)
      window.setTimeout(() => {
        if (iconEl) iconEl.className = 'bi bi-share'
        shareBtn.classList.remove('ciphers-unified__btn-ghost--copied')
        setFeedback('')
      }, 1200)
    } catch {
      setFeedback(labels.urlCopyFailed, true)
    }
  })

  tabEncode.addEventListener('click', () => {
    setMode('encode')
    scheduleApiRun()
  })

  tabDecode.addEventListener('click', () => {
    setMode('decode')
    scheduleApiRun()
  })

  liveModeInput?.addEventListener('change', () => {
    saveState()
    if (isLiveModeEnabled()) {
      scheduleApiRun()
    }
  })

  runBtn?.addEventListener('click', async () => {
    await runApiRequest()
  })

  applySavedState()
  if (matrixControl && keyInput && matrixGrid && matrixGrid.children.length === 0) {
    setMatrixFromKeyValue(keyInput.value)
  }
  saveState()
  setMode('encode')
  initCustomSelects()

  if (isMorseTool) {
    initMorsePlayer(output, input, ui, () => mode)
  }
}

/**
 * Заменяет все `.ciphers-settings-select` на кастомные dropdown,
 * сохраняя нативный select скрытым для совместимости с JS-логикой.
 */
function initCustomSelects() {
  let documentListenerAdded = false

  document.querySelectorAll('.ciphers-settings-select').forEach((nativeSelect) => {
    if (nativeSelect.dataset.customSelectInit) return
    nativeSelect.dataset.customSelectInit = '1'

    const wrapper = document.createElement('div')
    wrapper.className = 'ciphers-custom-select'
    nativeSelect.parentNode.insertBefore(wrapper, nativeSelect)
    nativeSelect.style.display = 'none'
    wrapper.appendChild(nativeSelect)

    const trigger = document.createElement('button')
    trigger.type = 'button'
    trigger.className = 'ciphers-custom-select__trigger'
    trigger.setAttribute('aria-haspopup', 'listbox')
    trigger.setAttribute('aria-expanded', 'false')

    const dropdown = document.createElement('div')
    dropdown.className = 'ciphers-custom-select__dropdown'
    dropdown.setAttribute('role', 'listbox')

    wrapper.appendChild(trigger)
    wrapper.appendChild(dropdown)

    const updateTrigger = () => {
      const opt = nativeSelect.options[nativeSelect.selectedIndex]
      trigger.textContent = opt ? opt.text : ''
    }

    const refreshOptions = () => {
      dropdown.innerHTML = ''
      Array.from(nativeSelect.options).forEach((opt) => {
        const item = document.createElement('div')
        item.className = 'ciphers-custom-select__option'
        item.setAttribute('role', 'option')
        const isSelected = opt.value === nativeSelect.value
        if (isSelected) item.classList.add('ciphers-custom-select__option--selected')
        item.setAttribute('aria-selected', isSelected ? 'true' : 'false')
        item.dataset.value = opt.value
        item.textContent = opt.text

        item.addEventListener('click', () => {
          nativeSelect.value = opt.value
          nativeSelect.dispatchEvent(new Event('change', { bubbles: true }))
          updateTrigger()
          close()
        })

        dropdown.appendChild(item)
      })
    }

    const open = () => {
      document.querySelectorAll('.ciphers-custom-select--open').forEach((el) => {
        if (el !== wrapper) el.classList.remove('ciphers-custom-select--open')
      })
      refreshOptions()
      wrapper.classList.add('ciphers-custom-select--open')
      trigger.setAttribute('aria-expanded', 'true')
    }

    const close = () => {
      wrapper.classList.remove('ciphers-custom-select--open')
      trigger.setAttribute('aria-expanded', 'false')
    }

    trigger.addEventListener('click', (e) => {
      e.stopPropagation()
      wrapper.classList.contains('ciphers-custom-select--open') ? close() : open()
    })

    dropdown.addEventListener('click', (e) => e.stopPropagation())

    nativeSelect.addEventListener('change', updateTrigger)

    updateTrigger()

    if (!documentListenerAdded) {
      documentListenerAdded = true
      document.addEventListener('click', () => {
        document.querySelectorAll('.ciphers-custom-select--open').forEach((el) => {
          el.classList.remove('ciphers-custom-select--open')
        })
      })
    }
  })
}

/**
 * Безопасно парсит JSON-строку.
 */
function parseJson(raw) {
  try {
    return JSON.parse(raw)
  } catch {
    return {}
  }
}

/**
 * Выполняет преобразование данных для выбранного инструмента.
 */
function transform(value, mode, decoder, opts) {
  if (!decoder) return ''
  return decoder.transform(value, mode, opts)
}

const ANALYTICS_COOLDOWN_MS = 5 * 60 * 1000

/**
 * Отправляет beacon аналитики использования клиентского инструмента.
 *
 * localStorage используется как первый фильтр — повторные события в пределах
 * cooldown-окна не отправляются. Сервер дополнительно проверяет cooldown через кеш.
 */
function sendAnalyticsBeacon(toolSlug, mode) {
  const key = `analytics:cd:${toolSlug}`
  try {
    const last = parseInt(localStorage.getItem(key) ?? '0', 10)
    if (Date.now() - last < ANALYTICS_COOLDOWN_MS) return
    localStorage.setItem(key, String(Date.now()))
  } catch {
    // localStorage недоступен — отправляем без фильтрации
  }
  const body = JSON.stringify({ tool: toolSlug, mode })
  if (typeof navigator.sendBeacon === 'function') {
    navigator.sendBeacon('/api/analytics/use', new Blob([body], { type: 'application/json' }))
  } else {
    fetch('/api/analytics/use', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body,
      keepalive: true,
    }).catch(() => {})
  }
}

/**
 * Эвристика автоопределения направления для примеров.
 */
function looksLikeEncoded(text, decoder) {
  const value = (text || '').trim()
  if (!value) return false
  if (!decoder) return false
  return decoder.looksLikeEncoded(value)
}

/**
 * Инициализирует аудиоплеер азбуки Морзе и внедряет его в DOM после блока результата.
 *
 * @param {HTMLTextAreaElement} outputEl
 * @param {HTMLTextAreaElement} inputEl
 * @param {Record<string, string>} ui
 * @param {() => string} getMode
 */
function initMorsePlayer(outputEl, inputEl, ui, getMode) {
  const resultCard = document.getElementById('ciphers-result-card')
  if (!resultCard) return

  const playLabel     = ui.morsePlayLabel     || 'Play'
  const stopLabel     = ui.morseStopLabel     || 'Stop'
  const downloadLabel = ui.morseDownloadLabel || 'Download WAV'
  const speedLabel    = ui.morseSpeedLabel    || 'Speed (WPM)'
  const freqLabel     = ui.morseFreqLabel     || 'Tone'
  const freqLow       = ui.morseFreqLow       || 'Low (400 Hz)'
  const freqMed       = ui.morseFreqMed       || 'Medium (600 Hz)'
  const freqHigh      = ui.morseFreqHigh      || 'High (800 Hz)'

  const player = document.createElement('div')
  player.className = 'morse-player'
  player.id = 'morse-player'
  player.innerHTML = `
    <div class="morse-player__controls">
      <button class="morse-player__play-btn" id="morse-play" type="button">
        <i class="bi bi-play-fill"></i><span>${playLabel}</span>
      </button>
      <div class="morse-player__settings">
        <label class="morse-player__label" for="morse-wpm">${speedLabel}</label>
        <div class="morse-player__wpm-group">
          <button class="morse-player__step-btn" id="morse-wpm-dec" type="button" aria-label="−">−</button>
          <input id="morse-wpm" class="morse-player__wpm-input" type="number" min="5" max="60" step="1" value="20">
          <button class="morse-player__step-btn" id="morse-wpm-inc" type="button" aria-label="+">+</button>
        </div>
        <label class="morse-player__label" for="morse-freq">${freqLabel}</label>
        <select id="morse-freq" class="morse-player__freq-select">
          <option value="400">${freqLow}</option>
          <option value="600" selected>${freqMed}</option>
          <option value="800">${freqHigh}</option>
        </select>
        <div class="morse-player__indicator" id="morse-indicator" aria-hidden="true">
          <span class="morse-player__indicator-dot" id="morse-indicator-dot"></span>
        </div>
      </div>
      <button class="morse-player__download-btn" id="morse-download" type="button">
        <i class="bi bi-download"></i><span>${downloadLabel}</span>
      </button>
    </div>
  `

  resultCard.after(player)

  const playBtn      = document.getElementById('morse-play')
  const downloadBtn  = document.getElementById('morse-download')
  const wpmInput     = document.getElementById('morse-wpm')
  const wpmDecBtn    = document.getElementById('morse-wpm-dec')
  const wpmIncBtn    = document.getElementById('morse-wpm-inc')
  const freqSelect   = document.getElementById('morse-freq')
  const indicatorDot = document.getElementById('morse-indicator-dot')

  const getMorseText = () => {
    const isDecodeMode = getMode() === 'decode'
    return (isDecodeMode ? inputEl?.value?.trim() : outputEl?.value?.trim()) || ''
  }
  const getWpm  = () => Math.min(60, Math.max(5, parseInt(wpmInput?.value || '20', 10) || 20))
  const getFreq = () => parseInt(freqSelect?.value || '600', 10)

  const setWpm = (v) => {
    if (wpmInput) wpmInput.value = String(Math.min(60, Math.max(5, Math.trunc(v))))
  }

  const setPlayState = (playing) => {
    if (!playBtn) return
    const icon = playBtn.querySelector('.bi')
    const span = playBtn.querySelector('span')
    if (playing) {
      icon && (icon.className = 'bi bi-stop-fill')
      span && (span.textContent = stopLabel)
      playBtn.classList.add('morse-player__play-btn--playing')
      player.classList.add('morse-player--playing')
    } else {
      icon && (icon.className = 'bi bi-play-fill')
      span && (span.textContent = playLabel)
      playBtn.classList.remove('morse-player__play-btn--playing')
      player.classList.remove('morse-player--playing')
      indicatorDot?.classList.remove('morse-player__indicator-dot--on')
    }
  }

  const updateAvailability = () => {
    const hasContent = Boolean(getMorseText())
    if (playBtn) playBtn.disabled = !hasContent
    if (downloadBtn) downloadBtn.disabled = !hasContent
  }

  playBtn?.addEventListener('click', () => {
    if (isMorsePlaying()) {
      stopMorse()
      setPlayState(false)
      return
    }

    const text = getMorseText()
    if (!text) return

    setPlayState(true)
    playMorse(text, getWpm(), getFreq(), () => {
      setPlayState(false)
    }, (isOn) => {
      if (isOn) {
        indicatorDot?.classList.add('morse-player__indicator-dot--on')
      } else {
        indicatorDot?.classList.remove('morse-player__indicator-dot--on')
      }
    })
  })

  downloadBtn?.addEventListener('click', async () => {
    const text = getMorseText()
    if (!text) return
    if (downloadBtn) downloadBtn.disabled = true
    try {
      await downloadMorseWav(text, getWpm(), getFreq(), 'morse.wav')
    } finally {
      if (downloadBtn) downloadBtn.disabled = false
      updateAvailability()
    }
  })

  wpmDecBtn?.addEventListener('click', () => setWpm(getWpm() - 1))
  wpmIncBtn?.addEventListener('click', () => setWpm(getWpm() + 1))

  wpmInput?.addEventListener('input', () => {
    const v = parseInt(wpmInput.value, 10)
    if (!isNaN(v) && v > 60) wpmInput.value = '60'
  })
  wpmInput?.addEventListener('blur', () => setWpm(parseInt(wpmInput.value || '20', 10) || 20))

  const observer = new MutationObserver(updateAvailability)
  if (outputEl) {
    observer.observe(outputEl, { attributes: true, characterData: true, subtree: true })
    outputEl.addEventListener('input', () => {
      if (isMorsePlaying()) { stopMorse(); setPlayState(false) }
      updateAvailability()
    })
  }
  if (inputEl) {
    inputEl.addEventListener('input', () => {
      if (isMorsePlaying()) { stopMorse(); setPlayState(false) }
      updateAvailability()
    })
  }

  // Обновляем доступность при смене вкладки encode/decode
  document.getElementById('tab-encode')?.addEventListener('click', () => {
    if (isMorsePlaying()) { stopMorse(); setPlayState(false) }
    window.setTimeout(updateAvailability, 0)
  })
  document.getElementById('tab-decode')?.addEventListener('click', () => {
    if (isMorsePlaying()) { stopMorse(); setPlayState(false) }
    window.setTimeout(updateAvailability, 0)
  })

  updateAvailability()
}
