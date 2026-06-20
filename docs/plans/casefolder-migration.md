# План: миграция cipher-сервисов на CaseFolder

Дата составления: 2026-06-20.
Статус: pilot (Bifid) — готов; остальные шифры — ожидают миграции.

## Контекст

`mb_strtolower` / `mb_strtoupper` без локали неверно сворачивают регистр пар турецких букв:

| Источник | Дефолт mb | Корректно для tr |
|----------|-----------|------------------|
| `I → ?`  | `i`       | `ı` (без точки)  |
| `İ → ?`  | `i` (или `i̇`) | `i`         |
| `ı → ?`  | `I`       | `I` (совпадает)  |
| `i → ?`  | `I`       | `İ` (с точкой)   |

Любой шифр, чья позиция буквы в алфавите влияет на координаты в шифре, выдаёт неверный round-trip, как только в открытом тексте или в шифре встречается `ı` либо `İ`. Это было воспроизведено на Bifid (см. историю ревью); по той же причине должны страдать все cipher-сервисы, поддерживающие `tr`.

Введён общий сервис `App\Cipher\CaseFolder` с методами `toLower(string $text, string $alphabet)` / `toUpper(string $text, string $alphabet)`, который применяет per-language карты исключений перед обычной mb-свёрткой. На сегодня заполнена только запись `tr`; добавление новых локалей сводится к расширению `LOWER_MAP` / `UPPER_MAP`.

## Что уже сделано

- `private/app/Cipher/CaseFolder.php` — сам сервис.
- `private/app/Cipher/AlphabetTool.php` — `hasAlphabetCharacters` / `detectAlphabet` идут через CaseFolder (зависимость опциональна с fallback ради обратной совместимости со старыми вызовами).
- `private/app/Cipher/BifidCipherService.php` — `prepareText` и `process` свёртывают регистр через CaseFolder.
- Тесты: `CaseFolderTest` (7 / 22), новые tr-кейсы в `BifidCipherServiceTest` (round-trip с `İSTANBULIRMAKİYİ`, ключ `İPEK ≡ ipek`, длинный tr-round-trip без потерь pad-цифр).

## Что осталось

Cipher-сервисы, поддерживающие `tr` (по наличию `'tr'` в их `getToolSettings()` / `process` / тестах) и до сих пор использующие голые `mb_strtolower`/`mb_strtoupper`:

| Сервис | Использование mb | Влияет ли на корректность tr | Приоритет |
|--------|------------------|------------------------------|-----------|
| `PlayfairCipherService` | свёртка ввода и ключа, верхний регистр вывода | да — биграммный шифр на квадрате | **высокий** |
| `PolybiusSquareCipherService` | свёртка ввода | да — координаты букв | **высокий** |
| `VigenereCipherService` | свёртка ввода/ключа | да — сдвиг по индексу буквы | **высокий** |
| `BeaufortCipherService` | то же | да | **высокий** |
| `GronsfeldCipherService` | то же | да | **высокий** |
| `AutokeyCipherService` | то же | да | **высокий** |
| `SimpleSubstitutionCipherService` | свёртка ввода и ключа | да — карта замен | **высокий** |
| `AffineCipherService` | свёртка ввода | да — индекс буквы | **высокий** |
| `HillCipherService` | свёртка ввода | да — индекс буквы | **высокий** |
| `CaesarCipherService` | свёртка ввода | да — индекс буквы | **высокий** |
| `AtbashCipherService` | свёртка ввода | да — позиция в алфавите | **высокий** |
| `A1z26CipherService` | свёртка ввода | да — позиция в алфавите | средний |
| `BaconCipherService` | свёртка ввода | да — индекс буквы | средний |

API-инструменты (`*ApiCipherTool.php`) обычно используют `mb_strtolower` только для нормализации параметров запроса (`alphabet`, `direction`, `delimiter`) — это **не** требует CaseFolder и оставляется как есть.

Анализаторы (`LetterFrequencyScorer`, `BigramFrequencyScorer`, `MorseCipherService`, `ColumnarTranspositionCipherService`) — проверить отдельно; некоторые работают только с латиницей и tr не поддерживают, тогда миграция не нужна.

## Шаблон миграции (по образцу Bifid)

Для каждого сервиса из таблицы:

1. **Конструктор** — добавить обязательную зависимость `private CaseFolder $caseFolder` (Container разрешит её автоматически).
2. **Свёртка ввода** — заменить:
   ```php
   $normalized = mb_strtolower($text);
   ```
   на:
   ```php
   $normalized = $this->caseFolder->toLower($text, $alphabet);
   ```
   Аналогично для ключа, если он тоже свёртывается.
3. **Свёртка вывода** — заменить:
   ```php
   return mb_strtoupper($result);
   ```
   на:
   ```php
   return $this->caseFolder->toUpper($result, $alphabet);
   ```
4. **Тесты** — обновить хелпер `createService()` / `setUp()` в тестах сервиса и в `ToolRegistryTest` / `ApiCipherToolRegistryTest`, передавая `CaseFolder` в конструктор.
5. **Покрытие** — добавить как минимум один tr-кейс:
   ```php
   public function testTurkishRoundTripPreservesDottedAndDotlessI(): void
   {
       $service = $this->createService();

       $plain = 'İSTANBULIRMAKİYİ';  // или подходящий для конкретного шифра набор
       $enc   = $service->process($plain, /* key */, 'tr', 'encrypt');
       $dec   = $service->process($enc, /* key */, 'tr', 'decrypt');

       self::assertSame($plain, $dec);
   }
   ```
   И, по возможности, кейс с ключом, где есть `İ` — проверить эквивалентность `'İPEK' ≡ 'ipek'`.

## Готово, когда…

- Все 13 сервисов из таблицы прошли миграцию.
- Для каждого добавлен tr-round-trip тест с `İ` / `ı` в открытом тексте.
- `AlphabetTool` принимает `CaseFolder` обязательно (nullable fallback удалён) — это можно сделать последним шагом, когда все сервисы уже передают его явно. До этого момента fallback страхует legacy-вызовы из ещё-не-мигрированных шифров.
- `vendor/bin/phpunit`, `vendor/bin/phpstan`, `vendor/bin/pint --test` — зелёные.

## Возможные расширения CaseFolder в будущем

Сейчас карта заполнена только для `tr`. Если появятся другие локали со «спорной» case-folding (немецкий `ß ↔ ẞ`, греческий final-sigma и т.п.), они добавляются как новые ключи в `LOWER_MAP` / `UPPER_MAP` без изменения кода методов.

Стоит держать в голове: если алфавит не содержит «спорной» буквы (например, в нашем `de` нет `ß`), запись для него можно не вводить, чтобы не плодить шум.
