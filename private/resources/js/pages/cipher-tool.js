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

  if (!input || !output || !tabEncode || !tabDecode || !inputLabel || !counter) return

  let mode = 'encode'
  const isEncodingTool = slug.startsWith('encoding/')

  const bySlug = {
    base64: slug === 'encoding/base64',
    hex: slug === 'encoding/hex',
    binary: slug === 'encoding/binary-converter',
    url: slug === 'encoding/url-encode',
    jwt: slug === 'encoding/jwt-decoder',
    ascii: slug === 'encoding/ascii-converter',
    unicode: slug === 'encoding/unicode-converter',
  }

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
  }

  const setFeedback = (message, isError = false) => {
    if (!feedback) return
    feedback.textContent = message
    feedback.classList.toggle('error', isError)
  }

  const updateCounter = () => {
    const val = input.value || ''
    const chars = val.length

    if (bySlug.jwt) {
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
    } else {
      resultCard?.classList.remove('ciphers-result-card--live')
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

    if (!isEncodingTool) {
      output.value = ''
      setOutputState(false)
      setFeedback('')
      return
    }

    try {
      output.value = transform(value, mode, bySlug)
      setOutputState(true)
      setFeedback('')
    } catch {
      output.value = ''
      setOutputState(false)
      setFeedback(labels.invalid, true)
    }
  }

  input.addEventListener('input', process)
  tabEncode.addEventListener('click', () => setMode('encode'))
  tabDecode.addEventListener('click', () => setMode('decode'))

  document.querySelectorAll('.ciphers-example-chip').forEach((chip) => {
    chip.addEventListener('click', () => {
      const text = chip.getAttribute('data-example') || ''
      input.value = text
      if (looksLikeEncoded(text, bySlug)) {
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
      if (looksLikeEncoded(text, bySlug)) {
        setMode('decode')
      } else {
        setMode('encode')
      }
      document.getElementById('ciphers-tool-shell')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
      input.focus()
    })
  })

  copyBtn?.addEventListener('click', async () => {
    if (!output.value) return
    try {
      await navigator.clipboard.writeText(output.value)
      setFeedback(labels.copied)
      window.setTimeout(() => setFeedback(''), 1200)
    } catch {
      setFeedback(labels.copyFailed, true)
    }
  })

  shareBtn?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(window.location.href)
      setFeedback(labels.urlCopied)
      window.setTimeout(() => setFeedback(''), 1200)
    } catch {
      setFeedback(labels.urlCopyFailed, true)
    }
  })

  setMode('encode')
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
function transform(value, mode, flags) {
  if (flags.jwt) {
    if (mode === 'decode') return ''
    return decodeJwtSummary(value)
  }

  if (flags.base64) {
    return mode === 'encode'
      ? btoa(unescape(encodeURIComponent(value)))
      : decodeURIComponent(escape(atob(value.replace(/\s+/g, ''))))
  }

  if (flags.hex) {
    return mode === 'encode' ? textToHex(value) : hexToText(value)
  }

  if (flags.binary) {
    return mode === 'encode' ? textToBinary(value) : binaryToText(value)
  }

  if (flags.url) {
    return mode === 'encode' ? encodeURIComponent(value) : decodeURIComponent(value)
  }

  if (flags.ascii) {
    return mode === 'encode' ? textToAscii(value) : asciiToText(value)
  }

  if (flags.unicode) {
    return mode === 'encode' ? textToUnicodeEscapes(value) : unicodeEscapesToText(value)
  }

  return ''
}

/**
 * Эвристика автоопределения направления для примеров.
 */
function looksLikeEncoded(text, flags) {
  const value = (text || '').trim()
  if (!value) return false

  if (flags.base64) return /^[A-Za-z0-9+/]+={0,2}$/.test(value.replace(/\s+/g, ''))
  if (flags.hex) return /^[0-9a-fA-F\s]+$/.test(value)
  if (flags.binary) return /^[01\s]+$/.test(value)
  if (flags.url) return /%[0-9A-Fa-f]{2}/.test(value) || value.includes('+')
  if (flags.ascii) return /^\d+(?:\s+\d+)*$/.test(value)
  if (flags.unicode) return /\\u[0-9a-fA-F]{4}|U\+[0-9a-fA-F]{4,6}|&#x?[0-9a-fA-F]+;/.test(value)
  if (flags.jwt) return value.split('.').length === 3
  return false
}

function textToHex(value) {
  const bytes = new TextEncoder().encode(value)
  return Array.from(bytes).map((b) => b.toString(16).padStart(2, '0')).join('')
}

function hexToText(value) {
  const clean = value.replace(/\s+/g, '').toLowerCase()
  if (!clean || clean.length % 2 !== 0 || /[^0-9a-f]/.test(clean)) throw new Error('hex')
  const bytes = new Uint8Array(clean.match(/.{1,2}/g).map((part) => parseInt(part, 16)))
  return new TextDecoder().decode(bytes)
}

function textToBinary(value) {
  const bytes = new TextEncoder().encode(value)
  return Array.from(bytes).map((b) => b.toString(2).padStart(8, '0')).join(' ')
}

function binaryToText(value) {
  const clean = value.replace(/\s+/g, ' ').trim()
  if (!clean || /[^01\s]/.test(clean)) throw new Error('binary')
  const bytes = clean.split(' ').map((part) => {
    if (!part || part.length > 8 || /[^01]/.test(part)) throw new Error('binary')
    return parseInt(part.padStart(8, '0'), 2)
  })
  return new TextDecoder().decode(new Uint8Array(bytes))
}

function textToAscii(value) {
  return Array.from(value || '').map((char) => {
    const code = char.codePointAt(0)
    if (typeof code !== 'number' || code > 127) throw new Error('ascii')
    return String(code)
  }).join(' ')
}

function asciiToText(value) {
  const clean = (value || '').trim()
  if (!clean) throw new Error('ascii')
  return clean.split(/\s+/).map((part) => {
    if (!/^\d+$/.test(part)) throw new Error('ascii')
    const code = Number(part)
    if (code < 0 || code > 127) throw new Error('ascii')
    return String.fromCharCode(code)
  }).join('')
}

function textToUnicodeEscapes(value) {
  let result = ''
  for (let i = 0; i < value.length; i += 1) {
    result += `\\u${value.charCodeAt(i).toString(16).padStart(4, '0')}`
  }
  return result
}

function unicodeEscapesToText(value) {
  const inputText = value || ''
  if (!inputText.trim()) throw new Error('unicode')
  return inputText
    .replace(/\\u\{([0-9a-fA-F]{1,6})\}/g, (_, hex) => String.fromCodePoint(parseInt(hex, 16)))
    .replace(/U\+([0-9a-fA-F]{4,6})/g, (_, hex) => String.fromCodePoint(parseInt(hex, 16)))
    .replace(/\\u([0-9a-fA-F]{4})/g, (_, hex) => String.fromCharCode(parseInt(hex, 16)))
    .replace(/&#(x?[0-9a-fA-F]+);/g, (_, code) => {
      const cp = /^x/i.test(code) ? parseInt(code.slice(1), 16) : parseInt(code, 10)
      return String.fromCodePoint(cp)
    })
}

function decodeJwtSummary(token) {
  const parts = (token || '').trim().split('.')
  if (parts.length !== 3) throw new Error('jwt')

  const decodePart = (part) => {
    const normalized = part.replace(/-/g, '+').replace(/_/g, '/')
    const padding = '='.repeat((4 - (normalized.length % 4)) % 4)
    const raw = atob(normalized + padding)
    const bytes = Uint8Array.from(raw, (char) => char.charCodeAt(0))
    return new TextDecoder().decode(bytes)
  }

  const header = JSON.stringify(JSON.parse(decodePart(parts[0])), null, 2)
  const payload = JSON.stringify(JSON.parse(decodePart(parts[1])), null, 2)
  return `Header:\n${header}\n\nPayload:\n${payload}`
}
