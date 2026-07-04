# Формат JSON статьи-гайда (`guide-article.v1`)

Контракт файлов раздела `/guides` для генерации LLM. Контент **файловый, без БД** —
по образцу глоссария ([glossary-term-json.md](glossary-term-json.md)).
План статей: [plans/guides-spec.md](plans/guides-spec.md).

## Расположение

```
private/storage/content/guides/{guide-slug}/{locale}.json
```

- `{guide-slug}` — канонический slug (имя папки), `[a-z0-9-]+`, **неизменный** после публикации.
- `{locale}` — код локали из `config('locale.locales')` (сейчас: `en, ru, de, es, fr, it, pt, tr`).
- **Полный перевод обязателен:** файл должен существовать для **всех** локалей. Нет fallback — отсутствующая локаль даёт 404.

## Схема

```json
{
  "meta": {
    "schema": "guide-article.v1",
    "guide_slug": "caesar-cipher-manual-decryption",
    "language": "en",
    "default_language": "en",
    "status": "published",
    "updated_at": "2026-07-04",
    "published_at": "2026-07-04"
  },
  "guide": {
    "title": "How to Decrypt a Caesar Cipher by Hand",
    "excerpt": "Одно-два предложения: карточка индекса, подзаголовок, fallback meta_description.",
    "meta_title": "How to Decrypt a Caesar Cipher by Hand — Step by Step | CiphersOnline",
    "meta_description": "...",
    "reading_time": 8
  },
  "body": [
    { "sort_order": 10, "type": "section", "title": "Introduction", "html": "<p>…</p>" },
    { "sort_order": 20, "type": "section", "title": "Method 1: Brute force", "html": "<p>…</p>" }
  ],
  "faq": [
    { "question": "…", "answer": "<p>…</p>" }
  ],
  "related_guides": ["vigenere-cipher-complete-guide"],
  "related_terms": ["shift-cipher", "frequency-analysis"],
  "related_tools": [
    { "slug": "classical-ciphers/caesar", "label": "Caesar Cipher tool" },
    { "slug": "classical-ciphers/rot13", "label": "ROT13" }
  ],
  "tags": ["caesar", "beginner"],
  "category": "how-to"
}
```

## Правила полей

| Поле | Обяз. | Правила |
|---|---|---|
| `meta.schema` | да | Ровно `guide-article.v1`. |
| `meta.guide_slug` | да | == имя папки. |
| `meta.language` | да | == имя файла (локаль). |
| `meta.status` | да | `draft` \| `published`. `draft` не отдаётся публично (404, вне индекса/sitemap). |
| `meta.updated_at` | да | `YYYY-MM-DD`. Используется как `lastmod` в sitemap и `dateModified` в Article schema. |
| `meta.published_at` | нет | `YYYY-MM-DD`. `datePublished` в Article + сортировка индекса (свежие сверху). Fallback: `updated_at`. |
| `guide.title` | да | Локализованный заголовок (см. §4 в spec — зафиксированные названия). |
| `guide.excerpt` | да | Одно-два предложения. Карточка индекса, подзаголовок, fallback `meta_description`. |
| `guide.meta_title` / `meta_description` | нет | Fallback: `title` / `excerpt`. |
| `guide.reading_time` | нет | Целое число минут (оценка). Показывается в карточке и шапке. |
| `body[]` | да | Непустой массив. `html` — доверенный HTML (`p, ul, ol, li, strong, em, code, a, h3, blockquote, table`), рендерится `nofilter`. `sort_order` задаёт порядок. `title` рендерится как `<h2>`. |
| `faq[]` | нет | `question` (plain), `answer` (HTML). Идёт в `FAQPage` schema. |
| `related_guides[]` | нет | slug'и других статей. Несуществующие пропускаются рантаймом; ловятся `guides:validate`. |
| `related_terms[]` | нет | slug'и терминов глоссария (строки) либо объекты `{slug, label}`. Ведут на `/glossary/{slug}`. |
| `related_tools[]` | нет | Объекты `{slug, label}`. `slug` вида `category/alias` (реальный инструмент). `label` — локализованный. URL строится без БД. |
| `category` | да | Одно из: `how-to`, `deep-dive`, `history`, `lists`. |
| `tags[]` | нет | Свободные метки. |

## Категории → метки UI

Метки берутся из переводов: `GUIDES_CAT_HOW_TO`, `GUIDES_CAT_DEEP_DIVE`, `GUIDES_CAT_HISTORY`, `GUIDES_CAT_LISTS`.

## Обратные ссылки «инструмент → гайд»

`config/guide_related.php`: ключ — slug инструмента `category/alias`, значение — список slug'ов статей.
Рендерится блоком «Из наших гайдов» на странице инструмента (`CipherController::buildGuideLinks`).

## Workflow

1. Сгенерировать `en.json` (эталон) для набора статей.
2. Перевести на остальные 7 локалей (slug'и в `related_*` сохранять).
3. `php bin/console guides:validate` (локально / в CI).
4. PR → merge → деплой → сброс кэша (`/admin` → «Очистить кэш» или `config:clear`).

## Что проверяет `guides:validate`

- Соответствие схеме, `guide_slug`/`language` == папка/файл.
- Наличие файла **всех** локалей для каждой статьи.
- `category`/`status` из допустимых значений; непустые `title`/`excerpt`/`body`.
- `related_guides` указывают на существующие статьи.
- `related_tools` указывают на существующие инструменты (`storage/content/*`).
- `related_terms` указывают на существующие термины глоссария (если глоссарий присутствует).

## Рантайм-заметки

- Кэш: `guides.raw.{slug}.{locale}`, `guides.index.{locale}`. Сброс — `flush-cache`.
- Только `status: published` попадает в индекс, sitemap, llms.txt, страницу статьи.
- Индекс сортируется по `published_at`/`updated_at` (свежие сверху).
- `slug` менять нельзя — при переименовании завести 301 в менеджере редиректов админки.
- SEO: `Article` + `BreadcrumbList` + (при FAQ) `FAQPage` в контроллере; на индексе — `CollectionPage`/`ItemList`.
