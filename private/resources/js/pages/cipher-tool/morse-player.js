import { playMorse, stopMorse, isMorsePlaying, downloadMorseWav } from './morse-audio.js'

/**
 * Инициализирует аудиоплеер азбуки Морзе и внедряет его в DOM после блока результата.
 *
 * @param {HTMLTextAreaElement} outputEl
 * @param {HTMLTextAreaElement} inputEl
 * @param {Record<string, string>} ui
 * @param {() => string} getMode
 */
export function initMorsePlayer(outputEl, inputEl, ui, getMode) {
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
