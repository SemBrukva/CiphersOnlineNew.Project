#!/usr/bin/env php
<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('PRIVATE_PATH', BASE_PATH . '/private');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', PRIVATE_PATH . '/storage');
define('CONFIG_PATH', PRIVATE_PATH . '/config');
define('RESOURCE_PATH', PRIVATE_PATH . '/resources');
define('APP_PATH', PRIVATE_PATH . '/app');
define('DATABASE_PATH', PRIVATE_PATH . '/database');

require_once BASE_PATH . '/vendor/autoload.php';
require_once PRIVATE_PATH . '/bootstrap.php';

$db     = app(App\Database\Database::class);
$driver = config('database.default', 'sqlite');

// ─── Миграции ────────────────────────────────────────────────────────────────

$migrator = app(App\Database\Migrator::class);
$ran      = $migrator->run();

if (empty($ran)) {
    echo 'База данных уже актуальна.' . PHP_EOL;
} else {
    foreach ($ran as $name) {
        echo "Применена: {$name}" . PHP_EOL;
    }
}

// ─── Начальные данные ─────────────────────────────────────────────────────────

$ignore = $driver === 'sqlite' ? 'INSERT OR IGNORE' : 'INSERT IGNORE';

$db->execute(
    "{$ignore} INTO system_pages (language, alias, name, text, published) VALUES (?, ?, ?, ?, ?)",
    ['en', 'privacy', 'Privacy Policy', '<p>This is the privacy policy page.</p>', 1]
);
echo 'Seeded: system_pages — Privacy Policy (en)' . PHP_EOL;

$db->execute(
    "{$ignore} INTO users (name, email, password) VALUES (?, ?, ?)",
    ['Admin', 'admin@example.com', password_hash('password', PASSWORD_BCRYPT)]
);
echo 'Seeded: users — admin@example.com / password' . PHP_EOL;
