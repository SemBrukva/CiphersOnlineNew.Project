import { getDecoderBySlug } from './cipher-tool/decoder-registry.js'
import { detectLanguage, getUnknownChars, isValidMorseFormat } from './cipher-tool/decoders/morse.js'
import { initJsonFormatter } from './cipher-tool/json-formatter.js'
import { initFrequencyAnalysis } from './cipher-tool/frequency-analysis.js'
import { initLetterFrequency } from './cipher-tool/letter-frequency.js'
import { initBruteForce } from './cipher-tool/brute-force.js'
import { initMatrixControl } from './cipher-tool/matrix-control.js'
import { initMorsePlayer } from './cipher-tool/morse-player.js'
import { initCustomSelects } from './cipher-tool/custom-selects.js'
import { sendAnalyticsBeacon } from './cipher-tool/analytics.js'

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
  const freqScopeSelect = document.getElementById('ciphers-freq-scope')
  const freqSortSelect = document.getElementById('ciphers-freq-sort')
  const freqLangSelect = document.getElementById('ciphers-freq-lang')
  const lfreqLangSelect = document.getElementById('ciphers-lfreq-lang')
  const lfreqSortSelect = document.getElementById('ciphers-lfreq-sort')
  const visualOutput = document.getElementById('ciphers-visual-output')
  const n2lTypeSelect = document.getElementById('ciphers-n2l-type')
  const jsonIndentSelect = document.getElementById('ciphers-json-indent')
  const jsonSortKeysBtn  = document.getElementById('ciphers-json-sort')
  const jsonDownloadBtn  = document.getElementById('ciphers-json-download')

  if (!input || !output || !tabEncode || !tabDecode || !inputLabel || !counter) return

  let mode = 'encode'
  const isEncodingTool = slug.startsWith('encoding/')
  const calculationMode = String(ui.calculationMode || 'client').toLowerCase()
  const isApiMode = calculationMode === 'api'
  const isMorseTool = slug === 'codes-and-alphabets/morse-code'
  const isAnalysisTool = Boolean(ui.analysisMode)
  const isBruteForceTool = Boolean(ui.bruteForceMode)
  const isLetterFrequencyTool = Boolean(ui.letterFrequencyMode)
  const isNumbersToLettersTool = Boolean(ui.numbersToLettersMode)
  const isJsonFormatterTool = Boolean(ui.jsonFormatterMode)
  const apiAction = String(ui.apiAction || '').trim()
  const stateStorageKey = `cipher-tool:state:${slug}`
  let liveModeDebounceTimer = null
  const decoder = getDecoderBySlug(slug)
  const isClientTool = isEncodingTool || decoder !== null
  let jsonFormatter = null
  let frequencyAnalysis = null
  let letterFrequency = null
  let bruteForce = null
  let matrixCtrl = null

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
    jsonFormatterErrInvalid: ui.jsonFormatterErrInvalid || 'Invalid JSON: :error',
    jsonFormatterErrAt: ui.jsonFormatterErrAt || 'Error at line :line, column :col',
    jsonFormatterViewText: ui.jsonFormatterViewText || 'Text',
    jsonFormatterViewTree: ui.jsonFormatterViewTree || 'Tree',
    jsonFormatterWarnDuplicate: ui.jsonFormatterWarnDuplicate || 'Duplicate key ":key"',
    jsonFormatterStatObjects: ui.jsonFormatterStatObjects || 'Objects',
    jsonFormatterStatArrays: ui.jsonFormatterStatArrays || 'Arrays',
    jsonFormatterStatKeys: ui.jsonFormatterStatKeys || 'Keys',
    jsonFormatterStatDepth: ui.jsonFormatterStatDepth || 'Depth',
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
        jsonIndent: String(jsonIndentSelect?.value ?? ''),
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

  const highlightErrorInInput = (line, col) => {
    if (!input || !line) return
    const lines = input.value.split('\n')
    if (line < 1 || line > lines.length) return
    let lineStart = 0
    for (let i = 0; i < line - 1; i++) lineStart += lines[i].length + 1
    const lineLen = lines[line - 1].length
    const selStart = col != null ? Math.min(lineStart + col - 1, lineStart + lineLen) : lineStart
    const selEnd   = col != null ? Math.min(lineStart + col, lineStart + lineLen + 1) : lineStart + lineLen
    input.focus()
    input.setSelectionRange(selStart, Math.max(selStart + 1, selEnd))
    if (lines.length > 1) {
      const approxLineH = input.scrollHeight / lines.length
      input.scrollTop = Math.max(0, (line - 3) * approxLineH)
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
      matrixCtrl?.setMatrixFromKeyValue(keyInput.value)
    }

    syncShiftWithAlphabet()

    if (liveModeInput && typeof savedState.liveMode === 'boolean') {
      liveModeInput.checked = savedState.liveMode
    }

    if (isJsonFormatterTool) {
      if (jsonIndentSelect && typeof savedState.jsonIndent === 'string' && savedState.jsonIndent !== '') {
        const hasIndent = Array.from(jsonIndentSelect.options).some((o) => o.value === savedState.jsonIndent)
        if (hasIndent) jsonIndentSelect.value = savedState.jsonIndent
      }
    }
  }

  const process = () => {
    const value = input.value || ''
    updateCounter()
    updateCoverCapacity()

    if (!value.trim()) {
      output.value = ''
      if (isAnalysisTool) frequencyAnalysis.showEmpty()
      if (isBruteForceTool) bruteForce.showEmpty()
      if (isLetterFrequencyTool) letterFrequency.showEmpty()
      if (isJsonFormatterTool) jsonFormatter.showEmpty()
      setOutputState(false)
      setFeedback('')
      return
    }

    if (isLetterFrequencyTool) {
      letterFrequency.run(value)
      return
    }

    if (isAnalysisTool) {
      frequencyAnalysis.run(value)
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

    if (isJsonFormatterTool) {
      jsonFormatter.run(value)
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

      const transformOpts = { language: effectiveLang }
      if (isNumbersToLettersTool) {
        transformOpts.encoding = n2lTypeSelect?.value || 'positional-1'
        transformOpts.delimiter = delimiterSelect?.value || 'space'
      }
      output.value = transform(value, mode, decoder, transformOpts)
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

      if (isBruteForceTool) {
        bruteForce.handleApiResponse(response, alphabetSelect)
      } else {
        output.value = String(response?.result ?? '')
        setOutputState(Boolean(output.value))
        if (output.value && response?.warning) {
          setFeedback(String(response.warning), false, true)
        } else if (output.value && mode === 'decode' && ui.decodeNote) {
          setFeedback(ui.decodeNote, false, true)
        } else {
          setFeedback('')
        }
      }
    } catch (error) {
      const fieldErrors = error?.response?.error?.details?.errors
      const firstFieldError = fieldErrors && typeof fieldErrors === 'object'
        ? Object.values(fieldErrors).flat()[0]
        : null
      const message = String(firstFieldError ?? error?.message ?? error?.response?.error?.message ?? labels.runFailed)
      if (isBruteForceTool) {
        bruteForce.showEmpty()
      }
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
    matrixCtrl?.updateMatrixStatus()
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
    saveState()
    scheduleApiRun()
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

  const applyExample = (text, el, { scrollToTool = false } = {}) => {
    const alphabet   = el.getAttribute('data-alphabet')  || ''
    const delimiter  = el.getAttribute('data-delimiter') || ''
    const encoding   = el.getAttribute('data-encoding')  || ''
    const key        = el.getAttribute('data-key')
    const keyInputId = el.getAttribute('data-key-input') || 'ciphers-key'
    const shift      = el.getAttribute('data-shift')
    const direction  = el.getAttribute('data-direction') || ''

    if (alphabet && alphabetSelect && alphabetSelect.value !== alphabet) {
      alphabetSelect.value = alphabet
      alphabetSelect.dispatchEvent(new Event('change', { bubbles: true }))
    }
    if (alphabet && freqLangSelect && freqLangSelect.value !== alphabet) {
      freqLangSelect.value = alphabet
      freqLangSelect.dispatchEvent(new Event('change', { bubbles: true }))
    }
    if (alphabet && lfreqLangSelect && lfreqLangSelect.value !== alphabet) {
      lfreqLangSelect.value = alphabet
      lfreqLangSelect.dispatchEvent(new Event('change', { bubbles: true }))
    }
    if (delimiter && delimiterSelect && delimiterSelect.value !== delimiter) {
      delimiterSelect.value = delimiter
      delimiterSelect.dispatchEvent(new Event('change', { bubbles: true }))
    }
    if (encoding && n2lTypeSelect && n2lTypeSelect.value !== encoding) {
      n2lTypeSelect.value = encoding
      n2lTypeSelect.dispatchEvent(new Event('change', { bubbles: true }))
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
    if (scrollToTool) {
      document.getElementById('ciphers-tool-shell')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
    input.focus()
  }

  document.querySelectorAll('.ciphers-example-chip').forEach((chip) => {
    chip.addEventListener('click', () => applyExample(chip.getAttribute('data-example') || '', chip))
  })

  document.querySelectorAll('.ciphers-example-use').forEach((btn) => {
    btn.addEventListener('click', () => applyExample(btn.getAttribute('data-example-text') || '', btn, { scrollToTool: true }))
  })

  clearBtn?.addEventListener('click', () => {
    input.value = ''
    if (coverInput) coverInput.value = ''
    if (coverCapacityEl) { coverCapacityEl.textContent = ''; coverCapacityEl.className = 'ciphers-cover-capacity' }
    output.value = ''
    if (isAnalysisTool) frequencyAnalysis.showEmpty()
    if (isLetterFrequencyTool) letterFrequency.showEmpty()
    if (isJsonFormatterTool) jsonFormatter.showEmpty()
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
    matrixCtrl?.setMatrixFromKeyValue(keyInput.value)
  }
  saveState()

  if (matrixControl) {
    matrixCtrl = initMatrixControl({
      matrixControl, matrixGrid, matrixStatus, keyInput, alphabetSelect,
      onSave: () => saveState(),
      onScheduleRun: () => scheduleApiRun(),
    })
  }

  if (isJsonFormatterTool) {
    jsonFormatter = initJsonFormatter({
      input, output, visualOutput, feedback,
      labels, jsonIndentSelect, jsonSortKeysBtn, jsonDownloadBtn,
      setFeedback, setOutputState, highlightErrorInInput,
      sendAnalyticsBeacon, slug, getMode: () => mode, onProcess: () => process(),
    })
  }

  if (isAnalysisTool) {
    frequencyAnalysis = initFrequencyAnalysis({
      output, visualOutput, tabDecode, ui, decoder,
      freqScopeSelect, freqSortSelect, freqLangSelect,
      labels, setFeedback, setOutputState, sendAnalyticsBeacon, slug,
      onProcess: () => process(),
    })
  }

  if (isBruteForceTool) {
    bruteForce = initBruteForce({
      output, visualOutput, tabDecode, ui, labels, setFeedback, setOutputState,
    })
  }

  if (isLetterFrequencyTool) {
    letterFrequency = initLetterFrequency({
      output, visualOutput, tabDecode, ui, decoder,
      lfreqLangSelect, lfreqSortSelect,
      labels, setFeedback, setOutputState, sendAnalyticsBeacon, slug,
      onProcess: () => process(),
    })
  }

  setMode('encode')
  initCustomSelects()

  if (isMorseTool) {
    initMorsePlayer(output, input, ui, () => mode)
  }

  if (isNumbersToLettersTool) {
    n2lTypeSelect?.addEventListener('change', () => {
      const isPositional = (n2lTypeSelect.value || '').startsWith('positional')
      const alphabetWrap = alphabetSelect?.closest('.ciphers-settings-item')
      if (alphabetWrap) alphabetWrap.style.display = isPositional ? '' : 'none'
      process()
    })
    // Скрываем выбор алфавита при старте, если выбран ASCII-режим.
    const isPositionalOnLoad = (n2lTypeSelect?.value || 'positional-1').startsWith('positional')
    const alphabetWrap = alphabetSelect?.closest('.ciphers-settings-item')
    if (alphabetWrap && !isPositionalOnLoad) alphabetWrap.style.display = 'none'
    delimiterSelect?.addEventListener('change', () => process())
  }

}
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

/**
 * Эвристика автоопределения направления для примеров.
 */
function looksLikeEncoded(text, decoder) {
  const value = (text || '').trim()
  if (!value) return false
  if (!decoder) return false
  return decoder.looksLikeEncoded(value)
}

