<?php

declare(strict_types=1);

// Основные параметры приложения: название, окружение, режим отладки, URL и часовой пояс.

return [
    'name' => env('APP_NAME', 'Application'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(
        env('APP_DEBUG', false),
        FILTER_VALIDATE_BOOL
    ),
    'url' => env('APP_URL', 'http://127.0.0.1:8080'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'force_https' => filter_var(
        env('APP_FORCE_HTTPS', false),
        FILTER_VALIDATE_BOOL
    ),
    'dev_basic_auth' => [
        'username' => env('DEV_BASIC_AUTH_USER', ''),
        'password' => env('DEV_BASIC_AUTH_PASSWORD', ''),
        'realm'    => env('DEV_BASIC_AUTH_REALM', 'Dev Server'),
    ],
    'charset' => 'UTF-8',
    'user_registration' => filter_var(
        env('USER_REGISTRATION', false),
        FILTER_VALIDATE_BOOL
    ),
    'user_verification' => filter_var(
        env('USER_VERIFICATION', false),
        FILTER_VALIDATE_BOOL
    ),
];
