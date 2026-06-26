<?php

declare(strict_types=1);

/**
 * Конфигурация интеграции с API Яндекс Вебмастера.
 */
return [
    'base_url' => env('YANDEX_WEBMASTER_BASE_URL', 'https://api.webmaster.yandex.net'),
    'token' => env('YANDEX_WEBMASTER_TOKEN', ''),
    'token_type' => env('YANDEX_WEBMASTER_TOKEN_TYPE', 'OAuth'),
    'user_id' => env('YANDEX_WEBMASTER_USER_ID', ''),
    'host_id' => env('YANDEX_WEBMASTER_HOST_ID', ''),
    'device_type_indicator' => env('YANDEX_WEBMASTER_DEVICE_TYPE', 'ALL'),
    'region_ids' => array_values(array_filter(array_map(
        static fn (string $id): int => (int) trim($id),
        explode(',', (string) env('YANDEX_WEBMASTER_REGION_IDS', ''))
    ))),
    'page_size' => max(1, min(500, (int) env('YANDEX_WEBMASTER_PAGE_SIZE', 500))),
    'max_pages' => max(1, (int) env('YANDEX_WEBMASTER_MAX_PAGES', 40)),
    'record_missing' => filter_var(env('YANDEX_WEBMASTER_RECORD_MISSING', true), FILTER_VALIDATE_BOOL),
];
