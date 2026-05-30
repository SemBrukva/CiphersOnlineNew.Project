<?php

declare(strict_types=1);

use App\Cache\CacheInterface;
use App\Cache\MemcacheCache;
use App\Cache\NullCache;
use App\Container\Container;
use App\Database\Database;
use App\Database\Migrator;
use App\Debug\DebugInfo;
use App\Event\ConfigListenerProvider;
use App\Event\EventDispatcher;
use App\Event\EventDispatcherInterface;
use App\Event\ListenerProviderInterface;
use App\Http\AdminRouter;
use App\Http\ApiRouter;
use App\Http\Client\HttpClient;
use App\Http\Client\HttpClientInterface;
use App\Http\RequestContext;
use App\Http\RouteCache;
use App\Http\RouteLoader;
use App\Http\RouteMatcherInterface;
use App\Http\Router;
use App\Http\Session;
use App\Http\Session\MemcachedSessionHandler;
use App\Http\Session\RedisSessionHandler;
use App\Http\UrlGenerator;
use App\I18n\Translator;
use App\Log\Logger;
use App\Log\LoggerInterface;
use App\Mail\Mailer;
use App\Mail\MailerInterface;
use App\Mail\MailjetStubSender;
use App\Queue\QueueManager;
use App\Queue\Worker;
use App\View\View;
use App\Controller\FavoritesController;
use App\Controller\Api\FavoritesController as ApiFavoritesController;

return [
    App\Http\Middleware\TrustedProxyMiddleware::class => static function (): App\Http\Middleware\TrustedProxyMiddleware {
        return new App\Http\Middleware\TrustedProxyMiddleware(
            config('trusted_proxies.proxies', [])
        );
    },

    HttpClient::class => static fn (): HttpClient => new HttpClient(config('http_client', [])),

    HttpClientInterface::class => static function (Container $container): HttpClientInterface {
        return $container->get(HttpClient::class);
    },

    CacheInterface::class => static function (): CacheInterface {
        $config = config('cache', []);
        $driver = $config['driver'] ?? 'null';

        if ($driver === 'memcache') {
            return new MemcacheCache($config['memcache'] ?? [], $config['prefix'] ?? '');
        }

        return new NullCache();
    },

    RequestContext::class => static fn (): RequestContext => new RequestContext(
        (string) ($_SERVER['APP_REQUEST_ID'] ?? uniqid('cli-', true)),
        isset($_SERVER['APP_STARTED_AT']) ? (float) $_SERVER['APP_STARTED_AT'] : microtime(true),
        false
    ),

    Logger::class => static function (Container $container): Logger {
        return new Logger(
            config('log', []),
            $container->get(RequestContext::class),
            $container->get(HttpClientInterface::class),
        );
    },

    LoggerInterface::class => static function (Container $container): LoggerInterface {
        return $container->get(Logger::class);
    },

    Translator::class => static fn (): Translator => new Translator(config('locale', [])),
    View::class => static fn (): View => new View(config('view')),
    Session::class => static function (): Session {
        $config = config('session', []);
        $driver = $config['driver'] ?? 'file';
        $ttl    = $config['ttl']    ?? 86400;

        $handler = match ($driver) {
            'memcached' => new MemcachedSessionHandler(
                array_merge($config['memcached'] ?? [], ['ttl' => $ttl])
            ),
            'redis' => new RedisSessionHandler(
                array_merge($config['redis'] ?? [], ['ttl' => $ttl])
            ),
            default => null,
        };

        return new Session($config, $handler);
    },

    Database::class => static function (Container $container): Database {
        $default = config('database.default', 'sqlite');

        return new Database(
            config("database.connections.{$default}", []),
            $container->get(RequestContext::class)
        );
    },

    DebugInfo::class => static function (Container $container): DebugInfo {
        return new DebugInfo(
            $container->get(App\Auth\Auth::class),
            $container->get(Database::class),
            $container->get(CacheInterface::class),
            $container->get(Session::class),
            $container->get(App\Debug\MatchedRoute::class),
            $container->get(App\Debug\TranslationTracker::class),
            $container->get(App\Debug\Profiler::class),
            $container->get(RequestContext::class),
            config('admin.ids', [])
        );
    },

    MailjetStubSender::class => static function (): MailjetStubSender {
        return new MailjetStubSender((string) config('mail.stub_log_path', STORAGE_PATH . '/logs/mailjet-stub.log'));
    },

    Mailer::class => static function (Container $container): Mailer {
        return new Mailer(
            config('mail', []),
            $container->get(View::class),
            $container->get(LoggerInterface::class),
            $container->get(MailjetStubSender::class),
            $container
        );
    },

    MailerInterface::class => static function (Container $container): MailerInterface {
        return $container->get(Mailer::class);
    },

    ListenerProviderInterface::class => static fn (): ListenerProviderInterface => new ConfigListenerProvider(
        config('events', [])
    ),

    EventDispatcherInterface::class => static function (Container $container): EventDispatcherInterface {
        return new EventDispatcher(
            $container->get(ListenerProviderInterface::class),
            $container
        );
    },

    UrlGenerator::class => static function (Container $container): UrlGenerator {
        $routeLoader = $container->get(RouteLoader::class);
        $merged = $routeLoader->loadMerged(
            config('routes', []),
            config('admin_routes', []),
            config('api_routes', [])
        );

        return new UrlGenerator(
            $merged['web'],
            $merged['admin'],
            $merged['api'],
            (string) config('admin.path', '/admin')
        );
    },

    Router::class => static function (Container $container): Router {
        $useRouteCache = (string) config('app.env', 'local') === 'production';
        $compiledRoutes = $useRouteCache
            ? RouteCache::load(STORAGE_PATH . '/cache/routes.php')
            : null;
        if (!is_array($compiledRoutes)) {
            $routeLoader = $container->get(RouteLoader::class);
            $merged = $routeLoader->loadMerged(
                config('routes', []),
                config('admin_routes', []),
                config('api_routes', [])
            );
            $compiledRoutes = RouteCache::compile(
                $merged['web'],
                $merged['admin'],
                $merged['api'],
                (string) config('admin.path', '/admin')
            );
        }

        $webRoutes = $compiledRoutes['web'] ?? [];

        $notFoundHandler = static function () use ($container): \App\Http\Response {
            $view = $container->get(View::class);
            $view->setTitle(trans('ERROR_404_TITLE'))->setContent($view->fetch('errors/404.tpl'));

            return new \App\Http\Response($view->render(), 404);
        };

        return new Router(
            $webRoutes,
            $container,
            $container->get(App\Http\Pipeline::class),
            $notFoundHandler,
            $container->get(App\Debug\MatchedRoute::class),
            $container->get(App\Debug\Profiler::class)
        );
    },

    RouteMatcherInterface::class => static fn (Container $container): RouteMatcherInterface => $container->get(Router::class),

    ApiRouter::class => static function (Container $container): ApiRouter {
        $useRouteCache = (string) config('app.env', 'local') === 'production';
        $compiledRoutes = $useRouteCache
            ? RouteCache::load(STORAGE_PATH . '/cache/routes.php')
            : null;
        if (!is_array($compiledRoutes)) {
            $routeLoader = $container->get(RouteLoader::class);
            $merged = $routeLoader->loadMerged(
                config('routes', []),
                config('admin_routes', []),
                config('api_routes', [])
            );
            $compiledRoutes = RouteCache::compile(
                $merged['web'],
                $merged['admin'],
                $merged['api'],
                (string) config('admin.path', '/admin')
            );
        }

        $routes = $compiledRoutes['api'] ?? [];

        $notFoundHandler = static function () use ($container): \App\Http\Response {
            $context = $container->get(RequestContext::class);

            return \App\Http\Response::json([
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Not Found',
                ],
                'request_id' => $context->requestId,
            ], 404);
        };

        return new ApiRouter(
            $routes,
            $container,
            $container->get(App\Http\Pipeline::class),
            $notFoundHandler,
            $container->get(App\Debug\MatchedRoute::class),
            $container->get(App\Debug\Profiler::class)
        );
    },

    AdminRouter::class => static function (Container $container): AdminRouter {
        $useRouteCache = (string) config('app.env', 'local') === 'production';
        $compiledRoutes = $useRouteCache
            ? RouteCache::load(STORAGE_PATH . '/cache/routes.php')
            : null;
        if (!is_array($compiledRoutes)) {
            $routeLoader = $container->get(RouteLoader::class);
            $merged = $routeLoader->loadMerged(
                config('routes', []),
                config('admin_routes', []),
                config('api_routes', [])
            );
            $compiledRoutes = RouteCache::compile(
                $merged['web'],
                $merged['admin'],
                $merged['api'],
                (string) config('admin.path', '/admin')
            );
        }

        $routes = $compiledRoutes['admin'] ?? [];

        $notFoundHandler = static function () use ($container): \App\Http\Response {
            $view = $container->get(View::class);
            $view->setTitle(trans('ERROR_404_TITLE'))->setContent($view->fetch('errors/404.tpl'));

            return new \App\Http\Response($view->render(), 404);
        };

        return new AdminRouter(
            $routes,
            $container,
            $container->get(App\Http\Pipeline::class),
            $notFoundHandler,
            $container->get(App\Debug\MatchedRoute::class),
            $container->get(App\Debug\Profiler::class)
        );
    },

    FavoritesController::class => static function (Container $container): FavoritesController {
        return new FavoritesController($container->get(View::class));
    },

    ApiFavoritesController::class => static function (Container $container): ApiFavoritesController {
        return new ApiFavoritesController(
            $container->get(App\Repository\CipherRepository::class),
            $container->get(Translator::class),
        );
    },

    Migrator::class => static function (Container $container): Migrator {
        return new Migrator($container->get(Database::class), DATABASE_PATH . '/migrations');
    },

    QueueManager::class => static function (Container $container): QueueManager {
        return new QueueManager(
            $container->get(Database::class),
            config('queue', [])
        );
    },

    Worker::class => static function (Container $container): Worker {
        return new Worker(
            $container->get(QueueManager::class),
            $container,
            $container->get(LoggerInterface::class)
        );
    },
];
