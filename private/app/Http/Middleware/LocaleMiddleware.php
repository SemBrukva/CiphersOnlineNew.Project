<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Http\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\RouteMatcherInterface;
use App\I18n\Translator;

/**
 * Middleware определения и применения локали из URL или профиля пользователя.
 *
 * Для гостей: локаль из URL-префикса; дефолтный язык работает без префикса,
 * остальные — с префиксом (/ru/contacts). Роутеру передаётся путь без префикса.
 *
 * Для авторизованных пользователей: локаль из users.language.
 *   - Приватные маршруты (AuthMiddleware) и неизвестные пути — без префикса;
 *     наличие префикса → 404.
 *   - Публичные маршруты — URL должен содержать префикс языка пользователя;
 *     несоответствие → 301-редирект на правильный URL.
 */
final readonly class LocaleMiddleware implements MiddlewareInterface
{
    /**
     * Создаёт экземпляр middleware.
     */
    public function __construct(
        private Translator           $translator,
        private Auth                 $auth,
        private RouteMatcherInterface $routeMatcher
    ) {
    }

    /**
     * Определяет локаль, нормализует URI и передаёт управление дальше.
     */
    public function process(Request $request, callable $next): Response
    {
        if (!$this->translator->isMultilang()) {
            return $next($request);
        }

        $path          = $request->path();
        $defaultLocale = $this->translator->getDefaultLocale();
        [$detectedLocale, $strippedPath, $hasPrefix] = $this->detectPrefix($path, $defaultLocale);

        if ($this->auth->check()) {
            return $this->handleAuthenticated($request, $next, $path, $strippedPath, $hasPrefix);
        }

        return $this->handleGuest($request, $next, $detectedLocale, $strippedPath, $hasPrefix);
    }

    /**
     * Обрабатывает запрос авторизованного пользователя.
     *
     * Локаль берётся из users.language. Для публичных маршрутов URL должен
     * содержать соответствующий языковой префикс; приватные маршруты — без префикса.
     */
    private function handleAuthenticated(
        Request  $request,
        callable $next,
        string   $originalPath,
        string   $strippedPath,
        bool     $hasPrefix
    ): Response {
        $user   = $this->auth->user();
        $locale = $user['language'] ?? $this->translator->getDefaultLocale();
        $this->translator->setLocale($locale);

        $route = $this->routeMatcher->match($request->getMethod(), $strippedPath);

        if ($route === null || $this->isPrivateRoute($route)) {
            // Приватные и неизвестные маршруты (в т.ч. /admin) — языковой префикс недопустим
            if ($hasPrefix) {
                return new Response('', 404);
            }

            return $next($request);
        }

        // Публичный маршрут: URL должен соответствовать языку пользователя
        $correctPath = $this->buildLocalePath($strippedPath, $locale);

        if ($originalPath !== $correctPath) {
            $query = parse_url($request->getUri(), PHP_URL_QUERY);

            return redirect($correctPath . ($query !== null ? '?' . $query : ''), 301);
        }

        // URL верный — передаём роутеру путь без языкового префикса
        if ($hasPrefix) {
            $query   = parse_url($request->getUri(), PHP_URL_QUERY);
            $request = $request->withUri($strippedPath . ($query !== null ? '?' . $query : ''));
        }

        return $next($request);
    }

    /**
     * Обрабатывает запрос гостя.
     *
     * Локаль из URL-префикса; URI очищается от префикса перед маршрутизацией.
     */
    private function handleGuest(
        Request  $request,
        callable $next,
        string   $detectedLocale,
        string   $strippedPath,
        bool     $hasPrefix
    ): Response {
        $this->translator->setLocale($detectedLocale);

        if ($hasPrefix) {
            $query   = parse_url($request->getUri(), PHP_URL_QUERY);
            $request = $request->withUri($strippedPath . ($query !== null ? '?' . $query : ''));
        }

        return $next($request);
    }

    /**
     * Формирует URL с нужным языковым префиксом для публичного маршрута.
     *
     * Дефолтный язык → путь без изменений. Прочие языки → /{locale}[/path].
     * Домашняя страница ('/') с ненулевым префиксом даёт '/{locale}' без завершающего слэша.
     */
    private function buildLocalePath(string $strippedPath, string $locale): string
    {
        $defaultLocale = $this->translator->getDefaultLocale();

        if ($locale === $defaultLocale) {
            return $strippedPath;
        }

        $prefix = '/' . $locale;

        return $strippedPath === '/' ? $prefix : $prefix . $strippedPath;
    }

    /**
     * Возвращает true, если маршрут доступен только авторизованным пользователям.
     *
     * @param array<string, mixed> $route
     */
    private function isPrivateRoute(array $route): bool
    {
        return in_array(AuthMiddleware::class, $route['middleware'] ?? [], true);
    }

    /**
     * Извлекает локаль и очищенный путь из URI.
     *
     * @return array{string, string, bool} [locale, strippedPath, hasPrefix]
     */
    private function detectPrefix(string $path, string $defaultLocale): array
    {
        foreach ($this->translator->getLocales() as $lang) {
            if ($lang === $defaultLocale) {
                continue;
            }

            $prefix = '/' . $lang;

            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                $stripped = substr($path, strlen($prefix)) ?: '/';

                return [$lang, $stripped, true];
            }
        }

        return [$defaultLocale, $path, false];
    }
}
