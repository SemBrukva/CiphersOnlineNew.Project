# P3 — Фичи фреймворка

## Цель плана

Расширить скелет до уровня, на котором на нём комфортно делать продакшн-приложения с интеграциями, фоновыми задачами и публичным API.

Все задачи P3 опираются на P1 (тесты, репозитории) — без них рост превратится в legacy.

---

## Задача 3.1. Event/Listener шина

**Что**: простой `EventDispatcher` + `ListenerProviderInterface` + регистрация листенеров в конфиге.

**Зачем**: побочные эффекты (после регистрации — отправить письмо, после заказа — обновить статистику) загрязняют контроллеры. Слабая связь через события — стандартный приём.

**Затрагиваемые файлы**:
- `private/app/Event/EventDispatcher.php` (новый)
- `private/app/Event/ListenerProviderInterface.php` (новый)
- `private/app/Event/ConfigListenerProvider.php` (новый — читает из `config/events.php`)
- `private/config/events.php` (новый) — `EventClass::class => [Listener1::class, Listener2::class]`
- `private/app/Event/Events/UserRegistered.php` (новый — пример)
- `private/app/Event/Listeners/SendVerificationEmail.php` (новый — пример)
- `private/config/services.php` — регистрация
- `GuestController::register` — `$dispatcher->dispatch(new UserRegistered($user))`

**Подход**:
- Совместимость с PSR-14 — необязательно, но желательно (один интерфейс, легко поменять реализацию).
- Без stoppable events на старте — не нужно.

**Готово, когда**:
- Можно повесить листенер в `events.php` без правки контроллера-источника.
- В тестах подменяется `null`-диспетчер.

---

## Задача 3.2. Очередь задач (database queue + worker)

**Что**: простая очередь на БД — таблица `jobs`, команда `queue:work`.

**Зачем**: отправка письма / генерация PDF / выгрузка отчёта не должны блокировать HTTP-ответ. Внешний Redis тащить рано — БД-очередь покрывает 90% малых проектов.

**Затрагиваемые файлы**:
- `private/database/migrations/{дата}_create_jobs_table.php` (новая миграция: `id, queue, payload, attempts, available_at, reserved_at, created_at`)
- `private/database/migrations/{дата}_create_failed_jobs_table.php` (новая миграция: `id, payload, exception, failed_at`)
- `private/app/Queue/JobInterface.php` (новый)
- `private/app/Queue/QueueManager.php` (новый — `push(Job, delay)`, `pop(queueName)`)
- `private/app/Queue/Worker.php` (новый — цикл выборки и выполнения)
- `private/app/Console/Commands/QueueWorkCommand.php` (новый)
- `private/app/Console/Commands/QueueRetryCommand.php` (новый)
- `private/app/Database/Tables.php` — `JOBS`, `FAILED_JOBS`
- `private/config/queue.php` (новый) — `max_attempts`, `retry_after`
- `private/app/Mail/Mailer.php` — опционально метод `queue()` для асинхронной отправки

**Подход**:
- Сериализация Job через `serialize()` со списком приватных полей.
- Транзакция `SELECT ... FOR UPDATE SKIP LOCKED` (MySQL) / `BEGIN IMMEDIATE` (SQLite) на pop.
- Failed jobs со стектрейсом — для расследования.
- В будущем — Redis-драйвер за тем же `QueueManager`.

**Готово, когда**:
- `php bin/console queue:work` крутится в фоне и подбирает задачи.
- Failed job попадает в `failed_jobs` с трассировкой.

---

## Задача 3.3. Schema Builder для миграций

**Что**: вместо сырого SQL в `up()`/`down()` — fluent-DSL: `$schema->create('users', fn(Blueprint $t) => $t->id()->string('email')->unique())`.

**Зачем**: сейчас в миграциях `if (config('database.default') === 'sqlite') { ... } else { ... }` — копипаста. Builder скроет различия диалектов.

**Затрагиваемые файлы**:
- `private/app/Database/Schema/Builder.php` (новый — `create/drop/dropIfExists/table` для alter)
- `private/app/Database/Schema/Blueprint.php` (новый — fluent-определение колонок)
- `private/app/Database/Schema/Grammar/SqliteGrammar.php` (новый)
- `private/app/Database/Schema/Grammar/MysqlGrammar.php` (новый)
- `private/app/Database/Migration.php` — добавить геттер `schema()` через DI или статически
- Опционально — переписать существующие миграции на новый DSL

**Подход**:
- Не пытаться повторить Laravel один-в-один. Минимум: `id, string, integer, boolean, text, timestamp, foreignId, index, unique, nullable, default`.
- ALTER TABLE для SQLite — известная боль; либо не поддерживаем, либо делаем `_temp` + копирование.

**Готово, когда**:
- Новые миграции пишутся без `if (driver === ...)`.
- Старые миграции работают как раньше (обратная совместимость).

---

## Задача 3.4. Console-генераторы

**Что**: команды `make:controller`, `make:middleware`, `make:migration`, `make:repository`, `make:job`.

**Зачем**: убрать ручное создание boilerplate-файлов.

**Затрагиваемые файлы**:
- `private/app/Console/Commands/Make/MakeControllerCommand.php` (и сёстры) (новые)
- `private/app/Console/Stubs/controller.stub.php` (новые) — текстовые шаблоны
- `private/config/commands.php` — регистрация

**Подход**:
- Stub-файлы с плейсхолдерами `{{Class}}`, `{{Namespace}}`.
- Каждый генератор спрашивает имя, при необходимости — путь.
- `make:migration name` сразу проставляет таймстемп в имя файла.

**Готово, когда**:
- `php bin/console make:controller UserController` создаёт скелет файла.

---

## Задача 3.5. Pluralization + ICU в Translator

**Что**: поддержка `trans_choice('apple', 5)` и/или ICU MessageFormat.

**Зачем**: с двумя локалями (en/ru) текущий простой `:param`-replace быстро становится тесным. В русском — 3 формы (1/2/5), не разрулить простой подстановкой.

**Затрагиваемые файлы**:
- `private/app/I18n/Translator.php` — `choice($key, $number, $replace)`
- `private/app/Support/helpers.php` — `trans_choice()`
- `private/translates/{ru,en}.php` — формат `'apples' => '{0} нет яблок | {1} яблоко | [2,4] :count яблока | [5,*] :count яблок'`
- Опционально — `ext-intl` + `MessageFormatter` для ICU

**Подход**:
- Сначала простой формат `{}|{}|...` с диапазонами.
- ICU как опция через `LOCALE_FORMATTER=icu`.

**Готово, когда**:
- `trans_choice('apples', 5)` корректно склоняет для ru и en.

---

## Задача 3.6. OpenAPI-генерация из контроллеров

**Что**: команда `php bin/console openapi:generate`, которая собирает `openapi.json` из атрибутов на методах API-контроллеров.

**Зачем**: фронт-разработчики или внешние интеграторы хотят актуальную доку без ручной поддержки.

**Затрагиваемые файлы**:
- `private/app/OpenApi/Attribute/{Operation,Parameter,RequestBody,Response,Schema}.php` (новые)
- `private/app/OpenApi/Generator.php` (новый — сканирует API-контроллеры через рефлексию)
- `private/app/Console/Commands/OpenApiGenerateCommand.php` (новый)
- `public/openapi.json` (артефакт)
- Опционально — `private/resources/views/openapi/swagger.tpl` + маршрут `/api/docs` со Swagger UI

**Подход**:
- Минимум: `#[Operation(summary, tags)]`, `#[Response(status, schema)]`, `#[RequestBody(schema)]`.
- Схемы — обычные DTO-классы с свойствами + атрибутами.
- Не пытаться покрыть весь OpenAPI 3.1 — только реально используемые куски.

**Готово, когда**:
- `make openapi-generate` создаёт валидный `openapi.json`.
- В Swagger UI документация рендерится без ошибок.

---

## Задача 3.7. HTTP-клиент

**Что**: тонкая обёртка над cURL c единым интерфейсом, retry-логикой, timeout-ами.

**Зачем**: рано или поздно понадобится дёргать внешнее API. Сейчас curl используется только в `Logger`, и придётся копировать-пасть.

**Затрагиваемые файлы**:
- `private/app/Http/Client/HttpClient.php` (новый)
- `private/app/Http/Client/HttpResponse.php` (новый — DTO с статусом, телом, заголовками)
- `private/app/Http/Client/HttpException.php` (новый)
- `private/config/services.php` — регистрация
- `private/app/Log/Logger.php` — переписать `sendWebhook` через `HttpClient`

**Подход**:
- Базовый API: `get/post/put/delete($url, $options)` + `request($method, $url, $options)`.
- `options`: `headers, body, json, timeout, retry`.
- Без PSR-18 на старте, но имена методов близки.

**Готово, когда**:
- `Logger` использует `HttpClient` вместо ручного cURL.
- Есть unit-тесты на ретраи и таймауты.

---

## Задача 3.8. Каскадная очистка кеша

**Что**: теги кеша (`cache()->tag('redirects')->remember(...)`, `cache()->tag('redirects')->flush()`).

**Зачем**: сейчас `RedirectController` руками вызывает `delete(RedirectMiddleware::CACHE_KEY)`. Если кеш-ключей станет много — это не масштабируется.

**Затрагиваемые файлы**:
- `private/app/Cache/CacheInterface.php` — `tag(string $tag)` или новый `TaggedCacheInterface`
- `private/app/Cache/MemcacheCache.php` — реализация тегов через индекс ключей в отдельной записи `_tag:<name>`
- `private/app/Cache/NullCache.php` — пустая реализация
- Контроллеры — переход на теги

**Подход**:
- Memcached не поддерживает теги нативно — эмуляция через индекс. Принять ограничения (не атомарно).
- Для будущего Redis-драйвера — нативные SET-команды.

**Готово, когда**:
- `cache()->tag('redirects')->flush()` инвалидирует все ключи с этим тегом.

---

## Чек-лист

- [x] 3.1 Event/Listener шина
- [x] 3.2 Очередь задач на БД + worker
- [x] 3.3 Schema Builder для миграций
- [x] 3.4 Console-генераторы (`make:*`)
- [x] 3.5 Pluralization в Translator
- [x] 3.6 OpenAPI-генерация
- [x] 3.7 HTTP-клиент
- [x] 3.8 Теги кеша
