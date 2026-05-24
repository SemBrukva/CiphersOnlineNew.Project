<?php

declare(strict_types=1);

namespace App\Log;

use Throwable;

/**
 * Глобальный обработчик PHP-ошибок приложения.
 *
 * Регистрируется один раз при старте через статический метод register().
 * Перехватывает Warning, Fatal Error, непойманные исключения и
 * фатальные ошибки на shutdown — и передаёт их в Logger.
 *
 * Замечания (Notice) намеренно не логируются, чтобы не засорять лог.
 * POST-поля из списка HIDDEN_FIELDS в лог не попадают.
 */
final class GlobalErrorHandler
{
    private static bool    $registered = false;
    private static ?LoggerInterface $logger = null;

    /**
     * Ошибки, собранные за время текущего запроса для debug-панели.
     *
     * @var array<int, array{level: string, message: string, file: string, line: int}>
     */
    private static array $collected = [];

    /** @var string[] POST-поля, которые не попадают в лог */
    private const array HIDDEN_FIELDS = ['password', 'password_confirmation', '_csrf_token'];

    /** @var array<int, string> Соответствие констант PHP-ошибок человекочитаемым меткам */
    private const array ERROR_LEVELS = [
        E_WARNING          => 'Warning',
        E_NOTICE           => 'Notice',
        E_DEPRECATED       => 'Deprecated',
        E_USER_WARNING     => 'User Warning',
        E_USER_NOTICE      => 'User Notice',
        E_USER_DEPRECATED  => 'User Deprecated',
        E_USER_ERROR       => 'User Error',
        E_ERROR            => 'Fatal Error',
        E_PARSE            => 'Parse Error',
        E_CORE_ERROR       => 'Core Error',
        E_COMPILE_ERROR    => 'Compile Error',
    ];

    /**
     * Регистрирует обработчики PHP-ошибок.
     * Повторный вызов безопасен — игнорируется.
     */
    public static function register(LoggerInterface $logger): void
    {
        if (self::$registered) {
            return;
        }

        self::$logger     = $logger;
        self::$registered = true;

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Обрабатывает PHP-ошибки: логирует Warning/Fatal, собирает все уровни в debug-коллекцию.
     * Возвращает false, чтобы не подавлять стандартное поведение PHP.
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $label = self::ERROR_LEVELS[$errno] ?? null;

        if ($label !== null) {
            self::$collected[] = [
                'level'   => $label,
                'message' => $errstr,
                'file'    => self::shortenPath($errfile),
                'line'    => $errline,
            ];
        }

        $logLevel = match ($errno) {
            E_WARNING, E_USER_WARNING                                      => 'Warning',
            E_ERROR, E_USER_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR => 'Fatal Error',
            default                                                         => null,
        };

        if ($logLevel !== null) {
            if ($logLevel === 'Warning') {
                self::$logger?->warning(
                    'PHP warning: {message}',
                    self::buildErrorContext($errstr, $errfile, $errline, 'warning')
                );
            } else {
                self::$logger?->critical(
                    'PHP fatal error: {message}',
                    self::buildErrorContext($errstr, $errfile, $errline, 'critical')
                );
            }
        }

        return false;
    }

    /**
     * Возвращает все ошибки PHP, перехваченные за время текущего запроса.
     *
     * @return array<int, array{level: string, message: string, file: string, line: int}>
     */
    public static function getCollected(): array
    {
        return self::$collected;
    }

    /**
     * Обрабатывает непойманные исключения (Throwable).
     */
    public static function handleException(Throwable $e): void
    {
        self::$logger?->critical(
            'Uncaught exception: {class}: {message}',
            [
                'class' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'ip' => self::ip(),
                'request' => self::request(),
            ]
        );
    }

    /**
     * Обрабатывает фатальные ошибки, пойманные на shutdown.
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        self::$logger?->critical(
            'Fatal shutdown error: {message}',
            self::buildErrorContext($error['message'], $error['file'], (int) $error['line'], 'critical')
        );
    }

    /**
     * Формирует контекст для записи ошибки.
     *
     * @return array{level: string, message: string, file: string, line: int, ip: string, request: string}
     */
    private static function buildErrorContext(
        string $message,
        string $file,
        int $line,
        string $level
    ): array {
        return [
            'level' => $level,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'ip' => self::ip(),
            'request' => self::request(),
        ];
    }

    /**
     * Обрезает BASE_PATH из пути к файлу для компактного отображения.
     */
    private static function shortenPath(string $path): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH . '/' : '';

        return $base !== '' ? str_replace($base, '', $path) : $path;
    }

    /**
     * Возвращает IP-адрес клиента или 'cli' при запуске из консоли.
     */
    private static function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'cli';
    }

    /**
     * Возвращает строку с методом, URI и POST-параметрами (без скрытых полей).
     */
    private static function request(): string
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return 'CLI';
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = ($_SERVER['SERVER_NAME'] ?? '') . $_SERVER['REQUEST_URI'];
        $result = $method . ' ' . $uri;

        if ($method === 'POST' && !empty($_POST)) {
            $params = [];

            foreach ($_POST as $key => $value) {
                if (in_array($key, self::HIDDEN_FIELDS, true)) {
                    continue;
                }
                $params[] = $key . '=' . substr((string) $value, 0, 255);
            }

            if ($params) {
                $result .= PHP_EOL . 'POST: ' . implode('&', $params);
            }
        }

        return $result;
    }
}
