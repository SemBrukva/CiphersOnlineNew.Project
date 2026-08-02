import { generateBatch, formatList, similarChars } from './cipher-tool/randomstring-core.js'
import { initCustomSelects } from './cipher-tool/custom-selects.js'

// Короткий, «человеческий» показ похожих символов по алфавиту (для подсказки у
// чекбокса). Реальное исключение использует более полный набор из ядра.
const SIMILAR_HINT = {
  ru: 'о, О, з, З, 0',
  tr: 'ı, i, l, 1, O, 0',
}
const SIMILAR_HINT_DEFAULT = 'i, l, 1, O, 0'

/**
 * Безопасно разбирает JSON конфигурации инструмента из data-атрибута.
 *
 * @param  {string|null} raw
 * @return {Record<string, *>}
 */
function parseUi(raw) {
  if (!raw) return {}
  try {
    const parsed = JSON.parse(raw)
    return parsed && typeof parsed === 'object' ? parsed : {}
  } catch {
    return {}
  }
}

/**
 * Инициализирует клиентский генератор случайных строк.
 * Страница помечена data-page="random-string"; на других страницах ничего не делает.
 */
export function initRandomStringGeneratorPage() {
  const root = document.querySelector('[data-page="random-string"]')
  if (!root) return

  const ui = parseUi(root.getAttribute('data-cipher-ui'))
  const t = (key, fallback) => (ui[key] != null && ui[key] !== '' ? ui[key] : fallback)
  const byId = (id) => document.getElementById(id)

  const output = byId('ciphers-rs-output')
  const generateBtn = byId('ciphers-rs-generate')
  if (!output || !generateBtn) return

  const lengthInput = byId('ciphers-rs-length')
  const countInput = byId('ciphers-rs-count')
  const formatSelect = byId('ciphers-rs-format')
  const alphabetSelect = byId('ciphers-rs-alphabet')
  const lowerCb = byId('ciphers-rs-lower')
  const upperCb = byId('ciphers-rs-upper')
  const digitsCb = byId('ciphers-rs-digits')
  const symbolsCb = byId('ciphers-rs-symbols')
  const customInput = byId('ciphers-rs-custom')
  const exSimilarCb = byId('ciphers-rs-exclude-similar')
  const noRepeatsCb = byId('ciphers-rs-no-repeats')
  const similarHint = byId('ciphers-rs-similar-hint')

  const counter = byId('ciphers-rs-counter')
  const feedback = byId('ciphers-feedback')
  const copyBtn = byId('ciphers-rs-copy')
  const clearBtn = byId('ciphers-rs-clear')
  const downloadBtn = byId('ciphers-rs-download')
  const shareBtn = byId('ciphers-share')

  const charsLabel = t('charsLabel', 'chars')
  const bytesLabel = t('bytesLabel', 'bytes')
  const OPTS_KEY = 'random-string:opts'
  // Алфавиты языков (код → строка строчных букв), прокинутые из PHP-каталога.
  const alphabets = ui.rsAlphabets && typeof ui.rsAlphabets === 'object' ? ui.rsAlphabets : {}

  let rawList = []
  let applying = false

  // Возвращает строчные/прописные буквы выбранного алфавита. Прописные строим
  // locale-aware (важно для турецкого i→İ); для латиницы по умолчанию — a–z/A–Z.
  const letterChars = () => {
    const code = alphabetSelect?.value || 'en'
    const lower = typeof alphabets[code] === 'string' ? alphabets[code] : ''
    if (lower === '') return { lowerChars: '', upperChars: '' }
    let upperChars
    try {
      upperChars = lower.toLocaleUpperCase(code)
    } catch {
      upperChars = lower.toUpperCase()
    }
    return { lowerChars: lower, upperChars }
  }

  // Обновляет подсказку с примером похожих символов под выбранный алфавит.
  const updateSimilarHint = () => {
    if (!similarHint) return
    const code = alphabetSelect?.value || 'en'
    similarHint.textContent = `(${SIMILAR_HINT[code] ?? SIMILAR_HINT_DEFAULT})`
  }

  const clamp = (value, min, max, def) => {
    const v = Math.trunc(Number(value))
    if (!Number.isFinite(v)) return def
    return Math.max(min, Math.min(max, v))
  }

  const setFeedback = (message, isError = false) => {
    if (!feedback) return
    feedback.textContent = message
    feedback.classList.toggle('error', isError)
  }

  const readOpts = () => ({
    length: clamp(lengthInput?.value, 1, 512, 32),
    count: clamp(countInput?.value, 1, 100, 1),
    lower: Boolean(lowerCb?.checked),
    upper: Boolean(upperCb?.checked),
    digits: Boolean(digitsCb?.checked),
    symbols: Boolean(symbolsCb?.checked),
    custom: customInput?.value ?? '',
    excludeSimilar: Boolean(exSimilarCb?.checked),
    noRepeats: Boolean(noRepeatsCb?.checked),
    similarChars: similarChars(alphabetSelect?.value || 'en'),
    ...letterChars(),
  })

  const updateCounter = () => {
    if (!counter) return
    const val = output.value || ''
    counter.textContent = `${val.length} ${charsLabel} · ${new TextEncoder().encode(val).length} ${bytesLabel}`
  }

  const regenerate = () => {
    setFeedback('')
    const opts = readOpts()
    try {
      rawList = generateBatch(opts)
    } catch (err) {
      rawList = []
      output.value = ''
      updateCounter()
      const code = err && err.message
      if (code === 'no-charset') setFeedback(t('rsErrNoCharset', 'Select at least one character set or enter a custom alphabet.'), true)
      else if (code === 'too-long-norepeat') setFeedback(t('rsErrTooLongNoRepeat', 'Length exceeds the number of unique characters available.'), true)
      else setFeedback(String(code || err), true)
      return
    }
    output.value = formatList(rawList, formatSelect?.value ?? 'newline')
    updateCounter()
  }

  const saveOpts = () => {
    try {
      window.localStorage.setItem(OPTS_KEY, JSON.stringify({
        ...readOpts(),
        format: formatSelect?.value ?? 'newline',
        alphabet: alphabetSelect?.value ?? 'en',
      }))
    } catch {
      // ignore storage errors
    }
  }

  const loadOpts = () => {
    let saved = null
    try {
      saved = JSON.parse(window.localStorage.getItem(OPTS_KEY) || 'null')
    } catch {
      saved = null
    }
    if (!saved || typeof saved !== 'object') return
    if (lengthInput && Number.isFinite(saved.length)) lengthInput.value = String(clamp(saved.length, 1, 512, 32))
    if (countInput && Number.isFinite(saved.count)) countInput.value = String(clamp(saved.count, 1, 100, 1))
    if (formatSelect && typeof saved.format === 'string') formatSelect.value = saved.format
    if (alphabetSelect && typeof saved.alphabet === 'string' && saved.alphabet in alphabets) alphabetSelect.value = saved.alphabet
    if (lowerCb && 'lower' in saved) lowerCb.checked = Boolean(saved.lower)
    if (upperCb && 'upper' in saved) upperCb.checked = Boolean(saved.upper)
    if (digitsCb && 'digits' in saved) digitsCb.checked = Boolean(saved.digits)
    if (symbolsCb && 'symbols' in saved) symbolsCb.checked = Boolean(saved.symbols)
    if (customInput && typeof saved.custom === 'string') customInput.value = saved.custom
    if (exSimilarCb && 'excludeSimilar' in saved) exSimilarCb.checked = Boolean(saved.excludeSimilar)
    if (noRepeatsCb && 'noRepeats' in saved) noRepeatsCb.checked = Boolean(saved.noRepeats)
  }

  // Применяет пресет чипа (data-rs-* атрибуты): сброс к дефолту, затем накладываем.
  const applyPreset = (el) => {
    applying = true
    const attr = (name) => el.getAttribute(name)
    const attrBool = (name) => {
      const v = attr(name)
      return v === '1' || v === 'true'
    }
    const has = (name) => el.getAttribute(name) !== null

    if (lengthInput) lengthInput.value = String(clamp(has('data-rs-length') ? attr('data-rs-length') : 32, 1, 512, 32))
    if (lowerCb) lowerCb.checked = has('data-rs-lower') ? attrBool('data-rs-lower') : true
    if (upperCb) upperCb.checked = has('data-rs-upper') ? attrBool('data-rs-upper') : true
    if (digitsCb) digitsCb.checked = has('data-rs-digits') ? attrBool('data-rs-digits') : true
    if (symbolsCb) symbolsCb.checked = has('data-rs-symbols') ? attrBool('data-rs-symbols') : false
    if (customInput) customInput.value = has('data-rs-custom') ? attr('data-rs-custom') : ''
    if (exSimilarCb) exSimilarCb.checked = attrBool('data-rs-exclude-similar')
    if (noRepeatsCb) noRepeatsCb.checked = attrBool('data-rs-no-repeats')
    if (alphabetSelect) {
      alphabetSelect.value = has('data-rs-alphabet') ? attr('data-rs-alphabet') : 'en'
      alphabetSelect.dispatchEvent(new Event('change', { bubbles: true }))
    }

    // Количество и формат вывода пресеты не задают — оставляем текущие.

    applying = false
    saveOpts()
    regenerate()
  }

  // --- Обработчики ---

  const onChange = () => {
    if (applying) return
    saveOpts()
    regenerate()
  }

  ;[lowerCb, upperCb, digitsCb, symbolsCb, exSimilarCb, noRepeatsCb].forEach((el) => el?.addEventListener('change', onChange))
  formatSelect?.addEventListener('change', onChange)
  alphabetSelect?.addEventListener('change', () => { updateSimilarHint(); onChange() })
  ;[lengthInput, countInput, customInput].forEach((el) => el?.addEventListener('input', onChange))

  const step = (input, delta, min, max, def) => {
    if (!input) return
    input.value = String(clamp(Number(input.value) + delta, min, max, def))
    onChange()
  }
  byId('ciphers-rs-length-dec')?.addEventListener('click', () => step(lengthInput, -1, 1, 512, 32))
  byId('ciphers-rs-length-inc')?.addEventListener('click', () => step(lengthInput, 1, 1, 512, 32))
  byId('ciphers-rs-count-dec')?.addEventListener('click', () => step(countInput, -1, 1, 100, 1))
  byId('ciphers-rs-count-inc')?.addEventListener('click', () => step(countInput, 1, 1, 100, 1))

  generateBtn.addEventListener('click', regenerate)

  document.querySelectorAll('.rs-example').forEach((el) => {
    el.addEventListener('click', () => {
      applyPreset(el)
      if (!root.contains(el)) {
        root.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
    })
  })

  copyBtn?.addEventListener('click', async () => {
    if (output.value === '') return
    try {
      await navigator.clipboard.writeText(output.value)
      setFeedback(t('rsCopiedLabel', 'Copied!'))
    } catch {
      setFeedback(t('feedbackUrlCopyFailed', 'Unable to copy'), true)
    }
  })

  clearBtn?.addEventListener('click', () => {
    rawList = []
    output.value = ''
    updateCounter()
    setFeedback('')
  })

  downloadBtn?.addEventListener('click', () => {
    if (output.value === '') return
    try {
      const blob = new Blob([output.value], { type: 'text/plain;charset=utf-8' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = 'random-strings.txt'
      document.body.appendChild(a)
      a.click()
      a.remove()
      URL.revokeObjectURL(url)
    } catch {
      setFeedback(t('feedbackUrlCopyFailed', 'Unable to download'), true)
    }
  })

  shareBtn?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(window.location.href)
      setFeedback(t('feedbackUrlCopied', 'Page URL copied.'))
    } catch {
      setFeedback(t('feedbackUrlCopyFailed', 'Unable to copy page URL.'), true)
    }
  })

  loadOpts()
  updateSimilarHint()
  initCustomSelects()
  regenerate()
}
