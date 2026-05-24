# CLAUDE.md

Этот файл содержит инструкции для Claude Code (claude.ai/code) при работе с данным репозиторием.

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

# Очередь задач
php bin/console queue:work [--queue=default] [--sleep=3] [--max-jobs=N] [--max-time=N]
php bin/console queue:retry all|<id> [<id>...]

# Генераторы boilerplate
php bin/console make:controller UserController        # → Controller/UserController.php
php bin/console make:controller Api/UserController    # → Controller/Api/UserController.php
php bin/console make:middleware RateLimitMiddleware    # → Http/Middleware/RateLimitMiddleware.php
php bin/console make:migration create_posts_table     # → database/migrations/YYYY_MM_DD_HHiiss_*.php
php bin/console make:migration add_status_to_posts    # то же, alter-stub (Schema::table)
php bin/console make:repository PostRepository        # → Repository/PostRepository.php
php bin/console make:job ProcessPaymentJob            # → Queue/Jobs/ProcessPaymentJob.php

# OpenAPI
php bin/console openapi:generate                      # → public/openapi.json
php bin/console openapi:generate path/to/output.json  # произвольный путь

# Фронтенд (Vite)
make npm-install      # npm install
make dev              # npm run dev  — Vite dev-сервер с HMR
make build            # npm run build — продакшн-сборка в public/build/

# Docker
make docker-build       # собрать образы
make docker-up          # запустить (SQLite, без профилей)
make docker-down        # остановить и удалить контейнеры
make docker-mysql       # запустить + поднять MySQL (--profile mysql)
make docker-memcached   # запустить + MySQL + Memcached
make docker-shell       # sh внутри контейнера app
make docker-logs        # следить за логами
make docker-migrate     # запустить миграции внутри контейнера
make docker-test        # phpunit внутри контейнера
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

**`Schema` / `Blueprint`** (`private/app/Database/Schema/`) — fluent-строитель DDL. Автоматически генерирует правильный SQL для SQLite и MySQL; выбор грамматики — по `config('database.default')`. Использовать в `up()` / `down()` миграций вместо сырого SQL.

Статический API `Schema`:

| Метод | Назначение |
|-------|-----------|
| `Schema::create(table, fn)` | Создать таблицу |
| `Schema::table(table, fn)` | Изменить таблицу (ADD / DROP COLUMN, ADD INDEX) |
| `Schema::dropIfExists(table)` | Удалить таблицу, если существует |
| `Schema::drop(table)` | Удалить таблицу |
| `Schema::hasTable(table)` | Проверить существование таблицы |
| `Schema::hasColumn(table, col)` | Проверить существование столбца |
| `Schema::raw(sql)` | Создать `RawExpression` для значений DEFAULT |

Методы `Blueprint` для добавления столбцов: `id()`, `bigId()`, `string(name, len)`, `text()`, `mediumText()`, `longText()`, `integer()`, `bigInteger()`, `smallInteger()`, `tinyInteger()`, `boolean()`, `unsignedBigInteger()`, `unsignedInteger()`, `unsignedSmallInteger()`, `unsignedTinyInteger()`, `float()`, `decimal(name, total, places)`, `timestamp()` (unix int), `datetime()`, `date()`, `timestamps()` (created_at + updated_at nullable), `softDeletes()`.

Модификаторы столбца (fluent): `->nullable()`, `->default(value)`, `->unsigned()`, `->unique()`, `->after(col)` (только MySQL).

Индексы и ограничения: `$table->unique(cols, name?)`, `$table->index(cols, name?)`, `$table->primary(cols)`, `$table->foreign(col)->references(col)->on(table)->onDelete(action)->onUpdate(action)`, `$table->dropColumn(col)`.

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('user_id');
    $table->string('status', 20)->default('pending');
    $table->decimal('total', 10, 2);
    $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
    $table->index('status', 'orders_status_idx');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
});
```

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

**`Translator`** (`private/app/I18n/Translator.php`) — загружает словари из `private/translates/{locale}.php`. `get(key, replace)`, `choice(key, count, replace)`, `all()`, `setLocale()`, `getLocale()`. Хелперы: `trans(key, replace)`, `trans_choice(key, count, replace)`, `locale()`, `locale_url(path)`.

Поддерживаются два формата подстановки параметров в `get()`:
- Классический: `:param` → значение (обратная совместимость).
- ICU MessageFormat: `{param}`, `{n, plural, one {# item} other {# items}}`, `{v, select, a {…} other {…}}`. Активируется автоматически, если строка содержит `{letter`.

`choice()` / `trans_choice()` работают с pipe-разделёнными формами в файле перевода:
```php
// en.php — 2 формы (one|other):
'ITEMS_COUNT' => ':count item|:count items'
// ru.php — 3 формы (one|few|many):
'ITEMS_COUNT' => ':count элемент|:count элемента|:count элементов'
```
Форма выбирается по локали согласно CLDR-правилам (`PluralRules`). Параметр `:count` / `{count}` подставляется автоматически.

**`Logger`** (`private/app/Log/Logger.php`) — пишет в `private/storage/logs/{env}-YYYY-MM-DD.log` или на вебхук (Slack/Discord). Настраивается через `LOG_WEBHOOK_LOCAL/DEV/PROD` в `.env`.

**`GlobalErrorHandler`** (`private/app/Log/GlobalErrorHandler.php`) — регистрирует глобальные обработчики PHP-ошибок, исключений и shutdown. Вырезает чувствительные поля (`password`, `_csrf_token`) из логов. Регистрируется в `bootstrap.php`.

**`HttpClient`** (`private/app/Http/Client/`) — HTTP-клиент на cURL без внешних зависимостей. `HttpClientInterface` с реализацией `HttpClient`. Хелпер: `http_client()`. Ответ возвращается как `HttpResponse` — методы: `status()`, `ok()`, `failed()`, `body()`, `json(key?, default?)`, `header(name)`, `throw()`. Fluent-API через `PendingRequest`: `withToken(token)`, `withHeaders([...])`, `withBasicAuth(user, pass)`, `timeout(sec)`, `retry(times, sleepMs)`, `asJson()`, `asForm()`. Конфигурация в `config/http_client.php`, переменные: `HTTP_CLIENT_TIMEOUT`, `HTTP_CLIENT_CONNECT_TIMEOUT`, `HTTP_CLIENT_VERIFY_SSL`.

```php
// Простой запрос
$response = http_client()->get('https://api.example.com/users');
$users = $response->throw()->json();

// Fluent-цепочка
$response = http_client()
    ->withToken($token)
    ->retry(3, 500)
    ->post('https://api.example.com/items', ['name' => 'Test']);
```

**`QueueManager` / `Worker`** (`private/app/Queue/`) — простая очередь задач на БД. `QueueManager::push(Job, delay, queue)` сериализует задачу (`serialize()`) и складывает в таблицу `jobs`; `pop(queue)` атомарно резервирует следующую запись (`SELECT ... FOR UPDATE SKIP LOCKED` для MySQL, `BEGIN IMMEDIATE` для SQLite). `Worker::run(queue, sleep, maxJobs, maxTime)` крутится в фоне, при успехе удаляет задачу, при падении — повторяет до `max_attempts`, после чего перемещает её в `failed_jobs` со стектрейсом. Задачи реализуют `JobInterface` (метод `handle()`); если нужны сервисы из контейнера — `ContainerAwareJobInterface` (воркер вызовет `setContainer()` перед `handle()`, контейнер не сериализуется через `__sleep()`). Конфигурация в `config/queue.php` (`QUEUE_MAX_ATTEMPTS`, `QUEUE_RETRY_AFTER`). Хелпер `Mailer::queue(Message, delay)` отправляет письмо через `SendMailJob`.

**`OpenApiGenerator`** (`private/app/OpenApi/OpenApiGenerator.php`) — генерирует спецификацию OpenAPI 3.0.3 из таблицы API-маршрутов и PHP-атрибутов контроллеров. Запускается командой `openapi:generate`, результат пишется в `public/openapi.json`. Выводит схему `requestBody` автоматически из `FormRequest::rules()`, добавляет `security` по наличию `ApiAuthMiddleware` / `ApiAdminMiddleware` в middleware-стеке маршрута.

**OpenAPI-атрибуты** (`private/app/Http/Attribute/`): применяются к методам API-контроллеров.

| Атрибут | Повторяем | Назначение |
|---------|-----------|------------|
| `#[ApiOperation(summary, description, tags, deprecated)]` | нет | Заголовок, описание, теги операции |
| `#[ApiResponse(status, description, schema)]` | да | Документирует вариант ответа |
| `#[ApiBody(class, description, required)]` | нет | Тело запроса; `class` — FQCN `FormRequest` для автовывода схемы |
| `#[ApiParam(name, in, description, required, type, example)]` | да | Параметр: `path` / `query` / `header` |

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

**Язык админки:** интерфейс панели администратора всегда на русском языке. В шаблонах `views/admin/` и контроллерах `Controller/Admin/` строки пишутся напрямую на русском — `trans()` и ключи переводов не используются.

### Добавление middleware

- **Глобальный:** добавить в `private/config/middleware.php` и зарегистрировать в `services.php`.
- **Per-route:** добавить `'middleware' => [MyMiddleware::class]` в определение маршрута.
- Все middleware реализуют `App\Http\MiddlewareInterface`: `process(Request $request, callable $next): Response`.

---

### Миграции

- Файлы: `private/database/migrations/YYYY_MM_DD_HHMMSS_description.php`.
- Имя класса: PascalCase из части description — `create_users_table` → `CreateUsersTable`.
- Наследовать `App\Database\Migration`, реализовать `up()` / `down()`.
- **DDL писать через `Schema` / `Blueprint`** — не через сырой SQL с ветками `if sqlite/mysql`.
- При создании новых таблиц регистрировать константы имён в `Tables`.
- Используй `php bin/console make:migration <name>` — timestamp подставляется автоматически; stub выбирается по имени: `create_*` / `drop_*` → `Schema::create`, остальные (`add_*`, `rename_*` и т.д.) → `Schema::table`.

### Консольные команды

Команды регистрируются в `private/config/commands.php`. Реализовывать `App\Console\CommandInterface`: `handle(array $args): int`.

```bash
php bin/console <команда>   # вызывает зарегистрированный класс
php bin/console             # выводит список доступных команд
```

**Генераторы boilerplate** (`private/app/Console/Commands/Make/`): базовый класс `AbstractMakeCommand`, stub-шаблоны в `private/app/Console/Stubs/`. Новые генераторы наследуют `AbstractMakeCommand` и реализуют `getType()`, `getStub(string $name)`, `getTargetPath(string $name)`, `buildReplacements(string $name)`.

---

### Глобальные хелперы (`private/app/Support/helpers.php`)

`env()`, `config()`, `app()`, `redirect()`, `session()`, `auth()`, `cache()`, `http_client()`, `validate()`, `trans()`, `trans_choice()`, `locale()`, `locale_url()`, `csrf_token()`.

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

### Docker / docker-compose

Конфигурация в `docker/`, точка входа — `docker-compose.yml`.

**Сервисы:**

| Сервис | Образ | Описание |
|--------|-------|----------|
| `app` | `Dockerfile` (target `development`) | PHP 8.3-FPM; bind-mount исходников |
| `nginx` | `nginx:1.27-alpine` | Проксирует `.php` на `app:9000`; порт `8080` |
| `mysql` | `mysql:8.0` | Профиль `mysql`; данные в named volume `mysql_data` |
| `memcached` | `memcached:1.6-alpine` | Профиль `memcached` |

**Файлы:**

```
Dockerfile                   # multi-stage: base / development / production
docker-compose.yml           # dev (bind-mount, SQLite по умолчанию)
docker-compose.prod.yml      # prod-оверрайд: production-образ, без bind-mount
docker/nginx/default.conf    # конфиг Nginx
docker/php/php.ini           # общие PHP-настройки
docker/php/php-dev.ini       # dev-оверрайд (display_errors, opcache off)
private/.env.docker          # пример .env для Docker-окружения
```

**Быстрый старт (dev):**

```bash
cp private/.env.docker private/.env
make docker-build
make docker-up        # http://localhost:8080
make docker-migrate
```

**Быстрый старт с MySQL:**

```bash
# В private/.env: DB_CONNECTION=mysql, DB_HOST=mysql, DB_DATABASE=skeleton, ...
make docker-mysql
make docker-migrate
```

**Продакшн:**

```bash
make build   # собрать фронтенд на хосте, результат попадёт в public/build/
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

**Vite в Docker:** Vite (`make dev`) запускается на хосте, т.к. браузер обращается к `localhost:5173` напрямую. PHP читает `public/build/hot` — механизм работает без изменений.

---

### Тестирование

Тесты расположены в `tests/`. Конфигурация PHPUnit: `phpunit.xml`.

```
tests/
  bootstrap.php              # инициализирует $config и $container для хелперов
  fixtures/
    http_server.php          # тестовый HTTP-сервер для интеграционных тестов
    translates/              # фикстуры словарей
  Unit/                      # юнит-тесты и интеграционные тесты компонентов
    Http/
      Client/                # HttpResponseTest, HttpExceptionTest, HttpClientTest,
                             # PendingRequestTest (требует built-in сервер)
    ...
  Feature/                   # полные сценарии через стек приложения (зарезервировано)
```

**Юнит-тесты** не обращаются к БД, сети или файловой системе. Зависимости мокаются или заменяются тестовыми дублями.

**Интеграционные тесты с внешним процессом** — используют `setUpBeforeClass()` / `tearDownAfterClass()` для запуска и остановки вспомогательного процесса. Тесты автоматически помечаются как `markTestSkipped`, если процесс не стартовал или нужное расширение недоступно. Пример: `PendingRequestTest` поднимает PHP built-in сервер на `127.0.0.1:18080` (скрипт `tests/fixtures/http_server.php`).

Сервер `http_server.php` отвечает на любой запрос JSON-эхом: `method`, `uri`, `query`, `headers`, `body`. Маршрут `/status/{code}` возвращает указанный HTTP-статус. Используй этот же сервер для новых тестов, требующих реальных HTTP-запросов.

Паттерн запуска процесса в тестах:

```php
public static function setUpBeforeClass(): void
{
    $cmd = sprintf('php -S 127.0.0.1:%d %s', self::$port, escapeshellarg($script));

    $descriptors = [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']];
    self::$serverProcess = proc_open($cmd, $descriptors, $pipes);

    $deadline = microtime(true) + 2.0;
    while (microtime(true) < $deadline) {
        $fp = @fsockopen('127.0.0.1', self::$port, $errno, $errstr, 0.1);
        if ($fp !== false) { fclose($fp); break; }
        usleep(50_000);
    }
}

public static function tearDownAfterClass(): void
{
    if (is_resource(self::$serverProcess)) {
        proc_terminate(self::$serverProcess);
        proc_close(self::$serverProcess);
    }
}
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
