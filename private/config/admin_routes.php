<?php

declare(strict_types=1);

use App\Controller\Admin\DashboardController;
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

];
