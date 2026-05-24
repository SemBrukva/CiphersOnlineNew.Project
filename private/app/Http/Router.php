<?php

declare(strict_types=1);

namespace App\Http;

use App\Container\Container;
use App\Debug\MatchedRoute;
use App\Debug\Profiler;
use Closure;
use RuntimeException;

/**
 * Маршрутизатор HTTP-запросов.
 *
 * Сопоставляет входящий запрос с таблицей маршрутов, разрешает контроллер
 * из контейнера и запускает per-route pipeline перед вызовом экшена.
 */
readonly class Router implements RouteMatcherInterface
{
    /**
     * Создаёт экземпляр роутера.
     *
     * @param array<string, array<string, mixed>> $routes          Таблица маршрутов из конфигурации.
     * @param Container                           $container       Сервис-контейнер для разрешения контроллеров.
     * @param Pipeline                            $pipeline        Pipeline для per-route middleware.
     * @param null|Closure                        $notFoundHandler Обработчик ответа для ненайденного маршрута.
     * @param MatchedRoute|null                   $matchedRoute    Хранитель данных о совпавшем маршруте (для отладки).
     * @param Profiler|null                       $profiler        Профайлер для span вызова контроллера.
     */
    public function __construct(
        private array         $routes,
        private Container     $container,
        private Pipeline      $pipeline,
        private ?Closure      $notFoundHandler = null,
        private ?MatchedRoute $matchedRoute    = null,
        private ?Profiler     $profiler        = null
    ) {
    }

    /**
     * Возвращает определение маршрута для HTTP-метода и пути или null, если маршрут не найден.
     *
     * @return array<string, mixed>|null
     */
    public function match(string $method, string $path): ?array
    {
        [$route] = $this->resolve($method . ' ' . $path);

        return $route;
    }

    /**
     * Диспетчеризирует запрос: находит маршрут, запускает middleware и вызывает контроллер.
     * Возвращает 404, если маршрут не найден.
     */
    public function dispatch(Request $request): Response
    {
        $path     = parse_url($request->getUri(), PHP_URL_PATH) ?: '/';
        $routeKey = $request->getMethod() . ' ' . $path;

        [$route, $params] = $this->resolve($routeKey);

        if ($route === null) {
            if ($this->notFoundHandler !== null) {
                return ($this->notFoundHandler)($request);
            }

            return new Response('404 Not Found', 404);
        }

        $controllerClass  = $route['controller'] ?? null;
        $controllerMethod = $route['method'] ?? null;

        if (!is_string($controllerClass) || !is_string($controllerMethod)) {
            throw new RuntimeException('Invalid route definition for ' . $routeKey);
        }

        if (!class_exists($controllerClass)) {
            throw new RuntimeException('Controller class not found: ' . $controllerClass);
        }

        $controller = $this->container->get($controllerClass);

        if (!method_exists($controller, $controllerMethod)) {
            throw new RuntimeException('Controller method not found: ' . $controllerClass . '::' . $controllerMethod);
        }

        $this->matchedRoute?->fill(
            $controllerClass,
            $controllerMethod,
            $route['middleware'] ?? [],
            $routeKey
        );

        $resolved = $params ? $request->withRouteParams($params) : $request;

        $shortController = substr(strrchr($controllerClass, '\\') ?: $controllerClass, 1);
        $spanName        = $shortController . '::' . $controllerMethod;

        return $this->pipeline->run(
            $resolved,
            $route['middleware'] ?? [],
            function (Request $req) use ($controller, $controllerMethod, $spanName): Response {
                $this->profiler?->start($spanName, 'controller');
                $response = $controller->{$controllerMethod}($req);
                $this->profiler?->stop($spanName);

                if (!$response instanceof Response) {
                    throw new RuntimeException('Controller response must be an instance of Response.');
                }

                return $response;
            }
        );
    }

    /**
     * Ищет маршрут по ключу «METHOD /path» — сначала точное совпадение, затем по шаблону.
     *
     * @return array{array<string,mixed>|null, array<string,string>} [маршрут, параметры URI]
     */
    private function resolve(string $routeKey): array
    {
        if (isset($this->routes[$routeKey])) {
            return [$this->routes[$routeKey], []];
        }

        foreach ($this->routes as $pattern => $route) {
            $params = $this->matchPattern($pattern, $routeKey);
            if ($params !== null) {
                return [$route, $params];
            }
        }

        return [null, []];
    }

    /**
     * Сопоставляет ключ запроса с шаблоном, содержащим плейсхолдеры вида {param}.
     * Возвращает ассоциативный массив параметров или null, если шаблон не совпал.
     *
     * @return array<string, string>|null
     */
    private function matchPattern(string $pattern, string $routeKey): ?array
    {
        if (!str_contains($pattern, '{')) {
            return null;
        }

        $names = [];
        $offset = 0;
        $regex = '';

        if (preg_match_all('/\{([a-z_][a-z0-9_]*)(?::([^}]+))?\}/i', $pattern, $all, PREG_OFFSET_CAPTURE) === false) {
            return null;
        }

        foreach ($all[0] as $index => [$token, $position]) {
            $static = substr($pattern, $offset, $position - $offset);
            $regex .= preg_quote($static, '~');

            $name = $all[1][$index][0];
            $constraint = $all[2][$index][0] !== '' ? $all[2][$index][0] : '[^/]+';

            $names[] = $name;
            $regex .= '(' . $constraint . ')';
            $offset = $position + strlen($token);
        }

        $regex .= preg_quote(substr($pattern, $offset), '~');

        if (!preg_match('~^' . $regex . '$~', $routeKey, $matches)) {
            return null;
        }

        array_shift($matches);

        /** @var array<string, string> $params */
        $params = array_combine($names, $matches) ?: [];

        return $params;
    }
}
