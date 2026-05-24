<?php

declare(strict_types=1);

return [

    /*
     | Драйвер кеша: 'null' — отключён, 'memcache' — Memcached.
     | Для локального окружения рекомендуется 'null'.
     */
    'driver' => env('CACHE_DRIVER', 'null'),

    /*
     | Префикс, добавляемый ко всем ключам, чтобы избежать коллизий
     | при совместном использовании одного Memcached-сервера несколькими приложениями.
     */
    'prefix' => env('CACHE_PREFIX', 'app_'),

    'memcache' => [
        'host' => env('MEMCACHE_HOST', '127.0.0.1'),
        'port' => (int) env('MEMCACHE_PORT', 11211),
    ],

    /*
     | TTL по умолчанию (секунды), используется как fallback в коде.
     */
    'ttl' => (int) env('CACHE_TTL', 3600),

];
