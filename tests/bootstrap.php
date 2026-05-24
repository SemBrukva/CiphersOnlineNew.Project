<?php

declare(strict_types=1);

use App\Config\Config;
use App\Container\Container;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('PRIVATE_PATH')) {
    define('PRIVATE_PATH', BASE_PATH . '/private');
}

if (!defined('APP_PATH')) {
    define('APP_PATH', PRIVATE_PATH . '/app');
}

require_once BASE_PATH . '/vendor/autoload.php';

// Инициализируем тестовые глобальные зависимости для хелперов config()/app().
$config = new Config([
    'app' => [
        'user_verification' => false,
    ],
]);

$container = new Container();
$container->instance(Config::class, $config);
$container->instance(Container::class, $container);
