# Cipher Identifier

Инструмент категории `text-analysis` для угадывания, каким шифром/кодировкой
зашифрован произвольный текст. Возвращает ранжированный список кандидатов с
confidence и, для лидера, при возможности — готовый результат brute-force.

Slug: `text-analysis/cipher-identifier`. Calculation mode: `api`.
Файлов `private/storage/content` НЕ создаём (как у `caesar-brute-force`).

---

## 1. Принципы

1. **Гибридный ответ.** API всегда возвращает список кандидатов с
   `confidence` и метаданными. Если лидер имеет `confidence ≥ THRESHOLD`
   и для него зарегистрирован brute-force, Identifier дополнительно
   делегирует вызов соответствующему `ApiCipherToolInterface` через
   уже существующий `ApiCipherToolRegistry` и кладёт результат в
   `auto_result`. Остальные кандидаты — только ссылки/подсказки.
2. **Авторегистрация шифров.** Каждый кандидат описывается классом,
   реализующим новый интерфейс `CipherDetectorInterface`. Identifier
   получает массив всех детекторов через `services.php` — добавление
   нового шифра в Identifier сводится к созданию одного класса и
   одной строки в массиве.
3. **Алфавит.** По умолчанию `auto` — используем
   `LetterFrequencyScorer::detectAlphabet()` для буквенных шифров.
   Клиент может явно передать `alphabet` в settings — тогда детекторы,
   которые умеют учитывать алфавит, привязаны к нему.
4. **Эвристики уровня «средний».** charset + структурные паттерны +
   Index of Coincidence (IoC) + χ² по существующему скореру. Без Kasiski
   и n-gram анализа в первой итерации.
5. **Идемпотентность и стоимость.** Идентификация — чистая функция от
   текста. Дорогие brute-force запускаются только для лидера, не для
   всех кандидатов сразу. Per-IP rate-limit стандартный
   (`RateLimitMiddleware`).

---

## 2. Архитектура

### 2.1 Новые классы

| Файл | Роль |
|------|------|
| `App\Cipher\CipherDetectorInterface` | Контракт детектора. |
| `App\Cipher\CipherDetection` | DTO результата детекции. |
| `App\Cipher\IndexOfCoincidence` | Утилита: подсчёт IoC по тексту и алфавиту. |
| `App\Cipher\CipherIdentifierService` | UI-сервис (`getToolSettings`, `getTrustItems`) + основной алгоритм идентификации. |
| `App\Cipher\CipherIdentifierApiCipherTool` | `ApiCipherToolInterface`, action = `cipher-identifier`. Делегирует brute-force через `ApiCipherToolRegistry`. |
| `App\Cipher\Detector\*Detector` | По одному классу на шифр/кодировку (см. §4). |

### 2.2 Контракт детектора

```php
namespace App\Cipher;

interface CipherDetectorInterface
{
    /**
     * Решает, насколько текст похож на «свой» шифр/кодировку.
     *
     * @param string      $text     Исходный текст пользователя.
     * @param string|null $alphabet Явно заданный код алфавита или null (auto).
     * @return CipherDetection|null  null, если шифр-кандидат заведомо не подходит.
     */
    public function detect(string $text, ?string $alphabet): ?CipherDetection;
}
```

### 2.3 DTO результата

```php
namespace App\Cipher;

final readonly class CipherDetection
{
    /**
     * @param string   $toolSlug          Canonical slug инструмента ('classical-ciphers/caesar').
     * @param string   $cipherKey         Ключ перевода названия шифра ('CIPHER_NAME_CAESAR').
     * @param float    $confidence        0.0–1.0.
     * @param string[] $evidenceKeys      Ключи перевода-объяснения ('CID_EV_CHARSET_LETTERS').
     * @param string|null $bruteForceAction action для ApiCipherToolRegistry ('caesar-brute-force').
     * @param string|null $detectedAlphabet 'en' | 'ru' | ... или null.
     * @param array<string, scalar> $hints Доп. подсказки для UI (например, key_required).
     */
    public function __construct(
        public string $toolSlug,
        public string $cipherKey,
        public float $confidence,
        public array $evidenceKeys = [],
        public ?string $bruteForceAction = null,
        public ?string $detectedAlphabet = null,
        public array $hints = [],
    ) {}
}
```

### 2.4 Identifier-сервис

```php
final readonly class CipherIdentifierService
{
    public function __construct(
        /** @var CipherDetectorInterface[] */
        private array $detectors,
    ) {}

    /** @return CipherDetection[] (отсортирован по confidence desc). */
    public function identify(string $text, ?string $alphabet): array;

    /** UI-настройки (для ToolRegistry). */
    public function getToolSettings(): array;

    /** Trust-bullets (для ToolRegistry). */
    public function getTrustItems(string $calculationMode): array;
}
```

### 2.5 API-инструмент

```php
final readonly class CipherIdentifierApiCipherTool implements ApiCipherToolInterface
{
    public function __construct(
        private CipherIdentifierService $identifier,
        private ApiCipherToolRegistry  $apiRegistry,
    ) {}

    public function action(): string { return 'cipher-identifier'; }

    /**
     * @return array{
     *   ok: true,
     *   candidates: array<int, array<string, mixed>>,
     *   auto_action: string|null,
     *   auto_result: array<string, mixed>|null,
     *   detected_alphabet: string|null
     * }
     */
    public function execute(array $payload): array;
}
```

Псевдокод `execute`:

```
text = payload.text (string, required, length 1..3000)
alphabet = payload.settings.alphabet ?? 'auto'
если alphabet == 'auto' → передаём null в identify()
candidates = identifier.identify(text, alphabetOrNull)
лидер = candidates[0] ?? null
auto = null

если лидер != null
   && лидер.confidence >= AUTO_THRESHOLD (см. §5.4)
   && лидер.bruteForceAction != null:
       try {
           auto = apiRegistry.execute(лидер.bruteForceAction, [
               'text' => text,
               'settings' => ['alphabet' => лидер.detectedAlphabet ?? alphabet],
           ])
       } catch (исключение) {
           auto = null  // ошибки brute-force не должны валить identify
       }

return { ok: true, candidates: [...], auto_action, auto_result: auto, detected_alphabet }
```

---

## 3. Регистрация и расширение

### 3.1 services.php

```php
// В private/config/services.php

CipherIdentifierService::class => fn($c) => new CipherIdentifierService([
    $c->get(MorseCodeDetector::class),
    $c->get(BaconDetector::class),
    $c->get(A1z26Detector::class),
    $c->get(PolybiusSquareDetector::class),
    $c->get(CaesarDetector::class),
    $c->get(Rot13Detector::class),
    $c->get(AtbashDetector::class),
    $c->get(AffineDetector::class),
    $c->get(SimpleSubstitutionDetector::class),
    $c->get(VigenereDetector::class),
    // ... и т.д., см. §4
]),
```

### 3.2 ToolRegistry

Добавить в `private/app/Cipher/ToolRegistry.php`:
- В конструктор: `private CipherIdentifierService $cipherIdentifier`.
- В `apiAction()`: `'text-analysis/cipher-identifier' => 'cipher-identifier'`.
- В `settings()` / `trustItems()`: соответствующие ветки.
- В `exampleChips()`: 4 примера, по одному на тип сигнала:
  1. Жёсткий формат (Morse: `... --- ...`)
  2. Кодировка (Base64: `SGVsbG8gV29ybGQh`)
  3. Моноалфавитный (Caesar shift-3: `KHOOR ZRUOG`)
  4. Полиалфавитный (Vigenere ключ KEY: `RIJVS UYVJN`)

### 3.3 ApiCipherToolRegistry

В конструктор и foreach `private/app/Cipher/ApiCipherToolRegistry.php`
добавить `CipherIdentifierApiCipherTool`.

### 3.4 api_routes.php

```php
'POST /tools/cipher-identifier' => [
    'controller' => GuestController::class,
    'method'     => 'cipherIdentifier',
    'middleware' => [RateLimitMiddleware::class],
    'name'       => 'api.tools.cipher-identifier',
],
```

### 3.5 GuestController

Добавить метод-обёртку:

```php
public function cipherIdentifier(Request $request): Response
{
    return $this->handleCipherTool($request, 'cipher-identifier');
}
```

### 3.6 api.js

Добавить в секцию `guest`:

```js
'cipher-identifier': (data) => this.#request('POST', '/tools/cipher-identifier', data),
```

### 3.7 Миграция

Запись в таблице `tools`:
- `alias='cipher-identifier'`
- `category='text-analysis'`
- `calculation_mode='api'`
- `sort_order` — рядом с `caesar-brute-force` / `vigenere-cracker`.
- `input_max_length=3000` (через `ui.inputMaxLength` в `cipher-tool.js`).

> JSON-каталог в `private/storage/content` для этого инструмента
> **не создаётся**. Каталог используется только как экспорт/импорт
> из БД, а Identifier ничего не хранит и не нуждается в внешних
> данных, кроме записи в `tools`.

### 3.8 Как добавлять новый шифр в Identifier

Когда в проект добавляется новый шифр (см. memory `project-cipher-architecture`),
дополнительно делаются ровно два шага:

1. Создать `App\Cipher\Detector\XxxDetector implements CipherDetectorInterface`.
2. Добавить строку `$c->get(XxxDetector::class)` в массив детекторов
   в `services.php` (фабрика `CipherIdentifierService`).

Никаких изменений в `CipherIdentifierService`, API-инструмент или UI
больше не требуется. Это и есть «органичная интеграция».

---

## 4. Список детекторов первой итерации

Группы — по типу сигнала. У каждого детектора в скобках — основной признак.

### 4.1 Кодировки / форматы (жёсткие сигналы, confidence 0.85–0.98)

| Detector | Признак | Toolslug |
|----------|---------|----------|
| `Base64Detector` | `^[A-Za-z0-9+/]+={0,2}$`, длина кратна 4 | `encoding/base64` |
| `HexDetector` | `^[0-9a-fA-F\s]+$`, длина чистых hex-символов чётная | `encoding/hex` |
| `BinaryDetector` | `^[01\s]+$`, длина блоков 7/8/16/32 бита | `encoding/binary-converter` |
| `MorseCodeDetector` | `^[.\-\/\s]+$`, последовательности из dot/dash длиной 1–5 | `codes-and-alphabets/morse-code` |
| `BaconDetector` | `^[ABab\s]+$`, длина без пробелов кратна 5 | `codes-and-alphabets/bacon` |
| `A1z26Detector` | `^\d+(?:[-\s,]\d+)+$`, все числа в диапазоне 1..N (32–33) | `codes-and-alphabets/a1z26` |
| `PolybiusSquareDetector` | пары цифр 1..5 (или 1..6) разделённые пробелом | `codes-and-alphabets/polybius-square` |
| `UrlEncodedDetector` | `%[0-9a-fA-F]{2}` встречается ≥ 1, иначе symbol-set ASCII | `encoding/url-encode` |
| `JwtDetector` | три части через `.`, каждая base64url | `encoding/jwt-decoder` |
| `UnicodeEscapeDetector` | `\\u[0-9a-fA-F]{4}` или `U+XXXX` | `encoding/unicode-converter` |

### 4.2 Подстановочные классические (мягкие, confidence 0.40–0.75)

Все они работают только если `LetterFrequencyScorer::countLetters` ≥ `MIN_LETTERS_FOR_RELIABLE_SCORING`. Для коротких текстов confidence нормируется вниз (см. §5).

| Detector | Базовый сигнал |
|----------|----------------|
| `CaesarDetector` | charset = буквы; IoC ≈ IoC(language); один из 26 сдвигов даёт малое χ². Имеет `bruteForceAction = 'caesar-brute-force'`. |
| `Rot13Detector` | частный случай Caesar shift=13; добавляется отдельно с более высоким confidence, если shift=13 даёт явно меньшее χ², чем другие. |
| `AtbashDetector` | charset = буквы; обратный сдвиг по алфавиту даёт малое χ². |
| `AffineDetector` | charset = буквы; IoC ≈ моно. Имеет `bruteForceAction = 'affine-brute-force'`. |
| `SimpleSubstitutionDetector` | charset = буквы; IoC ≈ моно; длина ≥ 80. Brute-force отсутствует — только ссылка. |
| `XorDetector` | hex, длина кратна 2, неравномерное распределение байт. |

### 4.3 Полиалфавитные (мягкие, confidence 0.40–0.70)

| Detector | Базовый сигнал |
|----------|----------------|
| `VigenereDetector` | charset = буквы; IoC меньше моноалфавитного, но больше случайного. Имеет `bruteForceAction = 'vigenere-cracker'`. |
| `BeaufortDetector` | то же, что Vigenere; отличить от него по тексту невозможно — оба пометятся одновременно. |
| `AutokeyDetector` | как Vigenere. |
| `GronsfeldDetector` | как Vigenere. Brute-force отсутствует — ссылка. |
| `AlbertiDetector` | как Vigenere. |
| `BifidDetector` | charset = буквы; IoC ниже типичного Vigenere. |
| `TrifidDetector` | charset = буквы; IoC ещё ниже. |

### 4.4 Транспозиционные (по сохранению частот)

| Detector | Базовый сигнал |
|----------|----------------|
| `RailFenceDetector` | charset = буквы; IoC и χ² ≈ обычному тексту языка (частоты сохранены). |
| `ColumnarTranspositionDetector` | то же. |

Признак «частоты сохранены, но текст не читается» — слабый, поэтому оба детектора возвращают confidence не выше 0.45 и помечают друг друга
неразличимыми. Brute-force в нашем стеке нет.

### 4.5 Специальные

| Detector | Базовый сигнал |
|----------|----------------|
| `PlayfairDetector` | charset = буквы; длина чётная; в тексте отсутствует J/одна из букв алфавита Polybius. Конфликт с Hill — оба пометятся. |
| `HillDetector` | charset = буквы; длина кратна 2 (для 2×2). Без brute-force. |
| `VernamDetector` | base64-подобный текст с фиксированной длиной как у потенциального ключа. Низкий приоритет. |

> Атрибуции в группе 4.3–4.5 заведомо неуверенные. Identifier признаёт это,
> возвращая все правдоподобные варианты с близким confidence и
> evidence-объяснением `CID_EV_AMBIGUOUS_POLYALPHA`.

---

## 5. Алгоритм идентификации (детали)

### 5.1 Препроцессинг

В `CipherIdentifierService::identify()` один раз вычисляются и
передаются детекторам через `IdentificationContext` (приватный value-объект):

- `cleanedText` — без пробелов/переводов строк, для проверок charset/length.
- `letterCount[alphabet]` — для каждого поддерживаемого алфавита.
- `detectedAlphabet` — `LetterFrequencyScorer::detectAlphabet()` (если auto).
- `ioc[alphabet]` — Index of Coincidence на буквах данного алфавита.
- `chiSquared[alphabet]` — χ² через `LetterFrequencyScorer::chiSquared`.

`IndexOfCoincidence` — отдельный класс (метод
`compute(string $text, string $alphabet): float`). Тривиальный, но
выносим, чтобы покрыть тестом.

### 5.2 Эталонные значения IoC

| Язык | IoC обычного текста (≈) | IoC случайного (≈ 1/|A|) |
|------|-------------------------|--------------------------|
| en   | 0.067 | 0.038 |
| ru   | 0.057 | 0.030 |
| de   | 0.072 | 0.038 |
| fr   | 0.078 | 0.038 |
| es   | 0.077 | 0.037 |
| it   | 0.074 | 0.048 |
| pt   | 0.072 | 0.029 |
| tr   | 0.066 | 0.034 |

Константы кладём в `IndexOfCoincidence::LANGUAGE_IOC`.

### 5.3 Confidence

Базовая шкала для каждой группы:

- **Жёсткая** (charset + строгий шаблон полностью совпали): `0.90`, минус
  штраф за «короткий текст» (< 16 символов) — до 0.75.
- **Моноалфавитный буквенный** (IoC ≈ языку, χ² шифрованного далёк от 0):
  base `0.55`, прибавка `+0.10`, если brute-force-проба находит «явного
  победителя» (один из 26 сдвигов / коэффициентов даёт χ² существенно
  ниже остальных).
- **Полиалфавитный буквенный** (IoC между моно и случайным): base `0.45`.
- **Транспозиция** (IoC ≈ языку, но текст не читается): base `0.40`.

Для коротких текстов общий коэффициент `min(1.0, letters / MIN_LETTERS_FOR_RELIABLE_SCORING)`
применяется ко всем «мягким» детекторам. В `CipherDetection.hints`
добавляется `low_sample = true`.

### 5.4 Порог автозапуска brute-force

`AUTO_THRESHOLD = 0.70`. Срабатывает только когда:
1. лидер имеет `confidence ≥ 0.70`,
2. лидер имеет `bruteForceAction != null`,
3. отрыв от второго кандидата `≥ 0.10`.

Третий пункт — чтобы не запускать brute-force, когда Identifier
колеблется между Caesar и Affine (пусть пользователь выберет сам).

---

## 6. UI

### 6.1 Режим карточки

В `CipherController` ввести флаг `$toolUi['identifierMode'] = true`
по `$cipherAlias === 'cipher-identifier'` (как уже делается для
`bruteForceMode`/`analysisMode`).

### 6.2 cipher-tool.js

Добавить ветку `isIdentifierTool = Boolean(ui.identifierMode)`. Поведение:

- Скрыть tab-decode, скрыть стандартный output.
- В блоке `visual-output` отрисовать:
  - **Auto result** (если есть) — карточка с расшифрованным текстом
    и подписью «Auto-decrypted as &lt;CipherName&gt; (confidence X%)».
  - **Candidates** — таблица:

    | Cipher | Confidence | Evidence | Action |
    |--------|------------|----------|--------|
    | Caesar | 78% (progress bar) | charset=letters, IoC≈en mono, best shift=3 | Кнопка «Open Caesar Brute Force» (link на инструмент с прокинутым текстом через query/localStorage) |
    | Atbash | 56% | charset=letters, IoC≈en mono | «Open Atbash» |
    | Vigenere | 42% | charset=letters, IoC=0.045 | «Open Vigenere Cracker» |

- Кнопка «Open …» строит URL по `tool_slug` через существующий
  механизм генерации ссылок инструментов **без query-параметров**.
  Текст переносится через `localStorage` (см. §6.3).

### 6.3 Перенос текста через localStorage

Цель: чтобы по клику «Open Caesar Brute Force» нужный текст
автоматически появился в input нового инструмента, без раздувания
URL и без шансов словить кешированные ссылки в логах/CDN.

Схема:

| Шаг | Где | Действие |
|-----|-----|----------|
| 1 | `cipher-tool.js` Identifier | По клику «Open …» сохраняем `{ text, sourceSlug, expiresAt }` в `localStorage` под ключом `ciphers:carry-over`. TTL — 60 секунд. |
| 2 | браузер | Переходим по обычной ссылке на инструмент. |
| 3 | `cipher-tool.js` (любой инструмент) | На инициализации читает `ciphers:carry-over`, проверяет TTL и (опционально) `targetSlug === slug`. Если payload валиден — подставляет `text` в `input`, очищает ключ, эмитит обычный change-event так, чтобы lifecycle инструмента сработал штатно. |

Существующий `stateStorageKey` (`ciphers:state:<slug>`) трогать не
нужно — это разные сущности: state-storage хранит пользовательские
настройки конкретного инструмента, carry-over — одноразовая
передача текста между инструментами.

### 6.4 CSS

Новые классы `.identifier-*` в `private/resources/css/app.css`, рядом
с `.brute-*` и `.freq-*`.

---

## 7. Переводы

Во все 8 локалей (`en/ru/de/es/fr/it/pt/tr`):

```
CIPHER_IDENTIFIER_TITLE
CIPHER_IDENTIFIER_DESCRIPTION
CIPHER_IDENTIFIER_INPUT_LABEL
CIPHER_IDENTIFIER_BUTTON_ACTION
CIPHER_IDENTIFIER_NO_CANDIDATES
CIPHER_IDENTIFIER_AUTO_RESULT_TITLE
CIPHER_IDENTIFIER_CANDIDATES_TITLE
CIPHER_IDENTIFIER_COLUMN_CIPHER
CIPHER_IDENTIFIER_COLUMN_CONFIDENCE
CIPHER_IDENTIFIER_COLUMN_EVIDENCE
CIPHER_IDENTIFIER_COLUMN_ACTION
CIPHER_IDENTIFIER_OPEN_TOOL
CIPHER_IDENTIFIER_TRUST_TYPE
CIPHER_IDENTIFIER_TRUST_MULTI_ALPHA
CIPHER_IDENTIFIER_ERR_TEXT_REQUIRED

# Evidence-keys (общие)
CID_EV_CHARSET_LETTERS
CID_EV_CHARSET_HEX
CID_EV_CHARSET_BASE64
CID_EV_CHARSET_BINARY
CID_EV_CHARSET_MORSE
CID_EV_CHARSET_BACON
CID_EV_CHARSET_NUMBERS
CID_EV_LENGTH_MULTIPLE_OF
CID_EV_IOC_MONO
CID_EV_IOC_POLY
CID_EV_IOC_PRESERVED
CID_EV_CHISQ_BEST_SHIFT
CID_EV_AMBIGUOUS_POLYALPHA
CID_EV_LOW_SAMPLE

# Названия шифров (переиспользуем существующие там, где они есть;
# при отсутствии — добавляем CIPHER_NAME_*)
CIPHER_NAME_CAESAR, CIPHER_NAME_ATBASH, ... (по списку §4)
```

---

## 8. Тестирование

### 8.1 Unit-тесты

- `IndexOfCoincidenceTest` — корректность IoC для известных строк (плоский,
  моно, случайный).
- На каждый детектор `XxxDetectorTest`:
  - возвращает `null`, когда текст заведомо не подходит,
  - возвращает `CipherDetection` с разумным confidence на эталонном тексте,
  - не падает на пустой строке.
- `CipherIdentifierServiceTest`:
  - сортирует кандидатов по confidence,
  - корректно прокидывает `alphabet=null`/`alphabet='en'`,
  - не дублирует кандидатов.
- `CipherIdentifierApiCipherToolTest`:
  - валидация `text` обязательно,
  - возвращает `auto_result`, когда лидер выше порога и имеет
    `bruteForceAction` — мокаем `ApiCipherToolRegistry`,
  - не возвращает `auto_result`, когда отрыв < 0.10,
  - исключение из brute-force не валит ответ (auto_result = null,
    candidates на месте).

### 8.2 Registry-тесты

- Обновить `ToolRegistryTest::makeRegistry()` и
  `ApiCipherToolRegistryTest::makeRegistry()` — добавить новые сервисы.

---

## 9. Этапы реализации

1. **Скелет.** Интерфейс, DTO, IoC, пустой `CipherIdentifierService` (всегда
   возвращает `[]`), API-инструмент, маршрут, контроллер, миграция,
   ToolRegistry/ApiCipherToolRegistry, переводы для UI-шелла, базовая
   страница без кандидатов («No candidates»).
2. **Кодировки** (жёсткие сигналы): Base64, Hex, Binary, Morse, Bacon, A1z26,
   Polybius. Эти детекторы тривиальные и дают видимый результат сразу.
3. **Brute-force-цепочка для Caesar.** `CaesarDetector` + ветка
   автозапуска `caesar-brute-force` в API. Полный UI с auto-result.
4. **Остальные моноалфавитные:** Atbash, Rot13, Affine, Simple Substitution.
5. **Полиалфавитные:** Vigenere/Beaufort/Autokey/Gronsfeld/Alberti/
   Bifid/Trifid с общим evidence «ambiguous polyalpha».
6. **Транспозиции и специальные:** RailFence, ColumnarTransposition,
   Playfair, Hill, Vernam, XOR, JWT/URL-encode/Unicode.
7. **Финал:** прогон тестов, обновление memory `project-cipher-architecture`
   с шагом «добавить XxxDetector в массив services.php».

---

## 10. Принятые решения

1. **`exampleChips()`** — 4 примера, по одному на тип сигнала (Morse,
   Base64, Caesar shift-3, Vigenere). Конкретные значения — см. §3.2.
2. **Передача текста в инструмент** — через `localStorage` под ключом
   `ciphers:carry-over` с TTL 60 секунд. Без query-параметров: не
   попадает в логи/CDN, не индексируется поисковиками, не создаёт
   риска кросс-кэширования. Детали — §6.3.
3. **Лимит длины входа** — 3000 символов. Для определения шифра этого
   с большим запасом достаточно (надёжная атрибуция полиалфавитных
   начинается с 200–500). Хранится в `tools.input_max_length`,
   фронт читает через `ui.inputMaxLength`, бэк дублирует проверку в
   `execute()`.
4. **JSON-каталог** — не используется. Записи в `private/storage/content`
   нужны только как экспорт/импорт БД, Identifier ничего туда не
   кладёт. Создаётся только миграция с записью в таблице `tools`.
