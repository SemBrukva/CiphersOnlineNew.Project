import { initAlbertiWheel } from './cipher-tool/alberti-wheel.js'
import { initEnigmaPanel } from './cipher-tool/enigma-panel.js'
import { getDecoderBySlug } from './cipher-tool/decoder-registry.js'
import { detectLanguage, getUnknownChars, isValidMorseFormat } from './cipher-tool/decoders/morse.js'
import { initJsonFormatter } from './cipher-tool/json-formatter.js'
import { initTimestampConverter } from './cipher-tool/timestamp-converter.js'
import { initFrequencyAnalysis } from './cipher-tool/frequency-analysis.js'
import { initLetterFrequency } from './cipher-tool/letter-frequency.js'
import { initBruteForce } from './cipher-tool/brute-force.js'
import { initVigenereCracker } from './cipher-tool/vigenere-cracker.js'
import { initCipherIdentifier } from './cipher-tool/cipher-identifier.js'
import { initCipherSolver } from './cipher-tool/cipher-solver.js'
import { initAnagramSolver } from './cipher-tool/anagram-solver.js'
import { initMatrixControl } from './cipher-tool/matrix-control.js'
import { initMorsePlayer } from './cipher-tool/morse-player.js'
import { initDancingMen } from './cipher-tool/dancing-men.js'
import { initPigpen } from './cipher-tool/pigpen.js'
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
  const downloadBtn = document.getElementById('ciphers-dm-download')
  const shareBtn = document.getElementById('ciphers-share')
  const resultCard = document.getElementById('ciphers-result-card')
  const resultLabel = document.querySelector('.ciphers-unified__field-label--result')
  const runBtn = document.getElementById('ciphers-primary')
  const liveModeInput = document.getElementById('ciphers-live-mode')
  const shiftInput = document.getElementById('ciphers-shift')
  const shiftDecBtn = document.getElementById('ciphers-shift-dec')
  const shiftIncBtn = document.getElementById('ciphers-shift-inc')
  const alphabetSelect = document.getElementById('ciphers-alphabet')
  const keyLengthSelect = document.getElementById('ciphers-key-length')
  const delimiterSelect = document.getElementById('ciphers-delimiter')
  const keyInput = document.getElementById('ciphers-key')
  const matrixControl = document.querySelector('[data-matrix-control]')
  const matrixGrid = matrixControl?.querySelector('[data-matrix-grid]') ?? null
  const matrixStatus = matrixControl?.querySelector('[data-matrix-status]') ?? null
  const generateKeyBtn = document.getElementById('ciphers-generate-key')
  const keyShuffleBtn  = document.getElementById('ciphers-key-shuffle')
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
  const bookSchemeSelect = document.getElementById('ciphers-book-scheme')
  const pigpenVariantSelect = document.getElementById('ciphers-pigpen-variant')
  const jsonIndentSelect = document.getElementById('ciphers-json-indent')
  const jsonSortKeysBtn  = document.getElementById('ciphers-json-sort')
  const jsonDownloadBtn  = document.getElementById('ciphers-json-download')
  const tsUnitSelect        = document.getElementById('ciphers-ts-unit')
  const tsNowBtn            = document.getElementById('ciphers-ts-now')
  const xorKeyFormatSelect  = document.getElementById('ciphers-xor-key-format')
  const albertiIndexSelect  = document.getElementById('ciphers-alberti-index')
  const enigmaReflectorSelect = document.getElementById('ciphers-enigma-reflector')
  const enigmaRotorLeftSelect = document.getElementById('ciphers-enigma-rotor-left')
  const enigmaRotorMiddleSelect = document.getElementById('ciphers-enigma-rotor-middle')
  const enigmaRotorRightSelect = document.getElementById('ciphers-enigma-rotor-right')
  const enigmaRingLeftSelect   = document.getElementById('ciphers-enigma-ring-left')
  const enigmaRingMiddleSelect = document.getElementById('ciphers-enigma-ring-middle')
  const enigmaRingRightSelect  = document.getElementById('ciphers-enigma-ring-right')
  const enigmaPosLeftSelect    = document.getElementById('ciphers-enigma-pos-left')
  const enigmaPosMiddleSelect  = document.getElementById('ciphers-enigma-pos-middle')
  const enigmaPosRightSelect   = document.getElementById('ciphers-enigma-pos-right')
  const enigmaPlugboardInput   = document.getElementById('ciphers-enigma-plugboard')
  const hashAlgorithmSelect    = document.getElementById('ciphers-hash-algorithm')
  const hmacKeyFormatSelect    = document.getElementById('ciphers-hmac-key-format')
  const kdfVerifyHashInput     = document.getElementById('ciphers-kdf-verify-hash')

  if (!input || !output || !tabEncode || !tabDecode || !inputLabel || !counter) return

  let mode = 'encode'
  const isEncodingTool = slug.startsWith('encoding/')
  const calculationMode = String(ui.calculationMode || 'client').toLowerCase()
  const isApiMode = calculationMode === 'api'
  const isMorseTool = slug === 'codes-and-alphabets/morse-code'
  const isAnalysisTool = Boolean(ui.analysisMode)
  const isBruteForceTool = Boolean(ui.bruteForceMode)
  const isVigenereCrackerTool  = Boolean(ui.vigenereCrackerMode)
  const isIdentifierTool       = Boolean(ui.identifierMode)
  const isSolverTool           = Boolean(ui.solverMode)
  const isAnagramTool          = Boolean(ui.anagramMode)
  const anagramModeSelect      = document.getElementById('ciphers-anagram-mode')
  const isLetterFrequencyTool  = Boolean(ui.letterFrequencyMode)
  const isAlbertiWheelTool     = Boolean(ui.albertiWheelMode)
  const isEnigmaTool           = Boolean(ui.enigmaMode)
  const isNumbersToLettersTool = Boolean(ui.numbersToLettersMode)
  const isBookTool = Boolean(ui.bookMode)
  const isJsonFormatterTool = Boolean(ui.jsonFormatterMode)
  const isTimestampConverterTool = Boolean(ui.timestampConverterMode)
  const isDancingMenTool = Boolean(ui.dancingMenMode)
  const isPigpenTool = Boolean(ui.pigpenMode)
  const isOneWayTool = Boolean(ui.oneWayMode)
  const isHashTool = slug.startsWith('hashing/')
  const isHmacTool = Boolean(ui.hmacMode)
  const isManualRunTool = Boolean(ui.manualRun)
  const isKdfTool = Boolean(ui.kdfMode)
  const isKdfVerifyMode = Boolean(ui.kdfVerifyMode)
  const apiAction = String(ui.apiAction || '').trim()
  const stateStorageKey = `cipher-tool:state:${slug}`
  let liveModeDebounceTimer = null

  // LRU-кеш ответов API в рамках текущей страницы: повторные одинаковые запросы
  // отдаются мгновенно без сетевого вызова. Особенно полезно для тяжёлых тулов
  // (vigenere-cracker, brute-force) и при повторных кликах после переключения настроек.
  const apiResultCache = new Map()
  const API_CACHE_LIMIT = 20

  const cacheKeyFor = (action, payload) => JSON.stringify([action, payload])

  const readApiCache = (key) => {
    if (!apiResultCache.has(key)) return undefined
    // Перемещаем запись в конец — для LRU-эвикции.
    const value = apiResultCache.get(key)
    apiResultCache.delete(key)
    apiResultCache.set(key, value)
    return value
  }

  const writeApiCache = (key, value) => {
    if (apiResultCache.has(key)) apiResultCache.delete(key)
    apiResultCache.set(key, value)
    while (apiResultCache.size > API_CACHE_LIMIT) {
      const oldestKey = apiResultCache.keys().next().value
      apiResultCache.delete(oldestKey)
    }
  }
  const decoder = getDecoderBySlug(slug)
  const isClientTool = isEncodingTool || decoder !== null
  let jsonFormatter = null
  let timestampConverter = null
  let frequencyAnalysis = null
  let letterFrequency = null
  let bruteForce = null
  let vigenereCracker = null
  let cipherIdentifier = null
  let cipherSolver = null
  let anagramSolver = null
  let dancingMen = null
  let pigpen = null
  let matrixCtrl = null
  let albertiWheel = null
  let enigmaPanel = null

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
    embedCopied: ui.embedCopiedLabel || 'Embed code copied.',
    embedCopyFailed: ui.embedCopyFailedLabel || 'Unable to copy embed code.',
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
    tsErrInvalidTs: ui.tsErrInvalidTs || 'Invalid timestamp — enter a number.',
    tsErrInvalidDate: ui.tsErrInvalidDate || 'Invalid date — try ISO 8601 format.',
    tsLabelUtc: ui.tsLabelUtc || 'UTC',
    tsLabelLocal: ui.tsLabelLocal || 'Local time',
    tsLabelIso: ui.tsLabelIso || 'ISO 8601',
    tsLabelRelative: ui.tsLabelRelative || 'Relative',
    tsLabelDay: ui.tsLabelDay || 'Day of week',
    tsLabelUnixSec: ui.tsLabelUnixSec || 'Unix (seconds)',
    tsLabelUnixMs: ui.tsLabelUnixMs || 'Unix (milliseconds)',
    tsResultSeconds: ui.tsResultSeconds || 'Unix (seconds)',
    tsResultMs: ui.tsResultMs || 'Unix (milliseconds)',
    bookErrUncovered: ui.bookErrUncovered || 'Not found in the reference text: :tokens',
    bookErrNoBook: ui.bookErrNoBook || 'Paste a reference text to use as the key.',
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
        tsUnit: String(tsUnitSelect?.value ?? ''),
        xorKeyFormat: String(xorKeyFormatSelect?.value ?? ''),
        albertiIndex: String(albertiIndexSelect?.value ?? 'A'),
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

  const loadCarryOver = () => {
    if (!canUsePreferenceStorage()) return
    try {
      const raw = window.localStorage.getItem('ciphers:carry-over')
      if (!raw) return
      const payload = JSON.parse(raw)
      if (!payload || typeof payload !== 'object') return
      if (Date.now() > (payload.expiresAt ?? 0)) {
        window.localStorage.removeItem('ciphers:carry-over')
        return
      }
      const text = String(payload.text ?? '')
      if (!text) return
      window.localStorage.removeItem('ciphers:carry-over')
      input.value = text
      input.dispatchEvent(new Event('input', { bubbles: true }))
    } catch {
      // ignore storage errors
    }
  }

  const inputMaxLength = Number(ui.inputMaxLength) || 0

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
    if (inputMaxLength > 0) {
      counter.textContent = `${chars} / ${inputMaxLength} ${labels.chars} · ${bytes} ${labels.bytes}`
      counter.classList.toggle('is-near-limit', chars >= inputMaxLength)
    } else {
      counter.textContent = `${chars} ${labels.chars} · ${bytes} ${labels.bytes}`
    }
  }

  const setMode = (nextMode) => {
    if (isOneWayTool && nextMode !== 'encode') return
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
    document.querySelectorAll('[data-decode-only]').forEach((el) => {
      el.style.display = isEncode ? 'none' : ''
    })
    if (isPigpenTool && pigpen) pigpen.setMode(mode)
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

    if (isTimestampConverterTool) {
      if (tsUnitSelect && typeof savedState.tsUnit === 'string' && savedState.tsUnit !== '') {
        const hasUnit = Array.from(tsUnitSelect.options).some((o) => o.value === savedState.tsUnit)
        if (hasUnit) tsUnitSelect.value = savedState.tsUnit
      }
    }

    if (xorKeyFormatSelect && typeof savedState.xorKeyFormat === 'string' && savedState.xorKeyFormat !== '') {
      const hasFormat = Array.from(xorKeyFormatSelect.options).some((o) => o.value === savedState.xorKeyFormat)
      if (hasFormat) xorKeyFormatSelect.value = savedState.xorKeyFormat
    }

    if (albertiIndexSelect && typeof savedState.albertiIndex === 'string' && savedState.albertiIndex !== '') {
      const hasIdx = Array.from(albertiIndexSelect.options).some((o) => o.value === savedState.albertiIndex)
      if (hasIdx) albertiIndexSelect.value = savedState.albertiIndex
    }
  }

  // ── Sharable permalink со state ──────────────────────────────────────────
  // Состояние (ввод + режим + настройки) кодируется в hash-фрагмент `#s=...`.
  // Фрагмент не отправляется на сервер, поэтому не плодит дублей и не индексируется.
  const SHARE_URL_MAX = 8000

  /** Устанавливает значение поля по id и диспатчит событие, чтобы синхронизировать контроллеры. */
  const setShareField = (id, value) => {
    const field = document.getElementById(id)
    if (!field) return
    if (field.type === 'checkbox') {
      const flag = value === true || value === 'true' || value === 1 || value === '1'
      if (field.checked !== flag) {
        field.checked = flag
        field.dispatchEvent(new Event('change', { bubbles: true }))
      }
      return
    }
    const stringValue = value == null ? '' : String(value)
    if (field.value === stringValue) return
    if (field.tagName === 'SELECT') {
      const hasOption = Array.from(field.options).some((option) => option.value === stringValue)
      if (!hasOption) return
      field.value = stringValue
      field.dispatchEvent(new Event('change', { bubbles: true }))
    } else {
      field.value = stringValue
      field.dispatchEvent(new Event('input', { bubbles: true }))
    }
  }

  /** Собирает текущее состояние инструмента для кодирования в permalink. */
  const collectShareState = () => {
    const fields = {}
    SHAREABLE_FIELD_IDS.forEach((id) => {
      const field = document.getElementById(id)
      if (!field) return
      if (field.type === 'checkbox') {
        if (field.checked) fields[id] = true
        return
      }
      if (field.value != null && field.value !== '') fields[id] = field.value
    })
    const state = { v: 1, m: mode, f: fields }
    const text = input.value || ''
    if (text) state.i = text
    return state
  }

  /** Строит абсолютный URL с закодированным состоянием в hash-фрагменте. */
  const buildShareUrl = () => {
    const base = window.location.href.split('#')[0]
    const state = collectShareState()
    let url = `${base}#s=${encodeShareState(state)}`
    if (url.length > SHARE_URL_MAX && 'i' in state) {
      // Слишком длинный ввод — делимся только настройками, без текста.
      delete state.i
      url = `${base}#s=${encodeShareState(state)}`
    }
    return url
  }

  /** Восстанавливает состояние из permalink при загрузке страницы. Возвращает true, если применено. */
  const applyShareState = () => {
    const match = window.location.hash.match(/(?:^#|[#&])s=([^&]+)/)
    if (!match) return false

    const state = decodeShareState(match[1])
    if (!state || state.v !== 1) return false

    if (state.f && typeof state.f === 'object') {
      Object.entries(state.f).forEach(([id, value]) => setShareField(id, value))
    }

    if (typeof state.i === 'string') {
      input.value = state.i
    }

    setMode(state.m === 'decode' ? 'decode' : 'encode')

    if (isApiMode) {
      if (liveModeInput && !liveModeInput.checked) liveModeInput.checked = true
      void runApiRequest()
    }

    return true
  }

  const process = () => {
    // В режиме decode вывод Pigpen формируется только кликами по клавиатуре символов,
    // поэтому обычный конвейер (который очищал бы output при пустом вводе) пропускаем.
    if (isPigpenTool && mode === 'decode') return

    const value = input.value || ''
    updateCounter()
    updateCoverCapacity()

    // Для hash/HMAC пустой ввод — валидный кейс: SHA-256("") = e3b0c44…
    // Поэтому раннего выхода нет, чтобы decoder посчитал хеш пустой строки.
    // KDF (argon2/bcrypt/pbkdf2) — исключение: hash-wasm бросает на пустой password.
    const allowEmptyInput = (isHashTool || isHmacTool) && !isKdfTool

    if (!value.trim() && !allowEmptyInput) {
      output.value = ''
      if (isAnalysisTool) frequencyAnalysis.showEmpty()
      if (isBruteForceTool) bruteForce.showEmpty()
      if (isVigenereCrackerTool) vigenereCracker.showEmpty()
      if (isIdentifierTool) cipherIdentifier.showEmpty()
      if (isSolverTool) cipherSolver.showEmpty()
      if (isAnagramTool) anagramSolver.showEmpty()
      if (isLetterFrequencyTool) letterFrequency.showEmpty()
      if (isJsonFormatterTool) jsonFormatter.showEmpty()
      if (isTimestampConverterTool) timestampConverter.showEmpty()
      if (isDancingMenTool) dancingMen.showEmpty()
      if (isPigpenTool) pigpen.showEmpty()
      setOutputState(false)
      setFeedback('')
      return
    }

    if (isDancingMenTool) {
      dancingMen.run(value)
      return
    }

    if (isPigpenTool) {
      pigpen.run(value)
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

    if (isTimestampConverterTool) {
      timestampConverter.run(value)
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
      if (isBookTool) {
        transformOpts.book = keyInput?.value || ''
        transformOpts.scheme = bookSchemeSelect?.value || 'word-index'
        transformOpts.delimiter = delimiterSelect?.value || 'space'
      }
      if (isHashTool) {
        transformOpts.algorithm = hashAlgorithmSelect?.value || 'sha-256'
      }
      if (isHmacTool) {
        transformOpts.key = keyInput?.value || ''
        transformOpts.keyFormat = hmacKeyFormatSelect?.value || 'text'
      }
      if (isKdfTool) {
        transformOpts.kdfParams = collectKdfParams()
        if (mode === 'decode') {
          transformOpts.verifyHash = kdfVerifyHashInput?.value || ''
        }
      }
      const result = transform(value, mode, decoder, transformOpts)
      const isPromise = result && typeof result.then === 'function'
      if (isPromise && isManualRunTool && runBtn) {
        runBtn.disabled = true
        runBtn.classList.add('is-loading')
      }
      const finalize = (resolvedValue) => {
        if (isManualRunTool && runBtn) {
          runBtn.disabled = false
          runBtn.classList.remove('is-loading')
        }
        output.value = resolvedValue
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
      }

      if (isPromise) {
        result.then(finalize).catch((error) => {
          if (isManualRunTool && runBtn) {
            runBtn.disabled = false
            runBtn.classList.remove('is-loading')
          }
          output.value = ''
          setOutputState(false)
          setFeedback(error?.message || labels.invalid, true)
        })
      } else {
        finalize(result)
      }
    } catch (error) {
      output.value = ''
      setOutputState(false)
      if (error?.code === 'book-uncovered') {
        setFeedback(labels.bookErrUncovered.replace(':tokens', (error.tokens || []).join(', ')), true)
      } else if (error?.code === 'book-empty') {
        setFeedback(labels.bookErrNoBook, false, true)
      } else {
        setFeedback(error?.code === 'not-json' ? labels.notJson : labels.invalid, true)
      }
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

    const shift        = Number(shiftInput?.value ?? 3)
    const alphabet     = String(alphabetSelect?.value ?? 'auto')
    const keyLength    = String(keyLengthSelect?.value ?? '')
    const delimiter    = String(delimiterSelect?.value ?? 'dash')
    const key          = String(keyInput?.value ?? '')
    const coverText    = String(coverInput?.value ?? '')
    const xorKeyFormat  = String(xorKeyFormatSelect?.value ?? '')
    const albertiIndex  = String(albertiIndexSelect?.value ?? 'A')
    const direction     = mode === 'decode' ? 'decrypt' : 'encrypt'

    if (runBtn) {
      runBtn.disabled = true
      runBtn.classList.add('is-loading')
    }

    try {
      const apiMethod = window.api?.guest?.[apiAction]
      if (typeof apiMethod !== 'function') {
        throw new Error(`Unknown API action: ${apiAction}`)
      }

      const anagramSettings = isAnagramTool ? {
        anagram_mode: String(anagramModeSelect?.value || 'anagram'),
        ...(anagramSolver?.collectSettings() ?? {}),
      } : {}

      const enigmaSettings = isEnigmaTool ? {
        enigma_reflector:    String(enigmaReflectorSelect?.value ?? 'B'),
        enigma_rotor_left:   String(enigmaRotorLeftSelect?.value ?? 'I'),
        enigma_rotor_middle: String(enigmaRotorMiddleSelect?.value ?? 'II'),
        enigma_rotor_right:  String(enigmaRotorRightSelect?.value ?? 'III'),
        enigma_ring_left:    String(enigmaRingLeftSelect?.value ?? 'A'),
        enigma_ring_middle:  String(enigmaRingMiddleSelect?.value ?? 'A'),
        enigma_ring_right:   String(enigmaRingRightSelect?.value ?? 'A'),
        enigma_pos_left:     String(enigmaPosLeftSelect?.value ?? 'A'),
        enigma_pos_middle:   String(enigmaPosMiddleSelect?.value ?? 'A'),
        enigma_pos_right:    String(enigmaPosRightSelect?.value ?? 'A'),
        enigma_plugboard:    String(enigmaPlugboardInput?.value ?? ''),
      } : {}

      const requestPayload = {
        text,
        direction,
        locale: ui.locale ?? 'en',
        settings: Object.fromEntries(
          Object.entries({
            shift,
            alphabet,
            key_length: keyLength,
            delimiter,
            key,
            cover_text: coverText,
            xor_key_format: xorKeyFormat,
            alberti_index: albertiIndex,
            ...enigmaSettings,
            ...anagramSettings,
          }).filter(([, value]) => value !== '')
        ),
      }

      const cacheKey = cacheKeyFor(apiAction, requestPayload)
      let response = readApiCache(cacheKey)
      if (response === undefined) {
        response = await apiMethod(requestPayload)
        writeApiCache(cacheKey, response)
      }

      if (isBruteForceTool) {
        bruteForce.handleApiResponse(response, alphabetSelect)
      } else if (isVigenereCrackerTool) {
        vigenereCracker.handleApiResponse(response, alphabetSelect)
      } else if (isIdentifierTool) {
        cipherIdentifier.handleApiResponse(response)
      } else if (isSolverTool) {
        cipherSolver.handleApiResponse(response)
      } else if (isAnagramTool) {
        anagramSolver.handleApiResponse(response)
      } else {
        if (isAlbertiWheelTool && albertiWheel && response?.inner_alphabet) {
          albertiWheel.update(String(response.inner_alphabet), Number(response.index_offset ?? 0))
        }
        if (isEnigmaTool && enigmaPanel) {
          enigmaPanel.showResult(response)
        }
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
      if (isVigenereCrackerTool) {
        vigenereCracker.showEmpty()
      }
      if (isIdentifierTool) {
        cipherIdentifier.showEmpty()
      }
      if (isSolverTool) {
        cipherSolver.showEmpty()
      }
      if (isAnagramTool) {
        anagramSolver.showEmpty()
      }
      output.value = ''
      setOutputState(false)
      setFeedback(message, true)
    } finally {
      if (runBtn) {
        runBtn.disabled = false
        runBtn.classList.remove('is-loading')
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

  /**
   * Собирает значения всех KDF-параметров (salt, iterations, cost, memory и т.п.) из DOM.
   * Каждый KDF использует своё подмножество — лишние пустые поля игнорирует декодер.
   */
  function collectKdfParams() {
    return {
      salt:        document.getElementById('ciphers-kdf-salt')?.value || '',
      iterations:  document.getElementById('ciphers-kdf-iterations')?.value || '',
      hash:        document.getElementById('ciphers-kdf-hash')?.value || '',
      keyLength:   document.getElementById('ciphers-kdf-key-length')?.value || '',
      cost:        document.getElementById('ciphers-kdf-cost')?.value || '',
      memory:      document.getElementById('ciphers-kdf-memory')?.value || '',
      parallelism: document.getElementById('ciphers-kdf-parallelism')?.value || '',
      variant:     document.getElementById('ciphers-kdf-variant')?.value || '',
    }
  }

  if (isManualRunTool) {
    // Для manual-run инструментов вход триггерит только обновление счётчика,
    // вычисление запускается только по клику на кнопку Compute.
    input.addEventListener('input', updateCounter)
  } else {
    input.addEventListener('input', process)
  }

  const fisherYatesShuffle = (str) => {
    const chars = [...str]
    for (let i = chars.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [chars[i], chars[j]] = [chars[j], chars[i]]
    }
    return chars.join('')
  }

  alphabetSelect?.addEventListener('change', () => {
    syncShiftWithAlphabet()
    matrixCtrl?.updateMatrixStatus()
    const selectedOption = alphabetSelect.options[alphabetSelect.selectedIndex]
    const letters = selectedOption?.dataset?.letters
    if (letters && keyInput) {
      keyInput.value = fisherYatesShuffle(letters)
      keyInput.dispatchEvent(new Event('input', { bubbles: true }))
    }
    saveState()
    if (isApiMode) {
      scheduleApiRun()
    } else if (isClientTool) {
      process()
    }
  })

  delimiterSelect?.addEventListener('change', () => {
    saveState()
    if (isBookTool) {
      process()
    } else {
      scheduleApiRun()
    }
  })

  bookSchemeSelect?.addEventListener('change', () => {
    saveState()
    process()
  })

  hashAlgorithmSelect?.addEventListener('change', () => {
    saveState()
    process()
  })

  hmacKeyFormatSelect?.addEventListener('change', () => {
    saveState()
    process()
  })

  xorKeyFormatSelect?.addEventListener('change', () => {
    saveState()
    scheduleApiRun()
  })

  albertiIndexSelect?.addEventListener('change', () => {
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
    if (isApiMode) {
      scheduleApiRun()
    } else if (isClientTool) {
      process()
    }
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

  keyShuffleBtn?.addEventListener('click', () => {
    if (!keyInput) return
    keyInput.value = fisherYatesShuffle(keyInput.value)
    keyInput.dispatchEvent(new Event('input', { bubbles: true }))
    scheduleApiRun()
  })

  const applyExample = (text, el, { scrollToTool = false } = {}) => {
    const alphabet      = el.getAttribute('data-alphabet')      || ''
    const delimiter     = el.getAttribute('data-delimiter')     || ''
    const encoding      = el.getAttribute('data-encoding')      || ''
    const keyFormat     = el.getAttribute('data-key-format')    || ''
    const key           = el.getAttribute('data-key')
    const keyInputId    = el.getAttribute('data-key-input') || 'ciphers-key'
    const shift         = el.getAttribute('data-shift')
    const direction     = el.getAttribute('data-direction')     || ''
    const albertiIndex  = el.getAttribute('data-alberti-index') || ''
    const anagramMode   = el.getAttribute('data-anagram-mode')  || ''
    const settingsAttr  = el.getAttribute('data-settings')

    if (settingsAttr) {
      try {
        const settings = JSON.parse(settingsAttr)
        if (settings && typeof settings === 'object') {
          Object.entries(settings).forEach(([fieldId, value]) => {
            const field = document.getElementById(fieldId)
            if (!field) return
            const stringValue = value == null ? '' : String(value)
            if (field.value === stringValue) return
            field.value = stringValue
            const isSelect = field.tagName === 'SELECT'
            field.dispatchEvent(new Event(isSelect ? 'change' : 'input', { bubbles: true }))
          })
        }
      } catch (err) {
        console.warn('Invalid data-settings JSON on example chip:', err)
      }
    }

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
    if (xorKeyFormatSelect) {
      const targetFormat = keyFormat || 'text'
      if (xorKeyFormatSelect.value !== targetFormat) {
        xorKeyFormatSelect.value = targetFormat
        xorKeyFormatSelect.dispatchEvent(new Event('change', { bubbles: true }))
      }
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
    if (albertiIndex && albertiIndexSelect && albertiIndexSelect.value !== albertiIndex) {
      albertiIndexSelect.value = albertiIndex
      albertiIndexSelect.dispatchEvent(new Event('change', { bubbles: true }))
    }
    if (anagramMode && anagramModeSelect && anagramModeSelect.value !== anagramMode) {
      anagramModeSelect.value = anagramMode
      anagramModeSelect.dispatchEvent(new Event('change', { bubbles: true }))
    }

    // ── Enigma: применяем настройки роторов, колец, позиций, рефлектора и plugboard.
    if (isEnigmaTool) {
      const enigmaSelectMap = [
        [enigmaReflectorSelect,   el.getAttribute('data-enigma-reflector')],
        [enigmaRotorLeftSelect,   el.getAttribute('data-enigma-rotor-left')],
        [enigmaRotorMiddleSelect, el.getAttribute('data-enigma-rotor-middle')],
        [enigmaRotorRightSelect,  el.getAttribute('data-enigma-rotor-right')],
        [enigmaRingLeftSelect,    el.getAttribute('data-enigma-ring-left')],
        [enigmaRingMiddleSelect,  el.getAttribute('data-enigma-ring-middle')],
        [enigmaRingRightSelect,   el.getAttribute('data-enigma-ring-right')],
        [enigmaPosLeftSelect,     el.getAttribute('data-enigma-pos-left')],
        [enigmaPosMiddleSelect,   el.getAttribute('data-enigma-pos-middle')],
        [enigmaPosRightSelect,    el.getAttribute('data-enigma-pos-right')],
      ]
      let enigmaChanged = false
      enigmaSelectMap.forEach(([sel, val]) => {
        if (!sel || val === null) return
        const upper = String(val).toUpperCase()
        const hasOption = Array.from(sel.options).some((o) => o.value === upper)
        if (hasOption && sel.value !== upper) {
          sel.value = upper
          enigmaChanged = true
        }
      })

      const plugboardAttr = el.getAttribute('data-enigma-plugboard')
      if (enigmaPlugboardInput && plugboardAttr !== null && enigmaPlugboardInput.value !== plugboardAttr) {
        enigmaPlugboardInput.value = plugboardAttr
        enigmaChanged = true
      }

      if (enigmaChanged) {
        // Один change-event на любом select достаточно, чтобы синхронизировать визуальную панель;
        // applyExample в конце всё равно вызовет API-запрос.
        enigmaReflectorSelect?.dispatchEvent(new Event('change', { bubbles: true }))
        enigmaPlugboardInput?.dispatchEvent(new Event('input', { bubbles: true }))
      }
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
    if (isApiMode) {
      if (liveModeInput && !liveModeInput.checked) {
        liveModeInput.checked = true
        saveState()
      }
      if (!liveModeInput) {
        void runApiRequest()
      } else {
        scheduleApiRun()
      }
    }
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
    if (isTimestampConverterTool) timestampConverter.showEmpty()
    updateCounter()
    setOutputState(false)
    setFeedback('')
    if ((isHashTool || isHmacTool) && !isKdfTool) {
      process()
    }
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
    const shareUrl = buildShareUrl()
    try {
      await navigator.clipboard.writeText(shareUrl)
      // Синхронизируем адресную строку со скопированной ссылкой.
      window.history.replaceState(null, '', shareUrl)
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

  // ── Встраиваемый виджет (iframe) ─────────────────────────────────────────
  const embedBtn = document.getElementById('ciphers-embed')
  const embedDialog = document.getElementById('ciphers-embed-dialog')
  const embedCode = document.getElementById('ciphers-embed-code')
  const embedCopyBtn = document.getElementById('ciphers-embed-copy')

  /** Формирует HTML-код iframe для встраивания текущего инструмента. */
  const buildEmbedCode = () => {
    const url = embedBtn?.getAttribute('data-embed-url') || ''
    const title = (embedBtn?.getAttribute('data-embed-title') || 'CiphersOnline')
      .replace(/"/g, '&quot;')
    return `<iframe src="${url}" width="100%" height="640" loading="lazy" title="${title} — CiphersOnline" style="border:1px solid #e2e8f0;border-radius:12px;max-width:680px;width:100%"></iframe>`
  }

  const closeEmbedDialog = () => {
    if (embedDialog) embedDialog.hidden = true
  }

  if (embedBtn && embedDialog && embedCode) {
    embedBtn.addEventListener('click', () => {
      embedCode.value = buildEmbedCode()
      embedDialog.hidden = false
      embedCode.focus()
      embedCode.select()
    })

    embedDialog.querySelectorAll('[data-embed-close]').forEach((el) => {
      el.addEventListener('click', closeEmbedDialog)
    })

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !embedDialog.hidden) closeEmbedDialog()
    })

    embedCopyBtn?.addEventListener('click', async () => {
      embedCode.select()
      try {
        await navigator.clipboard.writeText(embedCode.value)
        setFeedback(labels.embedCopied)
      } catch {
        setFeedback(labels.embedCopyFailed, true)
      }
      window.setTimeout(closeEmbedDialog, 600)
    })
  }

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
    if (isApiMode) {
      await runApiRequest()
    } else {
      process()
    }
  })

  kdfVerifyHashInput?.addEventListener('input', () => {
    // В manual-run режиме сам по себе ввод не запускает вычисление, но если
    // пользователь редактирует hash — стираем устаревший результат.
    if (isManualRunTool && output.value) {
      output.value = ''
      setOutputState(false)
    }
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

  if (isTimestampConverterTool) {
    timestampConverter = initTimestampConverter({
      input, output, visualOutput,
      labels, setFeedback, setOutputState,
      sendAnalyticsBeacon, slug,
      locale: String(ui.locale || 'en'),
      getMode: () => mode, onProcess: () => process(),
      tsUnitSelect, tsNowBtn,
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

  if (isVigenereCrackerTool) {
    vigenereCracker = initVigenereCracker({
      output, visualOutput, tabDecode, ui, labels, setFeedback, setOutputState,
    })
  }

  if (isIdentifierTool) {
    cipherIdentifier = initCipherIdentifier({
      output, visualOutput, tabDecode, ui, input, setFeedback, setOutputState,
    })
  }

  if (isSolverTool) {
    cipherSolver = initCipherSolver({
      output, visualOutput, tabDecode, ui, input, setFeedback, setOutputState,
    })
  }

  if (isAnagramTool) {
    anagramSolver = initAnagramSolver({
      output, visualOutput, tabDecode, ui,
      setFeedback, setOutputState,
      onChange: () => scheduleApiRun(),
    })
    anagramModeSelect?.addEventListener('change', () => scheduleApiRun())
  }

  if (isLetterFrequencyTool) {
    letterFrequency = initLetterFrequency({
      output, visualOutput, tabDecode, ui, decoder,
      lfreqLangSelect, lfreqSortSelect,
      labels, setFeedback, setOutputState, sendAnalyticsBeacon, slug,
      onProcess: () => process(),
    })
  }

  if (isDancingMenTool) {
    dancingMen = initDancingMen({
      output, visualOutput, tabDecode, downloadBtn,
      ui, setFeedback, setOutputState,
      sendAnalyticsBeacon, slug, alphabetSelect,
    })
  }

  if (isPigpenTool) {
    pigpen = initPigpen({
      input, output, visualOutput, tabDecode, downloadBtn,
      ui, setFeedback, setOutputState,
      sendAnalyticsBeacon, slug, variantSelect: pigpenVariantSelect,
      getMode: () => mode,
    })
    pigpenVariantSelect?.addEventListener('change', () => process())
  }

  setMode('encode')
  initCustomSelects()
  loadCarryOver()

  if (isAlbertiWheelTool) {
    const wheelWrap = document.createElement('div')
    wheelWrap.className = 'alberti-wheel-wrap'
    const inputWrap = document.querySelector('.ciphers-unified__input-wrap')
    inputWrap?.parentNode?.insertBefore(wheelWrap, inputWrap)
    albertiWheel = initAlbertiWheel({
      container: wheelWrap,
      keyInput,
      indexSelect: albertiIndexSelect,
      diskLabel:    String(ui.albertiWheelDiskLabel    || 'Alberti Cipher Disk'),
      mappingLabel: String(ui.albertiWheelMappingLabel || 'Current Mapping'),
    })
  }

  if (isEnigmaTool) {
    const panelWrap = document.createElement('div')
    panelWrap.className = 'enigma-panel-wrap'
    const inputWrap = document.querySelector('.ciphers-unified__input-wrap')
    inputWrap?.parentNode?.insertBefore(panelWrap, inputWrap)
    enigmaPanel = initEnigmaPanel({
      container: panelWrap,
      selects: {
        reflector: enigmaReflectorSelect,
        rotorL: enigmaRotorLeftSelect,
        rotorM: enigmaRotorMiddleSelect,
        rotorR: enigmaRotorRightSelect,
        ringL: enigmaRingLeftSelect,
        ringM: enigmaRingMiddleSelect,
        ringR: enigmaRingRightSelect,
        posL: enigmaPosLeftSelect,
        posM: enigmaPosMiddleSelect,
        posR: enigmaPosRightSelect,
      },
      plugboardInput: enigmaPlugboardInput,
      labels: {
        title:     String(ui.enigmaVisualTitle      || 'Machine state'),
        rotors:    String(ui.enigmaVisualRotors     || 'Rotors'),
        reflector: String(ui.enigmaVisualReflector  || 'Reflector'),
        plugboard: String(ui.enigmaVisualPlugboard  || 'Plugboard'),
        start:     String(ui.enigmaVisualStart      || 'Start'),
        final:     String(ui.enigmaVisualFinal      || 'Final'),
        letters:   String(ui.enigmaVisualLetters    || 'Letters processed'),
        empty:     String(ui.enigmaVisualEmpty      || 'no pairs'),
        reset:     String(ui.enigmaVisualReset      || 'Reset'),
        random:    String(ui.enigmaVisualRandom     || 'Randomize'),
      },
      onChange: () => { scheduleApiRun() },
    })

    // Селекты роторов/колец/позиций + plugboard должны триггерить API-запрос в live-режиме.
    const enigmaTriggers = [
      enigmaReflectorSelect, enigmaRotorLeftSelect, enigmaRotorMiddleSelect, enigmaRotorRightSelect,
      enigmaRingLeftSelect,  enigmaRingMiddleSelect,  enigmaRingRightSelect,
      enigmaPosLeftSelect,   enigmaPosMiddleSelect,   enigmaPosRightSelect,
    ]
    enigmaTriggers.forEach((el) => {
      el?.addEventListener('change', () => { scheduleApiRun() })
    })
    enigmaPlugboardInput?.addEventListener('input', () => { scheduleApiRun() })
  }

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

  // Восстанавливаем состояние из permalink (#s=...) последним — оно перекрывает
  // и сохранённые настройки, и carry-over ввод.
  applyShareState()

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

/**
 * Идентификаторы DOM-полей настроек, состояние которых кодируется в sharable-permalink.
 * Несуществующие на конкретном инструменте поля просто пропускаются.
 */
const SHAREABLE_FIELD_IDS = [
  'ciphers-live-mode',
  'ciphers-shift',
  'ciphers-key',
  'ciphers-alphabet',
  'ciphers-delimiter',
  'ciphers-key-length',
  'ciphers-cover',
  'ciphers-n2l-type',
  'ciphers-book-scheme',
  'ciphers-pigpen-variant',
  'ciphers-json-indent',
  'ciphers-ts-unit',
  'ciphers-xor-key-format',
  'ciphers-alberti-index',
  'ciphers-hash-algorithm',
  'ciphers-hmac-key-format',
  'ciphers-anagram-mode',
  'ciphers-freq-scope',
  'ciphers-freq-sort',
  'ciphers-freq-lang',
  'ciphers-lfreq-lang',
  'ciphers-lfreq-sort',
  'ciphers-enigma-reflector',
  'ciphers-enigma-rotor-left',
  'ciphers-enigma-rotor-middle',
  'ciphers-enigma-rotor-right',
  'ciphers-enigma-ring-left',
  'ciphers-enigma-ring-middle',
  'ciphers-enigma-ring-right',
  'ciphers-enigma-pos-left',
  'ciphers-enigma-pos-middle',
  'ciphers-enigma-pos-right',
  'ciphers-enigma-plugboard',
  'ciphers-kdf-salt',
  'ciphers-kdf-iterations',
  'ciphers-kdf-hash',
  'ciphers-kdf-key-length',
  'ciphers-kdf-cost',
  'ciphers-kdf-memory',
  'ciphers-kdf-parallelism',
  'ciphers-kdf-variant',
  'ciphers-kdf-verify-hash',
]

/**
 * Кодирует объект состояния в компактную URL-безопасную строку (base64url от UTF-8 JSON).
 */
function encodeShareState(state) {
  const json = JSON.stringify(state)
  const bytes = new TextEncoder().encode(json)
  let binary = ''
  bytes.forEach((byte) => { binary += String.fromCharCode(byte) })
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
}

/**
 * Декодирует строку permalink обратно в объект состояния; при ошибке возвращает null.
 */
function decodeShareState(encoded) {
  try {
    const normalized = encoded.replace(/-/g, '+').replace(/_/g, '/')
    const binary = atob(normalized)
    const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0))
    const parsed = JSON.parse(new TextDecoder().decode(bytes))
    return parsed && typeof parsed === 'object' ? parsed : null
  } catch {
    return null
  }
}

