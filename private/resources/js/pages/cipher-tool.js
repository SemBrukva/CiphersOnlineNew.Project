import { getDecoderBySlug } from './cipher-tool/decoder-registry.js'

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
  const keyInput = document.getElementById('ciphers-key')
  const clearBtn = document.getElementById('ciphers-clear')

  if (!input || !output || !tabEncode || !tabDecode || !inputLabel || !counter) return

  let mode = 'encode'
  const isEncodingTool = slug.startsWith('encoding/')
  const calculationMode = String(ui.calculationMode || 'client').toLowerCase()
  const isApiMode = calculationMode === 'api'
  const apiAction = String(ui.apiAction || '').trim()
  const stateStorageKey = `cipher-tool:state:${slug}`
  let liveModeDebounceTimer = null
  const decoder = getDecoderBySlug(slug)

  const labels = {
    chars: ui.charsLabel || 'chars',
    bytes: ui.bytesLabel || 'bytes',
    inputEncode: ui.inputLabelEncode || 'Input',
    inputDecode: ui.inputLabelDecode || 'Encoded input',
    placeholderEncode: ui.placeholderEncode || 'Enter text...',
    placeholderDecode: ui.placeholderDecode || 'Paste encoded value...',
    invalid: ui.feedbackInvalidInput || 'Invalid input for current mode.',
    copied: ui.feedbackResultCopied || 'Result copied.',
    copyFailed: ui.feedbackResultCopyFailed || 'Unable to copy result.',
    urlCopied: ui.feedbackUrlCopied || 'Page URL copied.',
    urlCopyFailed: ui.feedbackUrlCopyFailed || 'Unable to copy page URL.',
    runFailed: ui.feedbackInvalidInput || 'Unable to process request.',
  }

  const setFeedback = (message, isError = false) => {
    if (!feedback) return
    feedback.textContent = message
    feedback.classList.toggle('error', isError)
  }

  const saveState = () => {
    if (!slug) return

    try {
      const state = {
        alphabet: String(alphabetSelect?.value ?? 'auto'),
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
    if (!slug) return null

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
    if (!alphabetSelect) return 39
    const selected = alphabetSelect.options[alphabetSelect.selectedIndex]
    const rawValue = Number(selected?.dataset?.maxShift ?? 39)
    return Number.isFinite(rawValue) && rawValue >= 0 ? rawValue : 39
  }

  const normalizeShiftInput = () => {
    if (!shiftInput) return 0
    const numericValue = Number(shiftInput.value)
    return Number.isFinite(numericValue) ? Math.trunc(numericValue) : 0
  }

  const setShiftValue = (nextValue) => {
    if (!shiftInput) return
    const maxShift = getMaxShift()
    const clamped = Math.min(Math.max(0, Math.trunc(nextValue)), maxShift)
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

    if (shiftInput && Number.isFinite(Number(savedState.shift))) {
      shiftInput.value = String(Math.trunc(Number(savedState.shift)))
    }

    if (keyInput && typeof savedState.key === 'string') {
      keyInput.value = savedState.key
    }

    syncShiftWithAlphabet()

    if (liveModeInput && typeof savedState.liveMode === 'boolean') {
      liveModeInput.checked = savedState.liveMode
    }
  }

  const process = () => {
    const value = input.value || ''
    updateCounter()

    if (!value.trim()) {
      output.value = ''
      setOutputState(false)
      setFeedback('')
      return
    }

    if (!isEncodingTool || isApiMode) {
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
      output.value = transform(value, mode, decoder)
      setOutputState(true)
      setFeedback('')
    } catch {
      output.value = ''
      setOutputState(false)
      setFeedback(labels.invalid, true)
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
    const key = String(keyInput?.value ?? '')
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
        settings: Object.fromEntries(
          Object.entries({
            shift,
            alphabet,
            key,
          }).filter(([, value]) => value !== '')
        ),
      })

      output.value = String(response?.result ?? '')
      setOutputState(Boolean(output.value))
      setFeedback('')
    } catch (error) {
      const message = String(error?.message ?? error?.response?.error?.message ?? labels.runFailed)
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

  document.querySelectorAll('.ciphers-example-chip').forEach((chip) => {
    chip.addEventListener('click', () => {
      const text = chip.getAttribute('data-example') || ''
      input.value = text
      if (looksLikeEncoded(text, decoder)) {
        setMode('decode')
      } else {
        setMode('encode')
      }
      input.focus()
    })
  })

  document.querySelectorAll('.ciphers-example-use').forEach((btn) => {
    btn.addEventListener('click', () => {
      const text = btn.getAttribute('data-example-text') || ''
      input.value = text
      if (looksLikeEncoded(text, decoder)) {
        setMode('decode')
      } else {
        setMode('encode')
      }
      document.getElementById('ciphers-tool-shell')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
      input.focus()
    })
  })

  clearBtn?.addEventListener('click', () => {
    input.value = ''
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
  saveState()
  setMode('encode')
  initCustomSelects()
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
function transform(value, mode, decoder) {
  if (!decoder) return ''
  return decoder.transform(value, mode)
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
