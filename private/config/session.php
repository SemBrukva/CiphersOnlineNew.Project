<?php

declare(strict_types=1);

// Конфигурация HTTP-сессий: имя, время жизни, параметры cookie, драйвер хранилища.

return [
    /*
     | Драйвер хранилища: 'file' — файлы на диске, 'memcached' — Memcached, 'redis' — Redis.
     */
    'driver' => env('SESSION_DRIVER', 'file'),

    'name'      => env('SESSION_NAME', 'APP_SESSION'),
    'lifetime'  => (int) env('SESSION_LIFETIME', 0),
    'path'      => '/',
    'domain'    => env('SESSION_DOMAIN', ''),
    // В продакшне (не local) cookie должен передаваться только по HTTPS.
    'secure'    => filter_var(env('SESSION_SECURE', env('APP_ENV', 'production') !== 'local'), FILTER_VALIDATE_BOOL),
    'httponly'  => true,
    'samesite'  => 'Lax',
    'save_path' => STORAGE_PATH . '/sessions',

    /*
     | TTL хранения данных сессии в Memcached / Redis (секунды).
     | Для 'file'-драйвера не используется — очистка выполняется через cron.
     */
    'ttl' => (int) env('SESSION_TTL', 86400),

    /*
     | Параметры Memcached для хранения сессий.
     | Используются только при SESSION_DRIVER=memcached.
     */
    'memcached' => [
        'host'   => env('SESSION_MEMCACHED_HOST', env('MEMCACHE_HOST', '127.0.0.1')),
        'port'   => (int) env('SESSION_MEMCACHED_PORT', env('MEMCACHE_PORT', 11211)),
        'prefix' => env('SESSION_MEMCACHED_PREFIX', 'sess_'),
    ],

    /*
     | Параметры Redis для хранения сессий.
     | Используются только при SESSION_DRIVER=redis.
     */
    'redis' => [
        'host'     => env('SESSION_REDIS_HOST', '127.0.0.1'),
        'port'     => (int) env('SESSION_REDIS_PORT', 6379),
        'password' => env('SESSION_REDIS_PASSWORD', ''),
        'database' => (int) env('SESSION_REDIS_DB', 1),
        'prefix'   => env('SESSION_REDIS_PREFIX', 'sess_'),
    ],
];
