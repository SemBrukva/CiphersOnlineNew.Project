# AGENTS.md

Этот файл содержит инструкции для Codex (Codex.ai/code) при работе с данным репозиторием.

## Команды

```bash
# PHP
make install          # composer install
make autoload         # composer dump-autoload
make serve            # php -S 127.0.0.1:8080 -t public public/index.php
make test             # vendor/bin/phpunit
make migrate          # php bin/console migrate
make migrate-rollback # php bin/console migrate:rollback
make migrate-status   # php bin/console migrate:status
make db-setup         # php bin/setup.php

# Фронтенд (Vite)
make npm-install      # npm install
make dev              # npm run dev  — Vite dev-сервер с HMR
make build            # npm run build — продакшн-сборка в public/build/
```

Запуск одного теста: `vendor/bin/phpunit --filter TestClassName`

## Архитектура

Лёгкий MVC-скелет на PHP 8.3 без фреймворка — все компоненты написаны с нуля.

**Пространство имён автозагрузки:** `App\` → `private/app/` (PSR-4).

**Константы путей** (определяются в `private/bootstrap.php`): `BASE_PATH`, `PRIVATE_PATH`, `PUBLIC_PATH`, `STORAGE_PATH`, `CONFIG_PATH`, `RESOURCE_PATH`, `APP_PATH`, `DATABASE_PATH`.

---

### Жизненный цикл запроса

`public/index.php` разветвляется на три под-приложения по URL-префиксу:

| Префикс | Роутер | Стек middleware | Тип ответа |
|---------|--------|-----------------|------------|
| `/api/*` | `ApiRouter` | `config/api_middleware.php` | JSON |
| `/admin/*` | `AdminRouter` | `config/middleware.php` | HTML |
| всё остальное | `Router` | `config/middleware.php` | HTML |

Для каждой ветки: глобальный `Pipeline` → `Router::dispatch()` → per-route `Pipeline` → метод контроллера → `Response::send()`.

---

### Ключевые компоненты

**`Container`** (`private/app/Container/`) — сервис-локатор. `set(id, factory)` регистрирует ленивую фабрику; `get(id)` разрешает и кэширует как синглтон. Все привязки — в `private/config/services.php`.

**`Config`** (`private/app/Config/`) — загружает каждый `private/config/*.php` по имени файла. Доступ через точечную нотацию: `config('database.connections.mysql')`.

**`Router` / `AdminRouter` / `ApiRouter`** (`private/app/Http/`) — маршрутизация на основе плоского массива. `AdminRouter` и `ApiRouter` — маркерные подклассы; ключи маршрутов — относительные пути (префикс добавляется в `services.php`). `AdminRouter` автоматически добавляет `AdminMiddleware` к каждому маршруту. Файлы конфигурации: `routes.php`, `admin_routes.php`, `api_routes.php`. Формат маршрута:
```php
'GET /path/{id}' => ['controller' => Foo::class, 'method' => 'show', 'middleware' => [AuthMiddleware::class]]
```

**`Pipeline`** (`private/app/Http/Pipeline.php`) — выстраивает цепочку классов `MiddlewareInterface`. Используется дважды на каждый запрос: глобальный стек и per-route стек. Все middleware реализуют `process(Request $request, callable $next): Response`.

**`Database`** (`private/app/Database/Database.php`) — ленивая PDO-обёртка, подключается при первом запросе. Методы: `fetch()` / `fetchAll()` / `execute()` / `insert()` / `transaction(callable)`. Поддерживает SQLite и MySQL; драйвер выбирается через `config('database.default')`.

**`Tables`** (`private/app/Database/Tables.php`) — строковые константы для имён таблиц (`Tables::USERS`, `Tables::SYSTEM_PAGES`). Всегда использовать их вместо сырых строк.

**`Cache`** (`private/app/Cache/`) — кеширование произвольных данных и SQL-результатов. `CacheInterface` с реализациями `NullCache` (заглушка, для local) и `MemcacheCache` (через расширение Memcached). Драйвер выбирается через `config('cache.driver')` (`null` | `memcache`). Хелпер: `cache()`. Основные методы: `get(key, default)`, `set(key, value, ttl)`, `has(key)`, `delete(key)`, `remember(key, ttl, fn)`, `flush()`. Конфигурация в `config/cache.php`, переменные: `CACHE_DRIVER`, `CACHE_PREFIX`, `CACHE_TTL`, `MEMCACHE_HOST`, `MEMCACHE_PORT`.

**`View`** (`private/app/View/View.php`) — обёртка над Smarty 5. Шаблоны в `private/resources/views/`, компиляция в `private/storage/cache/templates/`. `share(key, value)` устанавливает переменную для всех последующих рендеров. Регистрирует Smarty-функцию `{vite}`.

**`ViteAssets`** (`private/app/View/ViteAssets.php`) — генерирует HTML-теги ассетов. Если существует `public/build/hot` → dev-режим (проксирует на Vite-сервер); иначе читает `public/build/.vite/manifest.json`. Использование в Smarty:
```smarty
{vite entry="resources/js/app.js" type="css"}  {* в <head> *}
{vite entry="resources/js/app.js" type="js"}   {* перед </body> *}
```
Два entry-point: `resources/js/app.js` (основной layout) и `resources/js/admin.js` (админ-layout).

**`Auth`** (`private/app/Auth/Auth.php`) — аутентификация на основе сессий. `attempt(email, password)`, `login(user)`, `logout()`, `check()`, `user()`, `id()`. Пароль хранится как bcrypt; сырой пароль удаляется из сессии после `login()`.

**`Session`** (`private/app/Http/Session.php`) — обёртка над `$_SESSION`. `get/set/has/remove()`, `flash(key, value)` / `getFlash(key)`, `csrfToken()`, `regenerate()`, `destroy()`. Конфигурация в `config/session.php`.

**`Validator`** (`private/app/Validation/Validator.php`) — валидация по правилам. `passes()` / `fails()` / `errors()` / `validate()` (бросает `ValidationException`). Правила: `required`, `string`, `integer`, `numeric`, `boolean`, `email`, `url`, `min:n`, `max:n`, `in:a,b,c`. Глобальный хелпер: `validate($data, $rules)`.

**`Translator`** (`private/app/I18n/Translator.php`) — загружает словари из `private/translates/{locale}.php`. `get(key)`, `all()`, `setLocale()`, `getLocale()`. Хелперы: `trans(key)`, `locale()`, `locale_url(path)`.

**`Logger`** (`private/app/Log/Logger.php`) — пишет в `private/storage/logs/{env}-YYYY-MM-DD.log` или на вебхук (Slack/Discord). Настраивается через `LOG_WEBHOOK_LOCAL/DEV/PROD` в `.env`.

**`GlobalErrorHandler`** (`private/app/Log/GlobalErrorHandler.php`) — регистрирует глобальные обработчики PHP-ошибок, исключений и shutdown. Вырезает чувствительные поля (`password`, `_csrf_token`) из логов. Регистрируется в `bootstrap.php`.

---

### Встроенные middleware

| Класс | Стек | Назначение |
|-------|------|------------|
| `SessionMiddleware` | глобальный (первый) | `session_start()` |
| `CsrfMiddleware` | глобальный | Проверяет CSRF-токен для POST/PUT/PATCH/DELETE |
| `LocaleMiddleware` | глобальный | Устанавливает локаль из URL-префикса (гости) или профиля пользователя (авторизованные) |
| `ShareViewDataMiddleware` | глобальный | Передаёт общие переменные в Smarty (см. ниже) |
| `AuthMiddleware` | per-route | Требует аутентификации; редирект на `/login` |
| `AdminMiddleware` | авто на admin-маршрутах | Проверяет `auth()->id()` в `config('admin.ids')` |
| `ApiAuthMiddleware` | per-route (API) | Требует аутентификации; возвращает JSON 401 |
| `ApiAdminMiddleware` | per-route (API) | Требует прав администратора; возвращает JSON 403 |

**Переменные, передаваемые `ShareViewDataMiddleware`** (доступны в каждом шаблоне):
`csrf_token`, `auth_user`, `nav_main`, `nav_pages`, `current_path`, `current_year`, `t` (все переводы), `multilang`, `current_locale`, `locale_prefix`, `available_locales`, `locale_urls`.

---

### Добавление нового веб-маршрута

1. Определить в `private/config/routes.php`.
2. Создать контроллер в `private/app/Controller/`, зарегистрировать в `private/config/services.php`.
3. Создать шаблон в `private/resources/views/`.

### Добавление нового API-маршрута

1. Определить в `private/config/api_routes.php` (путь относительно `/api`).
2. Создать контроллер в `private/app/Controller/Api/`; методы возвращают `Response::json($data)`.
3. Зарегистрировать в `services.php`.

### Добавление нового admin-маршрута

1. Определить в `private/config/admin_routes.php` (путь относительно `/admin`).
2. Создать контроллер в `private/app/Controller/Admin/`.
3. Зарегистрировать в `services.php`.
4. `AdminMiddleware` применяется автоматически — добавлять его вручную не нужно.

### Добавление middleware

- **Глобальный:** добавить в `private/config/middleware.php` и зарегистрировать в `services.php`.
- **Per-route:** добавить `'middleware' => [MyMiddleware::class]` в определение маршрута.
- Все middleware реализуют `App\Http\MiddlewareInterface`: `process(Request $request, callable $next): Response`.

---

### Миграции

- Файлы: `private/database/migrations/YYYY_MM_DD_HHMMSS_description.php`.
- Имя класса: PascalCase из части description — `create_users_table` → `CreateUsersTable`.
- Наследовать `App\Database\Migration`, реализовать `up()` / `down()`.
- Различия SQLite и MySQL: проверять `config('database.default')` внутри миграции.
- При создании новых таблиц регистрировать константы имён в `Tables`.

### Консольные команды

Команды регистрируются в `private/config/commands.php`. Реализовывать `App\Console\CommandInterface`: `handle(array $args): int`.

```bash
php bin/console <команда>   # вызывает зарегистрированный класс
php bin/console             # выводит список доступных команд
```

---

### Глобальные хелперы (`private/app/Support/helpers.php`)

`env()`, `config()`, `app()`, `redirect()`, `session()`, `auth()`, `cache()`, `validate()`, `trans()`, `locale()`, `locale_url()`, `csrf_token()`.

`config()` и `app()` зависят от глобальных переменных `$config` и `$container`, установленных в `bootstrap.php`.

---

### Фронтенд (Vite + Bootstrap 5)

Исходники находятся в `private/resources/` (рядом с `views/`). Собранные файлы попадают в `public/build/` (в gitignore).

```
private/resources/
  js/
    app.js      # entry основного layout: Bootstrap + ApiClient → window.api
    admin.js    # entry admin-layout: Bootstrap + сайдбар + ApiClient → window.api
    api.js      # ES-модуль ApiClient / ApiError (импортируется обоими entry)
  css/
    app.css     # Bootstrap + Bootstrap Icons
    admin.css   # импортирует app.css + стили для админки
  views/        # Smarty-шаблоны
```

Dev-режим определяется по наличию `public/build/hot` (создаётся при `make dev`, удаляется при остановке).

---

### Окружение

Скопировать `private/.env.example` в `private/.env`. Ключевые переменные:

```
APP_ENV=local          # local | dev | production
APP_DEBUG=true
APP_MULTILANG=false
APP_LOCALE=en
DB_CONNECTION=sqlite   # sqlite | mysql
ADMIN_PATH=/admin
ADMIN_IDS=1            # ID администраторов через запятую
LOG_WEBHOOK_LOCAL=     # URL вебхука Slack / Discord (опционально)
```

---

## Стандарты кода

Все создаваемые классы, методы и публичные свойства должны содержать PHPDoc-блок с кратким описанием **на русском языке**.

```php
/**
 * Краткое описание класса на русском.
 */
final class Example
{
    /**
     * Краткое описание метода. Для нетривиальных параметров — @param и @return.
     *
     * @param  string[] $items Список элементов для обработки.
     * @return int             Количество обработанных элементов.
     */
    public function process(array $items): int {}
}
```
