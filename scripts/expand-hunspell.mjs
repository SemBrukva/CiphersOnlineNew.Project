#!/usr/bin/env node
// Раскрывает Hunspell-словарь (.aff + .dic) в плоский текстовый список слов.
//
// Использование:
//   node scripts/expand-hunspell.mjs <aff-path> <dic-path> <out-path>
//
// Использует пакет `hunspell-reader` (от cspell), который умеет читать
// Hunspell-словарь и применять все аффиксы из .aff, отдавая поток словоформ.

import { promises as fs } from 'node:fs'
import path from 'node:path'

let HunspellReader
try {
  ;({ HunspellReader } = await import('hunspell-reader'))
} catch (err) {
  console.error('hunspell-reader is not installed. Run: npm install --save-dev hunspell-reader')
  console.error(String(err))
  process.exit(2)
}

const [, , affPath, dicPath, outPath] = process.argv
if (!affPath || !dicPath || !outPath) {
  console.error('Usage: node scripts/expand-hunspell.mjs <aff-path> <dic-path> <out-path>')
  process.exit(1)
}

const { IterableHunspellReader } = await import('hunspell-reader')
const reader = await IterableHunspellReader.createFromFiles(path.resolve(affPath), path.resolve(dicPath))

const allWords = new Set()
let processed  = 0

for (const word of reader.iterateWords()) {
  const text = String(word ?? '').trim()
  if (!text) continue
  const normalized = text.toLowerCase()
  if (!/^[\p{L}\p{Mn}]{1,32}$/u.test(normalized)) continue
  allWords.add(normalized)
  processed++
  if (processed % 50000 === 0) {
    process.stderr.write(`  processed: ${processed} entries, unique: ${allWords.size}\n`)
  }
}

const filtered = Array.from(allWords).sort()
await fs.mkdir(path.dirname(path.resolve(outPath)), { recursive: true })
await fs.writeFile(path.resolve(outPath), filtered.join('\n') + '\n', 'utf8')

console.log(`Wrote ${filtered.length} unique words to ${outPath}`)
