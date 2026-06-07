# PHP Skeleton

Лёгкий MVC-скелет на PHP 8.3 без внешнего фреймворка. Все компоненты написаны с нуля: роутер, контейнер, ORM-миграции, очередь задач, кеш, HTTP-клиент, i18n, аутентификация, OpenAPI-генератор.

## Требования

| Зависимость | Версия |
|-------------|--------|
| PHP         | ≥ 8.3  |
| Расширения  | `pdo`, `pdo_sqlite` / `pdo_mysql`, `curl`, `mbstring`, `xmlwriter` |
| Composer    | ≥ 2    |
| Node.js     | ≥ 20 (только для фронтенда) |

Опционально: Memcached или Redis (для кеша и сессий), MySQL 8.0 (вместо SQLite).

## Быстрый старт (локально)

```bash
# 1. Зависимости
composer install
npm install

# 2. Конфигурация
cp private/.env.example private/.env

# 3. База данных
make db-setup    # создаёт SQLite-файл
make migrate     # применяет миграции

# 4. Серверы
make serve       # PHP built-in: http://127.0.0.1:8080
make dev         # Vite HMR:     http://127.0.0.1:5173
```

## Быстрый старт (Docker)

```bash
cp private/.env.example private/.env
make docker-build
make docker-up        # http://localhost:8080
make docker-migrate
```

С MySQL:

```bash
# В private/.env: DB_CONNECTION=mysql, DB_HOST=mysql
make docker-mysql
make docker-migrate
```

Подробнее о продакшн-деплое — в [docs/deployment.md](docs/deployment.md).

## Команды

```bash
# PHP
make install          # composer install
make autoload         # composer dump-autoload
make serve            # php -S 127.0.0.1:8080
make test             # phpunit
make lint             # pint --test
make lint-fix         # pint
make phpstan          # статический анализ

# Миграции
make migrate          # применить
make migrate-rollback # откатить последний батч
make migrate-status   # статус

# Фронтенд
make npm-install      # npm install
make dev              # Vite dev-сервер (HMR)
make build            # продакшн-сборка → public/build/

# Docker
make docker-build     # собрать образы
make docker-up        # запустить (SQLite)
make docker-down      # остановить
make docker-mysql     # запустить + MySQL
make docker-shell     # sh в контейнере app
make docker-logs      # логи
make docker-migrate   # миграции в контейнере
make docker-test      # phpunit в контейнере
```

### Консольные команды

```bash
php bin/console                                  # список команд
php bin/console migrate                          # миграции
php bin/console queue:work [--queue=default]     # воркер очереди
php bin/console openapi:generate                 # → public/openapi.json

# Генераторы boilerplate
php bin/console make:controller UserController
php bin/console make:controller Api/UserController
php bin/console make:middleware RateLimitMiddleware
php bin/console make:migration create_posts_table
php bin/console make:repository PostRepository
php bin/console make:job ProcessPaymentJob
```

### Импорт SQLite в MySQL

Команда `db:import-sqlite` переносит данные из SQLite-файла в активную MySQL-базу. Это удобно для первичного наполнения или синхронизации окружения, когда нужно сохранить реальные `id` сущностей между SQLite и MySQL.

```bash
# Показать план импорта без записи в MySQL
php bin/console db:import-sqlite

# Импортировать SQLite-файл из конфига database.connections.sqlite.database
php bin/console db:import-sqlite --force

# Импортировать конкретный SQLite-файл
php bin/console db:import-sqlite private/storage/database/database.sqlite --force
php bin/console db:import-sqlite --source=private/storage/database/database.sqlite --force

# Перед импортом очистить выбранные MySQL-таблицы
php bin/console db:import-sqlite --force --clear

# Импортировать только указанные таблицы
php bin/console db:import-sqlite --force --tables=ciphers,ciphers_blocks,ciphers_blocks_translations

# Исключить таблицы из импорта
php bin/console db:import-sqlite --force --except=users,jobs,failed_jobs

# Изменить размер пачки чтения из SQLite
php bin/console db:import-sqlite --force --batch=1000
```

Возможности и ограничения:

- Работает только когда активное подключение приложения — MySQL (`DB_CONNECTION=mysql`).
- Без `--force` команда выполняет dry-run: показывает источник, целевую БД, режим очистки и список таблиц с количеством строк.
- По умолчанию источник берётся из `database.connections.sqlite.database`; также можно передать путь первым аргументом или через `--source=...`.
- Импортируются только таблицы, которые есть и в SQLite, и в текущей MySQL-базе.
- Для каждой таблицы переносятся только общие колонки источника и цели; таблицы без общих колонок пропускаются.
- Таблицы импортируются в порядке, учитывающем основные зависимости (`ciphers`, переводы, блоки, примеры, FAQ, теги и т. д.).
- На время импорта отключаются MySQL foreign key checks, запись выполняется в транзакции.
- После успешного импорта команда обновляет `AUTO_INCREMENT` по максимальному `id` в каждой импортированной таблице.
- `--clear` удаляет данные из целевых MySQL-таблиц перед импортом, поэтому используйте его только после dry-run и бэкапа.

### Контент страниц шифров через JSON

Для задач, где тексты редактируются вне админки (например, через Atlas), используйте экспорт/импорт JSON:

```bash
# Экспорт контента страницы шифра
php bin/console cipher:content:export <category_alias> <cipher_alias> <language> [output_path]

# Пример
php bin/console cipher:content:export classical-ciphers playfair en

# Проверка импорта без записи в БД
php bin/console cipher:content:import private/storage/content/classical-ciphers.playfair.en.json --dry-run

# Боевой импорт
php bin/console cipher:content:import private/storage/content/classical-ciphers.playfair.en.json
```

Подробный формат JSON и правила редактирования описаны в [docs/cipher-content-json.md](docs/cipher-content-json.md).
Для расширения секций `blocks`, `faq`, `examples`, `tags` добавляйте новые элементы без `id` (или с `id: 0`) только в файле, где `meta.language == meta.default_language` (обычно `en`).
В остальных языках добавлять новые сущности нельзя: там нужно переводить уже существующие `id`.

### Контент страниц категорий через JSON

Для категорий шифров доступен аналогичный экспорт/импорт:

```bash
# Экспорт контента категории
php bin/console cipher:category:content:export <category_alias> <language> [output_path]

# Проверка импорта без записи в БД
php bin/console cipher:category:content:import private/storage/content/categories/encoding.en.json --dry-run

# Боевой импорт
php bin/console cipher:category:content:import private/storage/content/categories/encoding.en.json
```

Подробный формат описан в [docs/cipher-category-content-json.md](docs/cipher-category-content-json.md).

### Skill для локализации JSON

В репозитории есть skill для локализации контента шифров с обязательной валидацией примеров через API:

- [docs/skills/cipher-content-localizer/SKILL.md](docs/skills/cipher-content-localizer/SKILL.md)

Ключевая идея:
- локализация, а не дословный перевод;
- адаптация `examples[].data.key/input/description` под целевой язык;
- обязательный пересчёт `examples[].data.output` через `/api/tools/{cipher}`.

Скрипт пересчёта:

```bash
python3 docs/skills/cipher-content-localizer/scripts/recompute_example_outputs.py \
  --source /abs/path/classical-ciphers.playfair.en.json \
  --target /abs/path/classical-ciphers.playfair.ru.json \
  --base-url http://127.0.0.1:8080
```

## Архитектура

```
public/             # document root (index.php, build/)
private/
  app/              # исходный код (App\, PSR-4)
    Auth/           # аутентификация
    Cache/          # кеш (Null / Memcached)
    Console/        # CLI-команды и генераторы
    Container/      # сервис-локатор
    Database/       # PDO-обёртка, Schema/Blueprint, миграции, очередь
    Http/           # роутер, pipeline, middleware, сессии, HTTP-клиент
    I18n/           # переводы (ICU MessageFormat, plural rules)
    Log/            # логгер, глобальный обработчик ошибок
    OpenApi/        # генератор OpenAPI 3.0
    Queue/          # очередь задач на БД
    Validation/     # валидатор
    View/           # Smarty 5 + Vite
  config/           # конфиги (services, routes, middleware, ...)
  database/migrations/
  resources/        # js/, css/, views/
  storage/          # логи, кеш шаблонов, сессии
docker/             # nginx.conf, php.ini
```

**Жизненный цикл запроса:** `public/index.php` → глобальный `Pipeline` → `Router::dispatch()` → per-route `Pipeline` → метод контроллера → `Response::send()`.

Три под-приложения по URL-префиксу: `/api/*` (JSON), `/admin/*` (HTML + AdminMiddleware), остальное (HTML).

## Тестирование

```bash
make test                                     # все тесты
vendor/bin/phpunit --filter TestClassName     # один тест
make docker-test                              # в Docker
```

Тесты расположены в `tests/Unit/` и `tests/Feature/`. Интеграционные тесты, требующие HTTP, запускают PHP built-in server на `127.0.0.1:18080` через `proc_open`.

## CI

GitHub Actions запускает три джоба на каждый push:

| Джоб    | Инструмент              |
|---------|-------------------------|
| Lint    | Laravel Pint            |
| Analyze | PHPStan (level по конфигу) |
| Tests   | PHPUnit 11              |
