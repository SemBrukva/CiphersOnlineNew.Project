<?php

declare(strict_types=1);

use App\Controller\Admin\DashboardController;
use App\Controller\Admin\CipherController;
use App\Controller\Admin\CipherCategoryController;
use App\Controller\Admin\RedirectController;

/**
 * Маршруты панели администратора.
 *
 * Пути указываются относительно ADMIN_PATH — без глобального префикса.
 * AdminMiddleware применяется ко всем маршрутам автоматически в services.php.
 */
return [

    'GET /' => [
        'controller' => DashboardController::class,
        'method'     => 'index',
        'name'       => 'admin.dashboard',
    ],

    'GET /redirects' => [
        'controller' => RedirectController::class,
        'method'     => 'index',
        'name'       => 'admin.redirects.index',
    ],

    'GET /redirects/create' => [
        'controller' => RedirectController::class,
        'method'     => 'create',
        'name'       => 'admin.redirects.create',
    ],

    'POST /redirects' => [
        'controller' => RedirectController::class,
        'method'     => 'store',
        'name'       => 'admin.redirects.store',
    ],

    'GET /redirects/{id:\\d+}/edit' => [
        'controller' => RedirectController::class,
        'method'     => 'edit',
        'name'       => 'admin.redirects.edit',
    ],

    'POST /redirects/{id:\\d+}' => [
        'controller' => RedirectController::class,
        'method'     => 'update',
        'name'       => 'admin.redirects.update',
    ],

    'POST /redirects/{id:\\d+}/delete' => [
        'controller' => RedirectController::class,
        'method'     => 'destroy',
        'name'       => 'admin.redirects.destroy',
    ],

    'GET /cipher-categories' => [
        'controller' => CipherCategoryController::class,
        'method'     => 'index',
        'name'       => 'admin.cipher_categories.index',
    ],

    'GET /cipher-categories/create' => [
        'controller' => CipherCategoryController::class,
        'method'     => 'create',
        'name'       => 'admin.cipher_categories.create',
    ],

    'POST /cipher-categories' => [
        'controller' => CipherCategoryController::class,
        'method'     => 'store',
        'name'       => 'admin.cipher_categories.store',
    ],

    'GET /cipher-categories/{id:\\d+}/edit' => [
        'controller' => CipherCategoryController::class,
        'method'     => 'edit',
        'name'       => 'admin.cipher_categories.edit',
    ],

    'GET /ciphers' => [
        'controller' => CipherController::class,
        'method'     => 'index',
        'name'       => 'admin.ciphers.index',
    ],

    'GET /ciphers/create' => [
        'controller' => CipherController::class,
        'method'     => 'create',
        'name'       => 'admin.ciphers.create',
    ],

    'POST /ciphers' => [
        'controller' => CipherController::class,
        'method'     => 'store',
        'name'       => 'admin.ciphers.store',
    ],

    'GET /ciphers/{id:\\d+}/edit' => [
        'controller' => CipherController::class,
        'method'     => 'edit',
        'name'       => 'admin.ciphers.edit',
    ],

    'POST /ciphers/{id:\\d+}' => [
        'controller' => CipherController::class,
        'method'     => 'update',
        'name'       => 'admin.ciphers.update',
    ],

    'POST /ciphers/{id:\\d+}/delete' => [
        'controller' => CipherController::class,
        'method'     => 'destroy',
        'name'       => 'admin.ciphers.destroy',
    ],

    'POST /cipher-categories/{id:\\d+}' => [
        'controller' => CipherCategoryController::class,
        'method'     => 'update',
        'name'       => 'admin.cipher_categories.update',
    ],

    'POST /cipher-categories/{id:\\d+}/delete' => [
        'controller' => CipherCategoryController::class,
        'method'     => 'destroy',
        'name'       => 'admin.cipher_categories.destroy',
    ],

];
