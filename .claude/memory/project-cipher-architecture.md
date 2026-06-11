---
name: project-cipher-architecture
description: Как добавлять новые шифры (клиентские и серверные), паттерн миграция + ToolRegistry + декодер; как добавить инструмент типа "анализ" (без encode/decode)
metadata:
  type: project
---

# Паттерн добавления нового инструмента/шифра

## Стандартный encode/decode инструмент

1. **Миграция категории** (если новая): `private/database/migrations/YYYY_MM_DD_..._create_X_category.php` — паттерн см. `2026_06_10_000002_create_codes_and_alphabets_category.php`
2. **Миграция инструмента**: `2026_06_11_000002_seed_frequency_analysis_tool.php` — паттерн такой же как `2026_06_10_000001_seed_morse_cipher.php`. ВАЖНО: вспомогательные методы `upsertParent`, `upsertTranslation` и т.д. определяются внутри каждой миграции (не в базовом классе).
3. **PHP-сервис**: `private/app/Cipher/XxxService.php` — методы `getToolSettings()` и `getTrustItems(string $calculationMode)`. Автовайринг через reflection-контейнер, добавлять в services.php НЕ нужно.
4. **ToolRegistry**: `private/app/Cipher/ToolRegistry.php` — добавить в конструктор + в четыре match: `exampleChips()`, `apiAction()`, `settings()`, `trustItems()`.
5. **JS-декодер**: `private/resources/js/pages/cipher-tool/decoders/xxx.js` — экспортирует `transformXxx(value, mode, opts)` и `looksLikeXxx(value)`.
6. **decoder-registry.js**: `private/resources/js/pages/cipher-tool/decoder-registry.js` — добавить slug → `{transform, looksLikeEncoded}`.
7. **Переводы**: добавить ключи во все 8 файлов `private/translates/*.php`.
8. **Тест**: `tests/Unit/Cipher/ToolRegistryTest.php` — добавить новый сервис в `makeRegistry()`.

## Схема таблицы ciphers_examples_translations
Колонки: `example_id`, `language`, `title`, `input`, `output`, `description`, `key`, `shift`, `alphabet`

## Инструмент типа "анализ" (без encode/decode)

Пример: Frequency Analysis (`text-analysis/frequency-analysis`).

**Дополнительно к стандартному паттерну:**

- `CipherController::show()`: для `$cipherAlias === 'frequency-analysis'` добавить `$toolUi['analysisMode'] = true` + специальные labels.
- `cipher-tool.js`: флаг `isAnalysisTool = Boolean(ui.analysisMode)`. При true: скрывает tab-decode, скрывает textarea output, показывает `#ciphers-visual-output`, JS-декодер возвращает JSON `{chars, total, unique}`, функция `renderFrequencyChart(data)` рендерит HTML-диаграмму.
- Настройки используют IDs: `ciphers-freq-scope` и `ciphers-freq-sort` (не стандартные `ciphers-alphabet` и т.д.).
- CSS-стили для `.freq-*` добавлены в `private/resources/css/app.css`.

## Категории
- `encoding` (alias) — кодирование (Base64, Hex, etc.)
- `codes-and-alphabets` — коды и алфавиты (Morse, Bacon, etc.)
- `classical-ciphers` — классические шифры
- `text-analysis` — анализ текста и криптоанализ (добавлена 2026-06-11)

## Важные файлы
- `private/app/Cipher/ToolRegistry.php` — центральный реестр
- `private/app/Controller/CipherController.php` — специальная обработка per-инструмент (morse-code, frequency-analysis)
- `private/app/Controller/HomeController.php` — `buildPlannedCategories()` список "coming soon"
- `private/resources/js/pages/cipher-tool.js` — основная JS-логика страницы инструмента
- `private/resources/js/pages/cipher-tool/decoder-registry.js` — реестр JS-декодеров
