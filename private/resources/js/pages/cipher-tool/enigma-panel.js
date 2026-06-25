/**
 * Визуализация состояния симулятора Enigma: окошки роторов с начальными
 * и финальными позициями, рефлектор, список plugboard-пар и счётчик
 * обработанных букв. Дополнительно — кнопки «Сбросить» и «Случайные».
 */

const ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'

/**
 * Создаёт HTML-элемент с атрибутами и дочерними узлами.
 */
function hel(tag, attrs = {}, ...children) {
  const node = document.createElement(tag)
  for (const [k, v] of Object.entries(attrs)) {
    if (k === 'class') node.className = v
    else if (k === 'html') node.innerHTML = v
    else node.setAttribute(k, String(v))
  }
  for (const child of children) {
    if (child == null) continue
    node.appendChild(typeof child === 'string' ? document.createTextNode(child) : child)
  }
  return node
}

/**
 * Создаёт окошко (rotor window) — внешняя коробочка с подписью «L / M / R»,
 * номером ротора и текущей буквой.
 */
function makeWindow(label) {
  const numEl = hel('div', { class: 'enigma-window__rotor' }, '—')
  const letterEl = hel('div', { class: 'enigma-window__letter' }, 'A')
  const finalEl = hel('div', { class: 'enigma-window__final' }, '')
  const ringEl = hel('div', { class: 'enigma-window__ring' }, 'Ring A')
  const wrap = hel('div', { class: 'enigma-window' },
    hel('div', { class: 'enigma-window__label' }, label),
    numEl,
    letterEl,
    finalEl,
    ringEl,
  )
  return { wrap, numEl, letterEl, finalEl, ringEl }
}

/**
 * Инициализирует панель Enigma.
 *
 * @param {{
 *   container: Element,
 *   selects: {
 *     reflector: HTMLSelectElement|null,
 *     rotorL: HTMLSelectElement|null, rotorM: HTMLSelectElement|null, rotorR: HTMLSelectElement|null,
 *     ringL: HTMLSelectElement|null,  ringM: HTMLSelectElement|null,  ringR: HTMLSelectElement|null,
 *     posL: HTMLSelectElement|null,   posM: HTMLSelectElement|null,   posR: HTMLSelectElement|null,
 *   },
 *   plugboardInput: HTMLTextAreaElement|null,
 *   labels: {
 *     title: string, rotors: string, reflector: string, plugboard: string,
 *     start: string, final: string, letters: string, empty: string, reset: string, random: string,
 *   },
 *   onChange: () => void,
 * }} opts
 */
export function initEnigmaPanel(opts) {
  const { container, selects, plugboardInput, labels, onChange } = opts

  // ─── Окошки роторов ─────────────────────────────────────────────
  const winL = makeWindow('L')
  const winM = makeWindow('M')
  const winR = makeWindow('R')
  const windowsRow = hel('div', { class: 'enigma-windows' }, winL.wrap, winM.wrap, winR.wrap)

  // ─── Метаблоки: рефлектор, plugboard, счётчик ───────────────────
  const reflectorBadge = hel('span', { class: 'enigma-badge enigma-badge--reflector' }, 'UKW-B')
  const plugboardList  = hel('div', { class: 'enigma-plugboard-list' })
  const lettersCount   = hel('span', { class: 'enigma-badge enigma-badge--counter' }, '0')

  const reflectorRow = hel('div', { class: 'enigma-meta-item' },
    hel('span', { class: 'enigma-meta-label' }, labels.reflector),
    reflectorBadge,
  )
  const plugboardRow = hel('div', { class: 'enigma-meta-item' },
    hel('span', { class: 'enigma-meta-label' }, labels.plugboard),
    plugboardList,
  )
  const countRow = hel('div', { class: 'enigma-meta-item' },
    hel('span', { class: 'enigma-meta-label' }, labels.letters),
    lettersCount,
  )

  const metaSection = hel('div', { class: 'enigma-meta' }, reflectorRow, plugboardRow, countRow)

  // ─── Кнопки управления ──────────────────────────────────────────
  const btnReset = hel('button', { class: 'enigma-btn', type: 'button' }, labels.reset)
  const btnRandom = hel('button', { class: 'enigma-btn enigma-btn--accent', type: 'button' }, labels.random)
  const buttonsRow = hel('div', { class: 'enigma-actions' }, btnReset, btnRandom)

  // ─── Заголовок и сборка ─────────────────────────────────────────
  const header = hel('div', { class: 'enigma-header' })
  header.innerHTML = `<i class="bi bi-hdd-stack-fill"></i> ${labels.title}`

  const panel = hel('div', { class: 'enigma-panel' },
    header,
    hel('div', { class: 'enigma-panel__body' },
      hel('div', { class: 'enigma-panel__top' }, windowsRow, metaSection, buttonsRow),
    ),
  )

  container.innerHTML = ''
  container.appendChild(panel)

  /* ── Логика рендера ────────────────────────────────────────────── */

  const render = () => {
    winL.numEl.textContent = selects.rotorL?.value ?? '—'
    winM.numEl.textContent = selects.rotorM?.value ?? '—'
    winR.numEl.textContent = selects.rotorR?.value ?? '—'
    winL.letterEl.textContent = (selects.posL?.value ?? 'A').toUpperCase().slice(0, 1)
    winM.letterEl.textContent = (selects.posM?.value ?? 'A').toUpperCase().slice(0, 1)
    winR.letterEl.textContent = (selects.posR?.value ?? 'A').toUpperCase().slice(0, 1)
    winL.ringEl.textContent = `Ring ${selects.ringL?.value ?? 'A'}`
    winM.ringEl.textContent = `Ring ${selects.ringM?.value ?? 'A'}`
    winR.ringEl.textContent = `Ring ${selects.ringR?.value ?? 'A'}`

    reflectorBadge.textContent = `UKW-${selects.reflector?.value ?? 'B'}`

    renderPlugboard(plugboardInput?.value || '')
  }

  const renderPlugboard = (raw) => {
    const clean = String(raw).toUpperCase().replace(/[^A-Z]/g, '')
    plugboardList.innerHTML = ''
    if (clean.length < 2) {
      plugboardList.appendChild(hel('span', { class: 'enigma-plugboard-empty' }, labels.empty))
      return
    }
    const pairs = []
    for (let i = 0; i + 1 < clean.length; i += 2) {
      pairs.push(clean.slice(i, i + 2))
    }
    pairs.forEach((p) => {
      plugboardList.appendChild(hel('span', { class: 'enigma-plug-pair' }, p))
    })
  }

  /* ── Подписка на изменения ─────────────────────────────────────── */
  Object.values(selects).forEach((sel) => {
    sel?.addEventListener('change', render)
  })
  plugboardInput?.addEventListener('input', render)

  /* ── Кнопки управления ─────────────────────────────────────────── */
  const setSelectValue = (sel, value) => {
    if (!sel) return
    const has = Array.from(sel.options).some((o) => o.value === value)
    if (has && sel.value !== value) {
      sel.value = value
      sel.dispatchEvent(new Event('change', { bubbles: true }))
    }
  }

  btnReset.addEventListener('click', () => {
    setSelectValue(selects.posL, 'A')
    setSelectValue(selects.posM, 'A')
    setSelectValue(selects.posR, 'A')
    setSelectValue(selects.ringL, 'A')
    setSelectValue(selects.ringM, 'A')
    setSelectValue(selects.ringR, 'A')
    if (plugboardInput) {
      plugboardInput.value = ''
      plugboardInput.dispatchEvent(new Event('input', { bubbles: true }))
    }
    onChange?.()
  })

  btnRandom.addEventListener('click', () => {
    // Случайные позиции букв и пар plugboard. Роторы не трогаем, чтобы
    // не оставить совпадающие в трёх слотах (валидация запретит).
    const randLetter = () => ALPHA[Math.floor(Math.random() * 26)]
    setSelectValue(selects.posL, randLetter())
    setSelectValue(selects.posM, randLetter())
    setSelectValue(selects.posR, randLetter())
    setSelectValue(selects.ringL, randLetter())
    setSelectValue(selects.ringM, randLetter())
    setSelectValue(selects.ringR, randLetter())

    if (plugboardInput) {
      const pool = ALPHA.split('')
      for (let i = pool.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [pool[i], pool[j]] = [pool[j], pool[i]]
      }
      const pairsCount = 10
      const out = []
      for (let i = 0; i < pairsCount; i++) {
        out.push(pool[2 * i] + pool[2 * i + 1])
      }
      plugboardInput.value = out.join(' ')
      plugboardInput.dispatchEvent(new Event('input', { bubbles: true }))
    }
    onChange?.()
  })

  render()

  return {
    /**
     * Перерисовывает панель и отображает финальные позиции / число обработанных букв.
     */
    showResult(response) {
      render()
      const finalPos = response?.final_positions || {}
      winL.finalEl.textContent = (finalPos.left   ?? '').toString().toUpperCase().slice(0, 1) || ''
      winM.finalEl.textContent = (finalPos.middle ?? '').toString().toUpperCase().slice(0, 1) || ''
      winR.finalEl.textContent = (finalPos.right  ?? '').toString().toUpperCase().slice(0, 1) || ''
      const letters = Number(response?.letters_processed ?? 0)
      lettersCount.textContent = String(letters)
    },
    /**
     * Сбрасывает «финальные» индикаторы и счётчик.
     */
    clearResult() {
      winL.finalEl.textContent = ''
      winM.finalEl.textContent = ''
      winR.finalEl.textContent = ''
      lettersCount.textContent = '0'
    },
  }
}
