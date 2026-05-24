<?php

declare(strict_types=1);

namespace App\Debug;

use App\Auth\Auth;
use App\Cache\CacheInterface;
use App\Cache\MemcacheCache;
use App\Database\Database;
use App\Http\Request;
use App\Http\RequestContext;
use App\Http\Response;
use App\Http\Session;
use App\Log\GlobalErrorHandler;
use App\Support\Sensitive;

/**
 * Формирует и фильтрует отладочную информацию для вывода в шаблоне.
 */
final readonly class DebugInfo
{
    /**
     * Создаёт экземпляр сервиса отладочной информации.
     *
     * @param Auth               $auth               Сервис аутентификации.
     * @param Database           $db                 Сервис базы данных с логом запросов.
     * @param CacheInterface     $cache              Сервис кеша.
     * @param Session            $session            Менеджер сессий.
     * @param MatchedRoute       $matchedRoute       Хранитель данных о совпавшем маршруте.
     * @param TranslationTracker $translationTracker Трекер использования ключей переводов.
     * @param Profiler           $profiler           Профайлер временных промежутков запроса.
     * @param RequestContext     $context            Контекст текущего HTTP-запроса.
     * @param int[]              $adminIds           Список ID администраторов.
     */
    public function __construct(
        private Auth               $auth,
        private Database           $db,
        private CacheInterface     $cache,
        private Session            $session,
        private MatchedRoute       $matchedRoute,
        private TranslationTracker $translationTracker,
        private Profiler           $profiler,
        private RequestContext     $context,
        private array              $adminIds
    ) {
    }

    /**
     * Возвращает готовые данные для вывода или null, если вывод запрещён.
     * Вызывается после завершения контроллера — все SQL, кеш, ответ уже актуальны.
     *
     * @return array<string, mixed>|null
     */
    public function build(Request $request, ?Response $response = null): ?array
    {
        if (!$this->shouldShow($request)) {
            return null;
        }

        $queryLog  = $this->db->getQueryLog();
        $memcached = $this->getMemcachedStats();
        $user      = $this->auth->user();
        $userId    = $this->auth->id();
        $execTime  = $this->calculateExecutionTimeMs();

        return [
            // ---- Производительность ----
            'execution_time'  => $execTime,
            'memory_usage'    => round(memory_get_usage(true)      / 1024 / 1024, 3),
            'memory_peak'     => round(memory_get_peak_usage(true) / 1024 / 1024, 3),
            'loaded_files'    => count(get_included_files()),
            'sql_total_time'  => $this->db->getTotalQueryTimeMs(),
            'memcached_usage' => $memcached['used_mb'],
            'memcached_total' => $memcached['limit_mb'],
            'timestamp'       => date('Y-m-d H:i:s'),

            // ---- SQL трейс и timeline ----
            'trace'          => $this->buildSqlTrace($queryLog),
            'timeline'       => $this->buildTimeline($queryLog, $this->profiler->getSpans(), $execTime),

            // ---- Кеш ----
            'cache_stats'    => $this->cache->getStats(),
            'cache_driver'   => (string) config('cache.driver', 'null'),

            // ---- PHP ошибки ----
            'php_errors'     => GlobalErrorHandler::getCollected(),

            // ---- HTTP-запрос ----
            'method'         => $request->getMethod(),
            'path'           => $request->path(),
            'full_uri'       => $request->getUri(),
            'ip'             => $request->ip(),
            'user_agent'     => (string) $request->header('User-Agent', ''),
            'referer'        => (string) $request->header('Referer', ''),
            'get_params'     => $this->maskSensitive($request->allQuery()),
            'post_params'    => $this->maskSensitive($request->allInput()),
            'headers'        => $this->filterHeaders($request->allHeaders()),

            // ---- HTTP-ответ ----
            'response_status' => $response?->getStatusCode(),
            'response_size'   => $response !== null ? strlen($response->getContent()) : null,

            // ---- Маршрут ----
            'route_pattern'    => $this->matchedRoute->getPattern(),
            'route_controller' => $this->matchedRoute->getController(),
            'route_action'     => $this->matchedRoute->getAction(),
            'route_middleware' => $this->matchedRoute->getMiddleware(),

            // ---- Сессия ----
            'session_id'   => session_id() ?: null,
            'session_data' => $this->buildSessionData(),

            // ---- Аутентификация ----
            'is_auth'    => $user !== null,
            'user_id'    => $userId,
            'user_name'  => $user['name']  ?? null,
            'user_email' => $user['email'] ?? null,
            'is_admin'   => $this->isAdmin($userId),

            // ---- Переводы ----
            'translation_used'    => $this->translationTracker->getUsed(),
            'translation_missing' => $this->translationTracker->getMissing(),

            // ---- Окружение ----
            'env_snapshot' => [
                'APP_ENV'       => (string) config('app.env', 'production'),
                'APP_DEBUG'     => config('app.debug', false) ? 'true' : 'false',
                'APP_LOCALE'    => (string) config('app.locale', 'en'),
                'DB_CONNECTION' => (string) config('database.default', 'sqlite'),
                'CACHE_DRIVER'  => (string) config('cache.driver', 'null'),
                'PHP_VERSION'   => PHP_VERSION,
                'PHP_OS'        => PHP_OS_FAMILY,
                'PHP_SAPI'      => PHP_SAPI,
            ],

            'app_env'   => (string) config('app.env', 'production'),
            'app_debug' => (bool)   config('app.debug', false),
        ];
    }

    /**
     * Проверяет, нужно ли показывать debug-блок для текущего запроса.
     * Публичный — используется в ShareViewDataMiddleware для раннего выхода.
     */
    public function shouldShow(Request $request): bool
    {
        $env       = (string) config('app.env', 'production');
        $hasCookie = $request->cookie('debug') === 'x';

        if ($env === 'local') {
            return true;
        }

        if ($env === 'dev') {
            return $hasCookie;
        }

        if ($env === 'production') {
            return $hasCookie && $this->isAdmin($this->auth->id());
        }

        return false;
    }

    /**
     * Строит строковый SQL-трейс для вывода в pre-блоке.
     *
     * @param array<int, array<string, mixed>> $queryLog
     */
    private function buildSqlTrace(array $queryLog): string
    {
        $lines = [];
        foreach ($queryLog as $index => $query) {
            $n       = (int) $index + 1;
            $lines[] = sprintf('#%d [%sms] %s', $n, (string) $query['execution_time'], $query['sql']);
            if ($query['bindings'] !== []) {
                $lines[] = 'bindings: ' . json_encode($query['bindings'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Строит единый timeline из spans профайлера и SQL-запросов.
     * Все элементы имеют offset_ms (старт относительно запроса) и duration_ms.
     * Отсортированы по offset_ms для хронологического отображения.
     *
     * @param  array<int, array<string, mixed>>                                      $queryLog
     * @param  array<int, array{name: string, category: string, offset_ms: float, duration_ms: float}> $profilerSpans
     * @param  float $totalMs Общее время запроса в мс (для вычисления процентов).
     * @return array<int, array<string, mixed>>
     */
    private function buildTimeline(array $queryLog, array $profilerSpans, float $totalMs): array
    {
        $items = [];

        foreach ($profilerSpans as $span) {
            $items[] = [
                'name'        => $span['name'],
                'category'    => $span['category'],
                'offset_ms'   => $span['offset_ms'],
                'duration_ms' => $span['duration_ms'],
                'pct_offset'  => $totalMs > 0 ? round($span['offset_ms']   / $totalMs * 100, 1) : 0.0,
                'pct_width'   => $totalMs > 0 ? round($span['duration_ms'] / $totalMs * 100, 1) : 0.0,
                'detail'      => null,
            ];
        }

        foreach ($queryLog as $i => $query) {
            $ms        = (float) $query['execution_time'];
            $offsetMs  = (float) ($query['offset_ms'] ?? 0);

            $items[] = [
                'name'        => 'SQL #' . ($i + 1),
                'category'    => 'sql',
                'offset_ms'   => $offsetMs,
                'duration_ms' => $ms,
                'pct_offset'  => $totalMs > 0 ? round($offsetMs / $totalMs * 100, 1) : 0.0,
                'pct_width'   => $totalMs > 0 ? round($ms       / $totalMs * 100, 1) : 0.0,
                'detail'      => $this->shortenSql((string) $query['sql']),
            ];
        }

        usort($items, static fn (array $a, array $b): int => $a['offset_ms'] <=> $b['offset_ms']);

        return $items;
    }

    /**
     * Возвращает первые 80 символов SQL для отображения в timeline.
     */
    private function shortenSql(string $sql): string
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql)) ?? $sql;

        return mb_strlen($sql) > 80 ? mb_substr($sql, 0, 77) . '…' : $sql;
    }

    /**
     * Возвращает данные сессии с маскировкой чувствительных ключей.
     *
     * @return array{data: array<string,mixed>, csrf: string|null, flash: array<string,mixed>}
     */
    private function buildSessionData(): array
    {
        $all   = $this->session->all();
        $csrf  = null;
        $flash = [];
        $data  = [];

        foreach ($all as $key => $value) {
            if ($key === '_csrf_token') {
                $csrf = '***';
                continue;
            }
            if ($key === '_flash') {
                $flash = is_array($value) ? $value : [];
                continue;
            }
            $data[$key] = $this->isSensitiveKey($key) ? '***' : $value;
        }

        return ['data' => $data, 'csrf' => $csrf, 'flash' => $flash];
    }

    /**
     * Маскирует значения чувствительных ключей в ассоциативном массиве.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function maskSensitive(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[$key] = $this->isSensitiveKey((string) $key) ? '***' : $value;
        }

        return $result;
    }

    /**
     * Оставляет только безопасные для вывода заголовки (маскирует Cookie и Authorization).
     *
     * @param  array<string, string> $headers
     * @return array<string, string>
     */
    private function filterHeaders(array $headers): array
    {
        $mask   = ['Cookie', 'Authorization'];
        $result = [];
        foreach ($headers as $name => $value) {
            $result[$name] = in_array($name, $mask, true) ? '***' : $value;
        }

        return $result;
    }

    /**
     * Проверяет, является ли ключ чувствительным (требует маскировки).
     */
    private function isSensitiveKey(string $key): bool
    {
        return Sensitive::isSensitive($key);
    }

    /**
     * Возвращает время выполнения текущего HTTP-запроса в миллисекундах.
     */
    private function calculateExecutionTimeMs(): float
    {
        return $this->context->elapsedMs();
    }

    /**
     * Проверяет, входит ли пользователь в список администраторов.
     */
    private function isAdmin(?int $userId): bool
    {
        return $userId !== null && in_array($userId, $this->adminIds, true);
    }

    /**
     * Возвращает статистику памяти Memcached или null-значения, если драйвер другой.
     *
     * @return array{used_mb: float|null, limit_mb: float|null}
     */
    private function getMemcachedStats(): array
    {
        if (!$this->cache instanceof MemcacheCache) {
            return ['used_mb' => null, 'limit_mb' => null];
        }

        try {
            return $this->cache->getMemoryStats();
        } catch (\Throwable) {
            return ['used_mb' => null, 'limit_mb' => null];
        }
    }
}
