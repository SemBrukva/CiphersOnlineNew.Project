/**
 * Инициализирует клиентскую логику Base64-инструмента на странице шифра.
 */
export function initBase64ToolPage() {
  const root = document.querySelector('[data-page="cipher-tool"][data-cipher-tool="encoding/base64"]')
  if (!root) return

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

  const encodeUtf8ToBase64 = (value) => btoa(unescape(encodeURIComponent(value)))
  const decodeBase64ToUtf8 = (value) => decodeURIComponent(escape(atob(value)))

  const updateCounter = () => {
    const val = input.value || ''
    const chars = val.length
    const bytes = new TextEncoder().encode(val).length
    counter.textContent = `${chars} chars · ${bytes} bytes`
  }

  const setMode = (nextMode) => {
    mode = nextMode
    const isEncode = mode === 'encode'
    tabEncode.classList.toggle('ciphers-tab--active', isEncode)
    tabEncode.setAttribute('aria-selected', isEncode ? 'true' : 'false')
    tabDecode.classList.toggle('ciphers-tab--active', !isEncode)
    tabDecode.setAttribute('aria-selected', !isEncode ? 'true' : 'false')
    inputLabel.textContent = isEncode ? 'Text' : 'Base64'
    input.placeholder = isEncode ? 'Type text for Base64 encoding' : 'Paste Base64 string for decoding'
    process()
  }

  const setFeedback = (message, isError = false) => {
    if (!feedback) return
    feedback.textContent = message
    feedback.classList.toggle('error', isError)
  }

  const process = () => {
    const value = input.value || ''
    updateCounter()

    if (!value.trim()) {
      output.value = ''
      output.classList.remove('ciphers-output--flash')
      resultCard?.classList.remove('ciphers-result-card--live')
      setFeedback('')
      return
    }

    try {
      output.value = mode === 'encode'
        ? encodeUtf8ToBase64(value)
        : decodeBase64ToUtf8(value.replace(/\s+/g, ''))
      output.classList.remove('ciphers-output--flash')
      void output.offsetWidth
      output.classList.add('ciphers-output--flash')
      resultCard?.classList.add('ciphers-result-card--live')
      setFeedback('')
    } catch (e) {
      output.value = ''
      output.classList.remove('ciphers-output--flash')
      resultCard?.classList.remove('ciphers-result-card--live')
      setFeedback('Invalid Base64 input.', true)
    }
  }

  input.addEventListener('input', process)
  tabEncode.addEventListener('click', () => setMode('encode'))
  tabDecode.addEventListener('click', () => setMode('decode'))

  document.querySelectorAll('.ciphers-example-chip').forEach((chip) => {
    chip.addEventListener('click', () => {
      const text = chip.getAttribute('data-example') || ''
      input.value = text
      setMode('encode')
      input.focus()
    })
  })

  document.querySelectorAll('.ciphers-example-use').forEach((btn) => {
    btn.addEventListener('click', () => {
      const text = btn.getAttribute('data-example-text') || ''
      input.value = text
      setMode('encode')
      document.getElementById('ciphers-tool-shell')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
      input.focus()
    })
  })

  copyBtn?.addEventListener('click', async () => {
    if (!output.value) return
    try {
      await navigator.clipboard.writeText(output.value)
      setFeedback('Result copied.')
      window.setTimeout(() => setFeedback(''), 1200)
    } catch {
      setFeedback('Unable to copy result.', true)
    }
  })

  shareBtn?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(window.location.href)
      setFeedback('Page URL copied.')
      window.setTimeout(() => setFeedback(''), 1200)
    } catch {
      setFeedback('Unable to copy page URL.', true)
    }
  })

  setMode('encode')
}
