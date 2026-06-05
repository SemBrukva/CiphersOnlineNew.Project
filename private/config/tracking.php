<?php

declare(strict_types=1);

// Конфигурация внешних аналитических и рекламных тегов.

return [
    'google' => [
        'analytics_id'   => env('TRACKING_GA_MEASUREMENT_ID', ''),
        'adsense_client_id' => env('TRACKING_ADSENSE_CLIENT_ID', ''),
        'adsense_slots'  => [
            'after_hero'        => env('TRACKING_ADSENSE_SLOT_AFTER_HERO', ''),
            'after_first_block' => env('TRACKING_ADSENSE_SLOT_AFTER_FIRST_BLOCK', ''),
            'after_faq'         => env('TRACKING_ADSENSE_SLOT_AFTER_FAQ', ''),
        ],
    ],
    'yandex' => [
        'metrica_id'      => env('TRACKING_YANDEX_METRICA_ID', ''),
        'metrica_webvisor' => filter_var(
            env('TRACKING_YANDEX_METRICA_WEBVISOR', false),
            FILTER_VALIDATE_BOOL
        ),
        'rsya_enabled'    => filter_var(
            env('TRACKING_YANDEX_RSYA_ENABLED', false),
            FILTER_VALIDATE_BOOL
        ),
        'rsya_slots'      => [
            'after_hero'        => env('TRACKING_RSYA_SLOT_AFTER_HERO', ''),
            'after_first_block' => env('TRACKING_RSYA_SLOT_AFTER_FIRST_BLOCK', ''),
            'after_faq'         => env('TRACKING_RSYA_SLOT_AFTER_FAQ', ''),
        ],
    ],
];
