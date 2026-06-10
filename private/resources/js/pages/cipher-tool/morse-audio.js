/**
 * Модуль аудио-плеера азбуки Морзе.
 * Использует Web Audio API для воспроизведения и генерации WAV.
 */

/** @type {AudioContext|null} */
let audioCtx = null

/** @type {OscillatorNode[]|null} */
let activeNodes = null

/** @type {number|null} */
let playbackEndTime = null

/** @type {ReturnType<typeof setTimeout>|null} */
let endTimer = null

/** @type {ReturnType<typeof setTimeout>[]} */
let beatTimers = []

const getAudioCtx = () => {
  if (!audioCtx || audioCtx.state === 'closed') {
    audioCtx = new AudioContext()
  }
  return audioCtx
}

/**
 * Разбирает строку азбуки Морзе на элементы для аудиорендеринга.
 *
 * @param {string} morseText
 * @returns {Array<{type:'dit'|'dah'|'letter-gap'|'word-gap'}>}
 */
function parseMorseElements(morseText) {
  const elements = []
  const words = morseText.trim().split(/\s*\/\s*/)

  words.forEach((word, wordIdx) => {
    const letters = word.trim().split(/\s+/).filter(Boolean)
    letters.forEach((letter, letterIdx) => {
      letter.split('').forEach((sym, symIdx) => {
        if (sym === '.') elements.push({ type: 'dit' })
        else if (sym === '-') elements.push({ type: 'dah' })
        if (symIdx < letter.length - 1) elements.push({ type: 'element-gap' })
      })
      if (letterIdx < letters.length - 1) elements.push({ type: 'letter-gap' })
    })
    if (wordIdx < words.length - 1) elements.push({ type: 'word-gap' })
  })

  return elements
}

/**
 * Вычисляет длительность одного дита в секундах по WPM.
 *
 * @param {number} wpm
 * @returns {number}
 */
const ditDuration = (wpm) => 1.2 / wpm

/**
 * Создаёт ноды для воспроизведения и возвращает время окончания.
 *
 * @param {BaseAudioContext} ctx
 * @param {Array<{type:string}>} elements
 * @param {number} wpm
 * @param {number} frequency
 * @param {number} startTime
 * @returns {number} время окончания воспроизведения
 */
function scheduleElements(ctx, elements, wpm, frequency, startTime) {
  const dit = ditDuration(wpm)
  const dah = dit * 3
  const elemGap = dit
  const letterGap = dit * 3
  const wordGap = dit * 7

  let t = startTime
  const gainNode = ctx.createGain()
  gainNode.gain.setValueAtTime(0.5, ctx.currentTime)
  gainNode.connect(ctx.destination)

  elements.forEach(({ type }) => {
    if (type === 'dit' || type === 'dah') {
      const duration = type === 'dit' ? dit : dah
      const osc = ctx.createOscillator()
      const gain = ctx.createGain()
      osc.type = 'sine'
      osc.frequency.setValueAtTime(frequency, ctx.currentTime)
      gain.gain.setValueAtTime(0, ctx.currentTime)
      gain.gain.linearRampToValueAtTime(0.5, t + 0.005)
      gain.gain.setValueAtTime(0.5, t + duration - 0.005)
      gain.gain.linearRampToValueAtTime(0, t + duration)
      osc.connect(gain)
      gain.connect(ctx.destination)
      osc.start(t)
      osc.stop(t + duration)
      t += duration + elemGap
    } else if (type === 'letter-gap') {
      t += letterGap - elemGap
    } else if (type === 'word-gap') {
      t += wordGap - elemGap
    }
  })

  return t
}

/**
 * Воспроизводит азбуку Морзе через Web Audio API.
 *
 * @param {string} morseText
 * @param {number} wpm
 * @param {number} frequency
 * @param {() => void} onEnd
 * @param {((isOn: boolean) => void)|null} [onBeat]
 */
export function playMorse(morseText, wpm, frequency, onEnd, onBeat = null) {
  stopMorse()

  if (!morseText.trim()) return

  const elements = parseMorseElements(morseText)
  if (!elements.length) return

  const ctx = getAudioCtx()
  if (ctx.state === 'suspended') {
    ctx.resume()
  }

  const startTime = ctx.currentTime + 0.05
  const endTime = scheduleElements(ctx, elements, wpm, frequency, startTime)
  playbackEndTime = endTime

  // Планируем визуальные вспышки индикатора
  if (onBeat) {
    const dit = ditDuration(wpm)
    const dah = dit * 3
    const elemGap = dit
    const letterGap = dit * 3
    const wordGap = dit * 7
    const now = ctx.currentTime
    let t = startTime

    elements.forEach(({ type }) => {
      if (type === 'dit' || type === 'dah') {
        const duration = type === 'dit' ? dit : dah
        const onDelay  = Math.max(0, (t - now) * 1000)
        const offDelay = Math.max(0, (t + duration - now) * 1000)
        beatTimers.push(window.setTimeout(() => onBeat(true),  onDelay))
        beatTimers.push(window.setTimeout(() => onBeat(false), offDelay))
        t += duration + elemGap
      } else if (type === 'letter-gap') {
        t += letterGap - elemGap
      } else if (type === 'word-gap') {
        t += wordGap - elemGap
      }
    })
  }

  const remaining = (endTime - ctx.currentTime) * 1000 + 100
  endTimer = window.setTimeout(() => {
    playbackEndTime = null
    endTimer = null
    onBeat?.(false)
    onEnd?.()
  }, remaining)
}

/**
 * Останавливает воспроизведение.
 */
export function stopMorse() {
  beatTimers.forEach((id) => clearTimeout(id))
  beatTimers = []

  if (endTimer !== null) {
    clearTimeout(endTimer)
    endTimer = null
  }
  playbackEndTime = null

  if (audioCtx && audioCtx.state !== 'closed') {
    audioCtx.close()
    audioCtx = null
  }
}

/**
 * Возвращает true, если воспроизведение активно.
 *
 * @returns {boolean}
 */
export function isMorsePlaying() {
  if (playbackEndTime === null) return false
  if (!audioCtx) return false
  return audioCtx.currentTime < playbackEndTime
}

/**
 * Конвертирует AudioBuffer в WAV-бинарные данные.
 *
 * @param {AudioBuffer} buffer
 * @returns {ArrayBuffer}
 */
function audioBufferToWav(buffer) {
  const numChannels = 1
  const sampleRate = buffer.sampleRate
  const format = 1 // PCM
  const bitDepth = 16
  const samples = buffer.getChannelData(0)
  const byteRate = (sampleRate * numChannels * bitDepth) / 8
  const blockAlign = (numChannels * bitDepth) / 8
  const dataSize = samples.length * blockAlign
  const headerSize = 44

  const arrayBuffer = new ArrayBuffer(headerSize + dataSize)
  const view = new DataView(arrayBuffer)

  const writeStr = (offset, str) => {
    for (let i = 0; i < str.length; i++) {
      view.setUint8(offset + i, str.charCodeAt(i))
    }
  }

  writeStr(0, 'RIFF')
  view.setUint32(4, 36 + dataSize, true)
  writeStr(8, 'WAVE')
  writeStr(12, 'fmt ')
  view.setUint32(16, 16, true)
  view.setUint16(20, format, true)
  view.setUint16(22, numChannels, true)
  view.setUint32(24, sampleRate, true)
  view.setUint32(28, byteRate, true)
  view.setUint16(32, blockAlign, true)
  view.setUint16(34, bitDepth, true)
  writeStr(36, 'data')
  view.setUint32(40, dataSize, true)

  let offset = 44
  for (let i = 0; i < samples.length; i++) {
    const s = Math.max(-1, Math.min(1, samples[i]))
    view.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7fff, true)
    offset += 2
  }

  return arrayBuffer
}

/**
 * Генерирует и скачивает WAV-файл с азбукой Морзе.
 *
 * @param {string} morseText
 * @param {number} wpm
 * @param {number} frequency
 * @param {string} filename
 */
export async function downloadMorseWav(morseText, wpm, frequency, filename = 'morse.wav') {
  const elements = parseMorseElements(morseText)
  if (!elements.length) return

  const dit = ditDuration(wpm)
  const dah = dit * 3
  const elemGap = dit
  const letterGap = dit * 3
  const wordGap = dit * 7

  let totalDuration = 0.1
  elements.forEach(({ type }) => {
    if (type === 'dit') totalDuration += dit + elemGap
    else if (type === 'dah') totalDuration += dah + elemGap
    else if (type === 'letter-gap') totalDuration += letterGap - elemGap
    else if (type === 'word-gap') totalDuration += wordGap - elemGap
  })
  totalDuration += 0.1

  const sampleRate = 44100
  const offlineCtx = new OfflineAudioContext(1, Math.ceil(totalDuration * sampleRate), sampleRate)

  scheduleElements(offlineCtx, elements, wpm, frequency, 0.05)

  const renderedBuffer = await offlineCtx.startRendering()
  const wavData = audioBufferToWav(renderedBuffer)
  const blob = new Blob([wavData], { type: 'audio/wav' })
  const url = URL.createObjectURL(blob)

  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.style.display = 'none'
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}
