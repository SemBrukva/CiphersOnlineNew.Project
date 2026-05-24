# Аудит реализации планов и список доработок

Дата аудита: 2026-05-24.
Дата доработки: 2026-05-24.

Проверка соответствия кода планам из `docs/plans/`. Все 31 задача формально реализованы (галочки в чек-листах стоят, 323 теста проходят), но найден ряд расхождений с критериями «готово, когда…». Все расхождения **исправлены** — итоговая проверка: 323 теста, `phpstan` чист, `pint --test` зелёный.

---

## P1 — Фундамент

### 1.2 PHPStan + Pint — требуется доработка

**План**: `docs/plans/priority-1-foundation.md:39`
**Критерии**: «`make phpstan` проходит без ошибок», «`make lint` показывает 0 нарушений».

**Текущее состояние**:

PHPStan (level 6, с `--memory-limit=512M`) находит **17 ошибок** в файлах:

| Файл | Тип ошибки |
|------|------------|
| `private/app/Cache/TaggedCache.php` | property never read |
| `private/app/Console/Commands/Make/AbstractMakeCommand.php` | `APP_PATH` not found |
| `private/app/Console/Commands/Make/MakeControllerCommand.php` | `APP_PATH` not found |
| `private/app/Console/Commands/Make/MakeJobCommand.php` | `APP_PATH` not found |
| `private/app/Console/Commands/Make/MakeMiddlewareCommand.php` | `APP_PATH` not found |
| `private/app/Console/Commands/Make/MakeMigrationCommand.php` | `DATABASE_PATH` not found |
| `private/app/Console/Commands/Make/MakeRepositoryCommand.php` | `APP_PATH` not found |
| `private/app/Console/Commands/OpenApiCommand.php` | `PUBLIC_PATH` not found |
| `private/app/Http/Client/PendingRequest.php` | variable on left side of `??` always exists |
| `private/app/Http/Session/MemcachedSessionHandler.php` | method never returns false (read/gc) |
| `private/app/Http/Session/RedisSessionHandler.php` | method never returns false (read/gc) |
| `private/app/I18n/IcuFormatter.php` | `missingType.iterableValue` ×4 |

Pint (`vendor/bin/pint --test`) находит **9 файлов** с нарушениями стиля:

- `private/app/Cache/TaggedCache.php` — `function_declaration`
- `private/app/Controller/Admin/RedirectController.php` — `no_unused_imports`
- `private/app/Http/Client/HttpResponse.php` — `braces_position`
- `private/app/Http/Client/HttpClient.php` — `braces_position`
- `private/config/commands.php` — `ordered_imports`
- `tests/Unit/Cache/TaggedCacheTest.php` — `function_declaration`
- `tests/Unit/Http/Middleware/RateLimitMiddlewareTest.php` — `method_argument_space`
- `tests/Unit/Http/Middleware/TrustedProxyMiddlewareTest.php` — `method_argument_space`
- `tests/Unit/Http/RequestTest.php` — `method_argument_space`

**Доработка**:

1. Добавить `bootstrap.php` или `bootstrapFiles` в `phpstan.neon`, чтобы константы путей (`APP_PATH`, `PUBLIC_PATH`, `DATABASE_PATH`) были видны анализатору.
2. Поправить типы итераций в `IcuFormatter` (`array<string, mixed>` вместо `array`).
3. Поправить возвращаемые типы у `*SessionHandler::read()` / `::gc()` (по `SessionHandlerInterface` они должны возвращать `string|false` / `int|false`).
4. Убрать неиспользуемое свойство в `TaggedCache::$tag` или начать его использовать.
5. Запустить `vendor/bin/pint` (без `--test`) для автофикса стиля и закоммитить.
6. Увеличить `memory_limit` для PHPStan в `Makefile`: `vendor/bin/phpstan analyse --memory-limit=512M`.

---

## P3 — Фичи фреймворка

### 3.7 HttpClient — требуется доработка

**План**: `docs/plans/priority-3-framework-features.md:175`
**Критерий**: «`Logger` использует `HttpClient` вместо ручного cURL».

**Текущее состояние**: `HttpClient` создан и зарегистрирован в контейнере, но `private/app/Log/Logger.php::sendWebhook()` всё ещё дёргает `curl_init()` напрямую (см. `Logger.php:262+`).

**Доработка**:

1. Инжектировать `HttpClientInterface` в `Logger` через конструктор (опционально — `nullable`, чтобы избежать циклической зависимости, если `HttpClient` сам логирует).
2. Переписать `sendWebhook()` на:
   ```php
   $response = $this->httpClient
       ->timeout(2)
       ->post($url, ['text' => $message]);
   return $response->ok();
   ```
3. Удалить ручной cURL-код.

⚠️ Внимание: возможна циклическая зависимость `Logger ↔ HttpClient`. Решение — лениво резолвить клиент через `Container` или использовать отдельный «тонкий» webhook-клиент без логирования.

---

## P4 — Инфраструктура

### 4.2 GitHub Actions CI — мелкая доработка

**План**: `docs/plans/priority-4-infrastructure.md:42`
**Критерий**: указан `.github/dependabot.yml`.

**Текущее состояние**: `.github/dependabot.yml` отсутствует. (`lint.yml` объединён с `ci.yml` — это приемлемо.)

**Доработка**: создать `.github/dependabot.yml`:

```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
  - package-ecosystem: "npm"
    directory: "/"
    schedule:
      interval: "weekly"
  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "monthly"
```

---

### 4.3 Безопасные дефолты — требуется доработка

**План**: `docs/plans/priority-4-infrastructure.md:75`
**Критерии**:
- «`APP_DEBUG=true` в `APP_ENV=production` приводит к warning в логах при bootstrap».
- «`private/bootstrap.php` — отказ `display_errors` для production даже при `APP_DEBUG=true` (с warning в лог)».

**Текущее состояние**: в `private/bootstrap.php` логика простая — `ini_set('display_errors', $isDebug ? '1' : '0')`. В production с `APP_DEBUG=true` ошибки показываются, никакого warning нет.

**Доработка**: добавить в `bootstrap.php` после загрузки конфига:

```php
$appEnv   = strtolower((string) config('app.env', 'production'));
$appDebug = (bool) config('app.debug', false);

if ($appEnv === 'production' && $appDebug) {
    // В prod display_errors всегда выключен, даже при APP_DEBUG=true.
    ini_set('display_errors', '0');

    // Логируем warning один раз при bootstrap.
    if (isset($container) && $container->has(LoggerInterface::class)) {
        $container->get(LoggerInterface::class)->warning(
            'APP_DEBUG=true в APP_ENV=production — display_errors принудительно отключён.'
        );
    }
} else {
    ini_set('display_errors', $appDebug ? '1' : '0');
}
```

---

### 4.5 CSP nonce — мелкая доработка

**План**: `docs/plans/priority-4-infrastructure.md:120`
**Критерий**: «Поиск `<script>` без `nonce` в шаблонах — должен показать пусто».

**Текущее состояние**: `private/resources/views/partials/debug_info.tpl:613` содержит inline `<script>` без `nonce`.

⚠️ Не критично: панель отладки показывается только в `env=local` или с cookie `debug=x` (см. `DebugInfo::shouldShow()`). В production не попадёт. Но формальное требование плана не выполнено.

**Доработка**: заменить в `debug_info.tpl:613`

```smarty
<script>
```

на

```smarty
<script nonce="{$csp_nonce}">
```

(переменная `csp_nonce` уже расшаривается через `ShareViewDataMiddleware`.)

---

### 4.8 Маскирование в трейсах — мелкая доработка

**План**: `docs/plans/priority-4-infrastructure.md:174`
**Критерий**: «Список чувствительных имён параметров — общий с `DebugInfo::SENSITIVE_KEYS`, вынести в отдельный класс».

**Текущее состояние**:
- ✅ Использование `getTraceAsString()` вместо полного `getTrace()` — реализовано (`GlobalErrorHandler.php`).
- ✅ `sanitizeContext()` фильтрует чувствительные ключи в `Logger`.
- ❌ Отдельный класс `private/app/Support/Sensitive.php` не создан.
- ❌ `Logger::HIDDEN_CONTEXT_KEYS` и `DebugInfo::SENSITIVE_KEYS` дублируются.

**Доработка**: создать `private/app/Support/Sensitive.php`:

```php
<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Утилита для маскирования чувствительных значений в логах и трейсах.
 */
final class Sensitive
{
    public const array KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        '_csrf_token',
        'authorization',
        'cookie',
        'secret',
        'api_key',
    ];

    /**
     * Маскирует значения в массиве по списку ключей.
     *
     * @param  array<string, mixed> $data
     * @param  list<string>         $keys
     * @return array<string, mixed>
     */
    public static function mask(array $data, array $keys = self::KEYS): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $keys, true)) {
                $data[$key] = '***';
            } elseif (is_array($value)) {
                $data[$key] = self::mask($value, $keys);
            }
        }

        return $data;
    }
}
```

Затем заменить:
- `Logger::HIDDEN_CONTEXT_KEYS` → `Sensitive::KEYS`.
- `DebugInfo::SENSITIVE_KEYS` → `Sensitive::KEYS`.
- Логику маскирования в `DebugInfo::maskRequestData()` и `Logger::sanitizeContext()` — на `Sensitive::mask()`.

---

## Сводка

| Задача | Было | Стало |
|--------|------|-------|
| 1.2 PHPStan + Pint | ❌ 17 ошибок phpstan + 9 файлов pint | ✅ `phpstan` чист, `pint --test` зелёный |
| 3.7 Logger через HttpClient | ❌ Использует cURL напрямую | ✅ `Logger::sendWebhook()` через `HttpClientInterface` |
| 4.2 dependabot.yml | ❌ Отсутствует | ✅ `.github/dependabot.yml` создан |
| 4.3 Bootstrap warning | ❌ Нет принудительного отключения display_errors | ✅ `display_errors=0` + warning в production |
| 4.5 CSP nonce в debug_info | ⚠️ `<script>` без nonce в debug-панели | ✅ `nonce="{$csp_nonce}"` добавлен |
| 4.8 Класс Sensitive | ⚠️ Дублирование констант | ✅ `App\Support\Sensitive` создан, дубли убраны |

## Резюме исправлений

**Изменённые файлы**:

- `phpstan.neon` — добавлен `bootstrapFiles: phpstan-bootstrap.php`.
- `phpstan-bootstrap.php` (новый) — определяет константы путей для статанализа.
- `Makefile` — убран флаг `--debug` у `make phpstan`.
- `private/app/I18n/IcuFormatter.php` — добавлены `@param array<string, mixed>` для 4 методов.
- `private/app/Http/Session/MemcachedSessionHandler.php` — `read(): string`, `gc(): int` (сужение типов через ковариантность).
- `private/app/Http/Session/RedisSessionHandler.php` — то же.
- `private/app/Cache/TaggedCache.php` — `$tag` стал локальной переменной конструктора; `$indexKey` — `readonly`.
- `private/app/Http/Client/PendingRequest.php` — заменена логика инициализации `$lastException` (избавились от `??`).
- `private/bootstrap.php` — отказ `display_errors` для production даже при `APP_DEBUG=true` + warning в лог.
- `private/app/Log/Logger.php` — `sendWebhook()` теперь использует `HttpClientInterface` вместо ручного cURL; `HIDDEN_CONTEXT_KEYS` заменён на `Sensitive::isSensitive()`.
- `private/app/Debug/DebugInfo.php` — `SENSITIVE_KEYS` заменён на `Sensitive::isSensitive()`.
- `private/app/Support/Sensitive.php` (новый) — общий список чувствительных ключей + `isSensitive()` / `mask()`.
- `private/config/services.php` — `Logger::class` получает `HttpClientInterface`.
- `tests/Unit/Log/LoggerTest.php` — обновлён конструктор Logger.
- `private/resources/views/partials/debug_info.tpl` — `<script>` получил `nonce="{$csp_nonce}"`.
- `.github/dependabot.yml` (новый) — еженедельные обновления composer/npm, ежемесячные github-actions.
- `vendor/bin/pint` — автофикс 9 файлов (`function_declaration`, `method_argument_space`, `braces_position`, `ordered_imports`, `no_unused_imports`).

**Итог**: `vendor/bin/phpunit` → 323 теста, `vendor/bin/phpstan` → 0 ошибок, `vendor/bin/pint --test` → passed.

Все остальные **25 задач** из планов выполнены полностью.

---

## Рекомендуемый порядок исправлений

1. **1.2 PHPStan + Pint** — починить ошибки и провести линтер. После этого CI станет реально полезным.
2. **4.3 Bootstrap warning** — гигиена prod-безопасности.
3. **3.7 Logger → HttpClient** — устранение дубля cURL-кода.
4. **4.8 Sensitive класс** — лёгкий рефакторинг, дедупликация.
5. **4.2 dependabot.yml** — однократно добавить файл.
6. **4.5 CSP nonce в debug-панели** — одна строка правки в шаблоне.
