<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Auth\Auth;
use App\Config\Config;
use App\Database\Database;
use App\Database\Tables;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Request;
use App\Http\RequestContext;
use App\Http\Response;
use App\Http\RouteMatcherInterface;
use App\Http\Session;
use App\I18n\Translator;
use App\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет определение локали и нормализацию URL в LocaleMiddleware.
 */
final class LocaleMiddlewareTest extends TestCase
{
    /**
     * Инициализирует тестовые глобальные данные сессии и конфигурации.
     */
    protected function setUp(): void
    {
        global $config;
        $config  = new Config(['app' => ['user_verification' => false]]);
        $_SESSION = [];
    }

    /**
     * Проверяет, что при выключенной мультиязычности middleware не меняет запрос.
     */
    public function testPassThroughWhenMultilangDisabled(): void
    {
        $translator = new Translator([
            'multilang' => false,
            'locale'    => 'en',
            'locales'   => ['en', 'ru'],
            'path'      => PRIVATE_PATH . '/translates',
        ]);

        $middleware = new LocaleMiddleware($translator, $this->makeGuestAuth(), $this->makeRouteMatcher([]));
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/ru/contacts'], [], [], [], []);

        $seenUri  = '';
        $response = $middleware->process($request, static function (Request $req) use (&$seenUri): Response {
            $seenUri = $req->getUri();

            return new Response('ok');
        });

        self::assertSame('ok', $response->getContent());
        self::assertSame('/ru/contacts', $seenUri);
    }

    /**
     * Проверяет срезание языкового префикса у гостя и установку локали.
     */
    public function testGuestStripsLocalePrefixAndSetsDetectedLocale(): void
    {
        $translator = $this->makeTranslator();
        $middleware = new LocaleMiddleware($translator, $this->makeGuestAuth(), $this->makeRouteMatcher([]));
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/ru/contacts?page=1'], [], [], [], []);

        $seenUri = '';
        $middleware->process($request, static function (Request $req) use (&$seenUri): Response {
            $seenUri = $req->getUri();

            return new Response('ok');
        });

        self::assertSame('ru', $translator->getLocale());
        self::assertSame('/contacts?page=1', $seenUri);
    }

    /**
     * Гость без префикса получает дефолтную локаль и URI без изменений.
     */
    public function testGuestWithoutPrefixKeepsUri(): void
    {
        $translator = $this->makeTranslator();
        $middleware = new LocaleMiddleware($translator, $this->makeGuestAuth(), $this->makeRouteMatcher([]));
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/contacts'], [], [], [], []);

        $seenUri = '';
        $middleware->process($request, static function (Request $req) use (&$seenUri): Response {
            $seenUri = $req->getUri();

            return new Response('ok');
        });

        self::assertSame('en', $translator->getLocale());
        self::assertSame('/contacts', $seenUri);
    }

    /**
     * Авторизованный пользователь на приватной странице без префикса проходит дальше.
     */
    public function testAuthenticatedOnPrivateRouteWithoutPrefixPassesThrough(): void
    {
        $translator = $this->makeTranslator();
        $matcher    = $this->makeRouteMatcher([
            'GET /cabinet' => ['middleware' => [AuthMiddleware::class]],
        ]);
        $middleware = new LocaleMiddleware($translator, $this->makeAuthenticatedAuth('ru'), $matcher);
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/cabinet'], [], [], [], []);

        $response = $middleware->process($request, static fn (Request $req): Response => new Response($req->getUri()));

        self::assertSame('ru', $translator->getLocale());
        self::assertSame('/cabinet', $response->getContent());
    }

    /**
     * Авторизованный пользователь на приватной странице с языковым префиксом получает 404.
     */
    public function testAuthenticatedOnPrivateRouteWithPrefixGets404(): void
    {
        $translator = $this->makeTranslator();
        $matcher    = $this->makeRouteMatcher([
            'GET /cabinet' => ['middleware' => [AuthMiddleware::class]],
        ]);
        $middleware = new LocaleMiddleware($translator, $this->makeAuthenticatedAuth('ru'), $matcher);
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/ru/cabinet'], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('next'));

        self::assertSame(404, $response->getStatusCode());
    }

    /**
     * Авторизованный пользователь с языком ru на публичной странице без префикса получает 301 → /ru/contacts.
     */
    public function testAuthenticatedRussianOnPublicPageWithoutPrefixRedirects(): void
    {
        $translator = $this->makeTranslator();
        $matcher    = $this->makeRouteMatcher([
            'GET /contacts' => [],
        ]);
        $middleware = new LocaleMiddleware($translator, $this->makeAuthenticatedAuth('ru'), $matcher);
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/contacts'], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('next'));

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/ru/contacts', $response->getHeaders()['Location'] ?? null);
    }

    /**
     * Авторизованный пользователь с языком en (дефолт) на /ru/contacts получает 301 → /contacts.
     */
    public function testAuthenticatedEnglishOnForeignPrefixRedirects(): void
    {
        $translator = $this->makeTranslator();
        $matcher    = $this->makeRouteMatcher([
            'GET /contacts' => [],
        ]);
        $middleware = new LocaleMiddleware($translator, $this->makeAuthenticatedAuth('en'), $matcher);
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/ru/contacts'], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('next'));

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/contacts', $response->getHeaders()['Location'] ?? null);
    }

    /**
     * Авторизованный пользователь с языком ru на корректном /ru/contacts проходит дальше.
     * Роутеру передаётся путь без префикса /contacts.
     */
    public function testAuthenticatedRussianOnCorrectPrefixPassesThrough(): void
    {
        $translator = $this->makeTranslator();
        $matcher    = $this->makeRouteMatcher([
            'GET /contacts' => [],
        ]);
        $middleware = new LocaleMiddleware($translator, $this->makeAuthenticatedAuth('ru'), $matcher);
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/ru/contacts?page=2'], [], [], [], []);

        $seenUri = '';
        $middleware->process($request, static function (Request $req) use (&$seenUri): Response {
            $seenUri = $req->getUri();

            return new Response('ok');
        });

        self::assertSame('ru', $translator->getLocale());
        self::assertSame('/contacts?page=2', $seenUri);
    }

    /**
     * Авторизованный пользователь с языком ru на главной странице / получает 301 → /ru.
     */
    public function testAuthenticatedRussianOnHomeWithoutPrefixRedirects(): void
    {
        $translator = $this->makeTranslator();
        $matcher    = $this->makeRouteMatcher([
            'GET /' => [],
        ]);
        $middleware = new LocaleMiddleware($translator, $this->makeAuthenticatedAuth('ru'), $matcher);
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('next'));

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/ru', $response->getHeaders()['Location'] ?? null);
    }

    /**
     * Авторизованный пользователь с языком ru на /ru (главная) проходит дальше.
     * Роутеру передаётся '/'.
     */
    public function testAuthenticatedRussianOnCorrectHomePassesThrough(): void
    {
        $translator = $this->makeTranslator();
        $matcher    = $this->makeRouteMatcher([
            'GET /' => [],
        ]);
        $middleware = new LocaleMiddleware($translator, $this->makeAuthenticatedAuth('ru'), $matcher);
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/ru'], [], [], [], []);

        $seenUri = '';
        $middleware->process($request, static function (Request $req) use (&$seenUri): Response {
            $seenUri = $req->getUri();

            return new Response('ok');
        });

        self::assertSame('/', $seenUri);
    }

    /**
     * Авторизованный пользователь с неизвестным маршрутом (например /admin) без префикса проходит дальше.
     */
    public function testAuthenticatedOnUnknownRouteWithoutPrefixPassesThrough(): void
    {
        $translator = $this->makeTranslator();
        $middleware = new LocaleMiddleware($translator, $this->makeAuthenticatedAuth('ru'), $this->makeRouteMatcher([]));
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/dashboard'], [], [], [], []);

        $response = $middleware->process($request, static fn (Request $req): Response => new Response($req->getUri()));

        self::assertSame('/admin/dashboard', $response->getContent());
    }

    /**
     * Авторизованный пользователь с неизвестным маршрутом и языковым префиксом получает 404.
     */
    public function testAuthenticatedOnUnknownRouteWithPrefixGets404(): void
    {
        $translator = $this->makeTranslator();
        $middleware = new LocaleMiddleware($translator, $this->makeAuthenticatedAuth('ru'), $this->makeRouteMatcher([]));
        $request    = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/ru/admin/dashboard'], [], [], [], []);

        $response = $middleware->process($request, static fn (): Response => new Response('next'));

        self::assertSame(404, $response->getStatusCode());
    }

    // --- Вспомогательные методы ---

    /**
     * Создаёт Translator с мультиязычностью en/ru.
     */
    private function makeTranslator(): Translator
    {
        return new Translator([
            'multilang' => true,
            'locale'    => 'en',
            'locales'   => ['en', 'ru'],
            'path'      => PRIVATE_PATH . '/translates',
        ]);
    }

    /**
     * Создаёт RouteMatcherInterface с заданной таблицей маршрутов (только точное совпадение).
     *
     * @param array<string, array<string, mixed>> $routes Ключ: «METHOD /path»
     */
    private function makeRouteMatcher(array $routes): RouteMatcherInterface
    {
        return new class ($routes) implements RouteMatcherInterface {
            /** @param array<string, array<string, mixed>> $routes */
            public function __construct(private readonly array $routes)
            {
            }

            public function match(string $method, string $path): ?array
            {
                return $this->routes[$method . ' ' . $path] ?? null;
            }
        };
    }

    /**
     * Создаёт Auth в гостевом состоянии.
     */
    private function makeGuestAuth(): Auth
    {
        return new Auth(new Session([]), new UserRepository($this->makeDatabase()));
    }

    /**
     * Создаёт Auth с авторизованным пользователем.
     */
    private function makeAuthenticatedAuth(string $language): Auth
    {
        $auth = new Auth(new Session([]), new UserRepository($this->makeDatabase()));
        $auth->login(['id' => 1, 'email' => 'user@example.com', 'language' => $language]);

        return $auth;
    }

    /**
     * Создаёт in-memory БД для конструктора Auth.
     */
    private function makeDatabase(): Database
    {
        $db = new Database([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'options'  => [],
        ], new RequestContext('test-request', microtime(true), false));

        $db->execute('CREATE TABLE ' . Tables::USERS . ' (id INTEGER PRIMARY KEY, email TEXT, password TEXT, email_verified_at TEXT)');

        return $db;
    }
}
