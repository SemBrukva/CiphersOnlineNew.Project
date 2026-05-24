<?php

/**
 * Простой тестовый HTTP-сервер для интеграционных тестов HTTP-клиента.
 *
 * Запускается через PHP built-in server:
 *   php -S 127.0.0.1:18080 tests/fixtures/http_server.php
 *
 * Маршруты:
 *   ANY  /*               — эхо-ответ с методом, URI, query, заголовками и телом
 *   GET  /status/{code}   — ответ с заданным HTTP-статусом
 */

declare(strict_types=1);

header('Content-Type: application/json');

$method  = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$uri     = (string) strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?');
$rawBody = (string) file_get_contents('php://input');

// Собираем заголовки запроса
$headers = [];

if (function_exists('getallheaders')) {
    foreach ((array) getallheaders() as $name => $value) {
        $headers[(string) $name] = (string) $value;
    }
} else {
    foreach ($_SERVER as $key => $value) {
        if (str_starts_with((string) $key, 'HTTP_')) {
            $normalized = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr((string) $key, 5)))));
            $headers[$normalized] = (string) $value;
        }
    }
}

// Специальный маршрут: /status/{code}
if (preg_match('#^/status/(\d+)$#', $uri, $m)) {
    $code = (int) $m[1];
    http_response_code($code);
    echo (string) json_encode(['status' => $code]);
    exit;
}

// Парсим тело запроса
$body = null;

if ($rawBody !== '') {
    $contentType = $headers['Content-Type'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        $body = json_decode($rawBody, true);
    } else {
        parse_str($rawBody, $body);
    }
}

// Эхо-ответ для всех остальных маршрутов
echo (string) json_encode([
    'method'  => $method,
    'uri'     => $uri,
    'query'   => $_GET,
    'headers' => $headers,
    'body'    => $body,
]);
