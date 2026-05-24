<?php

declare(strict_types=1);

namespace App\Http\Client;

/**
 * Контракт HTTP-клиента для выполнения внешних запросов.
 */
interface HttpClientInterface
{
    /**
     * Создаёт новый PendingRequest с базовыми настройками клиента.
     */
    public function pending(): PendingRequest;

    /**
     * Выполняет GET-запрос.
     *
     * @param  array<string, scalar> $query Параметры строки запроса.
     */
    public function get(string $url, array $query = []): HttpResponse;

    /**
     * Выполняет POST-запрос.
     *
     * @param  array<string, mixed> $data Тело запроса.
     */
    public function post(string $url, array $data = []): HttpResponse;

    /**
     * Выполняет PUT-запрос.
     *
     * @param  array<string, mixed> $data Тело запроса.
     */
    public function put(string $url, array $data = []): HttpResponse;

    /**
     * Выполняет PATCH-запрос.
     *
     * @param  array<string, mixed> $data Тело запроса.
     */
    public function patch(string $url, array $data = []): HttpResponse;

    /**
     * Выполняет DELETE-запрос.
     *
     * @param  array<string, mixed> $data Необязательное тело запроса.
     */
    public function delete(string $url, array $data = []): HttpResponse;

    /**
     * Устанавливает несколько заголовков и возвращает PendingRequest.
     *
     * @param  array<string, string> $headers
     */
    public function withHeaders(array $headers): PendingRequest;

    /**
     * Устанавливает заголовок Authorization и возвращает PendingRequest.
     */
    public function withToken(string $token, string $type = 'Bearer'): PendingRequest;

    /**
     * Устанавливает Basic Auth и возвращает PendingRequest.
     */
    public function withBasicAuth(string $user, string $password): PendingRequest;

    /**
     * Устанавливает таймаут и возвращает PendingRequest.
     */
    public function timeout(int $seconds): PendingRequest;

    /**
     * Устанавливает количество повторных попыток и возвращает PendingRequest.
     */
    public function retry(int $times, int $sleepMs = 100): PendingRequest;
}
