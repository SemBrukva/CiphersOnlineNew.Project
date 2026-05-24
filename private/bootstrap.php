<?php

declare(strict_types=1);

use App\Config\Config;
use App\Container\Container;
use App\Log\GlobalErrorHandler;
use App\Log\Logger;
use Dotenv\Dotenv;

// Определяем глобальные константы путей
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('PRIVATE_PATH')) {
    define('PRIVATE_PATH', BASE_PATH . '/private');
}

if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', BASE_PATH . '/public');
}

if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', PRIVATE_PATH . '/storage');
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', PRIVATE_PATH . '/config');
}

if (!defined('RESOURCE_PATH')) {
    define('RESOURCE_PATH', PRIVATE_PATH . '/resources');
}

if (!defined('APP_PATH')) {
    define('APP_PATH', PRIVATE_PATH . '/app');
}

if (!defined('DATABASE_PATH')) {
    define('DATABASE_PATH', PRIVATE_PATH . '/database');
}

require_once BASE_PATH . '/vendor/autoload.php';
require_once APP_PATH . '/Support/helpers.php';

// Загружаем переменные окружения из .env
$dotenv = Dotenv::createImmutable(PRIVATE_PATH);
$dotenv->safeLoad();

// Загружаем конфигурацию из кеша в production (без debug), иначе — из config/*.php.
$config = new Config();
$configCachePath = STORAGE_PATH . '/cache/config.php';
$isProduction    = strtolower((string) env('APP_ENV', 'production')) === 'production';
$isDebugEnv      = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL) === true;

if (!$isProduction || $isDebugEnv || !$config->loadFromCache($configCachePath)) {
    $config->load(CONFIG_PATH);
}

// Создаём контейнер и регистрируем базовые зависимости
$container = new Container();

$container->instance(Config::class, $config);
$container->instance(Container::class, $container);

// Регистрируем все сервисы из config/services.php.
// services.php загружается отдельно, так как содержит Closure и не кешируется через var_export.
foreach ((require CONFIG_PATH . '/services.php') as $id => $factory) {
    $container->set($id, $factory);
}

// Устанавливаем часовой пояс, настройки ошибок и кодировку
date_default_timezone_set(
    config('app.timezone', 'UTC')
);

error_reporting(E_ALL);

$isDebug = (bool) config('app.debug', false);

// В production display_errors всегда выключен, даже если APP_DEBUG=true:
// показывать стек-трейсы клиенту нельзя — это утечка внутренней информации.
if ($isProduction && $isDebug) {
    ini_set('display_errors', '0');
} else {
    ini_set('display_errors', $isDebug ? '1' : '0');
}

mb_internal_encoding('UTF-8');

// Регистрируем глобальный обработчик ошибок
$logger = $container->get(Logger::class);
GlobalErrorHandler::register($logger);

// Логируем предупреждение, если в production случайно оставлен APP_DEBUG=true.
if ($isProduction && $isDebug) {
    $logger->warning('APP_DEBUG=true в APP_ENV=production — display_errors принудительно отключён.');
}
