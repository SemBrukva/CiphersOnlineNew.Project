/**
 * Частота букв: тепловая карта всего алфавита с эталонными частотами для 8 языков.
 */

const LANG_FREQ = {
  en: { A:8.17,B:1.49,C:2.78,D:4.25,E:12.70,F:2.23,G:2.02,H:6.09,I:6.97,J:0.15,K:0.77,L:4.03,M:2.41,N:6.75,O:7.51,P:1.93,Q:0.10,R:5.99,S:6.33,T:9.06,U:2.76,V:0.98,W:2.36,X:0.15,Y:1.97,Z:0.07 },
  ru: { А:8.01,Б:1.59,В:4.54,Г:1.70,Д:2.98,Е:8.45,Ё:0.20,Ж:0.94,З:1.65,И:7.35,Й:1.21,К:3.49,Л:4.40,М:3.21,Н:6.70,О:10.97,П:2.81,Р:4.73,С:5.47,Т:6.26,У:2.62,Ф:0.26,Х:0.97,Ц:0.39,Ч:1.44,Ш:0.73,Щ:0.36,Ъ:0.04,Ы:1.90,Ь:1.74,Э:0.32,Ю:0.64,Я:2.01 },
  de: { A:6.51,Ä:0.54,B:1.89,C:2.73,D:5.08,E:16.93,F:1.66,G:3.01,H:4.76,I:7.55,J:0.24,K:1.22,L:3.44,M:2.53,N:9.78,O:2.51,Ö:0.30,P:0.67,Q:0.02,R:7.00,S:6.42,T:6.15,U:4.35,Ü:0.65,V:0.84,W:1.89,X:0.03,Y:0.04,Z:1.13 },
  es: { A:12.53,B:1.42,C:4.68,D:4.68,E:13.68,F:0.69,G:1.01,H:1.18,I:6.25,J:0.44,K:0.01,L:4.97,M:3.15,N:7.01,Ñ:0.31,O:8.68,P:2.51,Q:0.88,R:6.87,S:7.98,T:4.63,U:3.93,V:1.14,X:0.22,Y:1.54,Z:0.52 },
  fr: { A:7.64,B:0.90,C:3.26,D:3.67,E:14.71,F:1.07,G:0.87,H:0.74,I:7.53,J:0.61,K:0.05,L:5.46,M:2.97,N:7.10,O:5.80,P:2.52,Q:1.36,R:6.69,S:7.95,T:7.24,U:6.31,V:1.83,W:0.04,X:0.43,Y:0.30,Z:0.15 },
  it: { A:11.74,B:0.92,C:4.50,D:3.73,E:11.79,F:0.95,G:1.64,H:0.85,I:11.28,L:6.51,M:2.51,N:6.88,O:9.83,P:3.05,Q:0.51,R:6.37,S:4.98,T:5.62,U:3.01,V:2.10,Z:0.49 },
  pt: { A:14.63,Ã:1.78,B:1.04,C:3.88,Ç:0.53,D:4.99,E:12.57,F:1.02,G:1.30,H:1.28,I:6.18,J:0.40,L:2.78,M:4.74,N:5.05,O:10.73,Õ:0.63,P:2.52,Q:1.20,R:6.53,S:7.81,T:4.34,U:4.63,V:1.67,X:0.21,Z:0.47 },
  tr: { A:11.92,B:2.84,C:1.46,Ç:1.57,D:4.70,E:9.10,F:0.46,G:1.25,Ğ:1.13,H:1.31,I:13.91,J:0.03,K:5.68,L:5.92,M:3.75,N:7.99,O:2.52,Ö:0.77,P:0.89,R:7.34,S:3.01,Ş:1.78,T:3.11,U:3.40,Ü:1.85,V:0.96,Y:3.34,Z:1.50 },
}

const LANG_NAMES = {
  en: 'English', ru: 'Russian', de: 'German', es: 'Spanish',
  fr: 'French', it: 'Italian', pt: 'Portuguese', tr: 'Turkish',
}

/**
 * Определяет язык текста по наибольшему покрытию букв языковым алфавитом.
 */
function detectLanguage(letterCounts, totalLetters) {
  if (totalLetters < 5) return 'en'
  let bestLang = 'en'
  let bestScore = -1
  for (const [langCode, ref] of Object.entries(LANG_FREQ)) {
    const refChars = new Set(Object.keys(ref))
    let covered = 0
    for (const [char, count] of letterCounts.entries()) {
      if (refChars.has(char)) covered += count
    }
    const score = covered / totalLetters
    if (score > bestScore) {
      bestScore = score
      bestLang = langCode
    }
  }
  return bestLang
}

/**
 * Проверяет, соответствует ли выбранный язык тексту. Возвращает null, если всё хорошо.
 */
function checkMismatch(letterCounts, totalLetters, selectedLang) {
  if (totalLetters < 30) return null
  const ref = LANG_FREQ[selectedLang]
  if (!ref) return null
  const refChars = new Set(Object.keys(ref))
  let covered = 0
  for (const [char, count] of letterCounts.entries()) {
    if (refChars.has(char)) covered += count
  }
  if (covered / totalLetters >= 0.85) return null
  const detected = detectLanguage(letterCounts, totalLetters)
  if (detected === selectedLang) return null
  return { detectedLang: detected, detectedName: LANG_NAMES[detected] || detected }
}

/**
 * Выполняет подсчёт частот букв и строит данные для тепловой карты.
 *
 * @param {string} value  Входной текст
 * @param {string} _mode  Не используется
 * @param {object} opts   lang ('auto' | langCode), sort
 * @returns {string}      JSON с результатами
 */
export function transformLetterFrequency(value, _mode, opts) {
  const requestedLang = opts?.lang || 'auto'
  const sort = opts?.sort || 'alpha'
  const text = value || ''

  const counts = new Map()
  let totalLetters = 0
  for (const char of text) {
    if (/\p{L}/u.test(char)) {
      const key = char.toUpperCase()
      counts.set(key, (counts.get(key) || 0) + 1)
      totalLetters++
    }
  }

  const detectedLang = detectLanguage(counts, totalLetters)
  const mismatch = requestedLang !== 'auto'
    ? checkMismatch(counts, totalLetters, requestedLang)
    : null
  const lang = (requestedLang === 'auto' || mismatch) ? detectedLang : requestedLang

  const ref = LANG_FREQ[lang] || LANG_FREQ.en
  const alphabetLetters = Object.keys(ref)

  const heatmapItems = alphabetLetters.map((char) => {
    const count = counts.get(char) || 0
    const pct = totalLetters > 0 ? (count / totalLetters) * 100 : 0
    return { char, count, pct, expected: ref[char] || 0 }
  })

  const tableItems = [...heatmapItems]
  for (const [char, count] of counts.entries()) {
    if (!ref[char]) {
      const pct = totalLetters > 0 ? (count / totalLetters) * 100 : 0
      tableItems.push({ char, count, pct, expected: 0 })
    }
  }
  if (sort === 'frequency') {
    tableItems.sort((a, b) => b.count - a.count || a.char.localeCompare(b.char))
  }

  const missingLetters = heatmapItems.filter((it) => it.count === 0).map((it) => it.char)

  const stats = {
    totalChars: text.length,
    letters: totalLetters,
    unique: counts.size,
  }

  return JSON.stringify({
    lang, sort, stats, heatmapItems, tableItems, missingLetters, total: totalLetters,
    requestedLang, detectedLang,
    detectedLangName: LANG_NAMES[detectedLang] || detectedLang,
    mismatch,
  })
}

/**
 * Инструмент применим к любому непустому тексту.
 */
export function looksLikeLetterFreqText(value) {
  return Boolean(value && value.trim())
}
