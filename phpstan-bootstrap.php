<?php

declare(strict_types=1);

/**
 * Bootstrap для PHPStan: определяет константы путей, чтобы анализатор их видел.
 * Полный bootstrap (с загрузкой контейнера/конфига) для статанализа не нужен.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
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
