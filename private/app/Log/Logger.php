<?php

declare(strict_types=1);

namespace App\Log;

use App\Http\Client\HttpClientInterface;
use App\Http\RequestContext;
use App\Support\Sensitive;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;

/**
 * PSR-3 совместимый логгер приложения.
 *
 * Поддерживает уровни логирования, контекст, фильтрацию по минимальному уровню
 * и два формата вывода: text и json.
 */
final readonly class Logger implements LoggerInterface
{
    /** @var array<string, int> Приоритет уровней логирования. */
    private const array LEVEL_PRIORITY = [
        LogLevel::DEBUG => 100,
        LogLevel::INFO => 200,
        LogLevel::NOTICE => 250,
        LogLevel::WARNING => 300,
        LogLevel::ERROR => 400,
        LogLevel::CRITICAL => 500,
        LogLevel::ALERT => 550,
        LogLevel::EMERGENCY => 600,
    ];

    /**
     * Создаёт экземпляр логгера.
     *
     * @param array<string, mixed> $config Конфигурация из config/log.php.
     */
    public function __construct(
        private array $config,
        private RequestContext $context,
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Логирует сообщение уровня emergency.
     *
     * @param array<string, mixed> $context
     */
    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->write(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Логирует сообщение уровня alert.
     *
     * @param array<string, mixed> $context
     */
    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->write(LogLevel::ALERT, $message, $context);
    }

    /**
     * Логирует сообщение уровня critical.
     *
     * @param array<string, mixed> $context
     */
    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->write(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Логирует сообщение уровня error.
     *
     * @param array<string, mixed> $context
     */
    public function error(Stringable|string $message, array $context = []): void
    {
        $this->write(LogLevel::ERROR, $message, $context);
    }

    /**
     * Логирует сообщение уровня warning.
     *
     * @param array<string, mixed> $context
     */
    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->write(LogLevel::WARNING, $message, $context);
    }

    /**
     * Логирует сообщение уровня notice.
     *
     * @param array<string, mixed> $context
     */
    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->write(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Логирует сообщение уровня info.
     *
     * @param array<string, mixed> $context
     */
    public function info(Stringable|string $message, array $context = []): void
    {
        $this->write(LogLevel::INFO, $message, $context);
    }

    /**
     * Логирует сообщение уровня debug.
     *
     * @param array<string, mixed> $context
     */
    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->write(LogLevel::DEBUG, $message, $context);
    }

    /**
     * Логирует сообщение заданного уровня.
     *
     * @param array<string, mixed> $context
     */
    public function log(mixed $level, Stringable|string $message, array $context = []): void
    {
        if (!is_string($level) || !isset(self::LEVEL_PRIORITY[$level])) {
            throw new InvalidArgumentException('Unknown log level: ' . (string) $level);
        }

        $this->write($level, $message, $context);
    }

    /**
     * Выполняет запись сообщения в выбранный канал с учётом порога уровня.
     *
     * @param array<string, mixed> $context
     */
    private function write(string $level, Stringable|string $message, array $context): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $env = (string) env('APP_ENV', 'local');
        $webhook = $this->config['webhooks'][$env] ?? null;
        $format = (string) ($this->config['format'] ?? 'text');

        $safeContext = $this->sanitizeContext($context);
        $safeContext['request_id'] = $this->context->requestId;
        $safeContext['ip'] = (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');
        $safeContext['env'] = $env;

        $interpolatedMessage = $this->interpolate((string) $message, $safeContext);

        if (is_string($webhook) && $webhook !== '') {
            $payload = $this->buildWebhookPayload($webhook, $level, $interpolatedMessage, $safeContext);
            if ($this->sendWebhook($webhook, $payload)) {
                return;
            }
        }

        $rendered = $format === 'json'
            ? $this->renderJsonLine($level, $interpolatedMessage, $safeContext)
            : $this->renderTextLine($level, $interpolatedMessage, $safeContext);

        $this->writeToFile($env, $rendered);
    }

    /**
     * Проверяет, должен ли логироваться текущий уровень при заданном min_level.
     */
    private function shouldLog(string $level): bool
    {
        $minLevel = (string) ($this->config['min_level'] ?? LogLevel::WARNING);
        $minPriority = self::LEVEL_PRIORITY[$minLevel] ?? self::LEVEL_PRIORITY[LogLevel::WARNING];

        return self::LEVEL_PRIORITY[$level] >= $minPriority;
    }

    /**
     * Подготавливает контекст: убирает чувствительные данные и нормализует значения.
     *
     * @param array<string, mixed> $context
     * @return array<string, bool|int|float|string|null>
     */
    private function sanitizeContext(array $context): array
    {
        $result = [];

        foreach ($context as $key => $value) {
            if (Sensitive::isSensitive($key)) {
                continue;
            }

            $result[$key] = $this->normalizeContextValue($value);
        }

        return $result;
    }

    /**
     * Нормализует значение контекста в скаляр/null строкового вида.
     */
    private function normalizeContextValue(mixed $value): bool|int|float|string|null
    {
        if ($value instanceof \Throwable) {
            return sprintf('%s: %s', $value::class, $value->getMessage());
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        try {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '[unserializable context]';
        }
    }

    /**
     * Подставляет значения контекста в сообщение по шаблону {key}.
     *
     * @param array<string, bool|int|float|string|null> $context
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $value) {
            if ($key !== '') {
                $replace['{' . $key . '}'] = (string) ($value ?? 'null');
            }
        }

        return strtr($message, $replace);
    }

    /**
     * Формирует текстовую запись лога.
     *
     * @param array<string, bool|int|float|string|null> $context
     */
    private function renderTextLine(string $level, string $message, array $context): string
    {
        $line = sprintf('[%s] %s %s', date('Y-m-d H:i:s'), strtoupper($level), $message);

        if ($context !== []) {
            $line .= ' | ctx=' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        return $line;
    }

    /**
     * Формирует JSON-запись лога в одну строку.
     *
     * @param array<string, bool|int|float|string|null> $context
     */
    private function renderJsonLine(string $level, string $message, array $context): string
    {
        $payload = [
            'ts' => date('c'),
            'level' => $level,
            'msg' => $message,
            'ctx' => $context,
            'request_id' => (string) ($context['request_id'] ?? ''),
            'ip' => (string) ($context['ip'] ?? 'cli'),
            'env' => (string) ($context['env'] ?? 'local'),
        ];

        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Записывает сообщение в лог-файл.
     */
    private function writeToFile(string $env, string $message): void
    {
        $dir = rtrim((string) ($this->config['path'] ?? ''), '/');
        $file = $dir . '/' . $env . '-' . date('Y-m-d') . '.log';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Формирует payload для вебхука: Slack attachments, Discord embeds или fallback.
     *
     * @param array<string, bool|int|float|string|null> $context
     * @return array<string, mixed>
     */
    private function buildWebhookPayload(string $url, string $level, string $message, array $context): array
    {
        $color    = $this->levelColor($level);
        $title    = strtoupper($level);
        $method   = (string) ($context['request_method'] ?? '');
        $uri      = (string) ($context['request_uri'] ?? '');
        $ip       = (string) ($context['ip'] ?? 'cli');
        $file     = (string) ($context['file'] ?? '');
        $line     = (string) ($context['line'] ?? '');
        $requestId = (string) ($context['request_id'] ?? '');
        $env      = (string) ($context['env'] ?? '');

        $endpoint = trim($method . ' ' . $uri);
        $location = $file !== '' ? basename($file) . ':' . $line : '';
        $footer   = implode(' | ', array_filter([$requestId ?: null, $env ?: null]));

        $trace = isset($context['trace'])
            ? $this->truncateTrace((string) $context['trace'])
            : null;

        $body = $trace !== null ? $message . "\n```\n{$trace}\n```" : $message;

        if (str_contains($url, 'discord')) {
            return $this->buildDiscordPayload($color, $title, $endpoint, $body, $ip, $location, $footer);
        }

        if (str_contains($url, 'slack')) {
            return $this->buildSlackPayload($color, $title, $endpoint, $body, $ip, $location, $footer);
        }

        return ['text' => sprintf('[%s] %s %s', date('Y-m-d H:i:s'), $title, $message)];
    }

    /**
     * Формирует Slack Attachment payload.
     *
     * @return array<string, mixed>
     */
    private function buildSlackPayload(
        string $color,
        string $title,
        string $endpoint,
        string $body,
        string $ip,
        string $location,
        string $footer,
    ): array {
        $fields = [];

        if ($endpoint !== '') {
            $fields[] = ['title' => 'Endpoint', 'value' => $endpoint, 'short' => true];
        }

        if ($ip !== '' && $ip !== 'cli') {
            $fields[] = ['title' => 'IP', 'value' => $ip, 'short' => true];
        }

        if ($location !== '') {
            $fields[] = ['title' => 'File', 'value' => $location, 'short' => true];
        }

        return [
            'attachments' => [[
                'color'      => $color,
                'title'      => $title,
                'text'       => $body,
                'fields'     => $fields,
                'footer'     => $footer,
                'ts'         => time(),
                'mrkdwn_in'  => ['text'],
            ]],
        ];
    }

    /**
     * Формирует Discord Embed payload.
     *
     * @return array<string, mixed>
     */
    private function buildDiscordPayload(
        string $color,
        string $title,
        string $endpoint,
        string $body,
        string $ip,
        string $location,
        string $footer,
    ): array {
        $fields = [];

        if ($endpoint !== '') {
            $fields[] = ['name' => 'Endpoint', 'value' => $endpoint, 'inline' => true];
        }

        if ($ip !== '' && $ip !== 'cli') {
            $fields[] = ['name' => 'IP', 'value' => $ip, 'inline' => true];
        }

        if ($location !== '') {
            $fields[] = ['name' => 'File', 'value' => $location, 'inline' => true];
        }

        return [
            'embeds' => [[
                'color'       => (int) hexdec(ltrim($color, '#')),
                'title'       => $title,
                'description' => $body,
                'fields'      => $fields,
                'footer'      => ['text' => $footer],
                'timestamp'   => date('c'),
            ]],
        ];
    }

    /**
     * Обрезает трейс до первых нескольких фреймов.
     */
    private function truncateTrace(string $trace, int $maxFrames = 5): string
    {
        $lines = explode("\n", trim($trace));

        if (count($lines) <= $maxFrames) {
            return $trace;
        }

        $kept      = array_slice($lines, 0, $maxFrames);
        $remaining = count($lines) - $maxFrames;
        $kept[]    = "... ({$remaining} more frames — see log file)";

        return implode("\n", $kept);
    }

    /**
     * Возвращает hex-цвет для уровня лога.
     */
    private function levelColor(string $level): string
    {
        return match ($level) {
            LogLevel::DEBUG            => '#9e9e9e',
            LogLevel::INFO,
            LogLevel::NOTICE           => '#2196f3',
            LogLevel::WARNING          => '#f0a500',
            default                    => '#d00000',
        };
    }

    /**
     * Отправляет payload на вебхук через общий HTTP-клиент.
     *
     * @param array<string, mixed> $payload
     */
    private function sendWebhook(string $url, array $payload): bool
    {
        try {
            $response = $this->httpClient
                ->timeout(5)
                ->post($url, $payload);

            return $response->ok();
        } catch (Throwable) {
            return false;
        }
    }
}
