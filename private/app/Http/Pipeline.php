<?php

declare(strict_types=1);

namespace App\Http;

use App\Container\Container;
use App\Debug\Profiler;

/**
 * Запускает цепочку middleware вокруг финального обработчика запроса.
 *
 * Middleware оборачиваются друг вокруг друга через array_reduce,
 * образуя вложенную цепочку вызовов от первого к последнему.
 */
final readonly class Pipeline
{
    /**
     * Создаёт экземпляр Pipeline.
     *
     * @param Profiler|null $profiler Если передан — каждый middleware автоматически получает span.
     */
    public function __construct(
        private Container  $container,
        private ?Profiler  $profiler = null
    ) {
    }

    /**
     * Прогоняет запрос через список middleware и вызывает $handler в конце цепочки.
     *
     * @param string[] $middlewares FQCN middleware в порядке их выполнения.
     * @param callable $handler     Финальный обработчик (роутер).
     */
    public function run(Request $request, array $middlewares, callable $handler): Response
    {
        $chain = array_reduce(
            array_reverse($middlewares),
            function (callable $next, string $class): callable {
                return function (Request $request) use ($next, $class): Response {
                    /** @var MiddlewareInterface $middleware */
                    $middleware = $this->container->get($class);

                    if ($this->profiler === null) {
                        return $middleware->process($request, $next);
                    }

                    // Измеряем только pre-фазу (до вызова $next), чтобы не включать
                    // время всей оставшейся цепочки в длительность этого span.
                    $spanName  = $this->shortName($class);
                    $preStart  = microtime(true);

                    $wrappedNext = function (Request $req) use ($next, $spanName, $preStart): Response {
                        $this->profiler->addSpan($spanName, 'middleware', $preStart, microtime(true));
                        return $next($req);
                    };

                    return $middleware->process($request, $wrappedNext);
                };
            },
            $handler
        );

        return ($chain)($request);
    }

    /**
     * Возвращает читаемое короткое имя класса middleware для отображения в профайлере.
     * Например: App\Http\Middleware\SessionMiddleware → Session.
     */
    private function shortName(string $class): string
    {
        $base = substr(strrchr($class, '\\') ?: $class, 1);

        return str_ends_with($base, 'Middleware') ? substr($base, 0, -10) : $base;
    }
}
