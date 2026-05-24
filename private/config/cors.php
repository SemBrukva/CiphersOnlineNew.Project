<?php

declare(strict_types=1);

// Настройки CORS для API-запросов.

$rawOrigins = (string) env('CORS_ALLOWED_ORIGINS', '');
$origins = array_values(array_filter(array_map('trim', explode(',', $rawOrigins)), static fn (string $item): bool => $item !== ''));

if ($origins === []) {
    // Когда переменная не задана — разрешаем только APP_URL; если и он не задан — запрещаем всё.
    $appUrl = (string) env('APP_URL', '');
    $origins = $appUrl !== '' ? [$appUrl] : [];
}

return [
    'allowed_origins' => $origins,
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN', 'X-Request-Id'],
    'exposed_headers' => ['X-Request-Id'],
    'allow_credentials' => true,
    'max_age' => 600,
];
