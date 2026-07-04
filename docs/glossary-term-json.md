# Формат JSON термина глоссария (`glossary-term.v1`)

Контракт файлов глоссария для генерации LLM. Контент **файловый, без БД**.
Связано: [plans/glossary-spec.md](plans/glossary-spec.md).

## Расположение

```
private/storage/content/glossary/{term-slug}/{locale}.json
```

- `{term-slug}` — канонический slug (имя папки), `[a-z0-9-]+`, **неизменный** после публикации.
- `{locale}` — код локали из `config('locale.locales')` (сейчас: `en, ru, de, es, fr, it, pt, tr`).
- **Полный перевод обязателен:** файл должен существовать для **всех** локалей. Нет fallback — отсутствующая локаль даёт 404.

## Схема

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
    "short": "Одно предложение-определение: карточка индекса, тултип, fallback meta_description.",
    "meta_title": "Frequency Analysis — Definition | CiphersOnline",
    "meta_description": "Что такое частотный анализ, как он работает и где применяется."
  },
  "body": [
    { "sort_order": 10, "type": "definition",   "title": "Definition",   "html": "<p>…</p>" },
    { "sort_order": 20, "type": "how_it_works", "title": "How it works", "html": "<p>…</p>" }
  ],
  "faq": [
    { "question": "…", "answer": "<p>…</p>" }
  ],
  "related_terms": ["index-of-coincidence", "kasiski-examination"],
  "related_tools": [
    { "slug": "text-analysis/frequency-analysis", "label": "Frequency Analysis tool" },
    { "slug": "classical-ciphers/vigenere", "label": "Vigenère cipher" }
  ],
  "tags": ["cryptanalysis"],
  "category": "cryptanalysis"
}
```

## Правила полей

| Поле | Обяз. | Правила |
|---|---|---|
| `meta.schema` | да | Ровно `glossary-term.v1`. |
| `meta.term_slug` | да | == имя папки. |
| `meta.language` | да | == имя файла (локаль). |
| `meta.status` | да | `draft` \| `published`. `draft` не отдаётся публично (404, вне индекса/sitemap). |
| `meta.updated_at` | да | `YYYY-MM-DD`. |
| `term.name` | да | Локализованное название (см. §15 в spec — зафиксированные названия). |
| `term.aliases` | нет | Массив синонимов. |
| `term.short` | да | Одно предложение. |
| `term.meta_title` / `meta_description` | нет | Fallback: `name` / `short`. |
| `body[]` | да | Непустой массив. `html` — доверенный HTML (`p, ul, ol, li, strong, em, code, a`), рендерится `nofilter`. `sort_order` задаёт порядок. |
| `faq[]` | нет | `question` (plain), `answer` (HTML). Идёт в `FAQPage` schema. |
| `related_terms[]` | нет | slug'и других терминов. Несуществующие пропускаются рантаймом; ловятся `glossary:validate`. |
| `related_tools[]` | нет | Объекты `{slug, label}`. `slug` вида `category/alias` (реальный инструмент). `label` — локализованный. URL строится без БД. |
| `category` | да | Одно из: `concepts`, `classical`, `cryptanalysis`, `encoding`, `hashing`. |
| `tags[]` | нет | Свободные метки. |

## Категории → метки UI

Метки берутся из переводов: `GLOSSARY_CAT_CONCEPTS`, `GLOSSARY_CAT_CLASSICAL`, `GLOSSARY_CAT_CRYPTANALYSIS`, `GLOSSARY_CAT_ENCODING`, `GLOSSARY_CAT_HASHING`.

## Workflow

1. Сгенерировать `en.json` (эталон) для набора терминов.
2. Перевести на остальные 7 локалей (slug'и в `related_*` сохранять).
3. `php bin/console glossary:validate` (локально / в CI).
4. PR → merge → деплой → сброс кэша (`/admin` → «Очистить кэш» или `config:clear`).

## Что проверяет `glossary:validate`

- Соответствие схеме, `term_slug`/`language` == папка/файл.
- Наличие файла **всех** локалей для каждого термина.
- `category`/`status` из допустимых значений.
- `related_terms` указывают на существующие термины.
- `related_tools` указывают на существующие инструменты (`storage/content/*`).

## Рантайм-заметки

- Кэш: `glossary.raw.{slug}.{locale}`, `glossary.index.{locale}`. Сброс — `flush-cache`.
- Только `status: published` попадает в индекс, sitemap, страницу термина.
- `slug` менять нельзя — при переименовании завести 301 в менеджере редиректов админки.
