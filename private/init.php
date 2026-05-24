<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);

// Проверяем наличие Composer autoload и подключаем его
if (!is_file($basePath . '/vendor/autoload.php')) {
    http_response_code(500);
    echo 'Composer autoload file was not found. Run composer install.';
    exit;
}

require_once $basePath . '/vendor/autoload.php';

// Проверяем наличие bootstrap-файла и запускаем инициализацию приложения
if (!is_file(__DIR__ . '/bootstrap.php')) {
    http_response_code(500);
    echo 'Application bootstrap file was not found.';
    exit;
}

require_once __DIR__ . '/bootstrap.php';
