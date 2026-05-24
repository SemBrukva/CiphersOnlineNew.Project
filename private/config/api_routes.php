<?php

declare(strict_types=1);

// Маршруты API. Пути указываются относительно префикса /api, который добавляется в services.php.

use App\Controller\Api\AdminController;
use App\Controller\Api\GuestController;
use App\Controller\Api\UserController;
use App\Http\Middleware\ApiAdminMiddleware;
use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Middleware\RateLimitMiddleware;

return [

    // Публичные маршруты (гостевой доступ)
    'POST /auth/register' => [
        'controller' => GuestController::class,
        'method'     => 'register',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.auth.register',
    ],

    'POST /auth/login' => [
        'controller' => GuestController::class,
        'method'     => 'login',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.auth.login',
    ],

    'POST /contact' => [
        'controller' => GuestController::class,
        'method'     => 'contact',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.contact',
    ],

    // Маршруты авторизованного пользователя
    'GET /user/profile' => [
        'controller' => UserController::class,
        'method'     => 'profile',
        'middleware' => [RateLimitMiddleware::class, ApiAuthMiddleware::class],
        'name'       => 'api.user.profile',
    ],

    // Административные маршруты
    'GET /admin/stats' => [
        'controller' => AdminController::class,
        'method'     => 'stats',
        'middleware' => [RateLimitMiddleware::class, ApiAdminMiddleware::class],
        'name'       => 'api.admin.stats',
    ],

];
