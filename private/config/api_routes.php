<?php

declare(strict_types=1);

// Маршруты API. Пути указываются относительно префикса /api, который добавляется в services.php.

use App\Controller\Api\AdminController;
use App\Controller\Api\AnalyticsController;
use App\Controller\Api\FavoritesController as ApiFavoritesController;
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

    'POST /tools/caesar' => [
        'controller' => GuestController::class,
        'method'     => 'caesar',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.caesar',
    ],

    'POST /tools/affine' => [
        'controller' => GuestController::class,
        'method'     => 'affine',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.affine',
    ],

    'POST /tools/atbash' => [
        'controller' => GuestController::class,
        'method'     => 'atbash',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.atbash',
    ],

    'POST /tools/playfair' => [
        'controller' => GuestController::class,
        'method'     => 'playfair',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.playfair',
    ],

    'POST /tools/beaufort' => [
        'controller' => GuestController::class,
        'method'     => 'beaufort',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.beaufort',
    ],

    'POST /tools/gronsfeld' => [
        'controller' => GuestController::class,
        'method'     => 'gronsfeld',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.gronsfeld',
    ],

    'POST /tools/vigenere' => [
        'controller' => GuestController::class,
        'method'     => 'vigenere',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.vigenere',
    ],

    'POST /tools/vernam' => [
        'controller' => GuestController::class,
        'method'     => 'vernam',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.vernam',
    ],

    'POST /tools/bacon' => [
        'controller' => GuestController::class,
        'method'     => 'bacon',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.bacon',
    ],

    'POST /tools/a1z26' => [
        'controller' => GuestController::class,
        'method'     => 'a1z26',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.a1z26',
    ],

    'POST /tools/rail-fence' => [
        'controller' => GuestController::class,
        'method'     => 'railFence',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.rail_fence',
    ],

    'POST /tools/columnar-transposition' => [
        'controller' => GuestController::class,
        'method'     => 'columnarTransposition',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.columnar_transposition',
    ],

    'POST /tools/polybius-square' => [
        'controller' => GuestController::class,
        'method'     => 'polybiusSquare',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.polybius_square',
    ],

    'POST /analytics/use' => [
        'controller' => AnalyticsController::class,
        'method'     => 'record',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.analytics.use',
    ],

    'GET /tools/search' => [
        'controller' => GuestController::class,
        'method'     => 'searchTools',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.tools.search',
    ],

    'GET /favorites/ciphers' => [
        'controller' => ApiFavoritesController::class,
        'method'     => 'ciphers',
        'middleware' => [RateLimitMiddleware::class],
        'name'       => 'api.favorites.ciphers',
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

    'POST /admin/cipher-categories/{id:\\d+}' => [
        'controller' => AdminController::class,
        'method'     => 'updateCipherCategory',
        'middleware' => [RateLimitMiddleware::class, ApiAdminMiddleware::class],
        'name'       => 'api.admin.cipher_categories.update',
    ],

    'POST /admin/ciphers/{id:\\d+}' => [
        'controller' => AdminController::class,
        'method'     => 'updateCipher',
        'middleware' => [RateLimitMiddleware::class, ApiAdminMiddleware::class],
        'name'       => 'api.admin.ciphers.update',
    ],

];
