<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Config\Config;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет поведение CorsMiddleware.
 */
final class CorsMiddlewareTest extends TestCase
{
    /**
     * Инициализирует тестовую конфигурацию CORS.
     */
    protected function setUp(): void
    {
        global $config;
        $config = new Config([
            'cors' => [
                'allowed_origins' => ['*'],
                'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
                'allowed_headers' => ['Content-Type'],
                'exposed_headers' => ['X-Request-Id'],
                'allow_credentials' => false,
                'max_age' => 600,
            ],
        ]);
    }

    /**
     * Проверяет, что preflight-запрос обрабатывается с кодом 204.
     */
    public function testPreflightRequestReturnsNoContent(): void
    {
        $middleware = new CorsMiddleware();
        $request = new Request([
            'REQUEST_METHOD' => 'OPTIONS',
            'REQUEST_URI' => '/api/ping',
            'HTTP_ORIGIN' => 'https://frontend.example.com',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('next'));

        self::assertSame(204, $response->getStatusCode());
    }

    /**
     * Проверяет, что обычный API-запрос пропускается дальше по цепочке.
     */
    public function testNonPreflightRequestCallsNextMiddleware(): void
    {
        $middleware = new CorsMiddleware();
        $request = new Request([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/ping',
            'HTTP_ORIGIN' => 'https://frontend.example.com',
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('ok', 200));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', $response->getContent());
    }
}
