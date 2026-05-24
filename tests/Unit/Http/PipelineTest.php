<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Container\Container;
use App\Http\MiddlewareInterface;
use App\Http\Pipeline;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет порядок и правила выполнения middleware-цепочки.
 */
final class PipelineTest extends TestCase
{
    /**
     * Проверяет, что middleware исполняются в правильном порядке.
     */
    public function testRunsMiddlewaresInDeclaredOrder(): void
    {
        $trace = [];
        $container = new Container();
        $container->instance(FirstMiddleware::class, new FirstMiddleware($trace));
        $container->instance(SecondMiddleware::class, new SecondMiddleware($trace));

        $pipeline = new Pipeline($container);
        $request = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'], [], [], [], []);

        $response = $pipeline->run(
            $request,
            [FirstMiddleware::class, SecondMiddleware::class],
            static function () use (&$trace): Response {
                $trace[] = 'handler';
                return new Response('ok');
            }
        );

        self::assertSame('ok', $response->getContent());
        self::assertSame(['first:before', 'second:before', 'handler', 'second:after', 'first:after'], $trace);
    }

    /**
     * Проверяет, что pipeline с пустым списком middleware вызывает только handler.
     */
    public function testRunsHandlerWhenNoMiddlewaresPassed(): void
    {
        $pipeline = new Pipeline(new Container());
        $request = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'], [], [], [], []);

        $response = $pipeline->run($request, [], static fn (): Response => new Response('direct'));

        self::assertSame('direct', $response->getContent());
    }

    /**
     * Проверяет, что middleware может прервать цепочку и вернуть свой ответ.
     */
    public function testMiddlewareCanShortCircuitPipeline(): void
    {
        $container = new Container();
        $container->instance(StopMiddleware::class, new StopMiddleware());

        $pipeline = new Pipeline($container);
        $request = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'], [], [], [], []);

        $response = $pipeline->run(
            $request,
            [StopMiddleware::class],
            static fn (): Response => new Response('handler')
        );

        self::assertSame('stopped', $response->getContent());
    }
}

/**
 * Middleware для теста порядка выполнения (первый в цепочке).
 */
final class FirstMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт middleware с общей трассировкой.
     *
     * @param array<int, string> $trace
     */
    public function __construct(private array &$trace)
    {
    }

    /**
     * Добавляет маркеры до и после следующего обработчика.
     */
    public function process(Request $request, callable $next): Response
    {
        $this->trace[] = 'first:before';
        $response = $next($request);
        $this->trace[] = 'first:after';

        return $response;
    }
}

/**
 * Middleware для теста порядка выполнения (второй в цепочке).
 */
final class SecondMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт middleware с общей трассировкой.
     *
     * @param array<int, string> $trace
     */
    public function __construct(private array &$trace)
    {
    }

    /**
     * Добавляет маркеры до и после следующего обработчика.
     */
    public function process(Request $request, callable $next): Response
    {
        $this->trace[] = 'second:before';
        $response = $next($request);
        $this->trace[] = 'second:after';

        return $response;
    }
}

/**
 * Middleware, который завершает цепочку раньше времени.
 */
final class StopMiddleware implements MiddlewareInterface
{
    /**
     * Возвращает ответ без вызова следующего обработчика.
     */
    public function process(Request $request, callable $next): Response
    {
        return new Response('stopped');
    }
}
