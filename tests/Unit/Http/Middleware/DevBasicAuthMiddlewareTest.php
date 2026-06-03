<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Config\Config;
use App\Http\Middleware\DevBasicAuthMiddleware;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет поведение DevBasicAuthMiddleware.
 */
final class DevBasicAuthMiddlewareTest extends TestCase
{
    /**
     * Проверяет, что вне dev-окружения middleware не требует авторизацию.
     */
    public function testDoesNotRequireAuthOutsideDevEnvironment(): void
    {
        global $config;
        $config = new Config([
            'app' => [
                'env' => 'local',
            ],
        ]);

        $middleware = new DevBasicAuthMiddleware();
        $request    = new Request([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/',
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('ok'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', $response->getContent());
    }

    /**
     * Проверяет ответ 503, если dev-окружение включено без логина или пароля.
     */
    public function testReturnsServiceUnavailableWhenCredentialsAreNotConfigured(): void
    {
        global $config;
        $config = new Config([
            'app' => [
                'env' => 'dev',
                'dev_basic_auth' => [
                    'username' => '',
                    'password' => '',
                ],
            ],
        ]);

        $middleware = new DevBasicAuthMiddleware();
        $request    = new Request([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/',
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('ok'));

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('Dev Basic Auth is not configured.', $response->getContent());
    }

    /**
     * Проверяет, что запрос без Basic Auth получает challenge.
     */
    public function testReturnsChallengeWhenCredentialsAreMissing(): void
    {
        global $config;
        $config = $this->makeDevConfig();

        $middleware = new DevBasicAuthMiddleware();
        $request    = new Request([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/',
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('ok'));

        self::assertSame(401, $response->getStatusCode());
        self::assertSame('Basic realm="Dev Server", charset="UTF-8"', $response->getHeaders()['WWW-Authenticate'] ?? null);
    }

    /**
     * Проверяет, что неверные Basic Auth данные отклоняются.
     */
    public function testRejectsInvalidAuthorizationHeaderCredentials(): void
    {
        global $config;
        $config = $this->makeDevConfig();

        $middleware = new DevBasicAuthMiddleware();
        $request    = new Request([
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/',
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('dev:wrong'),
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('ok'));

        self::assertSame(401, $response->getStatusCode());
    }

    /**
     * Проверяет, что корректный заголовок Authorization пропускает запрос дальше.
     */
    public function testAllowsValidAuthorizationHeaderCredentials(): void
    {
        global $config;
        $config = $this->makeDevConfig();

        $middleware = new DevBasicAuthMiddleware();
        $request    = new Request([
            'REQUEST_METHOD'     => 'GET',
            'REQUEST_URI'        => '/',
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode('dev:secret'),
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('ok'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', $response->getContent());
    }

    /**
     * Проверяет, что PHP_AUTH_USER/PHP_AUTH_PW также поддерживаются.
     */
    public function testAllowsValidPhpAuthServerCredentials(): void
    {
        global $config;
        $config = $this->makeDevConfig();

        $middleware = new DevBasicAuthMiddleware();
        $request    = new Request([
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/',
            'PHP_AUTH_USER'  => 'dev',
            'PHP_AUTH_PW'    => 'secret',
        ], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('ok'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('ok', $response->getContent());
    }

    /**
     * Создаёт конфигурацию dev-окружения для тестов.
     */
    private function makeDevConfig(): Config
    {
        return new Config([
            'app' => [
                'env' => 'dev',
                'dev_basic_auth' => [
                    'username' => 'dev',
                    'password' => 'secret',
                    'realm'    => 'Dev Server',
                ],
            ],
        ]);
    }
}
