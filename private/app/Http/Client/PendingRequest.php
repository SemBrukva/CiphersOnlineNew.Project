<?php

declare(strict_types=1);

namespace App\Http\Client;

use JsonException;
use RuntimeException;

/**
 * Строитель HTTP-запроса с поддержкой fluent-интерфейса.
 *
 * Каждый модифицирующий метод возвращает клонированный экземпляр,
 * поэтому исходный объект остаётся неизменным.
 */
final class PendingRequest
{
    private const FORMAT_JSON = 'json';
    private const FORMAT_FORM = 'form';

    /** @var array<string, string> */
    private array $headers = [];

    private int $timeout = 30;
    private int $connectTimeout = 10;
    private int $retries = 0;
    private int $retryDelay = 100;
    private string $bodyFormat = self::FORMAT_JSON;
    private bool $verifySsl = true;

    /**
     * @param array<string, mixed> $config Конфигурация из config/http_client.php.
     */
    public function __construct(array $config = [])
    {
        $this->timeout        = (int) ($config['timeout'] ?? 30);
        $this->connectTimeout = (int) ($config['connect_timeout'] ?? 10);
        $this->verifySsl      = (bool) ($config['verify_ssl'] ?? true);

        foreach ((array) ($config['headers'] ?? []) as $name => $value) {
            $this->headers[(string) $name] = (string) $value;
        }
    }

    /**
     * Устанавливает несколько заголовков запроса.
     *
     * @param  array<string, string> $headers
     */
    public function withHeaders(array $headers): static
    {
        $clone = clone $this;

        foreach ($headers as $name => $value) {
            $clone->headers[$name] = $value;
        }

        return $clone;
    }

    /**
     * Устанавливает один заголовок запроса.
     */
    public function withHeader(string $name, string $value): static
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    /**
     * Устанавливает заголовок Authorization в формате «Bearer <token>».
     *
     * @param string $type Тип токена, по умолчанию «Bearer».
     */
    public function withToken(string $token, string $type = 'Bearer'): static
    {
        return $this->withHeader('Authorization', "{$type} {$token}");
    }

    /**
     * Устанавливает заголовок Authorization для Basic Auth.
     */
    public function withBasicAuth(string $user, string $password): static
    {
        return $this->withHeader(
            'Authorization',
            'Basic ' . base64_encode("{$user}:{$password}")
        );
    }

    /**
     * Устанавливает таймаут ожидания ответа в секундах.
     */
    public function timeout(int $seconds): static
    {
        $clone = clone $this;
        $clone->timeout = $seconds;

        return $clone;
    }

    /**
     * Задаёт количество повторных попыток при сетевой ошибке.
     *
     * @param int $times   Максимальное число повторов (не считая первую попытку).
     * @param int $sleepMs Пауза между попытками в миллисекундах.
     */
    public function retry(int $times, int $sleepMs = 100): static
    {
        $clone = clone $this;
        $clone->retries    = $times;
        $clone->retryDelay = $sleepMs;

        return $clone;
    }

    /**
     * Устанавливает формат тела запроса — application/json (по умолчанию).
     */
    public function asJson(): static
    {
        $clone = clone $this;
        $clone->bodyFormat = self::FORMAT_JSON;

        return $clone;
    }

    /**
     * Устанавливает формат тела запроса — application/x-www-form-urlencoded.
     */
    public function asForm(): static
    {
        $clone = clone $this;
        $clone->bodyFormat = self::FORMAT_FORM;

        return $clone;
    }

    /**
     * Выполняет GET-запрос.
     *
     * @param  array<string, scalar> $query Параметры строки запроса.
     */
    public function get(string $url, array $query = []): HttpResponse
    {
        return $this->sendWithRetry('GET', $url, ['query' => $query]);
    }

    /**
     * Выполняет POST-запрос.
     *
     * @param  array<string, mixed> $data Тело запроса.
     */
    public function post(string $url, array $data = []): HttpResponse
    {
        return $this->sendWithRetry('POST', $url, ['body' => $data]);
    }

    /**
     * Выполняет PUT-запрос.
     *
     * @param  array<string, mixed> $data Тело запроса.
     */
    public function put(string $url, array $data = []): HttpResponse
    {
        return $this->sendWithRetry('PUT', $url, ['body' => $data]);
    }

    /**
     * Выполняет PATCH-запрос.
     *
     * @param  array<string, mixed> $data Тело запроса.
     */
    public function patch(string $url, array $data = []): HttpResponse
    {
        return $this->sendWithRetry('PATCH', $url, ['body' => $data]);
    }

    /**
     * Выполняет DELETE-запрос.
     *
     * @param  array<string, mixed> $data Необязательное тело запроса.
     */
    public function delete(string $url, array $data = []): HttpResponse
    {
        return $this->sendWithRetry('DELETE', $url, ['body' => $data]);
    }

    /**
     * Выполняет запрос с повторными попытками при сетевых ошибках.
     *
     * HTTP-ошибки (4xx/5xx) не вызывают повтор — для проверки статуса
     * вызывайте ->throw() на возвращённом HttpResponse.
     *
     * @param  array<string, mixed> $options Поддерживаемые ключи: 'query', 'body'.
     * @throws RuntimeException При исчерпании всех попыток.
     */
    private function sendWithRetry(string $method, string $url, array $options): HttpResponse
    {
        $attempt       = 0;
        $lastException = new RuntimeException("HTTP request to {$url} failed.");

        do {
            if ($attempt > 0 && $this->retryDelay > 0) {
                usleep($this->retryDelay * 1000);
            }

            try {
                return $this->execute($method, $url, $options);
            } catch (RuntimeException $e) {
                $lastException = $e;
            }

            $attempt++;
        } while ($attempt <= $this->retries);

        throw $lastException;
    }

    /**
     * Выполняет один HTTP-запрос через cURL и возвращает HttpResponse.
     *
     * @param  array<string, mixed> $options
     * @throws RuntimeException При ошибке cURL или сериализации тела.
     */
    private function execute(string $method, string $url, array $options): HttpResponse
    {
        $query = (array) ($options['query'] ?? []);
        $body  = (array) ($options['body'] ?? []);

        if ($query !== []) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($query);
        }

        $ch = curl_init();

        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL handle.');
        }

        try {
            $headers     = $this->headers;
            $encodedBody = null;

            if ($body !== [] && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                if ($this->bodyFormat === self::FORMAT_JSON) {
                    try {
                        $encodedBody = json_encode($body, JSON_THROW_ON_ERROR);
                    } catch (JsonException $e) {
                        throw new RuntimeException("Failed to encode request body: {$e->getMessage()}", 0, $e);
                    }
                    $headers['Content-Type'] = 'application/json';
                } else {
                    $encodedBody = http_build_query($body);
                    $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                }
            }

            $headerLines = [];

            foreach ($headers as $name => $value) {
                $headerLines[] = "{$name}: {$value}";
            }

            /** @var array<string, string> $responseHeaders */
            $responseHeaders = [];

            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
                CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
                CURLOPT_HTTPHEADER     => $headerLines,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_HEADERFUNCTION => static function (
                    mixed $curl,
                    string $header
                ) use (&$responseHeaders): int {
                    $len   = strlen($header);
                    $parts = explode(':', $header, 2);

                    if (count($parts) === 2) {
                        $responseHeaders[trim($parts[0])] = trim($parts[1]);
                    }

                    return $len;
                },
            ]);

            if ($encodedBody !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedBody);
            }

            $rawBody    = curl_exec($ch);
            $errno      = curl_errno($ch);
            $error      = curl_error($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($errno !== 0) {
                throw new RuntimeException("cURL error [{$errno}]: {$error}");
            }

            return new HttpResponse($statusCode, $responseHeaders, (string) $rawBody);
        } finally {
            curl_close($ch);
        }
    }
}
