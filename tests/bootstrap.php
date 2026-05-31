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
// Используем $GLOBALS напрямую: PHPUnit загружает bootstrap внутри функции,
// поэтому обычные переменные не попадают в глобальный скоп.
$GLOBALS['config'] = new Config([
    'app' => [
        'user_verification' => false,
    ],
]);

$GLOBALS['container'] = new Container();
$GLOBALS['container']->instance(Config::class, $GLOBALS['config']);
$GLOBALS['container']->instance(Container::class, $GLOBALS['container']);

// Регистрируем Translator для хелпера trans() — без этого container пытается
// собрать его через рефлексию и падает (массив $config нельзя autowire).
$GLOBALS['container']->instance(
    App\I18n\Translator::class,
    new App\I18n\Translator([
        'locale'    => 'en',
        'locales'   => ['en', 'ru', 'de', 'es', 'fr', 'it', 'pt', 'tr'],
        'path'      => PRIVATE_PATH . '/translates',
        'multilang' => false,
    ])
);
