<?php

declare(strict_types=1);

/**
 * Конфигурация почтового сервиса.
 *
 * Примеры DSN:
 * - null://null                          — отключенная отправка (local)
 * - smtp://user:pass@smtp.mailjet.com:587
 * - mailjet+api://API_KEY:API_SECRET@default
 * - mailjet+stub://default              — заглушка Mailjet (запись в лог)
 */
return [
    'dsn' => (string) env('MAIL_DSN', 'null://null'),
    'from' => [
        'address' => (string) env('MAIL_FROM', 'no-reply@example.com'),
        'name' => (string) env('MAIL_FROM_NAME', 'Application'),
    ],
    'stub_log_path' => STORAGE_PATH . '/logs/mailjet-stub.log',
];
