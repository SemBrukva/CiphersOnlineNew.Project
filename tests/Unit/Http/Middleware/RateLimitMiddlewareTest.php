<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Cache\CacheInterface;
use App\Config\Config;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Request;
use App\Http\RequestContext;
use App\Http\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет поведение RateLimitMiddleware.
 */
final class RateLimitMiddlewareTest extends TestCase
{
    /** @var CacheInterface&MockObject */
    private CacheInterface $cache;

    private RequestContext $context;

    private RateLimitMiddleware $middleware;

    /**
     * Инициализирует зависимости и тестовую конфигурацию.
     */
    protected function setUp(): void
    {
        global $config;
        $config = new Config([
            'rate_limit' => [
                'rules' => [
                    'api_auth_login' => [
                        'method'         => 'POST',
                        'path'           => '/api/auth/login',
                        'max_attempts'   => 3,
                        'window_seconds' => 60,
                    ],
                    'api_contact' => [
                        'method'         => 'POST',
                        'path'           => '/api/contact',
                        'max_attempts'   => 5,
                        'window_seconds' => 120,
                    ],
                    'api_user_profile' => [
                        'method'         => 'GET',
                        'path'           => '/api/user/profile',
                        'max_attempts'   => 10,
                        'window_seconds' => 60,
                    ],
                ],
            ],
        ]);

        $this->cache   = $this->createMock(CacheInterface::class);
        $this->context = new RequestContext('test-request-id', microtime(true), true);

        $this->middleware = new RateLimitMiddleware($this->cache, $this->context);
    }

    /**
     * Создаёт тестовый Request с заданным методом, путём и IP.
     */
    private function makeRequest(string $method, string $path, string $ip = '127.0.0.1'): Request
    {
        return new Request(
            [
                'REQUEST_METHOD' => $method,
                'REQUEST_URI'    => $path,
                'REMOTE_ADDR'    => $ip,
            ],
            [],
            [],
            [],
            []
        );
    }

    /**
     * Возвращает callable-next, который фиксирует факт вызова.
     */
    private function makeNext(bool &$called): callable
    {
        return function (Request $request) use (&$called): Response {
            $called = true;
            return new Response('ok', 200);
        };
    }

    // -------------------------------------------------------------------------
    // Маршруты без правила
    // -------------------------------------------------------------------------

    /**
     * Запрос, не совпадающий ни с одним правилом, пропускается без обращения к кешу.
     */
    public function testRequestWithoutRulePassesThrough(): void
    {
        $this->cache->expects(self::never())->method('get');
        $this->cache->expects(self::never())->method('set');

        $called = false;
        $request = $this->makeRequest('GET', '/api/admin/stats');
        $response = $this->middleware->process($request, $this->makeNext($called));

        self::assertTrue($called);
        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * POST на путь, для которого есть правило GET, пропускается без лимита.
     */
    public function testMethodMismatchPassesThrough(): void
    {
        $this->cache->expects(self::never())->method('get');

        $called = false;
        $request = $this->makeRequest('POST', '/api/user/profile');
        $response = $this->middleware->process($request, $this->makeNext($called));

        self::assertTrue($called);
        self::assertSame(200, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Нормальный проход в пределах лимита
    // -------------------------------------------------------------------------

    /**
     * Первый запрос в пределах лимита пропускается, счётчик записывается в кеш.
     */
    public function testFirstRequestWithinLimitPasses(): void
    {
        $this->cache->method('get')->willReturn(0);
        $this->cache->expects(self::once())
            ->method('set')
            ->with(
                self::stringContains('rate_limit:api_auth_login:'),
                1,
                60
            );

        $called = false;
        $request = $this->makeRequest('POST', '/api/auth/login');
        $response = $this->middleware->process($request, $this->makeNext($called));

        self::assertTrue($called);
        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * Запрос с числом попыток ниже лимита пропускается, счётчик увеличивается.
     */
    public function testRequestBelowLimitPasses(): void
    {
        $this->cache->method('get')->willReturn(2); // max_attempts = 3, 2 < 3 → проходит
        $this->cache->expects(self::once())
            ->method('set')
            ->with(self::anything(), 3, self::anything());

        $called = false;
        $request = $this->makeRequest('POST', '/api/auth/login');
        $response = $this->middleware->process($request, $this->makeNext($called));

        self::assertTrue($called);
        self::assertSame(200, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Превышение лимита
    // -------------------------------------------------------------------------

    /**
     * При достижении лимита возвращается JSON 429 без вызова следующего обработчика.
     */
    public function testExceededLimitReturns429Json(): void
    {
        $this->cache->method('get')->willReturn(3); // attempts >= max_attempts (3)
        $this->cache->expects(self::never())->method('set');

        $called = false;
        $request = $this->makeRequest('POST', '/api/auth/login');
        $response = $this->middleware->process($request, $this->makeNext($called));

        self::assertFalse($called);
        self::assertSame(429, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        self::assertSame('too_many_requests', $body['error']['code']);
        self::assertSame('test-request-id', $body['error']['request_id']);
    }

    /**
     * При превышении лимита на не-API пути возвращается текстовый 429 с заголовком Retry-After.
     */
    public function testExceededLimitOnNonApiPathReturnsTextResponse(): void
    {
        global $config;
        $config = new Config([
            'rate_limit' => [
                'rules' => [
                    'login' => [
                        'method'         => 'POST',
                        'path'           => '/login',
                        'max_attempts'   => 2,
                        'window_seconds' => 30,
                    ],
                ],
            ],
        ]);

        $context    = new RequestContext('req-456', microtime(true), false);
        $middleware = new RateLimitMiddleware($this->cache, $context);

        $this->cache->method('get')->willReturn(5);

        $request  = $this->makeRequest('POST', '/login');
        $response = $middleware->process($request, fn () => new Response('ok'));

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('Too Many Requests', $response->getContent());
        self::assertSame('30', $response->getHeaders()['Retry-After']);
    }

    /**
     * Превышение лимита для /api/contact возвращает 429.
     */
    public function testContactEndpointRateLimitIsEnforced(): void
    {
        $this->cache->method('get')->willReturn(5); // max_attempts = 5 → заблокирован

        $called = false;
        $request = $this->makeRequest('POST', '/api/contact');
        $response = $this->middleware->process($request, $this->makeNext($called));

        self::assertFalse($called);
        self::assertSame(429, $response->getStatusCode());
    }

    // -------------------------------------------------------------------------
    // Изоляция по IP
    // -------------------------------------------------------------------------

    /**
     * Ключ кеша включает MD5 от IP, поэтому два разных IP не влияют друг на друга.
     */
    public function testCacheKeyIsolatedByIp(): void
    {
        $capturedKeys = [];

        $this->cache
            ->method('get')
            ->willReturnCallback(function (string $key) use (&$capturedKeys): int {
                $capturedKeys[] = $key;
                return 0;
            });
        $this->cache->method('set');

        $req1 = $this->makeRequest('POST', '/api/auth/login', '1.2.3.4');
        $req2 = $this->makeRequest('POST', '/api/auth/login', '5.6.7.8');

        $this->middleware->process($req1, fn () => new Response('ok'));
        $this->middleware->process($req2, fn () => new Response('ok'));

        self::assertCount(2, $capturedKeys);
        self::assertNotSame($capturedKeys[0], $capturedKeys[1]);
        self::assertStringStartsWith('rate_limit:api_auth_login:', $capturedKeys[0]);
        self::assertStringStartsWith('rate_limit:api_auth_login:', $capturedKeys[1]);
    }

    /**
     * Ключ кеша содержит имя правила из конфигурации.
     */
    public function testCacheKeyContainsRuleName(): void
    {
        $capturedKey = null;

        $this->cache
            ->method('get')
            ->willReturnCallback(function (string $key) use (&$capturedKey): int {
                $capturedKey = $key;
                return 0;
            });
        $this->cache->method('set');

        $request = $this->makeRequest('POST', '/api/contact');
        $this->middleware->process($request, fn () => new Response('ok'));

        self::assertNotNull($capturedKey);
        self::assertStringStartsWith('rate_limit:api_contact:', $capturedKey);
    }

    // -------------------------------------------------------------------------
    // Граничные значения
    // -------------------------------------------------------------------------

    /**
     * Запрос с нулевым счётчиком (отсутствие ключа) корректно обрабатывается.
     */
    public function testZeroAttemptsFromCacheDefaultPasses(): void
    {
        $this->cache->method('get')->willReturn(null); // get возвращает null → (int) null = 0

        $called = false;
        $request = $this->makeRequest('POST', '/api/auth/login');
        $response = $this->middleware->process($request, $this->makeNext($called));

        self::assertTrue($called);
        self::assertSame(200, $response->getStatusCode());
    }

    /**
     * При превышении лимита счётчик в кеш не записывается.
     */
    public function testCacheSetNotCalledWhenLimitExceeded(): void
    {
        $this->cache->method('get')->willReturn(100);
        $this->cache->expects(self::never())->method('set');

        $request = $this->makeRequest('GET', '/api/user/profile');
        $this->middleware->process($request, fn () => new Response('ok'));
    }
}
