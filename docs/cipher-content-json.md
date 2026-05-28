# Формат JSON для контента страницы шифра

Этот формат нужен для внешнего редактирования текстов (например, в Atlas) и обратного импорта в БД.

## Как получить файл

```bash
php bin/console cipher:content:export <category_alias> <cipher_alias> <language> [output_path]
```

Пример:

```bash
php bin/console cipher:content:export classical-ciphers playfair en
```

По умолчанию файл будет сохранён в:

`private/storage/content/<category_alias>.<cipher_alias>.<language>.json`

## Структура

```json
{
  "meta": {
    "schema": "cipher-content.v1",
    "category_alias": "classical-ciphers",
    "cipher_alias": "playfair",
    "language": "en"
  },
  "cipher_translation": {
    "id": 12,
    "data": {
      "name": "Playfair Cipher",
      "name_short": "Playfair",
      "description": "...",
      "description_stort": "...",
      "meta_title": "...",
      "meta_description": "..."
    }
  },
  "blocks": [
    {
      "id": 100,
      "sort_order": 10,
      "published": true,
      "data": {
        "title": "How it works",
        "text": "<p>...</p>"
      }
    }
  ],
  "faq": [
    {
      "id": 200,
      "sort_order": 10,
      "published": true,
      "data": {
        "question": "What is Playfair?",
        "answer": "<p>...</p>"
      }
    }
  ],
  "examples": [
    {
      "id": 300,
      "sort_order": 10,
      "published": true,
      "data": {
        "title": "Basic example",
        "input": "HELLO",
        "output": "KCNVMP",
        "description": "..."
      }
    }
  ],
  "tags": [
    {
      "id": 400,
      "sort_order": 10,
      "published": true,
      "data": {
        "tag": "digraph substitution"
      }
    }
  ]
}
```

## Правила редактирования

1. Не менять `id` у сущностей.
2. Менять только поля в `data`.
3. `sort_order` и `published` в этом процессе информационные, импорт их не меняет.
4. Пустые строки в `data` допустимы: если все текстовые поля сущности пусты, перевод для этой сущности и языка будет удалён.
5. Для расширения секций `blocks`, `faq`, `examples`, `tags` можно добавлять новые объекты без `id` (или с `id: 0`): при импорте будет создана базовая запись сущности и затем перевод для нужного языка.

## Импорт в БД

```bash
php bin/console cipher:content:import <json_path> [--dry-run]
```

Пример проверки без записи:

```bash
php bin/console cipher:content:import private/storage/content/classical-ciphers.playfair.en.json --dry-run
```
