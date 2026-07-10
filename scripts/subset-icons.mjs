// Сабсеттинг шрифта Bootstrap Icons под реально используемые иконки.
//
// Полный шрифт содержит ~2000 глифов (134 КБ woff2) и ~100 КБ CSS-определений,
// тогда как сайт использует меньше сотни иконок. Скрипт сканирует шаблоны и JS,
// оставляет только нужные глифы и генерирует компактный woff2 + CSS.
//
// Запуск: node scripts/subset-icons.mjs  (или npm run icons)
// Результат (коммитится в репозиторий, при сборке ничего доустанавливать не нужно):
//   private/resources/fonts/co-icons.woff2
//   private/resources/css/icons.css

import { readFileSync, writeFileSync, readdirSync, mkdirSync, statSync } from 'node:fs'
import { resolve, join, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'
import subsetFont from 'subset-font'

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..')

const SCAN_DIRS = [
    join(root, 'private/resources/views'),
    join(root, 'private/resources/js'),
]
const CODEPOINTS = join(root, 'node_modules/bootstrap-icons/font/bootstrap-icons.json')
const SRC_FONT   = join(root, 'node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff2')
const OUT_FONT   = join(root, 'private/resources/fonts/co-icons.woff2')
const OUT_CSS    = join(root, 'private/resources/css/icons.css')
const FONT_URL   = '../fonts/co-icons.woff2'   // относительно private/resources/css/

/** Рекурсивно собирает пути ко всем файлам в каталоге. */
function walk(dir) {
    const out = []
    for (const entry of readdirSync(dir)) {
        const full = join(dir, entry)
        if (statSync(full).isDirectory()) out.push(...walk(full))
        else out.push(full)
    }
    return out
}

// 1. Находим все использованные классы bi-*.
const used = new Set()
for (const dir of SCAN_DIRS) {
    for (const file of walk(dir)) {
        const text = readFileSync(file, 'utf8')
        for (const m of text.matchAll(/bi-([a-z0-9]+(?:-[a-z0-9]+)*)/g)) used.add(m[1])
    }
}

// 2. Сопоставляем имена с кодпоинтами.
const map = JSON.parse(readFileSync(CODEPOINTS, 'utf8'))
const icons = []
const unknown = []
for (const name of [...used].sort()) {
    if (Object.prototype.hasOwnProperty.call(map, name)) icons.push({ name, cp: map[name] })
    else unknown.push(name)
}
if (unknown.length) console.warn(`⚠ Пропущены (нет в bootstrap-icons.json): ${unknown.join(', ')}`)
if (!icons.length) { console.error('Не найдено ни одной иконки — прерываю.'); process.exit(1) }

// 3. Сабсеттим шрифт по символам используемых кодпоинтов.
const keepText = icons.map(i => String.fromCodePoint(i.cp)).join('')
const subset = await subsetFont(readFileSync(SRC_FONT), keepText, { targetFormat: 'woff2' })
mkdirSync(dirname(OUT_FONT), { recursive: true })
writeFileSync(OUT_FONT, subset)

// 4. Генерируем компактный CSS (только @font-face + используемые иконки).
const rules = icons
    .map(i => `.bi-${i.name}::before{content:"\\${i.cp.toString(16)}"}`)
    .join('\n')

const css = `/* АВТОГЕНЕРАЦИЯ: scripts/subset-icons.mjs. Не редактировать вручную.
   Сабсет Bootstrap Icons — только используемые ${icons.length} иконок. */
@font-face {
  font-family: "bootstrap-icons";
  font-display: swap;
  src: url("${FONT_URL}") format("woff2");
}
.bi::before,
[class^="bi-"]::before,
[class*=" bi-"]::before {
  display: inline-block;
  font-family: "bootstrap-icons" !important;
  font-style: normal;
  font-weight: normal !important;
  font-variant: normal;
  text-transform: none;
  line-height: 1;
  vertical-align: -0.125em;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
${rules}
`
writeFileSync(OUT_CSS, css)

const srcKb = (statSync(SRC_FONT).size / 1024).toFixed(1)
const outKb = (subset.length / 1024).toFixed(1)
console.log(`✓ Иконок: ${icons.length}`)
console.log(`✓ Шрифт:  ${srcKb} КБ → ${outKb} КБ  (${OUT_FONT.replace(root + '/', '')})`)
console.log(`✓ CSS:    ${(css.length / 1024).toFixed(1)} КБ            (${OUT_CSS.replace(root + '/', '')})`)
