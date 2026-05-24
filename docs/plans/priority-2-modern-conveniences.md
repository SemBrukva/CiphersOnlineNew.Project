# P2 — Современные удобства DX

## Цель плана

После закрытия фундамента (P1) — сделать ежедневную работу с фреймворком приятнее: меньше boilerplate в `services.php`, удобнее маршруты, быстрее prod, чище ответы API.

---

## Задача 2.1. Auto-wiring контейнера через рефлексию

**Что**: научить `Container` разрешать зависимости автоматически по типам в конструкторе, если ручная фабрика не зарегистрирована.

**Зачем**: сейчас каждый контроллер и middleware требует ручной записи в `services.php`. Это половина файла на 300+ строк. Auto-wiring уберёт 80% записей.

**Затрагиваемые файлы**:
- `private/app/Container/Container.php` — метод `build($id)` через `ReflectionClass`
- `private/app/Container/ContainerException.php` (новый)
- `private/config/services.php` — убрать тривиальные фабрики, оставить только сложные (где нужен `config()` или конкретные параметры)

**Подход**:
- `get($id)` сначала проверяет `instances`/`bindings`, затем пробует `build($id)`.
- `build()` смотрит на конструктор; для каждого параметра типа `class-string` рекурсивно `get()`; для скалярных параметров с дефолтом — берёт дефолт; иначе кидает `ContainerException`.
- Кешировать результаты `build()` в `instances` (синглтон по умолчанию).
- Опционально — поддержка `#[Singleton]`/`#[Transient]` атрибутов.

**Готово, когда**:
- `services.php` сократился минимум вдвое.
- Любой новый контроллер с типизированным конструктором работает без правки `services.php`.

---

## Задача 2.2. Именованные маршруты и типизированные параметры

**Что**: добавить опциональные ключи `name` и type-constraints в шаблонах `{id:\d+}`.

**Зачем**: сейчас при смене URL приходится менять его во всех `redirect(...)` и шаблонах. Имена позволяют рефакторить URL безопасно.

**Затрагиваемые файлы**:
- `private/app/Http/Router.php` — поддержка `'name' => 'user.show'`, генерация regex с constraint
- `private/app/Http/UrlGenerator.php` (новый) — `url('user.show', ['id' => 1])`
- `private/app/Support/helpers.php` — `route_url(name, params)`
- `private/config/routes.php` — добавить `name` к ключевым маршрутам
- `private/config/services.php` — регистрация `UrlGenerator`
- Контроллеры/шаблоны — переход на `route_url()` где уместно

**Подход**:
- Constraint в `{id:\d+}` парсится один раз при регистрации маршрута.
- `UrlGenerator` строит таблицу `name → pattern` при первом запросе.

**Готово, когда**:
- `route_url('user.show', ['id' => 1])` возвращает `/user/1`.
- Регистрация одинакового имени дважды — `RuntimeException`.

---

## Задача 2.3. Кеш конфига и маршрутов для production

**Что**: команды `config:cache` и `route:cache`, которые собирают всё в один PHP-файл с `var_export`.

**Зачем**: в prod бессмысленно каждый запрос делать `glob()` по `config/*.php` и `require` десять файлов. Один скомпилированный файл загружается в разы быстрее.

**Затрагиваемые файлы**:
- `private/app/Console/Commands/ConfigCacheCommand.php` (новый)
- `private/app/Console/Commands/ConfigClearCommand.php` (новый)
- `private/app/Console/Commands/RouteCacheCommand.php` (новый — только если `Router` рефакторят под кеш)
- `private/app/Config/Config.php` — метод `loadFromCache($path)`
- `private/bootstrap.php` — попытка загрузить кеш, фолбэк на `load($dir)`
- `private/storage/cache/config.php` (артефакт) — добавить в `.gitignore`
- `private/config/commands.php` — регистрация
- `Makefile` — `make config-cache`, `make config-clear`

**Подход**:
- В local/dev кеш не используется (debug=true пропускает загрузку из кеша).
- Команда — простой `var_export($config->all())` в файл.
- Замыкания в `services.php` не кешируются (`var_export` не умеет closures) — `services.php` остаётся как есть.

**Готово, когда**:
- После `make config-cache` приложение работает; `make config-clear` убирает артефакт.
- На бенчмарке prod-режим показывает заметное снижение времени bootstrap.

---

## Задача 2.4. Единая обработка ошибок API + ValidationException

**Что**: глобальный exception handler для API — `ValidationException` отдаёт 422 со структурой `{errors: {...}}`, бизнес-ошибки можно бросать как исключения и не оборачивать в JSON руками.

**Зачем**: сейчас контроллеры API возвращают `Response::json(['error' => '...'], 422)` руками; форматы отличаются. Это путает фронт.

**Затрагиваемые файлы**:
- `private/app/Http/Exception/HttpException.php` (новый — базовый, с `statusCode` и `errorCode`)
- `private/app/Http/Exception/NotFoundException.php`, `UnauthorizedException.php`, `ForbiddenException.php`, `ValidationFailedException.php` (новые)
- `private/app/Http/ErrorHandler.php` — обработка `HttpException`, `ValidationException`
- `private/app/Validation/ValidationException.php` — добавить `httpStatusCode()` = 422
- API-контроллеры — пробрасывать исключения вместо ручных JSON-ответов

**Подход**:
- Единый формат ответа API: `{error: {code, message, details?}, request_id}`.
- Веб-ветка пусть формирует HTML 404/403 (как сейчас через `notFoundHandler`).

**Готово, когда**:
- В `GuestController` нет приватных хелперов `loginError()`/`registrationError()` — они заменены на `throw new ValidationFailedException(...)`.
- Все 4xx-ответы API имеют единую структуру.

---

## Задача 2.5. Route-атрибуты (опционально)

**Что**: возможность регистрировать маршруты через `#[Route(method: 'GET', path: '/user/{id}')]` поверх методов контроллера, не правя `routes.php`.

**Зачем**: при росте проекта `routes.php` на 200 строк становится сложно читать. Атрибуты держат маршрут рядом с кодом.

**Затрагиваемые файлы**:
- `private/app/Http/Attribute/Route.php` (новый)
- `private/app/Http/RouteLoader.php` (новый — сканирует `Controller/`, собирает атрибуты)
- `private/app/Http/Router.php` — принимать массив маршрутов из обоих источников (`routes.php` + RouteLoader)
- `private/app/Console/Commands/RouteListCommand.php` (новый) — `php bin/console route:list`
- `RouteLoader` должен кешироваться (см. 2.3)

**Подход**:
- Не выкидывать `routes.php` — он удобен для статичных страниц и быстрого редактирования.
- Атрибуты — параллельный канал. Конфликты имён — `RuntimeException`.

**Готово, когда**:
- Можно зарегистрировать маршрут через атрибут и `route:list` его показывает.

---

## Задача 2.6. CORS-middleware + HTTPS-redirect

**Что**: два простых middleware: CORS для API, HTTPS-редирект для production.

**Зачем**: первое нужно для любого SPA-фронта, второе — гигиенический минимум в production.

**Затрагиваемые файлы**:
- `private/app/Http/Middleware/CorsMiddleware.php` (новый)
- `private/app/Http/Middleware/EnforceHttpsMiddleware.php` (новый)
- `private/config/cors.php` (новый) — allowed_origins, headers, methods, max_age
- `private/config/middleware.php` + `api_middleware.php` — подключить
- `private/config/services.php` — регистрация
- `private/.env.example` — `APP_FORCE_HTTPS=false`, `CORS_ALLOWED_ORIGINS=*`

**Подход**:
- CORS: preflight OPTIONS заворачивает 204 со всеми нужными заголовками; обычные запросы — добавляет `Access-Control-Allow-*`.
- HTTPS-redirect: если `app.env=production` и `app.force_https=true` и `REQUEST_SCHEME !== 'https'` — 301 на `https://...`.

**Готово, когда**:
- API доступен из SPA на другом домене (preflight отрабатывает).
- В prod с `APP_FORCE_HTTPS=true` HTTP-запросы редиректят на HTTPS.

---

## Задача 2.7. Health-check endpoint

**Что**: `/healthz` — простой эндпоинт для k8s/Docker liveness/readiness.

**Зачем**: без него балансер не понимает, живой ли инстанс.

**Затрагиваемые файлы**:
- `private/app/Controller/HealthController.php` (новый)
- `private/config/routes.php` — `GET /healthz`
- `private/config/services.php` — регистрация (если не делали 2.1 auto-wiring)

**Подход**:
- Проверяет: БД (`SELECT 1`), кеш (`set/get` тестового ключа), наличие writable `storage/`.
- Возвращает `{status: ok|degraded|fail, checks: {db: ok, cache: ok, ...}}` + HTTP 200/503.
- Должен быть исключён из rate-limit и не требовать сессии — отдельная ветка без сессионного middleware.

**Готово, когда**:
- `curl /healthz` возвращает JSON с полями `status` и `checks`.

---

## Задача 2.8. Структурированный JSON-логгер

**Что**: формат JSON-line для prod-логов (одна запись = один JSON).

**Зачем**: ELK/Loki/Datadog читают JSON-line из коробки. Текстовый формат требует grok-парсинга.

**Затрагиваемые файлы**:
- `private/app/Log/Logger.php` — поддержка `LOG_FORMAT=json|text`
- `private/config/log.php` — `format`
- `private/.env.example` — задокументировать

**Подход**:
- `{ts, level, msg, ctx, request_id, ip, env}` — одна строка в файл/вебхук.
- В local — text (читаемо), в prod — json.

**Готово, когда**:
- При `LOG_FORMAT=json` файл `private/storage/logs/*.log` содержит валидный JSON-line.

---

## Чек-лист

- [x] 2.1 Auto-wiring контейнера (рефлексия + сокращение `services.php`)
- [x] 2.2 Именованные маршруты + типизированные параметры
- [x] 2.3 `config:cache` / `route:cache` для production
- [x] 2.4 Единая обработка ошибок API + `HttpException` иерархия
- [x] 2.5 Route-атрибуты (опционально)
- [x] 2.6 CORS-middleware + EnforceHttps-middleware
- [x] 2.7 Health-check endpoint `/healthz`
- [x] 2.8 JSON-режим логгера
