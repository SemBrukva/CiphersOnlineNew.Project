<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Container\Container;
use App\Http\Pipeline;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Проверяет диспетчеризацию маршрутов и вызов контроллеров.
 */
final class RouterTest extends TestCase
{
    /**
     * Проверяет обработку точного маршрута.
     */
    public function testDispatchMatchesExactRoute(): void
    {
        $container = new Container();
        $container->instance(RouterTestController::class, new RouterTestController());

        $router = new Router(
            ['GET /hello' => ['controller' => RouterTestController::class, 'method' => 'hello']],
            $container,
            new Pipeline($container)
        );

        $request = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/hello'], [], [], [], []);
        $response = $router->dispatch($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('hello', $response->getContent());
    }

    /**
     * Проверяет извлечение параметров из маршрута с плейсхолдерами.
     */
    public function testDispatchMatchesPatternRouteAndPassesParams(): void
    {
        $container = new Container();
        $container->instance(RouterTestController::class, new RouterTestController());

        $router = new Router(
            ['GET /users/{id}' => ['controller' => RouterTestController::class, 'method' => 'show']],
            $container,
            new Pipeline($container)
        );

        $request = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/users/42'], [], [], [], []);
        $response = $router->dispatch($request);

        self::assertSame('id=42', $response->getContent());
    }

    /**
     * Проверяет поддержку constraint в параметре маршрута.
     */
    public function testDispatchMatchesPatternRouteWithConstraint(): void
    {
        $container = new Container();
        $container->instance(RouterTestController::class, new RouterTestController());

        $router = new Router(
            ['GET /users/{id:\\d+}' => ['controller' => RouterTestController::class, 'method' => 'show']],
            $container,
            new Pipeline($container)
        );

        $ok = $router->dispatch(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/users/42'], [], [], [], []));
        $fail = $router->dispatch(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/users/abc'], [], [], [], []));

        self::assertSame('id=42', $ok->getContent());
        self::assertSame(404, $fail->getStatusCode());
    }

    /**
     * Проверяет стандартный 404-ответ при отсутствии маршрута.
     */
    public function testDispatchReturnsDefault404WhenRouteMissing(): void
    {
        $container = new Container();
        $router = new Router([], $container, new Pipeline($container));

        $request = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/missing'], [], [], [], []);
        $response = $router->dispatch($request);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('404 Not Found', $response->getContent());
    }

    /**
     * Проверяет пользовательский обработчик 404.
     */
    public function testDispatchUsesCustomNotFoundHandler(): void
    {
        $container = new Container();

        $router = new Router(
            [],
            $container,
            new Pipeline($container),
            static fn (): Response => new Response('custom-404', 404)
        );

        $request = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/missing'], [], [], [], []);
        $response = $router->dispatch($request);

        self::assertSame('custom-404', $response->getContent());
    }

    /**
     * Проверяет исключение для невалидной конфигурации маршрута.
     */
    public function testDispatchThrowsForInvalidRouteDefinition(): void
    {
        $container = new Container();
        $router = new Router(['GET /bad' => ['controller' => RouterTestController::class]], $container, new Pipeline($container));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid route definition for GET /bad');

        $router->dispatch(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/bad'], [], [], [], []));
    }

    /**
     * Проверяет исключение, если контроллер вернул не Response.
     */
    public function testDispatchThrowsWhenControllerReturnsNotResponse(): void
    {
        $container = new Container();
        $container->instance(BadRouterController::class, new BadRouterController());

        $router = new Router(
            ['GET /bad' => ['controller' => BadRouterController::class, 'method' => 'bad']],
            $container,
            new Pipeline($container)
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Controller response must be an instance of Response.');

        $router->dispatch(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/bad'], [], [], [], []));
    }
}

/**
 * Тестовый контроллер для позитивных сценариев роутера.
 */
final class RouterTestController
{
    /**
     * Возвращает статичный ответ.
     */
    public function hello(Request $request): Response
    {
        return new Response('hello');
    }

    /**
     * Возвращает ответ с параметром маршрута.
     */
    public function show(Request $request): Response
    {
        return new Response('id=' . (string) $request->route('id'));
    }
}

/**
 * Тестовый контроллер, нарушающий контракт возвращаемого типа.
 */
final class BadRouterController
{
    /**
     * Возвращает некорректный тип для проверки ошибки роутера.
     */
    public function bad(Request $request): string
    {
        return 'bad';
    }
}
