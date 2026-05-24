<?php

declare(strict_types=1);

/**
 * Конфигурация HTTP-клиента.
 *
 * Переменные окружения:
 *   HTTP_CLIENT_TIMEOUT         — таймаут ожидания ответа (секунды, по умолчанию 30)
 *   HTTP_CLIENT_CONNECT_TIMEOUT — таймаут установки соединения (секунды, по умолчанию 10)
 *   HTTP_CLIENT_VERIFY_SSL      — проверять SSL-сертификат (true | false, по умолчанию true)
 */
return [
    'timeout'         => (int) env('HTTP_CLIENT_TIMEOUT', 30),
    'connect_timeout' => (int) env('HTTP_CLIENT_CONNECT_TIMEOUT', 10),
    'verify_ssl'      => filter_var(env('HTTP_CLIENT_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    'headers'         => [
        'Accept'     => 'application/json',
        'User-Agent' => env('APP_NAME', 'Skeleton') . '/1.0',
    ],
];
