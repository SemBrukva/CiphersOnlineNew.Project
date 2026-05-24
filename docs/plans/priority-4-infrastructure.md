# P4 — Инфраструктура и безопасность

## Цель плана

Привести инфраструктурную обвязку и безопасность по умолчанию к уровню «не стыдно поставить в production без долгого тюнинга».

Задачи P4 не блокируют разработку, но без них любой реальный деплой будет сопровождаться неприятными сюрпризами.

---

## Задача 4.1. Docker / docker-compose

**Что**: контейнеризация: PHP-FPM, nginx, MySQL, Memcached.

**Зачем**: воспроизводимое окружение для разработки и CI. Сейчас каждый разработчик настраивает локально с нуля.

**Затрагиваемые файлы**:
- `docker/php/Dockerfile` (новый — PHP 8.3 + ext-pdo + ext-memcached + ext-curl + ext-intl)
- `docker/nginx/default.conf` (новый)
- `docker-compose.yml` (новый — services: app, nginx, db, cache, vite)
- `docker/php/php.ini` (новый — opcache, лимиты)
- `.dockerignore` (новый)
- `Makefile` — `up`, `down`, `sh`, `logs`

**Подход**:
- В compose — пробрасывать `private/` и `public/` как volume для горячей правки.
- Vite-сервис: `node:20-alpine`, `npm run dev`.
- MySQL и Memcached — официальные образы; данные в volume.
- Опционально — отдельный `docker-compose.prod.yml` без volume и с opcache=on.

**Готово, когда**:
- `make up` поднимает приложение, доступное на `http://localhost:8080`.
- Vite HMR работает в браузере (websocket-проксирование).

---

## Задача 4.2. CI / GitHub Actions

**Что**: автоматический прогон тестов, статанализа и линтера на каждый PR.

**Зачем**: ловить регрессии до мержа. Без CI тесты постепенно превращаются в декорацию.

**Затрагиваемые файлы**:
- `.github/workflows/ci.yml` (новый — matrix PHP 8.3, 8.4)
- `.github/workflows/lint.yml` (новый — pint --test, phpstan)
- `.github/dependabot.yml` (новый — обновление composer/npm)
- `README.md` — добавить бейджи статуса

**Подход**:
- Job-ы: `test`, `phpstan`, `pint`, `npm-build` (проверить, что фронт собирается).
- Кешировать `vendor/` и `node_modules/` между запусками.
- Composer audit + npm audit для известных CVE.

**Готово, когда**:
- PR на `main` блокируется красным CI.
- В сводке PR видны 4 зелёных чека.

---

## Задача 4.3. Безопасные дефолты `.env`

**Что**: пересмотр `.env.example` и связанных конфигов на production-friendly дефолты.

**Зачем**: сейчас `APP_DEBUG=true`, `SESSION_SECURE=false`, `APP_ENV=local` — нормально для разработки, но первое же production-развёртывание с копированием `.env.example` создаст уязвимости.

**Затрагиваемые файлы**:
- `private/.env.example` — комментарии «production: ...» рядом с каждой важной переменной
- `private/config/session.php` — `secure` по умолчанию `true`, если `app.env=production`
- `private/config/app.php` — `debug` по умолчанию `false`
- `private/app/Http/Middleware/SecurityHeadersMiddleware.php` — затянуть CSP для production (без `unsafe-inline`, без `unsafe-eval`)
- `private/bootstrap.php` — отказ `display_errors` для production даже при `APP_DEBUG=true` (с warning в лог)

**Подход**:
- В `.env.example` — два блока: «development defaults» и закомментированный «production checklist».
- Создать `docs/deployment.md` с чек-листом перед деплоем.

**Готово, когда**:
- `APP_DEBUG=true` в `APP_ENV=production` приводит к warning в логах при bootstrap.
- CSP в prod не содержит `unsafe-*`.

---

## Задача 4.4. Trusted proxies и единая логика IP

**Что**: конфигурируемый список доверенных прокси; `Request::ip()` доверяет `X-Forwarded-For` только если запрос пришёл от доверенного IP.

**Зачем**: сейчас `Request::ip()` всегда верит `X-Forwarded-For`, что позволяет любому подделать клиентский IP для обхода rate-limit и аналитики. `RateLimitMiddleware` читает `$_SERVER` напрямую — несогласованно.

**Затрагиваемые файлы**:
- `private/app/Http/Request.php` — параметр `trustedProxies` в `capture()`, проверка remote-IP
- `private/config/app.php` — `trusted_proxies => env('TRUSTED_PROXIES', '')`
- `private/.env.example` — `TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12`
- `private/app/Http/Middleware/RateLimitMiddleware.php` — использовать `$request->ip()` вместо `$_SERVER['REMOTE_ADDR']`

**Подход**:
- Поддержать CIDR-нотацию (`10.0.0.0/8`).
- Если `trusted_proxies` пуст и `app.env=production` — кинуть warning в лог.

**Готово, когда**:
- При запросе с `X-Forwarded-For` от не-доверенного IP — заголовок игнорируется.

---

## Задача 4.5. Затягивание CSP через nonce

**Что**: nonce для inline-скриптов вместо `unsafe-inline`.

**Зачем**: текущий CSP практически не защищает от XSS из-за `unsafe-inline` и `unsafe-eval`. Nonce-механизм — индустриальный стандарт.

**Затрагиваемые файлы**:
- `private/app/Http/Middleware/SecurityHeadersMiddleware.php` — генерировать nonce на запрос, добавлять в CSP, шарить через `ShareViewDataMiddleware`
- `private/resources/views/layouts/*.tpl` — добавить `nonce="{$csp_nonce}"` ко всем `<script>`
- Поиск `<script>` без `nonce` в шаблонах — должен показать пусто

**Подход**:
- Nonce — `bin2hex(random_bytes(16))`, разный на каждый запрос.
- Vite-сборка генерирует уже хешированные файлы — им nonce не нужен.
- В dev-режиме допустить `unsafe-inline` (Vite HMR), в prod — только nonce.

**Готово, когда**:
- В prod CSP-заголовок не содержит `unsafe-inline` (для `script-src`).
- Браузер не блокирует ни один свой `<script>`.

---

## Задача 4.6. Сессии в Memcached/Redis

**Что**: альтернативный хендлер сессий для масштабирования за reverse-proxy.

**Зачем**: при горизонтальном масштабировании файловые сессии требуют sticky-balancing. Сессии в общем сторе снимают это ограничение.

**Затрагиваемые файлы**:
- `private/app/Http/Session/MemcachedSessionHandler.php` (новый — `SessionHandlerInterface`)
- `private/app/Http/Session.php` — выбор хендлера по конфигу
- `private/config/session.php` — `'driver' => env('SESSION_DRIVER', 'file')`
- `private/.env.example` — задокументировать

**Подход**:
- В local — `file` (как сейчас).
- В prod при наличии Memcached — `memcached`.
- TTL хендлера = `session.lifetime` + grace.

**Готово, когда**:
- Переключение `SESSION_DRIVER=memcached` без других правок продолжает работать.

---

## Задача 4.7. README + deployment.md

**Что**: документация для нового разработчика и для деплоя.

**Зачем**: CLAUDE.md и AGENTS.md — для агентов; человеку с гитхаба нужен README. И отдельный документ «как разворачивать на prod».

**Затрагиваемые файлы**:
- `README.md` (новый/переписать) — стек, быстрый старт, ссылки на планы
- `docs/deployment.md` (новый) — чек-лист перед прод-деплоем (env, миграции, opcache, www-data права, cron)
- `docs/architecture.md` (новый — опционально) — диаграмма потока запроса

**Подход**:
- README — коротко, ссылками на детали.
- deployment.md — таблица «было/стало» для production-настроек.

**Готово, когда**:
- Новый разработчик может развернуть проект локально по README за 10 минут.

---

## Задача 4.8. Маскирование чувствительных данных в трейсах

**Что**: фильтровать значения аргументов функций в `$exception->getTrace()` перед логированием.

**Зачем**: трасса исключения может содержать пароли и токены, передавшиеся как аргументы. При отправке в Slack/Discord — утечка.

**Затрагиваемые файлы**:
- `private/app/Log/Logger.php` или `GlobalErrorHandler.php` — функция-санитайзер
- Список чувствительных имён параметров — общий с `DebugInfo::SENSITIVE_KEYS`, вынести в отдельный класс
- `private/app/Support/Sensitive.php` (новый — `mask(array $data, array $keys): array`)

**Подход**:
- Параметры из трассы: смотреть на reflection метода и по имени параметра решать, маскировать ли значение.
- Альтернатива (проще): не логировать `getTrace()` целиком в вебхук, только `getTraceAsString()`. Но тогда теряются аргументы и затрудняется отладка.
- Возможный компромисс: в `LOG_FORMAT=json` трассу включаем строкой через `getTraceAsString()`; локальные файлы — могут содержать аргументы.

**Готово, когда**:
- В вебхук-канале нет случаев, когда из-за паники в `Auth::attempt` пароль уехал в Slack.

---

## Чек-лист

- [x] 4.1 Docker / docker-compose окружение
- [x] 4.2 GitHub Actions CI (test + phpstan + pint + audit)
- [x] 4.3 Безопасные дефолты `.env`/CSP/debug для prod
- [x] 4.4 Trusted proxies и единая логика `Request::ip()`
- [x] 4.5 CSP через nonce вместо `unsafe-inline`
- [x] 4.6 Сессии в Memcached/Redis
- [x] 4.7 README + `docs/deployment.md`
- [x] 4.8 Маскирование секретов в трейсах исключений
