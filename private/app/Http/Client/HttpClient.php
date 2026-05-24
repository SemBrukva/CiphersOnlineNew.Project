<?php

declare(strict_types=1);

namespace App\Http\Client;

/**
 * HTTP-клиент для выполнения внешних HTTP-запросов.
 *
 * Создаёт PendingRequest с базовой конфигурацией и предоставляет
 * удобные методы-сокращения для стандартных HTTP-методов.
 *
 * Пример использования:
 *
 *   $client = app(HttpClientInterface::class);
 *
 *   // Быстрый запрос
 *   $response = $client->get('https://api.example.com/users');
 *
 *   // Fluent-цепочка
 *   $response = $client->withToken('secret')
 *       ->retry(3, 500)
 *       ->post('https://api.example.com/items', ['name' => 'Test']);
 *
 *   $data = $response->throw()->json();
 */
final class HttpClient implements HttpClientInterface
{
    /**
     * @param array<string, mixed> $config Конфигурация из config/http_client.php.
     */
    public function __construct(private readonly array $config = [])
    {
    }

    /**
     * Создаёт новый PendingRequest с базовыми настройками клиента.
     */
    public function pending(): PendingRequest
    {
        return new PendingRequest($this->config);
    }

    /**
     * Выполняет GET-запрос.
     *
     * @param  array<string, scalar> $query Параметры строки запроса.
     */
    public function get(string $url, array $query = []): HttpResponse
    {
        return $this->pending()->get($url, $query);
    }

    /**
     * Выполняет POST-запрос.
     *
     * @param  array<string, mixed> $data Тело запроса.
     */
    public function post(string $url, array $data = []): HttpResponse
    {
        return $this->pending()->post($url, $data);
    }

    /**
     * Выполняет PUT-запрос.
     *
     * @param  array<string, mixed> $data Тело запроса.
     */
    public function put(string $url, array $data = []): HttpResponse
    {
        return $this->pending()->put($url, $data);
    }

    /**
     * Выполняет PATCH-запрос.
     *
     * @param  array<string, mixed> $data Тело запроса.
     */
    public function patch(string $url, array $data = []): HttpResponse
    {
        return $this->pending()->patch($url, $data);
    }

    /**
     * Выполняет DELETE-запрос.
     *
     * @param  array<string, mixed> $data Необязательное тело запроса.
     */
    public function delete(string $url, array $data = []): HttpResponse
    {
        return $this->pending()->delete($url, $data);
    }

    /**
     * Устанавливает несколько заголовков и возвращает PendingRequest.
     *
     * @param  array<string, string> $headers
     */
    public function withHeaders(array $headers): PendingRequest
    {
        return $this->pending()->withHeaders($headers);
    }

    /**
     * Устанавливает один заголовок и возвращает PendingRequest.
     */
    public function withHeader(string $name, string $value): PendingRequest
    {
        return $this->pending()->withHeader($name, $value);
    }

    /**
     * Устанавливает заголовок Authorization и возвращает PendingRequest.
     */
    public function withToken(string $token, string $type = 'Bearer'): PendingRequest
    {
        return $this->pending()->withToken($token, $type);
    }

    /**
     * Устанавливает Basic Auth и возвращает PendingRequest.
     */
    public function withBasicAuth(string $user, string $password): PendingRequest
    {
        return $this->pending()->withBasicAuth($user, $password);
    }

    /**
     * Устанавливает таймаут ожидания ответа и возвращает PendingRequest.
     */
    public function timeout(int $seconds): PendingRequest
    {
        return $this->pending()->timeout($seconds);
    }

    /**
     * Устанавливает количество повторных попыток и возвращает PendingRequest.
     */
    public function retry(int $times, int $sleepMs = 100): PendingRequest
    {
        return $this->pending()->retry($times, $sleepMs);
    }

    /**
     * Устанавливает формат тела запроса JSON и возвращает PendingRequest.
     */
    public function asJson(): PendingRequest
    {
        return $this->pending()->asJson();
    }

    /**
     * Устанавливает формат тела запроса form-encoded и возвращает PendingRequest.
     */
    public function asForm(): PendingRequest
    {
        return $this->pending()->asForm();
    }
}
