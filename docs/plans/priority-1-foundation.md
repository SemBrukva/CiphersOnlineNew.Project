# P1 — Фундамент: тесты, качество кода, базовые слои

## Цель плана

Закрыть слабые места, которые блокируют дальнейшее развитие: отсутствие тестов на самописный фреймворк, отсутствие статанализа, размазанный SQL по контроллерам, отсутствие почты и PSR-3 логов.

После закрытия P1 можно безопасно браться за P2/P3 — будет защита от регрессий.

---

## Задача 1.1. Базовые unit-тесты ядра

**Что**: написать стартовый набор unit-тестов на критичные компоненты ядра.

**Зачем**: фреймворк самописный, без тестов любая правка `Router`/`Pipeline`/`Auth` — игра в русскую рулетку.

**Затрагиваемые файлы**:
- `tests/Unit/Http/RouterTest.php` (новый)
- `tests/Unit/Http/PipelineTest.php` (новый)
- `tests/Unit/Container/ContainerTest.php` (новый)
- `tests/Unit/Validation/ValidatorTest.php` (новый)
- `tests/Unit/Auth/AuthTest.php` (новый)
- `tests/Unit/Http/Middleware/LocaleMiddlewareTest.php` (новый)
- `tests/Unit/Config/ConfigTest.php` (новый)
- `tests/bootstrap.php` (новый — изолированный bootstrap для тестов)
- `phpunit.xml` (обновить testsuite)

**Подход**:
- Все зависимости передавать через конструктор, без `app()`/`config()` хелперов.
- Для БД-тестов поднимать SQLite in-memory.
- Минимум 30 тестов на старте, целевое покрытие критических классов — 80%.

**Готово, когда**:
- `make test` падает на сломанной логике этих классов.
- В `tests/` есть подпапки `Unit/` и `Feature/`.

---

## Задача 1.2. Статический анализ + линтер

**Что**: подключить PHPStan и Pint/PHP-CS-Fixer.

**Зачем**: ловить ошибки до запуска, единый стиль без обсуждений.

**Затрагиваемые файлы**:
- `composer.json` — `require-dev: phpstan/phpstan, laravel/pint`
- `phpstan.neon` (новый) — level 6, paths: `private/app`
- `pint.json` (новый) — PSR-12 + предпочтения проекта
- `Makefile` — добавить `phpstan`, `lint`, `lint-fix`

**Подход**:
- Начать с level 6, постепенно поднимать до 8.
- В `phpstan.neon` указать игнор для нужных мест вместо понижения уровня.
- Pint запускать с `--test` в CI, с `--write` локально.

**Готово, когда**:
- `make phpstan` проходит без ошибок.
- `make lint` показывает 0 нарушений.

---

## Задача 1.3. Repository-слой

**Что**: вынести SQL из контроллеров в репозитории.

**Зачем**: сейчас `RedirectController`, `GuestController`, `ShareViewDataMiddleware` напрямую таскают `SELECT ... FROM ...`. При смене схемы — править придётся по всему дереву.

**Затрагиваемые файлы**:
- `private/app/Repository/AbstractRepository.php` (новый — `find/findBy/findAll/insert/update/delete`)
- `private/app/Repository/UserRepository.php` (новый)
- `private/app/Repository/RedirectRepository.php` (новый)
- `private/app/Repository/SystemPageRepository.php` (новый)
- `private/app/Repository/ContactRepository.php` (новый)
- `private/config/services.php` — зарегистрировать репозитории
- Контроллеры — заменить вызовы `$this->db->fetch(...)` на репозитории
- `private/app/Auth/Auth.php` — заменить SELECT через `UserRepository`

**Подход**:
- `AbstractRepository` принимает `Database` + имя таблицы (через `Tables::*`).
- Возвращает `array<string,mixed>|null` (не `false`), приведение в конструкторе.
- Никаких ORM — просто метод-обёртки над `Database`.

**Готово, когда**:
- В `Controller/`, `Middleware/`, `Auth/` нет ни одного прямого вызова `$this->db->fetch*`.
- Все таблицы из `Tables::*` имеют свой репозиторий.

---

## Задача 1.4. PSR-3-совместимый Logger

**Что**: расширить `Logger` до уровней `debug/info/notice/warning/error/critical` с контекст-массивом.

**Зачем**: текущий `Logger::error()` без контекста и уровней не подключишь к Sentry/Loki/ELK. Для дев-нужды (отладка) тоже не годится.

**Затрагиваемые файлы**:
- `private/app/Log/Logger.php` — добавить методы
- `private/app/Log/LoggerInterface.php` (новый) — наследовать `Psr\Log\LoggerInterface` либо повторить интерфейс
- `composer.json` — `require: psr/log: ^3`
- `private/config/log.php` — `'min_level' => env('LOG_LEVEL', 'warning')`
- `private/app/Http/ErrorHandler.php` — использовать `critical()` для исключений
- `private/app/Log/GlobalErrorHandler.php` — `warning()` для Warning, `critical()` для Fatal

**Подход**:
- Внутри писать одной строкой JSON для prod (формат `{"ts","level","msg","ctx","request_id"}`).
- Для local — оставить читаемый текст.
- Фильтр по `min_level`.

**Готово, когда**:
- Все вызовы `error()` в коде заменены на корректные уровни.
- JSON-режим включается через `env('LOG_FORMAT', 'text')`.

---

## Задача 1.5. RequestContext вместо $_SERVER-флагов

**Что**: завести объект `RequestContext` (или поля в `Request`) для `request_id`, `started_at`, `is_api`.

**Зачем**: сейчас `Database`, `Profiler`, `Logger`, `ErrorHandler` читают эти значения из `$_SERVER` напрямую. Это глобальное состояние, ломает long-running среды и тестируемость.

**Затрагиваемые файлы**:
- `private/app/Http/RequestContext.php` (новый — readonly DTO)
- `public/index.php` — создавать `RequestContext`, регистрировать как `instance` в контейнере
- `private/app/Database/Database.php` — инжектить `RequestContext`
- `private/app/Debug/Profiler.php` — то же
- `private/app/Log/Logger.php` — то же
- `private/app/Http/ErrorHandler.php` — то же

**Подход**:
- `RequestContext` создаётся один раз в `index.php` и привязывается к контейнеру через `instance()`.
- Полностью убрать чтение `$_SERVER['APP_REQUEST_ID']` и `$_SERVER['APP_STARTED_AT']` из доменного кода.

**Готово, когда**:
- `grep -r 'APP_REQUEST_ID\|APP_STARTED_AT' private/app/` ничего не находит, кроме самого `RequestContext`.

---

## Задача 1.6. Mailer-сервис

**Что**: интегрировать Symfony Mailer + `MailerInterface` в контейнере.

**Зачем**: при первой же фиче с почтой (верификация email уже есть в схеме users!) придётся изобретать. Сейчас инфраструктуры нет вообще.

**Затрагиваемые файлы**:
- `composer.json` — `symfony/mailer: ^7.x`
- `private/app/Mail/MailerInterface.php` (новый)
- `private/app/Mail/Mailer.php` (новый — обёртка над Symfony Mailer)
- `private/app/Mail/Message.php` (новый — лёгкий DTO)
- `private/config/mail.php` (новый) — DSN, from-address, from-name
- `private/.env.example` — `MAIL_DSN=null://null`, `MAIL_FROM`, `MAIL_FROM_NAME`
- `private/config/services.php` — регистрация
- `private/resources/views/emails/` (новая папка) — Smarty-шаблоны писем

**Подход**:
- В local — `MAIL_DSN=null://null` (письма не шлются).
- Метод-фабрика `Message::make()` с fluent-сеттерами `to/subject/view/with`.
- Рендеринг тела через тот же `View`.

**Готово, когда**:
- `$mailer->send(Message::make()->to(...)->view(...))` работает.
- В local тесте можно отправить тестовое письмо через `php bin/console mail:test you@example.com`.

---

## Задача 1.7. Form Request / DTO для входных данных

**Что**: типизированные объекты-запросы вместо `$request->input('foo')` в каждом контроллере.

**Зачем**: убрать дублирование валидации и приведения типов; компактнее контроллеры; легче читать.

**Затрагиваемые файлы**:
- `private/app/Http/FormRequest.php` (новый — базовый класс)
- `private/app/Http/InputValidatorTrait.php` (новый — общая логика)
- `private/app/Controller/Api/Request/LoginRequest.php` (новый — пример)
- `private/app/Controller/Api/Request/RegisterRequest.php` (новый)
- `private/app/Controller/Api/Request/ContactRequest.php` (новый)
- `private/app/Controller/Admin/Request/RedirectRequest.php` (новый)
- `private/app/Http/Router.php` — поддержка type-hint на наследников `FormRequest` в методах контроллера (либо ручное `LoginRequest::fromRequest($request)`)
- Контроллеры — переход на новые типы

**Подход**:
- Минимум магии. Сначала — статический `fromRequest()`, без авторазрешения через type-hint в `Router`.
- Внутри `fromRequest()` вызывается `Validator` с правилами из `rules()`.
- Если есть желание — потом доточить роутер до автоматического резолва.

**Готово, когда**:
- В `GuestController` и `RedirectController` нет ручного `(string) $request->input(...)`.
- Ошибки валидации возвращаются единообразно (422 + структура `{errors: {field: [...]}}` для API).

---

## Чек-лист

- [x] 1.1 Базовые unit-тесты ядра (минимум 30 тестов, `make test` зелёный)
- [x] 1.2 PHPStan level 6+ и Pint в `make`/CI
- [x] 1.3 Repository-слой, SQL вытащен из контроллеров
- [x] 1.4 PSR-3 logger с уровнями и контекстом
- [x] 1.5 RequestContext вместо `$_SERVER`-флагов
- [x] 1.6 Mailer-сервис (Symfony Mailer + шаблоны)
- [x] 1.7 Form Request / DTO для входных данных
