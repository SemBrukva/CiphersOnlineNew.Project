<?php

declare(strict_types=1);

// Конфигурация геолокации на основе MaxMind GeoLite2 Country.
// Файл базы данных (.mmdb) необходимо обновлять вручную (рекомендуется ежемесячно).

return [
    'enabled'        => filter_var(env('GEOIP_ENABLED', true), FILTER_VALIDATE_BOOL),
    'db_path'        => env('GEOIP_DB_PATH', STORAGE_PATH . '/geo/GeoLite2-Country.mmdb'),
    // Страны, для которых показывается РСЯ вместо Adsense.
    'rsya_countries' => ['RU', 'BY', 'KZ'],
];
