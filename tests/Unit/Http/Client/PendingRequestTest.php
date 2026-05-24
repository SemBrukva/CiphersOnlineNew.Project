<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Client;

use App\Http\Client\HttpException;
use App\Http\Client\PendingRequest;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Интеграционные тесты PendingRequest с реальным PHP built-in сервером.
 *
 * Сервер запускается один раз для всего класса через setUpBeforeClass().
 * Тесты пропускаются, если cURL недоступен или сервер не удалось запустить.
 */
final class PendingRequestTest extends TestCase
{
    /** @var resource|false|null */
    private static mixed $serverProcess = null;

    private static int $port = 18080;

    /**
     * Запускает тестовый HTTP-сервер перед запуском тестов класса.
     */
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('curl')) {
            return;
        }

        $script = dirname(__DIR__, 3) . '/fixtures/http_server.php';

        if (!file_exists($script)) {
            return;
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ];

        $cmd = sprintf('php -S 127.0.0.1:%d %s', self::$port, escapeshellarg($script));

        self::$serverProcess = proc_open($cmd, $descriptors, $pipes);

        // Ждём готовности сервера (до 2 секунд)
        $deadline = microtime(true) + 2.0;
        while (microtime(true) < $deadline) {
            $fp = @fsockopen('127.0.0.1', self::$port, $errno, $errstr, 0.1);
            if ($fp !== false) {
                fclose($fp);
                break;
            }
            usleep(50_000);
        }
    }

    /**
     * Останавливает тестовый HTTP-сервер после завершения всех тестов класса.
     */
    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$serverProcess)) {
            proc_terminate(self::$serverProcess);
            proc_close(self::$serverProcess);
            self::$serverProcess = null;
        }
    }

    protected function setUp(): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('Расширение cURL недоступно.');
        }

        if (!is_resource(self::$serverProcess)) {
            $this->markTestSkipped('Тестовый HTTP-сервер не запущен.');
        }
    }

    // -------------------------------------------------------------------------
    // Вспомогательные методы
    // -------------------------------------------------------------------------

    private function url(string $path = '/'): string
    {
        return 'http://127.0.0.1:' . self::$port . $path;
    }

    private function req(): PendingRequest
    {
        return new PendingRequest(['timeout' => 5]);
    }

    // -------------------------------------------------------------------------
    // GET-запросы
    // -------------------------------------------------------------------------

    /**
     * Проверяет успешный GET-запрос.
     */
    public function testGetReturnsSuccessfulResponse(): void
    {
        $response = $this->req()->get($this->url('/'));

        self::assertTrue($response->ok());
        self::assertSame(200, $response->status());
        self::assertSame('GET', $response->json('method'));
    }

    /**
     * Проверяет передачу параметров строки запроса в GET.
     */
    public function testGetAppendsQueryParameters(): void
    {
        $response = $this->req()->get($this->url('/'), ['page' => '2', 'q' => 'test']);

        $query = $response->json('query');
        self::assertSame('2', $query['page']);
        self::assertSame('test', $query['q']);
    }

    /**
     * Проверяет добавление параметров к URL, который уже содержит строку запроса.
     */
    public function testGetAppendsQueryToExistingQueryString(): void
    {
        $response = $this->req()->get($this->url('/?existing=1'), ['extra' => 'yes']);

        $query = $response->json('query');
        self::assertSame('1', $query['existing']);
        self::assertSame('yes', $query['extra']);
    }

    // -------------------------------------------------------------------------
    // POST/PUT/PATCH/DELETE
    // -------------------------------------------------------------------------

    /**
     * Проверяет POST с JSON-телом по умолчанию.
     */
    public function testPostSendsJsonBodyByDefault(): void
    {
        $response = $this->req()->post($this->url('/'), ['name' => 'Alice', 'age' => 30]);

        self::assertTrue($response->ok());
        self::assertSame('POST', $response->json('method'));

        $body = $response->json('body');
        self::assertSame('Alice', $body['name']);
        self::assertSame(30, $body['age']);

        self::assertStringContainsString('application/json', $response->json('headers')['Content-Type'] ?? '');
    }

    /**
     * Проверяет POST с form-encoded телом через asForm().
     */
    public function testPostAsFormSendsUrlEncodedBody(): void
    {
        $response = $this->req()
            ->asForm()
            ->post($this->url('/'), ['field' => 'hello', 'count' => '5']);

        self::assertTrue($response->ok());

        $body = $response->json('body');
        self::assertSame('hello', $body['field']);
        self::assertSame('5', $body['count']);

        self::assertStringContainsString(
            'application/x-www-form-urlencoded',
            $response->json('headers')['Content-Type'] ?? ''
        );
    }

    /**
     * Проверяет PUT-запрос с JSON-телом.
     */
    public function testPutSendsJsonBody(): void
    {
        $response = $this->req()->put($this->url('/'), ['status' => 'updated']);

        self::assertTrue($response->ok());
        self::assertSame('PUT', $response->json('method'));
        self::assertSame('updated', $response->json('body')['status'] ?? null);
    }

    /**
     * Проверяет PATCH-запрос с JSON-телом.
     */
    public function testPatchSendsJsonBody(): void
    {
        $response = $this->req()->patch($this->url('/'), ['active' => true]);

        self::assertTrue($response->ok());
        self::assertSame('PATCH', $response->json('method'));
        self::assertTrue($response->json('body')['active'] ?? false);
    }

    /**
     * Проверяет DELETE-запрос.
     */
    public function testDeleteRequest(): void
    {
        $response = $this->req()->delete($this->url('/'));

        self::assertTrue($response->ok());
        self::assertSame('DELETE', $response->json('method'));
    }

    // -------------------------------------------------------------------------
    // Заголовки авторизации
    // -------------------------------------------------------------------------

    /**
     * Проверяет установку Bearer-токена в заголовке Authorization.
     */
    public function testWithTokenSetsBearerAuthorizationHeader(): void
    {
        $response = $this->req()
            ->withToken('my-secret-token')
            ->get($this->url('/'));

        $auth = $response->json('headers')['Authorization'] ?? '';
        self::assertSame('Bearer my-secret-token', $auth);
    }

    /**
     * Проверяет поддержку нестандартного типа токена.
     */
    public function testWithTokenSupportsCustomType(): void
    {
        $response = $this->req()
            ->withToken('key123', 'ApiKey')
            ->get($this->url('/'));

        $auth = $response->json('headers')['Authorization'] ?? '';
        self::assertSame('ApiKey key123', $auth);
    }

    /**
     * Проверяет установку Basic Auth с кодированием base64.
     */
    public function testWithBasicAuthSetsBase64EncodedHeader(): void
    {
        $response = $this->req()
            ->withBasicAuth('admin', 'secret')
            ->get($this->url('/'));

        $expected = 'Basic ' . base64_encode('admin:secret');
        $auth     = $response->json('headers')['Authorization'] ?? '';
        self::assertSame($expected, $auth);
    }

    // -------------------------------------------------------------------------
    // Кастомные заголовки
    // -------------------------------------------------------------------------

    /**
     * Проверяет передачу произвольных заголовков.
     */
    public function testWithHeadersSetsCustomRequestHeaders(): void
    {
        $response = $this->req()
            ->withHeaders(['X-Api-Version' => '2', 'X-Trace-Id' => 'trace-abc'])
            ->get($this->url('/'));

        $headers = $response->json('headers');
        self::assertSame('2', $headers['X-Api-Version'] ?? null);
        self::assertSame('trace-abc', $headers['X-Trace-Id'] ?? null);
    }

    // -------------------------------------------------------------------------
    // Иммутабельность
    // -------------------------------------------------------------------------

    /**
     * Проверяет, что modifier-методы не изменяют исходный PendingRequest.
     */
    public function testFluentModifiersDoNotMutateOriginalRequest(): void
    {
        $original  = $this->req();
        $withToken = $original->withToken('secret');

        $responseOriginal  = $original->get($this->url('/'));
        $responseWithToken = $withToken->get($this->url('/'));

        // Оригинальный запрос не должен содержать Authorization
        self::assertNull($responseOriginal->json('headers')['Authorization'] ?? null);
        // Запрос с токеном должен содержать Authorization
        self::assertSame('Bearer secret', $responseWithToken->json('headers')['Authorization'] ?? null);
    }

    // -------------------------------------------------------------------------
    // Обработка HTTP-ошибок
    // -------------------------------------------------------------------------

    /**
     * Проверяет, что 4xx-ответ возвращается без броска исключения.
     */
    public function test4xxResponseIsReturnedWithoutThrowing(): void
    {
        $response = $this->req()->get($this->url('/status/404'));

        self::assertFalse($response->ok());
        self::assertTrue($response->failed());
        self::assertTrue($response->clientError());
        self::assertSame(404, $response->status());
    }

    /**
     * Проверяет, что 5xx-ответ возвращается без броска исключения.
     */
    public function test5xxResponseIsReturnedWithoutThrowing(): void
    {
        $response = $this->req()->get($this->url('/status/500'));

        self::assertFalse($response->ok());
        self::assertTrue($response->failed());
        self::assertTrue($response->serverError());
        self::assertSame(500, $response->status());
    }

    /**
     * Проверяет, что throw() бросает HttpException на 4xx-ответ.
     */
    public function testThrowRaisesHttpExceptionFor4xx(): void
    {
        $this->expectException(HttpException::class);

        $this->req()->get($this->url('/status/422'))->throw();
    }

    /**
     * Проверяет, что HttpException содержит правильный код статуса.
     */
    public function testHttpExceptionContainsCorrectStatus(): void
    {
        try {
            $this->req()->get($this->url('/status/403'))->throw();
            self::fail('Ожидалось исключение HttpException.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->response()->status());
        }
    }

    // -------------------------------------------------------------------------
    // Сетевые ошибки и retry
    // -------------------------------------------------------------------------

    /**
     * Проверяет, что подключение к недоступному хосту бросает RuntimeException.
     */
    public function testConnectionErrorThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        (new PendingRequest(['timeout' => 1, 'connect_timeout' => 1]))
            ->get('http://127.0.0.1:19999/unavailable');
    }

    /**
     * Проверяет, что retry() исчерпывает все попытки и пробрасывает исключение.
     */
    public function testRetryExhaustsAttemptsAndThrows(): void
    {
        $this->expectException(RuntimeException::class);

        (new PendingRequest(['timeout' => 1, 'connect_timeout' => 1]))
            ->retry(2, 0)
            ->get('http://127.0.0.1:19999/unavailable');
    }

    // -------------------------------------------------------------------------
    // Формат ответа
    // -------------------------------------------------------------------------

    /**
     * Проверяет доступ к заголовку Content-Type в ответе.
     */
    public function testResponseHeaderIsAccessible(): void
    {
        $response = $this->req()->get($this->url('/'));

        self::assertStringContainsString('application/json', $response->header('Content-Type') ?? '');
    }

    /**
     * Проверяет body() для нестандартного текстового ответа.
     */
    public function testResponseBodyIsAccessible(): void
    {
        $response = $this->req()->get($this->url('/'));

        self::assertNotEmpty($response->body());
        self::assertStringStartsWith('{', $response->body());
    }
}
