<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Client;

use App\Http\Client\HttpClient;
use App\Http\Client\HttpClientInterface;
use App\Http\Client\PendingRequest;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет фабричное поведение HttpClient и делегирование в PendingRequest.
 */
final class HttpClientTest extends TestCase
{
    private HttpClient $client;

    protected function setUp(): void
    {
        $this->client = new HttpClient(['timeout' => 10]);
    }

    /**
     * Проверяет реализацию контракта HttpClientInterface.
     */
    public function testImplementsHttpClientInterface(): void
    {
        self::assertInstanceOf(HttpClientInterface::class, $this->client);
    }

    /**
     * Проверяет, что pending() возвращает экземпляр PendingRequest.
     */
    public function testPendingReturnsPendingRequest(): void
    {
        self::assertInstanceOf(PendingRequest::class, $this->client->pending());
    }

    /**
     * Проверяет, что каждый вызов pending() создаёт новый экземпляр.
     */
    public function testPendingCreatesFreshInstanceOnEachCall(): void
    {
        $a = $this->client->pending();
        $b = $this->client->pending();

        self::assertNotSame($a, $b);
    }

    /**
     * Проверяет, что withHeaders() возвращает PendingRequest.
     */
    public function testWithHeadersReturnsPendingRequest(): void
    {
        $pending = $this->client->withHeaders(['X-Test' => 'value']);

        self::assertInstanceOf(PendingRequest::class, $pending);
    }

    /**
     * Проверяет, что withHeader() возвращает PendingRequest.
     */
    public function testWithHeaderReturnsPendingRequest(): void
    {
        $pending = $this->client->withHeader('X-Test', 'value');

        self::assertInstanceOf(PendingRequest::class, $pending);
    }

    /**
     * Проверяет, что withToken() возвращает PendingRequest.
     */
    public function testWithTokenReturnsPendingRequest(): void
    {
        $pending = $this->client->withToken('secret-token');

        self::assertInstanceOf(PendingRequest::class, $pending);
    }

    /**
     * Проверяет, что withBasicAuth() возвращает PendingRequest.
     */
    public function testWithBasicAuthReturnsPendingRequest(): void
    {
        $pending = $this->client->withBasicAuth('user', 'pass');

        self::assertInstanceOf(PendingRequest::class, $pending);
    }

    /**
     * Проверяет, что timeout() возвращает PendingRequest.
     */
    public function testTimeoutReturnsPendingRequest(): void
    {
        $pending = $this->client->timeout(60);

        self::assertInstanceOf(PendingRequest::class, $pending);
    }

    /**
     * Проверяет, что retry() возвращает PendingRequest.
     */
    public function testRetryReturnsPendingRequest(): void
    {
        $pending = $this->client->retry(3, 200);

        self::assertInstanceOf(PendingRequest::class, $pending);
    }

    /**
     * Проверяет, что asJson() возвращает PendingRequest.
     */
    public function testAsJsonReturnsPendingRequest(): void
    {
        $pending = $this->client->asJson();

        self::assertInstanceOf(PendingRequest::class, $pending);
    }

    /**
     * Проверяет, что asForm() возвращает PendingRequest.
     */
    public function testAsFormReturnsPendingRequest(): void
    {
        $pending = $this->client->asForm();

        self::assertInstanceOf(PendingRequest::class, $pending);
    }

    /**
     * Проверяет, что builder-методы возвращают независимые экземпляры PendingRequest.
     */
    public function testBuilderMethodsReturnIndependentInstances(): void
    {
        $a = $this->client->withToken('token-a');
        $b = $this->client->withToken('token-b');

        self::assertNotSame($a, $b);
    }
}
