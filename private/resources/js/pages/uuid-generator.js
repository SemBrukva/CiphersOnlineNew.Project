import { generate, format, NAME_BASED } from './cipher-tool/uuid-core.js'
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
 * Инициализирует клиентский генератор UUID / GUID.
 * Страница помечена data-page="uuid-generator"; на других страницах инициализатор ничего не делает.
 */
export function initUuidGeneratorPage() {
  const root = document.querySelector('[data-page="uuid-generator"]')
  if (!root) return

  const ui = parseUi(root.getAttribute('data-cipher-ui'))
  const t = (key, fallback) => (ui[key] != null && ui[key] !== '' ? ui[key] : fallback)

  const versionSelect = document.getElementById('ciphers-uuid-version')
  const countInput = document.getElementById('ciphers-uuid-count')
  const nameBasedWrap = document.getElementById('ciphers-uuid-namebased')
  const namespaceSelect = document.getElementById('ciphers-uuid-namespace')
  const namespaceCustomWrap = document.getElementById('ciphers-uuid-namespace-custom-wrap')
  const namespaceCustom = document.getElementById('ciphers-uuid-namespace-custom')
  const nameInput = document.getElementById('ciphers-uuid-name')
  const output = document.getElementById('ciphers-uuid-output')
  const counter = document.getElementById('ciphers-uuid-counter')
  const feedback = document.getElementById('ciphers-feedback')

  if (!versionSelect || !output) return

  const optUppercase = document.getElementById('ciphers-uuid-uppercase')
  const optHyphens = document.getElementById('ciphers-uuid-hyphens')
  const optBraces = document.getElementById('ciphers-uuid-braces')
  const optUrn = document.getElementById('ciphers-uuid-urn')
  const formatInputs = [optUppercase, optHyphens, optBraces, optUrn]

  const countDecBtn = document.getElementById('ciphers-uuid-count-dec')
  const countIncBtn = document.getElementById('ciphers-uuid-count-inc')
  const generateBtn = document.getElementById('ciphers-uuid-generate')
  const copyBtn = document.getElementById('ciphers-uuid-copy')
  const clearBtn = document.getElementById('ciphers-uuid-clear')
  const shareBtn = document.getElementById('ciphers-share')

  const charsLabel = t('charsLabel', 'chars')
  const bytesLabel = t('bytesLabel', 'bytes')

  const FORMAT_KEY = 'uuid-generator:format'

  // Последняя партия в каноническом виде (без форматирования) — чтобы переключение
  // формата не перегенерировало случайные значения.
  let rawList = []
  // Флаг подавления перегенерации на время программной установки полей (примеры).
  let applying = false

  const clampCount = (n) => {
    const v = Math.trunc(Number(n))
    if (!Number.isFinite(v) || v < 1) return 1
    return Math.min(100, v)
  }

  const setFeedback = (message, isError = false) => {
    if (!feedback) return
    feedback.textContent = message
    feedback.classList.toggle('error', isError)
  }

  const readFormat = () => ({
    uppercase: Boolean(optUppercase?.checked),
    hyphens: optHyphens ? optHyphens.checked : true,
    braces: Boolean(optBraces?.checked),
    urn: Boolean(optUrn?.checked),
  })

  const saveFormat = () => {
    try {
      window.localStorage.setItem(FORMAT_KEY, JSON.stringify(readFormat()))
    } catch {
      // ignore storage errors
    }
  }

  const loadFormat = () => {
    let saved = null
    try {
      saved = JSON.parse(window.localStorage.getItem(FORMAT_KEY) || 'null')
    } catch {
      saved = null
    }
    if (!saved || typeof saved !== 'object') return
    if (optUppercase) optUppercase.checked = Boolean(saved.uppercase)
    if (optHyphens) optHyphens.checked = saved.hyphens !== false
    if (optBraces) optBraces.checked = Boolean(saved.braces)
    if (optUrn) optUrn.checked = Boolean(saved.urn)
  }

  // Обновляет счётчик символов и байт по текущему содержимому поля вывода.
  const updateCounter = () => {
    if (!counter) return
    const val = output.value || ''
    const chars = val.length
    const bytes = new TextEncoder().encode(val).length
    counter.textContent = `${chars} ${charsLabel} · ${bytes} ${bytesLabel}`
  }

  // Переключение формата только переформатирует последнюю партию (ядро format()),
  // не перегенерируя случайные значения.
  const render = () => {
    const fmt = readFormat()
    output.value = rawList.map((u) => format(u, fmt)).join('\n')
    updateCounter()
  }

  const resolveNamespaceValue = () => {
    if (namespaceSelect?.value === 'custom') {
      return (namespaceCustom?.value || '').trim()
    }
    return namespaceSelect?.value || 'dns'
  }

  const currentVersion = () => versionSelect.value
  const isNameBased = (version) => NAME_BASED.includes(version)

  // Синхронизирует видимость полей namespace/name и доступность количества с версией.
  const syncControls = () => {
    const version = currentVersion()
    const nameBased = isNameBased(version)
    if (nameBasedWrap) nameBasedWrap.hidden = !nameBased
    if (namespaceCustomWrap) namespaceCustomWrap.hidden = !(nameBased && namespaceSelect?.value === 'custom')
    // Для констант (nil/max) количество идентичных значений бессмысленно.
    const fixed = version === 'nil' || version === 'max'
    if (countInput) countInput.disabled = fixed
  }

  const regenerate = async () => {
    const version = currentVersion()
    setFeedback('')

    if (isNameBased(version)) {
      const name = (nameInput?.value || '').trim()
      if (name === '') {
        rawList = []
        render()
        setFeedback(t('uuidErrNameRequired', 'Enter a name to hash for v3/v5 UUIDs.'), true)
        return
      }
    }

    let count = clampCount(countInput?.value)
    if (countInput?.disabled) count = 1

    try {
      // Генерируем в каноническом виде; форматирование накладывает render().
      rawList = await generate({
        version,
        count,
        name: nameInput?.value || '',
        namespace: resolveNamespaceValue(),
        format: {},
      })
      render()
    } catch (err) {
      rawList = []
      render()
      const code = err && err.message
      if (code === 'bad-namespace') {
        setFeedback(t('uuidErrNamespaceInvalid', 'Invalid namespace UUID.'), true)
      } else {
        setFeedback(String(code || err), true)
      }
    }
  }

  // Применяет пресет примера (data-uuid-* атрибуты). Поля СНАЧАЛА сбрасываются к
  // значениям по умолчанию, и только затем накладываются заданные в пресете — иначе
  // неуказанные настройки «залипают» от предыдущего примера (например количество).
  // Программная установка value + dispatch('change') синхронизирует кастомный select;
  // флаг applying гасит промежуточные regenerate.
  const applyPreset = (el) => {
    applying = true

    // Версия (по умолчанию v4).
    versionSelect.value = el.getAttribute('data-uuid-version') || 'v4'

    // Количество (по умолчанию 1).
    if (countInput) {
      const count = el.getAttribute('data-uuid-count')
      countInput.value = String(clampCount(count !== null ? count : 1))
    }

    // Пространство имён (по умолчанию dns); произвольное значение → вариант custom.
    if (namespaceSelect) {
      const namespace = el.getAttribute('data-uuid-namespace') || 'dns'
      if (['dns', 'url', 'oid', 'x500'].includes(namespace)) {
        namespaceSelect.value = namespace
        if (namespaceCustom) namespaceCustom.value = ''
      } else {
        namespaceSelect.value = 'custom'
        if (namespaceCustom) namespaceCustom.value = namespace
      }
    }

    // Имя (по умолчанию пустое).
    if (nameInput) nameInput.value = el.getAttribute('data-uuid-name') || ''

    // Формат: всегда к дефолту (только дефисы), затем накладываем указанные флаги.
    if (optHyphens) optHyphens.checked = true
    if (optUppercase) optUppercase.checked = false
    if (optBraces) optBraces.checked = false
    if (optUrn) optUrn.checked = false
    const fmtInputs = { uppercase: optUppercase, hyphens: optHyphens, braces: optBraces, urn: optUrn }
    Object.entries(fmtInputs).forEach(([k, input]) => {
      const v = el.getAttribute(`data-uuid-${k}`)
      if (v !== null && input) input.checked = v === '1' || v === 'true'
    })
    saveFormat()

    // Синхронизируем видимость namespace/name, доступность count и триггеры кастомных
    // селектов (custom-selects.js слушает change), затем перегенерируем один раз.
    versionSelect.dispatchEvent(new Event('change', { bubbles: true }))
    namespaceSelect?.dispatchEvent(new Event('change', { bubbles: true }))

    applying = false
    regenerate()
  }

  // --- Обработчики ---

  versionSelect.addEventListener('change', () => {
    syncControls()
    if (!applying) regenerate()
  })

  countInput?.addEventListener('input', () => { if (!applying) regenerate() })

  countDecBtn?.addEventListener('click', () => {
    if (countInput) countInput.value = String(clampCount(Number(countInput.value) - 1))
    regenerate()
  })
  countIncBtn?.addEventListener('click', () => {
    if (countInput) countInput.value = String(clampCount(Number(countInput.value) + 1))
    regenerate()
  })

  namespaceSelect?.addEventListener('change', () => {
    syncControls()
    if (!applying) regenerate()
  })
  namespaceCustom?.addEventListener('input', () => { if (!applying) regenerate() })
  nameInput?.addEventListener('input', () => { if (!applying) regenerate() })

  // Переключение формата не перегенерирует значения — только переформатирует.
  formatInputs.forEach((el) => el?.addEventListener('change', () => {
    saveFormat()
    render()
  }))

  generateBtn?.addEventListener('click', regenerate)

  // Чипы в тулбаре + карточки примеров ниже по странице.
  document.querySelectorAll('.uuid-example').forEach((el) => {
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
      setFeedback(t('uuidCopiedLabel', 'Copied!'))
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

  shareBtn?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(window.location.href)
      setFeedback(t('feedbackUrlCopied', 'Page URL copied.'))
    } catch {
      setFeedback(t('feedbackUrlCopyFailed', 'Unable to copy page URL.'), true)
    }
  })

  loadFormat()
  initCustomSelects()
  syncControls()
  regenerate()
}
