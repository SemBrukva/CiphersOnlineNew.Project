<?php

declare(strict_types=1);

// Крон-скрипты разрешено запускать только из CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Cron scripts must be run from the command line.');
}

define('BASE_PATH', dirname(__DIR__, 2));
define('PRIVATE_PATH', BASE_PATH . '/private');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', PRIVATE_PATH . '/storage');
define('CONFIG_PATH', PRIVATE_PATH . '/config');
define('RESOURCE_PATH', PRIVATE_PATH . '/resources');
define('APP_PATH', PRIVATE_PATH . '/app');
define('DATABASE_PATH', PRIVATE_PATH . '/database');

require_once PRIVATE_PATH . '/init.php';
