import {
  generatePasswordBatch,
  generatePassphraseBatch,
  passwordEntropy,
  passphraseEntropy,
  strengthBand,
} from './cipher-tool/password-core.js'
import { initCustomSelects } from './cipher-tool/custom-selects.js'

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
 * Инициализирует клиентский генератор паролей и парольных фраз.
 * Страница помечена data-page="password-generator"; на других страницах ничего не делает.
 */
export function initPasswordGeneratorPage() {
  const root = document.querySelector('[data-page="password-generator"]')
  if (!root) return

  const ui = parseUi(root.getAttribute('data-cipher-ui'))
  const t = (key, fallback) => (ui[key] != null && ui[key] !== '' ? ui[key] : fallback)
  const byId = (id) => document.getElementById(id)

  const output = byId('ciphers-pwd-output')
  const generateBtn = byId('ciphers-pwd-generate')
  if (!output || !generateBtn) return

  // Табы режима.
  const tabPassword = byId('ciphers-pwd-tab-password')
  const tabPassphrase = byId('ciphers-pwd-tab-passphrase')
  const panelPassword = byId('ciphers-pwd-panel-password')
  const panelPassphrase = byId('ciphers-pwd-panel-passphrase')

  // Пароль.
  const lengthInput = byId('ciphers-pwd-length')
  const lowerCb = byId('ciphers-pwd-lower')
  const upperCb = byId('ciphers-pwd-upper')
  const digitsCb = byId('ciphers-pwd-digits')
  const symbolsCb = byId('ciphers-pwd-symbols')
  const exSimilarCb = byId('ciphers-pwd-exclude-similar')
  const exAmbiguousCb = byId('ciphers-pwd-exclude-ambiguous')
  const noRepeatsCb = byId('ciphers-pwd-no-repeats')

  // Парольная фраза.
  const wordsInput = byId('ciphers-pwd-words')
  const separatorSelect = byId('ciphers-pwd-separator')
  const caseSelect = byId('ciphers-pwd-case')
  const addNumberCb = byId('ciphers-pwd-add-number')

  // Общее.
  const countInput = byId('ciphers-pwd-count')
  const counter = byId('ciphers-pwd-counter')
  const feedback = byId('ciphers-feedback')
  const copyBtn = byId('ciphers-pwd-copy')
  const clearBtn = byId('ciphers-pwd-clear')
  const shareBtn = byId('ciphers-share')

  // Индикатор надёжности.
  const strengthBox = byId('ciphers-pwd-strength')
  const strengthVerdict = byId('ciphers-pwd-strength-verdict')
  const meterSegs = strengthBox ? Array.from(strengthBox.querySelectorAll('.pwd__meter-seg')) : []
  const entropyOut = byId('ciphers-pwd-entropy')
  const crackOut = byId('ciphers-pwd-crack')

  const charsLabel = t('charsLabel', 'chars')
  const bytesLabel = t('bytesLabel', 'bytes')
  const bitsLabel = t('pwdBitsLabel', 'bits')
  const locale = document.documentElement.lang || 'en'
  const OPTS_KEY = 'password-generator:opts'

  let mode = 'password'
  let rawList = []
  let applying = false
  let wordlistCache = null
  let zxcvbnCache = null
  let zxcvbnToken = 0

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

  // Ленивая загрузка тяжёлого словаря (отдельный чанк) — только для парольных фраз.
  const ensureWordlist = async () => {
    if (wordlistCache) return wordlistCache
    const mod = await import('./cipher-tool/password-wordlist.js')
    wordlistCache = mod.EFF_WORDLIST
    return wordlistCache
  }

  // Ленивая загрузка zxcvbn (отдельный чанк) для реалистичной оценки времени взлома.
  const ensureZxcvbn = async () => {
    if (zxcvbnCache) return zxcvbnCache
    const mod = await import('zxcvbn')
    zxcvbnCache = mod.default || mod
    return zxcvbnCache
  }

  const readPasswordOpts = () => ({
    length: clamp(lengthInput?.value, 4, 128, 16),
    lower: Boolean(lowerCb?.checked),
    upper: Boolean(upperCb?.checked),
    digits: Boolean(digitsCb?.checked),
    symbols: Boolean(symbolsCb?.checked),
    excludeSimilar: Boolean(exSimilarCb?.checked),
    excludeAmbiguous: Boolean(exAmbiguousCb?.checked),
    noRepeats: Boolean(noRepeatsCb?.checked),
    count: clamp(countInput?.value, 1, 100, 1),
  })

  const readPassphraseOpts = () => ({
    words: clamp(wordsInput?.value, 3, 20, 6),
    separator: separatorSelect?.value ?? '-',
    wordCase: caseSelect?.value ?? 'lower',
    addNumber: Boolean(addNumberCb?.checked),
    count: clamp(countInput?.value, 1, 100, 1),
  })

  const updateCounter = () => {
    if (!counter) return
    const val = output.value || ''
    counter.textContent = `${val.length} ${charsLabel} · ${new TextEncoder().encode(val).length} ${bytesLabel}`
  }

  // Обновляет полосу надёжности и подпись энтропии по количеству бит.
  const updateStrength = (bits) => {
    const band = strengthBand(bits)
    if (strengthBox) strengthBox.setAttribute('data-band', String(band))
    meterSegs.forEach((seg, i) => seg.classList.toggle('is-on', i < band))
    if (strengthVerdict) strengthVerdict.textContent = t(`pwdStrength${band}`, '')
    if (entropyOut) {
      entropyOut.textContent = `${t('pwdEntropyLabel', 'Entropy')}: ~${Math.round(bits)} ${bitsLabel}`
    }
  }

  // Локализованно форматирует длительность взлома из числа секунд (zxcvbn отдаёт
  // готовую строку только по-английски, поэтому строим её сами через Intl).
  const formatCrackTime = (seconds) => {
    const minute = 60
    const hour = minute * 60
    const day = hour * 24
    const month = day * 31
    const year = month * 12
    const century = year * 100
    if (seconds < 1) return t('pwdCrackInstant', 'less than a second')
    if (seconds >= century) return t('pwdCrackCenturies', 'centuries')

    let num
    let unit
    if (seconds < minute) { num = Math.round(seconds); unit = 'second' }
    else if (seconds < hour) { num = Math.round(seconds / minute); unit = 'minute' }
    else if (seconds < day) { num = Math.round(seconds / hour); unit = 'hour' }
    else if (seconds < month) { num = Math.round(seconds / day); unit = 'day' }
    else if (seconds < year) { num = Math.round(seconds / month); unit = 'month' }
    else { num = Math.round(seconds / year); unit = 'year' }

    try {
      return new Intl.NumberFormat(locale, { style: 'unit', unit, unitDisplay: 'long', maximumFractionDigits: 0 }).format(Math.max(1, num))
    } catch {
      return `${Math.max(1, num)} ${unit}`
    }
  }

  // Асинхронно уточняет время взлома через zxcvbn для первого значения партии.
  const updateCrackTime = (sample) => {
    if (!crackOut) return
    const token = ++zxcvbnToken
    if (!sample) {
      crackOut.textContent = ''
      return
    }
    ensureZxcvbn()
      .then((zxcvbn) => {
        if (token !== zxcvbnToken) return
        // zxcvbn ограничивает вход 100 символами — длинные значения обрезаем для скорости.
        const res = zxcvbn(String(sample).slice(0, 72))
        // Берём число секунд (а не готовую англ. строку display) и локализуем сами.
        const seconds = res.crack_times_seconds?.offline_slow_hashing_1e4_per_second
        crackOut.textContent = Number.isFinite(seconds)
          ? `${t('pwdCrackLabel', 'Crack time')}: ${formatCrackTime(seconds)}`
          : ''
      })
      .catch(() => {
        if (token === zxcvbnToken) crackOut.textContent = ''
      })
  }

  const currentSample = () => (rawList.length > 0 ? rawList[0] : '')

  const regenerate = async () => {
    setFeedback('')

    if (mode === 'password') {
      const opts = readPasswordOpts()
      try {
        rawList = generatePasswordBatch(opts)
      } catch (err) {
        rawList = []
        output.value = ''
        updateCounter()
        updateStrength(0)
        updateCrackTime('')
        const code = err && err.message
        if (code === 'no-charset') setFeedback(t('pwdErrNoCharset', 'Select at least one character set.'), true)
        else if (code === 'too-long-norepeat') setFeedback(t('pwdErrTooLongNoRepeat', 'Length exceeds the number of unique characters available.'), true)
        else setFeedback(String(code || err), true)
        return
      }
      output.value = rawList.join('\n')
      updateCounter()
      updateStrength(passwordEntropy(opts))
      updateCrackTime(currentSample())
      return
    }

    // Парольная фраза.
    let wordlist
    try {
      wordlist = await ensureWordlist()
    } catch {
      setFeedback(t('pwdErrNoCharset', 'Unable to load the wordlist.'), true)
      return
    }
    const opts = readPassphraseOpts()
    rawList = generatePassphraseBatch(opts, wordlist)
    output.value = rawList.join('\n')
    updateCounter()
    updateStrength(passphraseEntropy(opts, wordlist.length))
    updateCrackTime(currentSample())
  }

  const setMode = (next) => {
    mode = next === 'passphrase' ? 'passphrase' : 'password'
    const isPw = mode === 'password'
    if (panelPassword) panelPassword.hidden = !isPw
    if (panelPassphrase) panelPassphrase.hidden = isPw
    if (tabPassword) {
      tabPassword.classList.toggle('is-active', isPw)
      tabPassword.setAttribute('aria-selected', String(isPw))
    }
    if (tabPassphrase) {
      tabPassphrase.classList.toggle('is-active', !isPw)
      tabPassphrase.setAttribute('aria-selected', String(!isPw))
    }
  }

  const saveOpts = () => {
    try {
      window.localStorage.setItem(OPTS_KEY, JSON.stringify({ mode, ...readPasswordOpts(), ...readPassphraseOpts() }))
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
    if (lengthInput && Number.isFinite(saved.length)) lengthInput.value = String(clamp(saved.length, 4, 128, 16))
    if (lowerCb && 'lower' in saved) lowerCb.checked = saved.lower !== false
    if (upperCb && 'upper' in saved) upperCb.checked = Boolean(saved.upper)
    if (digitsCb && 'digits' in saved) digitsCb.checked = Boolean(saved.digits)
    if (symbolsCb && 'symbols' in saved) symbolsCb.checked = Boolean(saved.symbols)
    if (exSimilarCb && 'excludeSimilar' in saved) exSimilarCb.checked = Boolean(saved.excludeSimilar)
    if (exAmbiguousCb && 'excludeAmbiguous' in saved) exAmbiguousCb.checked = Boolean(saved.excludeAmbiguous)
    if (noRepeatsCb && 'noRepeats' in saved) noRepeatsCb.checked = Boolean(saved.noRepeats)
    if (wordsInput && Number.isFinite(saved.words)) wordsInput.value = String(clamp(saved.words, 3, 20, 6))
    if (addNumberCb && 'addNumber' in saved) addNumberCb.checked = Boolean(saved.addNumber)
    if (saved.mode === 'passphrase') setMode('passphrase')
  }

  // Применяет пресет примера/чипа (data-pwd-* атрибуты): сначала сброс к дефолту,
  // затем накладываем указанные поля. Флаг applying гасит промежуточные regenerate.
  const applyPreset = (el) => {
    applying = true
    const attr = (name) => el.getAttribute(name)
    const attrBool = (name) => {
      const v = attr(name)
      return v === '1' || v === 'true'
    }
    const has = (name) => el.getAttribute(name) !== null

    setMode(attr('data-pwd-mode') || 'password')

    // Пароль: дефолт lower+upper+digits, символы/опции выкл; затем накладываем.
    if (lengthInput) lengthInput.value = String(clamp(has('data-pwd-length') ? attr('data-pwd-length') : 16, 4, 128, 16))
    if (lowerCb) lowerCb.checked = has('data-pwd-lower') ? attrBool('data-pwd-lower') : true
    if (upperCb) upperCb.checked = has('data-pwd-upper') ? attrBool('data-pwd-upper') : true
    if (digitsCb) digitsCb.checked = has('data-pwd-digits') ? attrBool('data-pwd-digits') : true
    if (symbolsCb) symbolsCb.checked = has('data-pwd-symbols') ? attrBool('data-pwd-symbols') : false
    if (exSimilarCb) exSimilarCb.checked = attrBool('data-pwd-exclude-similar')
    if (exAmbiguousCb) exAmbiguousCb.checked = attrBool('data-pwd-exclude-ambiguous')
    if (noRepeatsCb) noRepeatsCb.checked = attrBool('data-pwd-no-repeats')

    // Парольная фраза.
    if (wordsInput) wordsInput.value = String(clamp(has('data-pwd-words') ? attr('data-pwd-words') : 6, 3, 20, 6))
    if (separatorSelect) separatorSelect.value = has('data-pwd-separator') ? attr('data-pwd-separator') : '-'
    if (caseSelect) caseSelect.value = has('data-pwd-case') ? attr('data-pwd-case') : 'lower'
    if (addNumberCb) addNumberCb.checked = attrBool('data-pwd-add-number')

    // Количество примеры не задают — оставляем текущее.

    // Синхронизируем кастомные селекты (custom-selects.js слушает change).
    separatorSelect?.dispatchEvent(new Event('change', { bubbles: true }))
    caseSelect?.dispatchEvent(new Event('change', { bubbles: true }))

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

  tabPassword?.addEventListener('click', () => { setMode('password'); if (!applying) { saveOpts(); regenerate() } })
  tabPassphrase?.addEventListener('click', () => { setMode('passphrase'); if (!applying) { saveOpts(); regenerate() } })

  ;[lowerCb, upperCb, digitsCb, symbolsCb, exSimilarCb, exAmbiguousCb, noRepeatsCb, addNumberCb].forEach(
    (el) => el?.addEventListener('change', onChange),
  )
  ;[separatorSelect, caseSelect].forEach((el) => el?.addEventListener('change', onChange))
  ;[lengthInput, wordsInput, countInput].forEach((el) => el?.addEventListener('input', onChange))

  // Степперы.
  const step = (input, delta, min, max, def) => {
    if (!input) return
    input.value = String(clamp(Number(input.value) + delta, min, max, def))
    onChange()
  }
  byId('ciphers-pwd-length-dec')?.addEventListener('click', () => step(lengthInput, -1, 4, 128, 16))
  byId('ciphers-pwd-length-inc')?.addEventListener('click', () => step(lengthInput, 1, 4, 128, 16))
  byId('ciphers-pwd-words-dec')?.addEventListener('click', () => step(wordsInput, -1, 3, 20, 6))
  byId('ciphers-pwd-words-inc')?.addEventListener('click', () => step(wordsInput, 1, 3, 20, 6))
  byId('ciphers-pwd-count-dec')?.addEventListener('click', () => step(countInput, -1, 1, 100, 1))
  byId('ciphers-pwd-count-inc')?.addEventListener('click', () => step(countInput, 1, 1, 100, 1))

  generateBtn.addEventListener('click', regenerate)

  document.querySelectorAll('.pwd-example').forEach((el) => {
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
      setFeedback(t('pwdCopiedLabel', 'Copied!'))
    } catch {
      setFeedback(t('feedbackUrlCopyFailed', 'Unable to copy'), true)
    }
  })

  clearBtn?.addEventListener('click', () => {
    rawList = []
    output.value = ''
    updateCounter()
    updateStrength(0)
    updateCrackTime('')
    setFeedback('')
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
  initCustomSelects()
  setMode(mode)
  regenerate()
}
