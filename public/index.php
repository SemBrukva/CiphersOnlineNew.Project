<?php

declare(strict_types=1);

// Пропускаем статические файлы при использовании PHP built-in server
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

use App\Http\AdminRouter;
use App\Http\ApiRouter;
use App\Http\ErrorHandler;
use App\Http\Middleware\DevBasicAuthMiddleware;
use App\Http\Middleware\EnforceHttpsMiddleware;
use App\Http\Middleware\TrailingSlashMiddleware;
use App\Http\Middleware\TrustedProxyMiddleware;
use App\Http\Pipeline;
use App\Http\Request;
use App\Http\RequestContext;
use App\Http\Router;

// Загружаем зависимости, конфигурацию и сервис-контейнер
require_once dirname(__DIR__) . '/private/init.php';

// Захватываем входящий запрос и инстанциируем ключевые сервисы
$pipeline     = app(Pipeline::class);
$request      = Request::capture();
$startedAt    = microtime(true);
$requestId    = $request->header('X-Request-Id');

if (!is_string($requestId) || $requestId === '') {
    try {
        $requestId = bin2hex(random_bytes(16));
    } catch (Throwable) {
        $requestId = uniqid('req_', true);
    }
}

// Определяем роутер, набор middleware и Content-Type по URL запроса
$requestPath = parse_url($request->getUri(), PHP_URL_PATH) ?: '/';
$adminPath   = config('admin.path', '/admin');

['router' => $router, 'middleware' => $middlewareConfig, 'contentType' => $contentType, 'isApi' => $isApi] = match(true) {
    $requestPath === '/healthz' => [
        'router' => app(Router::class),
        'middleware' => [],
        'contentType' => null,
        'isApi' => true,
    ],
    in_array($requestPath, ['/sitemap.xml', '/sitemap.xsl'], true) => [
        'router' => app(Router::class),
        'middleware' => [
            TrustedProxyMiddleware::class,
            DevBasicAuthMiddleware::class,
            EnforceHttpsMiddleware::class,
            TrailingSlashMiddleware::class,
        ],
        'contentType' => null,
        'isApi' => false,
    ],
    $requestPath === $adminPath || str_starts_with($requestPath, $adminPath . '/') => [
        'router' => app(AdminRouter::class),
        'middleware' => config('middleware', []),
        'contentType' => 'text/html; charset=utf-8',
        'isApi' => false,
    ],
    $requestPath === '/api' || str_starts_with($requestPath, '/api/') => [
        'router' => app(ApiRouter::class),
        'middleware' => config('api_middleware', []),
        'contentType' => null,
        'isApi' => true,
    ],
    default => [
        'router' => app(Router::class),
        'middleware' => config('middleware', []),
        'contentType' => 'text/html; charset=utf-8',
        'isApi' => false,
    ],
};

app()->instance(
    RequestContext::class,
    new RequestContext($requestId, $startedAt, $isApi)
);

$_SERVER['APP_REQUEST_ID'] = $requestId;
$_SERVER['APP_STARTED_AT'] = $startedAt;

/** @var ErrorHandler $errorHandler */
$errorHandler = app(ErrorHandler::class);

if ($contentType !== null) {
    header("Content-Type: $contentType");
}

header('X-Request-Id: ' . $requestId);

// Прогоняем запрос через middleware-цепочку и роутер; при ошибке — формируем ответ через ErrorHandler
try {
    $response = $pipeline->run(
        $request,
        $middlewareConfig,
        fn (Request $req) => $router->dispatch($req)
    );
} catch (Throwable $exception) {
    $response = $isApi
        ? $errorHandler->handleApi($exception)
        : $errorHandler->handle($exception);
}

$response->send();
