/**
 * Частотный анализ: буквы, символы, слова, биграммы, триграммы; IC; сравнение с эталонными частотами.
 */

const LANG_FREQ = {
  en: { A:8.17,B:1.49,C:2.78,D:4.25,E:12.70,F:2.23,G:2.02,H:6.09,I:6.97,J:0.15,K:0.77,L:4.03,M:2.41,N:6.75,O:7.51,P:1.93,Q:0.10,R:5.99,S:6.33,T:9.06,U:2.76,V:0.98,W:2.36,X:0.15,Y:1.97,Z:0.07 },
  ru: { А:8.01,Б:1.59,В:4.54,Г:1.70,Д:2.98,Е:8.45,Ё:0.20,Ж:0.94,З:1.65,И:7.35,Й:1.21,К:3.49,Л:4.40,М:3.21,Н:6.70,О:10.97,П:2.81,Р:4.73,С:5.47,Т:6.26,У:2.62,Ф:0.26,Х:0.97,Ц:0.39,Ч:1.44,Ш:0.73,Щ:0.36,Ъ:0.04,Ы:1.90,Ь:1.74,Э:0.32,Ю:0.64,Я:2.01 },
  de: { A:6.51,B:1.89,C:2.73,D:5.08,E:16.93,F:1.66,G:3.01,H:4.76,I:7.55,J:0.24,K:1.22,L:3.44,M:2.53,N:9.78,O:2.51,P:0.67,Q:0.02,R:7.00,S:6.42,T:6.15,U:4.35,V:0.84,W:1.89,X:0.03,Y:0.04,Z:1.13 },
  es: { A:12.53,B:1.42,C:4.68,D:4.68,E:13.68,F:0.69,G:1.01,H:1.18,I:6.25,J:0.44,K:0.01,L:4.97,M:3.15,N:7.01,O:8.68,P:2.51,Q:0.88,R:6.87,S:7.98,T:4.63,U:3.93,V:1.14,W:0.01,X:0.22,Y:1.54,Z:0.52 },
  fr: { A:7.64,B:0.90,C:3.26,D:3.67,E:14.71,F:1.07,G:0.87,H:0.74,I:7.53,J:0.61,K:0.05,L:5.46,M:2.97,N:7.10,O:5.80,P:2.52,Q:1.36,R:6.69,S:7.95,T:7.24,U:6.31,V:1.83,W:0.04,X:0.43,Y:0.30,Z:0.15 },
  it: { A:11.74,B:0.92,C:4.50,D:3.73,E:11.79,F:0.95,G:1.64,H:0.85,I:11.28,L:6.51,M:2.51,N:6.88,O:9.83,P:3.05,Q:0.51,R:6.37,S:4.98,T:5.62,U:3.01,V:2.10,Z:0.49 },
  pt: { A:14.63,B:1.04,C:3.88,D:4.99,E:12.57,F:1.02,G:1.30,H:1.28,I:6.18,J:0.40,K:0.02,L:2.78,M:4.74,N:5.05,O:10.73,P:2.52,Q:1.20,R:6.53,S:7.81,T:4.34,U:4.63,V:1.67,W:0.01,X:0.21,Y:0.01,Z:0.47 },
  tr: { A:11.92,B:2.84,C:1.46,D:4.70,E:9.10,F:0.46,G:1.25,H:1.31,I:8.60,J:0.03,K:5.68,L:5.92,M:3.75,N:7.99,O:2.52,P:0.89,R:7.34,S:3.01,T:3.11,U:3.40,V:0.96,Y:3.34,Z:1.50 },
}

const LANG_NAMES = {
  en: 'English', ru: 'Russian', de: 'German', es: 'Spanish',
  fr: 'French', it: 'Italian', pt: 'Portuguese', tr: 'Turkish',
}

function computeIC(letterCounts) {
  const counts = [...letterCounts.values()]
  const N = counts.reduce((a, b) => a + b, 0)
  if (N < 2) return 0
  const numerator = counts.reduce((sum, n) => sum + n * (n - 1), 0)
  return numerator / (N * (N - 1))
}

function computeStats(text) {
  const letters = new Map()
  let letterCount = 0
  for (const char of text) {
    if (/\p{L}/u.test(char)) {
      const key = char.toUpperCase()
      letters.set(key, (letters.get(key) || 0) + 1)
      letterCount++
    }
  }
  const wordCount = (text.match(/\S+/gu) ?? []).length
  const allChars = new Map()
  for (const char of text) {
    allChars.set(char, (allChars.get(char) || 0) + 1)
  }
  const ic = computeIC(letters)
  let icInterpretation
  if (letterCount < 50) {
    icInterpretation = 'short'
  } else if (ic >= 0.060) {
    icInterpretation = 'natural'
  } else if (ic >= 0.040) {
    icInterpretation = 'polyalpha'
  } else {
    icInterpretation = 'random'
  }
  return {
    totalChars: text.length,
    letters: letterCount,
    words: wordCount,
    uniqueChars: allChars.size,
    uniqueLetters: letters.size,
    ic: Math.round(ic * 1e6) / 1e6,
    icInterpretation,
  }
}

/**
 * Определяет символы текста, отсутствующие в языковом профиле, и предлагает альтернативный язык.
 */
function detectMismatch(text, lang) {
  const ref = LANG_FREQ[lang] || LANG_FREQ.en
  const refChars = new Set(Object.keys(ref))

  const outsideCount = new Map()
  let totalLetters = 0
  for (const char of text) {
    if (!/\p{L}/u.test(char)) continue
    const key = char.toUpperCase()
    totalLetters++
    if (!refChars.has(key)) {
      outsideCount.set(key, (outsideCount.get(key) || 0) + 1)
    }
  }

  if (outsideCount.size === 0 || totalLetters === 0) return null
  const outsidePct = ([...outsideCount.values()].reduce((a, b) => a + b, 0) / totalLetters) * 100
  if (outsidePct < 2) return null

  const suggestions = []
  for (const [langCode, freq] of Object.entries(LANG_FREQ)) {
    if (langCode === lang) continue
    const langChars = new Set(Object.keys(freq))
    const covered = [...outsideCount.keys()].filter((c) => langChars.has(c)).length
    if (covered > 0) {
      suggestions.push({ lang: langCode, name: LANG_NAMES[langCode] || langCode, covered })
    }
  }
  suggestions.sort((a, b) => b.covered - a.covered)

  return {
    outsideLetters: [...outsideCount.keys()].slice(0, 6),
    suggestions: suggestions.slice(0, 2),
  }
}

/**
 * Вычисляет степень соответствия текста каждому из 8 языковых профилей (0–100 %).
 */
function computeLangMatch(text) {
  const counts = new Map()
  let total = 0
  for (const char of text) {
    if (!/\p{L}/u.test(char)) continue
    const key = char.toUpperCase()
    counts.set(key, (counts.get(key) || 0) + 1)
    total++
  }
  if (total < 20) return null

  const actual = new Map([...counts.entries()].map(([c, n]) => [c, (n / total) * 100]))

  const results = []
  for (const [langCode, ref] of Object.entries(LANG_FREQ)) {
    const allLetters = new Set([...actual.keys(), ...Object.keys(ref)])
    let diffSum = 0
    for (const letter of allLetters) {
      diffSum += Math.abs((actual.get(letter) ?? 0) - (ref[letter] ?? 0))
    }
    results.push({ lang: langCode, name: LANG_NAMES[langCode] || langCode, score: Math.max(0, Math.round((1 - diffSum / 200) * 100)) })
  }
  results.sort((a, b) => b.score - a.score)
  return results
}

function sortEntries(entries, sort) {
  if (sort === 'alpha') {
    return [...entries].sort((a, b) => a.char.localeCompare(b.char))
  }
  return [...entries].sort((a, b) => b.count - a.count || a.char.localeCompare(b.char))
}

function getLetterFrequencies(text, lang, sort) {
  const ref = LANG_FREQ[lang] || LANG_FREQ.en
  const counts = new Map()
  for (const char of text) {
    if (!/\p{L}/u.test(char)) continue
    const key = char.toUpperCase()
    counts.set(key, (counts.get(key) || 0) + 1)
  }
  const total = [...counts.values()].reduce((a, b) => a + b, 0)
  const entries = [...counts.entries()].map(([char, count]) => {
    const pct = total > 0 ? (count / total) * 100 : 0
    const expected = ref[char] ?? 0
    return { char, count, pct, expected, diff: pct - expected }
  })
  return { items: sortEntries(entries, sort), total }
}

function getCharFrequencies(text, sort) {
  const counts = new Map()
  for (const char of text) {
    const key = char === '\n' ? '↵' : char === '\t' ? '⇥' : char === ' ' ? '·' : char.toUpperCase()
    counts.set(key, (counts.get(key) || 0) + 1)
  }
  const total = [...counts.values()].reduce((a, b) => a + b, 0)
  const entries = [...counts.entries()].map(([char, count]) => ({
    char, count, pct: total > 0 ? (count / total) * 100 : 0,
  }))
  return { items: sortEntries(entries, sort), total }
}

function getWordFrequencies(text, sort) {
  const words = text.match(/\S+/gu) ?? []
  const counts = new Map()
  for (const w of words) {
    const key = w.toLowerCase()
    counts.set(key, (counts.get(key) || 0) + 1)
  }
  const total = words.length
  const entries = [...counts.entries()].map(([char, count]) => ({
    char, count, pct: total > 0 ? (count / total) * 100 : 0,
  }))
  return { items: sortEntries(entries, sort).slice(0, 50), total }
}

function getBigramFrequencies(text, sort) {
  const counts = new Map()
  let prev = null
  for (const char of text) {
    if (!/\p{L}/u.test(char)) { prev = null; continue }
    const key = char.toUpperCase()
    if (prev !== null) {
      const bigram = prev + key
      counts.set(bigram, (counts.get(bigram) || 0) + 1)
    }
    prev = key
  }
  const total = [...counts.values()].reduce((a, b) => a + b, 0)
  const entries = [...counts.entries()].map(([char, count]) => ({
    char, count, pct: total > 0 ? (count / total) * 100 : 0,
  }))
  return { items: sortEntries(entries, sort).slice(0, 10), total }
}

function getTrigramFrequencies(text, sort) {
  const counts = new Map()
  let prev1 = null
  let prev2 = null
  for (const char of text) {
    if (!/\p{L}/u.test(char)) { prev1 = null; prev2 = null; continue }
    const key = char.toUpperCase()
    if (prev1 !== null && prev2 !== null) {
      const trigram = prev2 + prev1 + key
      counts.set(trigram, (counts.get(trigram) || 0) + 1)
    }
    prev2 = prev1
    prev1 = key
  }
  const total = [...counts.values()].reduce((a, b) => a + b, 0)
  const entries = [...counts.entries()].map(([char, count]) => ({
    char, count, pct: total > 0 ? (count / total) * 100 : 0,
  }))
  return { items: sortEntries(entries, sort).slice(0, 10), total }
}

/**
 * Выполняет частотный анализ текста.
 *
 * @param {string} value  Входной текст
 * @param {string} _mode  Не используется
 * @param {object} opts   scope, sort, lang
 * @returns {string}      JSON с результатами
 */
export function transformFrequency(value, _mode, opts) {
  const scope = opts?.scope || 'letters'
  const sort = opts?.sort || 'frequency'
  const lang = opts?.lang || 'en'
  const text = value || ''

  const stats = computeStats(text)
  const mismatch = scope === 'letters' ? detectMismatch(text, lang) : null
  const langMatch = computeLangMatch(text)

  let result
  if (scope === 'letters') {
    result = getLetterFrequencies(text, lang, sort)
  } else if (scope === 'all') {
    result = getCharFrequencies(text, sort)
  } else if (scope === 'words') {
    result = getWordFrequencies(text, sort)
  } else if (scope === 'bigrams') {
    result = getBigramFrequencies(text, sort)
  } else if (scope === 'trigrams') {
    result = getTrigramFrequencies(text, sort)
  } else {
    result = getLetterFrequencies(text, lang, sort)
  }

  return JSON.stringify({ scope, lang, stats, items: result.items, total: result.total, mismatch, langMatch })
}

/**
 * Частотный анализ применим к любому непустому тексту.
 */
export function looksLikeText(value) {
  return Boolean(value && value.trim())
}
