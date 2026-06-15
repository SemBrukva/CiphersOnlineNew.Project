# Формат JSON для контента страницы категории шифров

Этот формат нужен для внешнего редактирования текстов категории и обратного импорта в БД.

## Как получить файл

```bash
php bin/console cipher:category:content:export <category_alias> <language> [output_path]
```

Пример:

```bash
php bin/console cipher:category:content:export encoding en
```

По умолчанию файл будет сохранён в:

`private/storage/content/<category_alias>/category.<language>.json`

## Структура

JSON содержит:

- `category_translation` — название, краткое название, описание и SEO-поля категории;
- `blocks` — информационные блоки;
- `tasks` — популярные задачи со связанным шифром в `cipher_alias`;
- `used_together` — связки инструментов в `first_cipher_alias` и `second_cipher_alias`;
- `faq` — вопросы и ответы категории.

Для каждой переводимой сущности тексты находятся внутри `data`.

## Правила редактирования

1. Не менять `id` у существующих сущностей.
2. Менять только поля в `data`.
3. `sort_order`, `published` и alias связанных шифров у существующих сущностей информационные: импорт их не меняет.
4. Пустые строки в `data` допустимы: если все текстовые поля сущности пусты, перевод для языка будет удалён.
5. В секции `blocks`, `tasks`, `used_together`, `faq` можно добавлять объекты без `id` или с `id: 0`.
6. Добавление новых объектов разрешено только когда `meta.language == meta.default_language`.
7. Для новых `tasks` нужно указать `cipher_alias`, для новых `used_together` — `first_cipher_alias` и `second_cipher_alias`.

## Импорт в БД

```bash
php bin/console cipher:category:content:import <json_path> [--dry-run]
```

Пример проверки без записи:

```bash
php bin/console cipher:category:content:import private/storage/content/encoding/category.en.json --dry-run
```
