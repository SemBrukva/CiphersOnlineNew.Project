/**
 * Модуль озвучивания фонетического алфавита через Web Speech API (SpeechSynthesis).
 * Проговаривает кодовые слова (Alfa, Bravo, …) из результата кодирования.
 */

/**
 * Возвращает список кодовых слов для озвучивания из текста результата.
 * Отбрасывает разделители, знаки «=» и одиночные буквы-подписи режима «пары».
 *
 * @param {string} text
 * @returns {string[]}
 */
function wordsToSpeak(text) {
  return text
    .replace(/[A-Za-zÄÖÜß0-9]\s*=\s*/g, '')
    .split(/\s*\/\s*|\n+|\s*,\s*|\s+|\s*-\s+/)
    .map((t) => t.trim())
    .filter((t) => t && t !== '/')
}

/**
 * Инициализирует кнопку озвучивания и внедряет её после блока результата.
 *
 * @param {HTMLTextAreaElement} outputEl Поле результата (кодовые слова).
 * @param {Record<string, string>} ui    Локализованные подписи.
 * @param {() => string} getMode         Текущий режим (encode/decode).
 */
export function initNatoSpeech(outputEl, ui, getMode) {
  const synth = typeof window !== 'undefined' ? window.speechSynthesis : null
  const resultCard = document.getElementById('ciphers-result-card')
  if (!synth || !resultCard) return

  const playLabel = ui.natoPlayLabel || 'Listen'
  const stopLabel = ui.natoStopLabel || 'Stop'
  const rateLabel = ui.natoRateLabel || 'Speed'

  const player = document.createElement('div')
  player.className = 'nato-speech'
  player.id = 'nato-speech'
  player.innerHTML = `
    <div class="nato-speech__controls">
      <button class="nato-speech__play-btn" id="nato-play" type="button">
        <i class="bi bi-volume-up"></i><span>${playLabel}</span>
      </button>
      <label class="nato-speech__label" for="nato-rate">${rateLabel}</label>
      <input id="nato-rate" class="nato-speech__rate" type="range" min="0.5" max="1.2" step="0.1" value="0.9">
    </div>
  `

  resultCard.after(player)

  const playBtn = document.getElementById('nato-play')
  const rateInput = document.getElementById('nato-rate')

  let speaking = false

  const setPlayState = (isOn) => {
    speaking = isOn
    if (!playBtn) return
    const icon = playBtn.querySelector('.bi')
    const span = playBtn.querySelector('span')
    if (isOn) {
      icon && (icon.className = 'bi bi-stop-fill')
      span && (span.textContent = stopLabel)
      playBtn.classList.add('nato-speech__play-btn--playing')
    } else {
      icon && (icon.className = 'bi bi-volume-up')
      span && (span.textContent = playLabel)
      playBtn.classList.remove('nato-speech__play-btn--playing')
    }
  }

  const stop = () => {
    synth.cancel()
    setPlayState(false)
  }

  const updateAvailability = () => {
    // Озвучивание осмысленно только в режиме кодирования (кодовые слова).
    const hasContent = getMode() === 'encode' && Boolean(outputEl?.value?.trim())
    if (playBtn) playBtn.disabled = !hasContent
  }

  playBtn?.addEventListener('click', () => {
    if (speaking) { stop(); return }

    const words = wordsToSpeak(outputEl?.value || '')
    if (words.length === 0) return

    const rate = parseFloat(rateInput?.value || '0.9') || 0.9
    synth.cancel()
    setPlayState(true)

    words.forEach((word, idx) => {
      const utter = new SpeechSynthesisUtterance(word)
      utter.rate = rate
      utter.lang = 'en-US'
      if (idx === words.length - 1) {
        utter.onend = () => setPlayState(false)
      }
      synth.speak(utter)
    })
  })

  outputEl?.addEventListener('input', () => { if (speaking) stop(); updateAvailability() })
  document.getElementById('tab-encode')?.addEventListener('click', () => { stop(); window.setTimeout(updateAvailability, 0) })
  document.getElementById('tab-decode')?.addEventListener('click', () => { stop(); window.setTimeout(updateAvailability, 0) })

  // Останавливаем синтез при уходе со страницы.
  window.addEventListener('beforeunload', () => synth.cancel())

  const observer = new MutationObserver(updateAvailability)
  if (outputEl) observer.observe(outputEl, { attributes: true, characterData: true, subtree: true })

  updateAvailability()
}
