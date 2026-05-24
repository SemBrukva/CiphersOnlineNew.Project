<?php

declare(strict_types=1);

// Список доверенных обратных прокси-серверов.
// Поддерживаются: одиночный IPv4-адрес, CIDR-диапазон (10.0.0.0/8), '*' — доверять всем.
// TRUSTED_PROXIES в .env — строка через запятую.

return [
    'proxies' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))),
        static fn (string $v): bool => $v !== ''
    )),
];
