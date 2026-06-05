<?php

declare(strict_types=1);

// Таблица веб-маршрутов приложения. Ключ: «METHOD /path», значение: controller, method, middleware.

use App\Controller\AuthController;
use App\Controller\CabinetController;
use App\Controller\CipherCategoryController;
use App\Controller\CipherController;
use App\Controller\ContactsController;
use App\Controller\FavoritesController;
use App\Controller\HealthController;
use App\Controller\HomeController;
use App\Controller\SitemapController;
use App\Http\Middleware\AuthMiddleware;

return [

    'GET /' => [
        'controller' => HomeController::class,
        'method'     => 'index',
        'name'       => 'home',
    ],

    'GET /contacts' => [
        'controller' => ContactsController::class,
        'method'     => 'index',
        'name'       => 'contacts',
    ],

    'GET /healthz' => [
        'controller' => HealthController::class,
        'method'     => 'status',
        'name'       => 'healthz',
    ],

    'GET /cabinet' => [
        'controller' => CabinetController::class,
        'method'     => 'index',
        'middleware' => [AuthMiddleware::class],
        'name'       => 'cabinet',
    ],

    'GET /login' => [
        'controller' => AuthController::class,
        'method'     => 'loginForm',
        'name'       => 'auth.login',
    ],

    'GET /registration' => [
        'controller' => AuthController::class,
        'method'     => 'registrationForm',
        'name'       => 'auth.registration',
    ],

    'POST /logout' => [
        'controller' => AuthController::class,
        'method'     => 'logout',
        'name'       => 'auth.logout',
    ],

    'GET /sitemap' => [
        'controller' => SitemapController::class,
        'method'     => 'html',
        'name'       => 'sitemap.html',
    ],

    'GET /sitemap.xml' => [
        'controller' => SitemapController::class,
        'method'     => 'xml',
        'name'       => 'sitemap.xml',
    ],

    'GET /sitemap.xsl' => [
        'controller' => SitemapController::class,
        'method'     => 'xsl',
        'name'       => 'sitemap.xsl',
    ],

    'GET /favorites' => [
        'controller' => FavoritesController::class,
        'method'     => 'index',
        'name'       => 'favorites.index',
    ],

    'GET /{category:[a-z0-9-]+}/{cipher:[a-z0-9-]+}' => [
        'controller' => CipherController::class,
        'method'     => 'show',
        'name'       => 'ciphers.show',
    ],

    'GET /{alias:[a-z0-9-]+}' => [
        'controller' => CipherCategoryController::class,
        'method'     => 'show',
        'name'       => 'cipher_categories.show',
    ],

];
