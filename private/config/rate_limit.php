<?php

declare(strict_types=1);

// Конфигурация rate limit для чувствительных маршрутов.

return [
    'rules' => [
        'api_auth_login' => [
            'method' => 'POST',
            'path' => '/api/auth/login',
            'max_attempts' => (int) env('RATE_LIMIT_LOGIN_MAX_ATTEMPTS', 10),
            'window_seconds' => (int) env('RATE_LIMIT_LOGIN_WINDOW_SECONDS', 60),
        ],
        'api_contact' => [
            'method' => 'POST',
            'path' => '/api/contact',
            'max_attempts' => (int) env('RATE_LIMIT_CONTACT_MAX_ATTEMPTS', 10),
            'window_seconds' => (int) env('RATE_LIMIT_CONTACT_WINDOW_SECONDS', 60),
        ],
        'api_user_profile' => [
            'method' => 'GET',
            'path' => '/api/user/profile',
            'max_attempts' => (int) env('RATE_LIMIT_API_USER_MAX_ATTEMPTS', 120),
            'window_seconds' => (int) env('RATE_LIMIT_API_USER_WINDOW_SECONDS', 60),
        ],
        'api_auth_register' => [
            'method' => 'POST',
            'path' => '/api/auth/register',
            'max_attempts' => (int) env('RATE_LIMIT_API_REGISTER_MAX_ATTEMPTS', 10),
            'window_seconds' => (int) env('RATE_LIMIT_API_REGISTER_WINDOW_SECONDS', 60),
        ],
        'api_admin_stats' => [
            'method' => 'GET',
            'path' => '/api/admin/stats',
            'max_attempts' => (int) env('RATE_LIMIT_API_ADMIN_MAX_ATTEMPTS', 60),
            'window_seconds' => (int) env('RATE_LIMIT_API_ADMIN_WINDOW_SECONDS', 60),
        ],
    ],
];
