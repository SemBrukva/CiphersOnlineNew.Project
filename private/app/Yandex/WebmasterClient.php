<?php

declare(strict_types=1);

namespace App\Yandex;

use App\Http\Client\HttpClientInterface;
use App\Http\Client\HttpException;
use RuntimeException;

/**
 * Клиент API Яндекс Вебмастера.
 */
final readonly class WebmasterClient
{
    /**
     * Создаёт клиент API Вебмастера.
     *
     * @param array<string, mixed> $config Конфигурация yandex_webmaster.
     */
    public function __construct(
        private HttpClientInterface $http,
        private array $config,
    ) {
    }

    /**
     * Проверяет, достаточно ли настроек для обращения к API.
     */
    public function isConfigured(): bool
    {
        return $this->token() !== '' && $this->userId() !== '' && $this->hostId() !== '';
    }

    /**
     * Возвращает список URL из мониторинга с их статусами индексации.
     *
     * Яндекс Вебмастер API v4: GET /v4/user/{user-id}/hosts/{host-id}/urls/monitoring
     *
     * @param  int                  $limit  Максимум записей (1-100).
     * @param  int                  $offset Смещение для пагинации.
     * @return array<string, mixed>         Декодированный JSON-ответ (поле `urls`).
     */
    public function urlMonitoringList(int $limit = 100, int $offset = 0): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Не настроены YANDEX_WEBMASTER_TOKEN, YANDEX_WEBMASTER_USER_ID или YANDEX_WEBMASTER_HOST_ID.');
        }

        $path = '/v4/user/' . rawurlencode($this->userId()) . '/hosts/' . rawurlencode($this->hostId()) . '/urls/monitoring';
        $url = $this->endpoint($path) . '?' . http_build_query(['offset' => $offset, 'limit' => min(100, max(1, $limit))]);

        try {
            $response = $this->http
                ->withToken($this->token(), $this->tokenType())
                ->withHeader('Accept', 'application/json')
                ->retry(2, 500)
                ->get($url)
                ->throw();
        } catch (HttpException $e) {
            $data = $e->response()->json();
            if (is_array($data)) {
                throw new WebmasterApiException(
                    $e->response()->status(),
                    $data,
                    $this->errorMessage($e),
                    $e
                );
            }

            throw new RuntimeException($this->errorMessage($e), 0, $e);
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new RuntimeException('API Яндекс Вебмастера вернул невалидный JSON.');
        }

        return $data;
    }

    /**
     * Добавляет URL в мониторинг Яндекс Вебмастера.
     *
     * Яндекс Вебмастер API v4: PUT /v4/user/{user-id}/hosts/{host-id}/urls/monitoring
     *
     * @param  string[] $urls Список URL для добавления в мониторинг.
     * @return array<string, mixed> Декодированный JSON-ответ.
     */
    public function addUrlsToMonitoring(array $urls): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Не настроены YANDEX_WEBMASTER_TOKEN, YANDEX_WEBMASTER_USER_ID или YANDEX_WEBMASTER_HOST_ID.');
        }

        $path = '/v4/user/' . rawurlencode($this->userId()) . '/hosts/' . rawurlencode($this->hostId()) . '/urls/monitoring';

        try {
            $response = $this->http
                ->withToken($this->token(), $this->tokenType())
                ->withHeader('Accept', 'application/json')
                ->retry(2, 500)
                ->put($this->endpoint($path), ['urls' => array_values($urls)])
                ->throw();
        } catch (HttpException $e) {
            $data = $e->response()->json();
            if (is_array($data)) {
                throw new WebmasterApiException(
                    $e->response()->status(),
                    $data,
                    $this->errorMessage($e),
                    $e
                );
            }

            throw new RuntimeException($this->errorMessage($e), 0, $e);
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * Возвращает список запросов и URL из query-analytics/list.
     *
     * @param  array<string, mixed> $payload Тело POST-запроса к API.
     * @return array<string, mixed>          Декодированный JSON-ответ.
     */
    public function queryAnalyticsList(array $payload): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Не настроены YANDEX_WEBMASTER_TOKEN, YANDEX_WEBMASTER_USER_ID или YANDEX_WEBMASTER_HOST_ID.');
        }

        try {
            $response = $this->http
                ->withToken($this->token(), $this->tokenType())
                ->withHeader('Accept', 'application/json')
                ->retry(2, 500)
                ->post($this->endpoint('/v4/user/' . rawurlencode($this->userId()) . '/hosts/' . rawurlencode($this->hostId()) . '/query-analytics/list'), $payload)
                ->throw();
        } catch (HttpException $e) {
            $data = $e->response()->json();
            if (is_array($data)) {
                throw new WebmasterApiException(
                    $e->response()->status(),
                    $data,
                    $this->errorMessage($e),
                    $e
                );
            }

            throw new RuntimeException($this->errorMessage($e), 0, $e);
        }

        $data = $response->json();
        if (!is_array($data)) {
            throw new RuntimeException('API Яндекс Вебмастера вернул невалидный JSON.');
        }

        return $data;
    }

    /**
     * Возвращает полный URL API-метода.
     */
    private function endpoint(string $path): string
    {
        return rtrim((string) ($this->config['base_url'] ?? 'https://api.webmaster.yandex.net'), '/') . $path;
    }

    /**
     * Возвращает OAuth-токен.
     */
    private function token(): string
    {
        return trim((string) ($this->config['token'] ?? ''));
    }

    /**
     * Возвращает тип токена для заголовка Authorization.
     */
    private function tokenType(): string
    {
        $type = trim((string) ($this->config['token_type'] ?? 'OAuth'));

        return $type !== '' ? $type : 'OAuth';
    }

    /**
     * Возвращает ID пользователя Вебмастера.
     */
    private function userId(): string
    {
        return trim((string) ($this->config['user_id'] ?? ''));
    }

    /**
     * Возвращает host_id сайта в Вебмастере.
     */
    private function hostId(): string
    {
        return trim((string) ($this->config['host_id'] ?? ''));
    }

    /**
     * Формирует понятное сообщение об ошибке API.
     */
    private function errorMessage(HttpException $exception): string
    {
        $response = $exception->response();
        $data = $response->json();
        if (!is_array($data)) {
            $body = trim($response->body());
            if ($body === '') {
                return 'API Яндекс Вебмастера вернул HTTP ' . $response->status() . ' без тела ответа.';
            }

            return 'API Яндекс Вебмастера вернул HTTP ' . $response->status() . ': ' . mb_substr($body, 0, 1000);
        }

        $parts = ['API Яндекс Вебмастера вернул HTTP ' . $response->status()];
        foreach (['error_code', 'message', 'error_message', 'field_name', 'field_value'] as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $parts[] = $key . '=' . (is_scalar($data[$key]) ? (string) $data[$key] : json_encode($data[$key], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }

        if (count($parts) === 1) {
            $parts[] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return implode('; ', $parts);
    }
}
