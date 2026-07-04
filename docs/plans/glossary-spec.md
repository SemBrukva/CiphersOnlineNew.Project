# ТЗ: Глоссарий терминов (файловый, без БД)

> Раздел `/glossary` — словарь терминов криптографии и кодирования.
> Контент генерируется LLM и хранится **в JSON-файлах** (Git = источник истины).
> **Без БД, без миграций, без админ-CRUD, без команд export/import.**

Связано с: [product-growth-roadmap.md](product-growth-roadmap.md) (Опора A, инициатива A1, Фаза 1).

---

## 1. Решение об архитектуре и его обоснование

**Глоссарий — чисто файловый.** В отличие от шифров (`CipherRepository` → БД, JSON лишь формат обмена через `cipher:content:import/export`), термины глоссария читаются напрямую из файлов и кэшируются.

**Почему без БД:**
- Контент read-mostly, статичный, авторства LLM → БД не даёт выигрыша, но добавляет слой синхронизации «файл ↔ таблица».
- Git = версионирование + review через PR + откат из коробки.
- Убираются: миграция, репозиторий-над-БД, админ-CRUD, команды import/export.

**Компромиссы, которые закрывает это ТЗ:**
1. Рантайм-чтение файлов → агрессивный кэш (`cache()->remember()`), в local (`NullCache`) допустим глоб на запрос.
2. Две контент-модели в проекте (БД для шифров, файлы для глоссария) — задокументировано здесь намеренно.
3. Sitemap/поиск сейчас из БД → добавляется файловая ветка (sitemap — в scope v1, поиск — v2).
4. Никакого ручного build-индекса: список терминов собирается глобом + кэш с длинным TTL, сброс при `flush-cache`/деплое.

---

## 2. Цели, не-цели, scope v1

**Цели:**
- SEO-охват информационных запросов («что такое …», «… это», «… определение») на всех языках.
- Плотная перелинковка глоссарий ↔ инструменты ↔ глоссарий (усиление E-E-A-T и внутреннего PageRank).
- Нулевой операционный оверхед: LLM пишет файл → PR → деплой.

**Не-цели (v1):**
- Админ-редактор терминов (правки — через файлы/PR).
- Полнотекстовый поиск по глоссарию (v2).
- Пользовательские фичи (избранное терминов и т.п.).

**Scope v1:**
- Страница-индекс `/glossary` (список терминов, сгруппированный).
- Страница термина `/glossary/{term}`.
- Полное мультиязычие (8 текущих локалей) с fallback.
- SEO: meta, canonical, hreflang, JSON-LD (`DefinedTerm` / `DefinedTermSet`), хлебные крошки, sitemap.
- Перелинковка term↔term и term→tool; tool→term (обратные ссылки) — опционально в v1.
- Команда валидации `glossary:validate` для CI.

---

## 3. Информационная архитектура и роутинг

### URL
| URL | Назначение |
|---|---|
| `/glossary` | Индекс: все термины, сгруппированы по `category`, алфавит/поиск на клиенте |
| `/glossary/{term}` | Страница термина |
| `/{locale}/glossary`, `/{locale}/glossary/{term}` | Локализованные версии (префикс добавляет `LocaleMiddleware`) |

`{term}` — slug `[a-z0-9-]+`.

### Регистрация маршрутов
В `private/config/routes.php` — **строго ВЫШE** catch-all маршрутов `/{category}/{cipher}` и `/{alias}`, иначе `/glossary/foo` уедет в `CipherController`, а `/glossary` — в `CipherCategoryController`.

```php
// routes.php — ДО ciphers.show и cipher_categories.show
'GET /glossary' => [
    'controller' => GlossaryController::class,
    'method'     => 'index',
    'name'       => 'glossary.index',
],
'GET /glossary/{term:[a-z0-9-]+}' => [
    'controller' => GlossaryController::class,
    'method'     => 'show',
    'name'       => 'glossary.show',
],
```

Проверить, что ни одна категория/шифр не имеет alias `glossary` (конфликт brand-путей).

### Навигация
Пункт добавляется **только в футер** (ключ перевода `MENU_GLOSSARY`); в главное меню не выносим, чтобы не раздувать его. Дополнительный вход в глоссарий — через перелинковку из инструментов и статей.

---

## 4. Модель данных (файлы + схема JSON)

### Расположение файлов
Зеркалит существующий `storage/content/` (LLM уже знаком с этим layout):

```
private/storage/content/glossary/
  frequency-analysis/
    en.json
    ru.json
    de.json  es.json  fr.json  it.json  pt.json  tr.json
  one-time-pad/
    en.json
    ...
```

- Одна папка = один термин (slug = имя папки).
- Один файл = один язык. Отсутствие файла локали → fallback на `default_language` (см. §7).
- Никаких `category.*.json` и никаких `_index.json` — индекс собирается рантаймом.

### Схема `glossary-term.v1`
```json
{
  "meta": {
    "schema": "glossary-term.v1",
    "term_slug": "frequency-analysis",
    "language": "en",
    "default_language": "en",
    "status": "published",
    "updated_at": "2026-07-04"
  },
  "term": {
    "name": "Frequency Analysis",
    "aliases": ["letter frequency attack"],
    "short": "Односложное определение: используется в карточках, тултипах и meta_description по умолчанию.",
    "meta_title": "Frequency Analysis — Definition | CiphersOnline",
    "meta_description": "Что такое частотный анализ, как он работает и где применяется в криптоанализе."
  },
  "body": [
    { "sort_order": 10, "type": "definition",    "title": "Definition",     "html": "<p>…</p>" },
    { "sort_order": 20, "type": "how_it_works",  "title": "How it works",   "html": "<p>…</p>" },
    { "sort_order": 30, "type": "example",       "title": "Example",        "html": "<p>…</p>" }
  ],
  "faq": [
    { "question": "…", "answer": "…" }
  ],
  "related_terms": ["kasiski-examination", "index-of-coincidence"],
  "related_tools": ["text-analysis/frequency-analysis", "classical-ciphers/vigenere"],
  "tags": ["cryptanalysis", "classical"],
  "category": "cryptanalysis"
}
```

**Правила полей:**
- `meta.status`: `draft` | `published`. `draft` → рендерится с `<meta name="robots" content="noindex">` и не попадает в индекс/sitemap. Позволяет заливать сырой машинный перевод, не индексируя его (митигинг риска «тонкого контента» из роадмапа).
- `term.short` — обязателен; fallback для `meta_description`, текст карточки в индексе, тултип при наведении в перелинковке.
- `body[].html` — доверенный HTML (как `blocks` у шифров, рендерится через `nofilter`). Источник — только наш LLM-пайплайн, не пользователь.
- `related_terms` — slug'и терминов; битые ссылки отсеиваются рантаймом (ссылка не рендерится, если термин отсутствует) + ловятся `glossary:validate`.
- `related_tools` — slug'и вида `category/alias`. URL строится без обращения к БД. Существование инструмента проверяет `glossary:validate` (см. §11).
- `category` — группировка для страницы-индекса (напр. `cryptanalysis`, `classical`, `encoding`, `hashing`, `concepts`). Метки категорий — через ключи переводов.

---

## 5. Рендеринг: компоненты

### `GlossaryRepository` (файловый, НЕ над БД)
`private/app/Repository/Glossary/GlossaryRepository.php` (или `App\Glossary\GlossaryRepository` — вне `Repository/`, чтобы не путать с БД-репозиториями; **рекомендация: `App\Glossary\`**).

Методы:
- `find(string $slug, string $locale): ?array` — читает `content/glossary/{slug}/{locale}.json`; при отсутствии файла запрошенной локали → `null` (→ 404, без fallback, см. §7); декодирует, кэширует.
- `index(string $locale): array` — глоб по `content/glossary/*/`, для каждого термина берёт `meta` + `term.name` + `term.short` + `category` + `tags` + список доступных локалей; кэширует весь индекс.
- `exists(string $slug): bool`.
- `slugs(): string[]` — для sitemap.

Кэширование:
- Ключи: `glossary.term.{slug}.{locale}`, `glossary.index.{locale}`.
- TTL длинный (напр. `config('cache.ttl')` × N или отдельный `GLOSSARY_CACHE_TTL`); сброс при `flush-cache` (кнопка в админке уже есть: `POST /admin/settings/flush-cache`).
- В local (`NullCache`) — прозрачно читает файлы каждый раз.

### `GlossaryController`
`private/app/Controller/GlossaryController.php` (зарегистрировать в `services.php`).

- `index(Request)`:
  - `$terms = $repo->index(locale())`; сгруппировать по `category`; отфильтровать `draft`.
  - meta_title/description индекса — через переводы (`GLOSSARY_INDEX_TITLE`, …).
  - JSON-LD `DefinedTermSet` + `BreadcrumbList` (Home → Glossary).
  - Рендер `glossary/index.tpl`.
- `show(Request, string $term)`:
  - `$data = $repo->find($term, locale())`; если нет → 404 (`errors/404.tpl`, как в `CipherController`).
  - Если `status = draft` → выставить `meta_robots = noindex`.
  - Резолвить `related_terms` (в объекты name+url, отбросить несуществующие) и `related_tools` (в name+url).
  - JSON-LD: `DefinedTerm` (+ `inDefinedTermSet`), `BreadcrumbList` (Home → Glossary → Term), `FAQPage` при наличии `faq`.
  - Рендер `glossary/show.tpl`.

Переиспользовать существующие механизмы: `structured_data` (см. `View.php:183`), `partials/breadcrumbs.tpl`, `meta_robots`/`meta_description`/`og_image` из `main.tpl`.

### Шаблоны
- `views/glossary/index.tpl` — сетка/список карточек по категориям, алфавитный якорь, клиентский фильтр по названию (простой JS, без бэкенда).
- `views/glossary/show.tpl` — заголовок (name + aliases), `body`-блоки (`{$block.html nofilter}`), FAQ-аккордеон (можно переиспользовать паттерн из `cipher/show.tpl`), блок «Related terms», блок «Related tools», хлебные крошки.
- Опционально `views/glossary/_term_card.tpl` — переиспользуемая карточка.

---

## 6. SEO

- **meta_title/description**: из `term.meta_title` / `term.meta_description`, fallback на `term.name` / `term.short`.
- **canonical + hreflang + x-default**: уже реализованы в `main.tpl` через `locale_urls`. Нужно, чтобы `LocaleMiddleware`/`ShareViewDataMiddleware` корректно строили `locale_urls` для glossary-путей. Проверить и при необходимости расширить логику генерации `locale_urls` так, чтобы альтернаты указывали только на **реально существующие** локали термина (иначе hreflang на 404). Источник наличия локалей — `index()`/`find()`.
- **JSON-LD**:
  - Термин: `DefinedTerm` (`name`, `description` = `short`, `inDefinedTermSet` = URL `/glossary`, `termCode` = slug).
  - Индекс: `DefinedTermSet` (`hasDefinedTerm` — список) — либо облегчённо только `CollectionPage`, чтобы не раздувать разметку.
  - `BreadcrumbList` везде; `FAQPage` при наличии FAQ.
- **Sitemap** (`SitemapController`): добавить ветку — `GlossaryRepository::slugs()` → пути `/glossary` и `/glossary/{slug}` с `changefreq=monthly`, `priority` ниже инструментов (напр. 0.5). Учесть `status=draft` → исключать. `lastmod` = `meta.updated_at`. Добавить и в HTML-sitemap, и в XML.
- **robots**: `draft` → noindex (на уровне страницы). `/glossary?…` уже покрыт `Disallow: /*?`.
- **llms.txt** (`LlmsController`): добавить секцию со ссылками на глоссарий (усиливает цитируемость LLM-ами).
- **Внутренняя перелинковка**:
  - term → related_tools (в контент инструмента); term → related_terms.
  - tool → term: расширить логику блока «Related» в `CipherController` или добавить в контент шифра ссылки на 1–2 термина глоссария (можно через новый конфиг `glossary_related.php` по образцу `cipher_related.php`, ключ = slug инструмента → список слагов терминов). **Рекомендация v1:** отдельный конфиг-маппинг, чтобы не трогать БД-контент шифров.

---

## 7. Мультиязычность (без fallback)

**Правило: полный перевод на все локали — обязательная часть разработки терма.** Терм считается готовым, только когда существуют файлы `{slug}/{locale}.json` для всех локалей из `config('locale.locales')`. Частично переведённый терм не публикуется.

- **Никакого fallback-рендеринга** англ. контента под нац. URL — это by design исключает дубли и «тонкий контент».
- Чтение строго `{slug}/{locale}.json`. Если файла запрошенной локали нет → терм в этой локали недоступен: **404**, и он не попадает в индекс/sitemap этой локали.
- Практически, т.к. перевод — часть разработки терма, все локали появляются одновременно (один PR = терм на всех языках).
- Незавершённый терм до готовности всех языков либо не заливается вовсе, либо держится во всех локалях со `status: draft`; публикация (`published`) тоже происходит сразу на всех языках.
- hreflang проставляется по фактически существующим (published) локалям — при полном переводе это всегда полный набор.
- `category`-метки и UI-строки индекса — через `trans()` (ключи в `translates/{locale}.php`).

---

## 8. Перелинковка (сводка правил)

| Связь | Источник | Резолв |
|---|---|---|
| term → term | `related_terms[]` в файле | `GlossaryRepository::exists()`, битые скрываются |
| term → tool | `related_tools[]` в файле | slug `cat/alias` → URL напрямую; наличие проверяет `glossary:validate` |
| tool → term | `config/glossary_related.php` (маппинг slug инструмента → термины) | рендерится в блоке «Related» инструмента |
| индекс → term | `GlossaryRepository::index()` | группировка по `category` |

Все ссылки — через `locale_url()` для сохранения языкового префикса.

---

## 9. Производительность и кэш

- Один запрос страницы термина = 1–2 чтения файла (термин + возможный fallback) + резолв related (из уже кэшированного индекса, без чтения всех файлов).
- Индекс `/glossary` = 1 кэш-хит (`glossary.index.{locale}`); на miss — глоб + чтение только `meta`+`short` каждого файла.
- OPcache на JSON не распространяется, поэтому кэш обязателен на проде (Memcache уже сконфигурирован).
- Инвалидация: `flush-cache` в админке + автоматически при деплое (добавить сброс glossary-ключей в деплой-хук или использовать версионированный префикс кэша).

---

## 10. Workflow генерации контента LLM

**Контракт для LLM (документировать в `docs/glossary-term-json.md` по образцу `docs/cipher-content-json.md`):**
- Строгое соответствие схеме `glossary-term.v1`.
- `en.json` пишется первым как эталон (`default_language: en`), остальные локали — перевод с сохранением slug'ов в `related_*`.
- Список допустимых `category` фиксирован.
- HTML в `body[].html` — ограниченный набор тегов (`p, ul, ol, li, strong, em, code, a`); никаких `script`/inline-стилей.
- `related_tools` — только реально существующие slug'и (список инструментов можно отдавать LLM из `route:list` или из `storage/content/*`).

**Пайплайн:**
1. LLM генерирует `en.json` для набора терминов.
2. `php bin/console glossary:validate` (см. §11) — локально/в CI.
3. Перевод на остальные локали (LLM), повторная валидация.
4. PR → review → merge → деплой → flush-cache.

**Стартовый список терминов v1 (40–60), сгруппировано:**
- *Concepts:* plaintext, ciphertext, key, encryption, decryption, cipher vs code, symmetric vs asymmetric, block vs stream, nonce/IV, salt, entropy, avalanche effect.
- *Classical:* substitution cipher, transposition cipher, polyalphabetic cipher, monoalphabetic cipher, keystream, tabula recta, rotor machine.
- *Cryptanalysis:* frequency analysis, Kasiski examination, index of coincidence, brute force, known-plaintext attack, ciphertext-only attack, crib.
- *Encoding:* Base64, hexadecimal, binary, ASCII, Unicode, URL encoding, character set/charset.
- *Hashing:* hash function, checksum, HMAC, salt, rainbow table, collision, key derivation function.

---

## 11. Ops и валидация (вместо админ-CRUD)

Команда `glossary:validate` (`private/app/Console/Commands/`, регистрация в `commands.php`):
- Каждый файл соответствует схеме `glossary-term.v1` (обязательные поля, типы).
- `meta.term_slug` == имя папки; `meta.language` == имя файла.
- Все `related_terms` существуют.
- Все `related_tools` существуют (сверка со списком инструментов из `storage/content/*` или БД read-only при доступности).
- Каждый термин имеет `en.json` (эталон).
- Возвращает ненулевой код при ошибках → подключить в CI (`.github/workflows`).

Опционально read-only обзорная страница в админке (список терминов + статус) — **не** редактор. В v1 можно опустить.

---

## 12. Критерии приёмки (Definition of Done)

- [ ] `/glossary` и `/glossary/{term}` открываются на всех 8 локалях; корректный локальный префикс.
- [ ] Маршруты не перехватываются catch-all'ами шифров.
- [ ] Отсутствующий термин → 404; `draft` → noindex.
- [ ] canonical, hreflang (только для существующих локалей), x-default, OG корректны.
- [ ] JSON-LD `DefinedTerm`/`DefinedTermSet` + `BreadcrumbList` (+ `FAQPage` при FAQ) валидны в Rich Results Test.
- [ ] Термины и `/glossary` присутствуют в `sitemap.xml` (кроме draft) с корректным lastmod.
- [ ] Перелинковка term↔term и term→tool работает; битые ссылки скрыты.
- [ ] Кэш работает; `flush-cache` сбрасывает глоссарий.
- [ ] `glossary:validate` проходит на стартовом наборе; подключён в CI.
- [ ] Контент читается только из файлов — ни одной таблицы БД, миграции или import-команды не добавлено.

---

## 13. План реализации (по шагам)

1. **Схема и доки:** зафиксировать `glossary-term.v1`, написать `docs/glossary-term-json.md`.
2. **Репозиторий:** `App\Glossary\GlossaryRepository` (файлы + кэш), зарегистрировать в `services.php`.
3. **Контроллер + маршруты:** `GlossaryController` (`index`, `show`), маршруты выше catch-all, регистрация в `services.php`.
4. **Шаблоны:** `glossary/index.tpl`, `glossary/show.tpl`, карточка; переводы UI-строк во все `translates/*`.
5. **SEO:** JSON-LD в контроллере; расширить `SitemapController` (XML + HTML); `LlmsController`; проверить генерацию `locale_urls` для glossary.
6. **Перелинковка:** `config/glossary_related.php` + рендер в блоке «Related» инструментов.
7. **Валидация:** команда `glossary:validate`, регистрация в `commands.php`, подключение в CI.
8. **Контент:** LLM генерирует стартовые 40–60 терминов (en → перевод), валидация, PR.
9. **Навигация:** пункт в футер (и/или меню), ключ `MENU_GLOSSARY`.
10. **Приёмка:** пройти чеклист §12; Rich Results Test; Search Console (submit sitemap).

---

## 14. Принятые решения

1. **URL сегмент:** `/glossary` для всех локалей (локализованные slug'и — возможный v2).
2. **Перевод:** полный перевод на все локали — обязательная часть разработки терма; fallback не используется (см. §7). Незавершённый терм либо не публикуется, либо держится во всех локалях в `status: draft` до готовности.
3. **Навигация:** только футер (в главное меню не выносим).
4. **Обратные ссылки tool→term:** включаются сразу в v1 (через `config/glossary_related.php`).
5. **Локализованные `category`-метки:** через ключи переводов; список категорий зафиксирован в §15.

---

## 15. ТОП-50 терминов v1 (зафиксировано)

**Правила:**
- **`slug` канонический и неизменный** — имя папки и значение `meta.term_slug`. Менять после публикации нельзя (только через 301-редирект в менеджере редиректов админки).
- Названия ниже — целевые значения `term.name` для каждой локали (в JSON-файле). `meta_title`/`meta_description`/`body` LLM пишет отдельно по контракту §10.
- **`category`** (для группировки на `/glossary` и меток UI) — одно из: `concepts`, `classical`, `cryptanalysis`, `encoding`, `hashing`. Ключи переводов меток: `GLOSSARY_CAT_CONCEPTS`, `GLOSSARY_CAT_CLASSICAL`, `GLOSSARY_CAT_CRYPTANALYSIS`, `GLOSSARY_CAT_ENCODING`, `GLOSSARY_CAT_HASHING`.

### 15.1. Concepts — Основные понятия (14)

| slug | EN | RU | DE | ES | FR | IT | PT | TR |
|---|---|---|---|---|---|---|---|---|
| plaintext | Plaintext | Открытый текст | Klartext | Texto plano | Texte en clair | Testo in chiaro | Texto claro | Düz metin |
| ciphertext | Ciphertext | Шифротекст | Geheimtext | Texto cifrado | Texte chiffré | Testo cifrato | Texto cifrado | Şifreli metin |
| encryption | Encryption | Шифрование | Verschlüsselung | Cifrado | Chiffrement | Cifratura | Criptografia | Şifreleme |
| decryption | Decryption | Расшифрование | Entschlüsselung | Descifrado | Déchiffrement | Decifratura | Descriptografia | Şifre çözme |
| cryptographic-key | Key | Ключ | Schlüssel | Clave | Clé | Chiave | Chave | Anahtar |
| cipher | Cipher | Шифр | Chiffre | Cifra | Chiffre | Cifrario | Cifra | Şifre |
| code | Code | Код | Code | Código | Code | Codice | Código | Kod |
| symmetric-encryption | Symmetric encryption | Симметричное шифрование | Symmetrische Verschlüsselung | Cifrado simétrico | Chiffrement symétrique | Cifratura simmetrica | Criptografia simétrica | Simetrik şifreleme |
| asymmetric-encryption | Asymmetric encryption | Асимметричное шифрование | Asymmetrische Verschlüsselung | Cifrado asimétrico | Chiffrement asymétrique | Cifratura asimmetrica | Criptografia assimétrica | Asimetrik şifreleme |
| block-cipher | Block cipher | Блочный шифр | Blockchiffre | Cifrado por bloques | Chiffrement par bloc | Cifrario a blocchi | Cifra de bloco | Blok şifre |
| stream-cipher | Stream cipher | Потоковый шифр | Stromchiffre | Cifrado de flujo | Chiffrement par flot | Cifrario a flusso | Cifra de fluxo | Akış şifresi |
| nonce | Nonce | Одноразовое число (nonce) | Nonce | Nonce | Nonce | Nonce | Nonce | Nonce |
| initialization-vector | Initialization vector (IV) | Вектор инициализации | Initialisierungsvektor | Vector de inicialización | Vecteur d'initialisation | Vettore di inizializzazione | Vetor de inicialização | Başlatma vektörü |
| entropy | Entropy | Энтропия | Entropie | Entropía | Entropie | Entropia | Entropia | Entropi |

### 15.2. Classical — Классические шифры (12)

| slug | EN | RU | DE | ES | FR | IT | PT | TR |
|---|---|---|---|---|---|---|---|---|
| substitution-cipher | Substitution cipher | Шифр замены | Substitutionschiffre | Cifrado por sustitución | Chiffrement par substitution | Cifrario a sostituzione | Cifra de substituição | Yerine koyma şifresi |
| transposition-cipher | Transposition cipher | Шифр перестановки | Transpositionschiffre | Cifrado por transposición | Chiffrement par transposition | Cifrario a trasposizione | Cifra de transposição | Yer değiştirme şifresi |
| monoalphabetic-cipher | Monoalphabetic cipher | Моноалфавитный шифр | Monoalphabetische Chiffre | Cifrado monoalfabético | Chiffrement monoalphabétique | Cifrario monoalfabetico | Cifra monoalfabética | Tek alfabeli şifre |
| polyalphabetic-cipher | Polyalphabetic cipher | Полиалфавитный шифр | Polyalphabetische Chiffre | Cifrado polialfabético | Chiffrement polyalphabétique | Cifrario polialfabetico | Cifra polialfabética | Çok alfabeli şifre |
| keystream | Keystream | Гамма (ключевой поток) | Schlüsselstrom | Flujo de clave | Suite chiffrante | Flusso di chiave | Fluxo de chave | Anahtar akışı |
| tabula-recta | Tabula recta | Таблица Виженера (tabula recta) | Tabula recta | Tabula recta | Table de Vigenère | Tabula recta | Tabula recta | Tabula recta |
| rotor-machine | Rotor machine | Роторная машина | Rotor-Chiffriermaschine | Máquina de rotores | Machine à rotors | Macchina a rotori | Máquina de rotores | Rotorlu makine |
| shift-cipher | Shift cipher | Шифр сдвига | Verschiebechiffre | Cifrado por desplazamiento | Chiffrement par décalage | Cifrario a scorrimento | Cifra de deslocamento | Kaydırma şifresi |
| key-schedule | Key schedule | Развёртывание ключа | Schlüsselplan | Programación de claves | Cadencement de clé | Espansione della chiave | Agenda de chaves | Anahtar çizelgesi |
| running-key-cipher | Running key cipher | Шифр бегущего ключа | Running-Key-Chiffre | Cifrado de clave corriente | Chiffre à clé courante | Cifrario a chiave scorrevole | Cifra de chave corrente | Akan anahtar şifresi |
| null-cipher | Null cipher | Нулевой шифр | Nullchiffre | Cifrado nulo | Chiffre nul | Cifrario nullo | Cifra nula | Boş şifre |
| steganography | Steganography | Стеганография | Steganografie | Esteganografía | Stéganographie | Steganografia | Esteganografia | Steganografi |

### 15.3. Cryptanalysis — Криптоанализ (10)

| slug | EN | RU | DE | ES | FR | IT | PT | TR |
|---|---|---|---|---|---|---|---|---|
| cryptanalysis | Cryptanalysis | Криптоанализ | Kryptoanalyse | Criptoanálisis | Cryptanalyse | Crittoanalisi | Criptoanálise | Kriptoanaliz |
| frequency-analysis | Frequency analysis | Частотный анализ | Häufigkeitsanalyse | Análisis de frecuencia | Analyse fréquentielle | Analisi delle frequenze | Análise de frequência | Frekans analizi |
| kasiski-examination | Kasiski examination | Метод Касиски | Kasiski-Test | Examen de Kasiski | Test de Kasiski | Esame di Kasiski | Exame de Kasiski | Kasiski incelemesi |
| index-of-coincidence | Index of coincidence | Индекс совпадений | Koinzidenzindex | Índice de coincidencia | Indice de coïncidence | Indice di coincidenza | Índice de coincidência | Rastlaşma indeksi |
| brute-force-attack | Brute-force attack | Полный перебор | Brute-Force-Angriff | Ataque de fuerza bruta | Attaque par force brute | Attacco a forza bruta | Ataque de força bruta | Kaba kuvvet saldırısı |
| known-plaintext-attack | Known-plaintext attack | Атака на основе открытого текста | Known-Plaintext-Angriff | Ataque de texto plano conocido | Attaque à texte clair connu | Attacco con testo in chiaro noto | Ataque de texto claro conhecido | Bilinen düz metin saldırısı |
| ciphertext-only-attack | Ciphertext-only attack | Атака на основе шифротекста | Ciphertext-only-Angriff | Ataque de solo texto cifrado | Attaque à texte chiffré seul | Attacco con solo testo cifrato | Ataque de somente texto cifrado | Yalnızca şifreli metin saldırısı |
| chosen-plaintext-attack | Chosen-plaintext attack | Атака на основе подобранного открытого текста | Chosen-Plaintext-Angriff | Ataque de texto plano elegido | Attaque à texte clair choisi | Attacco con testo in chiaro scelto | Ataque de texto claro escolhido | Seçilmiş düz metin saldırısı |
| crib | Crib (probable word) | Криб (вероятное слово) | Crib (wahrscheinliches Wort) | Crib (palabra probable) | Mot probable (crib) | Crib (parola probabile) | Crib (palavra provável) | Crib (olası kelime) |
| dictionary-attack | Dictionary attack | Атака по словарю | Wörterbuchangriff | Ataque de diccionario | Attaque par dictionnaire | Attacco a dizionario | Ataque de dicionário | Sözlük saldırısı |

### 15.4. Encoding — Кодирование (8)

| slug | EN | RU | DE | ES | FR | IT | PT | TR |
|---|---|---|---|---|---|---|---|---|
| base64 | Base64 | Base64 | Base64 | Base64 | Base64 | Base64 | Base64 | Base64 |
| hexadecimal | Hexadecimal | Шестнадцатеричная система | Hexadezimalsystem | Hexadecimal | Hexadécimal | Esadecimale | Hexadecimal | On altılık sistem |
| binary | Binary | Двоичная система | Binärsystem | Binario | Binaire | Binario | Binário | İkilik sistem |
| ascii | ASCII | ASCII | ASCII | ASCII | ASCII | ASCII | ASCII | ASCII |
| unicode | Unicode | Юникод | Unicode | Unicode | Unicode | Unicode | Unicode | Unicode |
| url-encoding | URL encoding | URL-кодирование | URL-Kodierung | Codificación URL | Encodage d'URL | Codifica URL | Codificação de URL | URL kodlaması |
| character-encoding | Character encoding | Кодировка символов | Zeichenkodierung | Codificación de caracteres | Codage des caractères | Codifica dei caratteri | Codificação de caracteres | Karakter kodlaması |
| bit | Bit | Бит | Bit | Bit | Bit | Bit | Bit | Bit |

### 15.5. Hashing — Хеширование (6)

| slug | EN | RU | DE | ES | FR | IT | PT | TR |
|---|---|---|---|---|---|---|---|---|
| hash-function | Hash function | Хеш-функция | Hashfunktion | Función hash | Fonction de hachage | Funzione di hash | Função hash | Özet (hash) fonksiyonu |
| checksum | Checksum | Контрольная сумма | Prüfsumme | Suma de verificación | Somme de contrôle | Checksum (somma di controllo) | Soma de verificação | Sağlama toplamı |
| hmac | HMAC | HMAC | HMAC | HMAC | HMAC | HMAC | HMAC | HMAC |
| salt | Salt | Соль (salt) | Salt | Sal (salt) | Sel (salt) | Sale (salt) | Sal (salt) | Tuz (salt) |
| rainbow-table | Rainbow table | Радужная таблица | Rainbow Table | Tabla arcoíris | Table arc-en-ciel | Tabella arcobaleno | Tabela arco-íris | Gökkuşağı tablosu |
| hash-collision | Hash collision | Коллизия хешей | Hash-Kollision | Colisión de hash | Collision de hachage | Collisione di hash | Colisão de hash | Özet çakışması |

**Итого: 50 терминов** (14 + 12 + 10 + 8 + 6).

> Порядок реализации контента: сначала `en.json` для всех 50 (эталон + перелинковка `related_terms`/`related_tools`), затем перевод на остальные 7 локалей, `glossary:validate`, PR. Названия из таблиц — стартовые; при написании `body` LLM может уточнить формулировку, но `slug` остаётся неизменным.
