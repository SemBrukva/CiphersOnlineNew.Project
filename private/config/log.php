<?php

declare(strict_types=1);

/**
 * Конфигурация логирования ошибок.
 *
 * Для каждого окружения можно задать вебхук в .env.
 * Если вебхук не задан — ошибки пишутся в файл storage/logs/{env}-YYYY-MM-DD.log.
 * Если вебхук задан, но недоступен — автоматически переключается на файл.
 *
 * Поддерживаемые вебхуки: Slack (Attachments), Discord (Embeds) и generic ({"text": "..."}).
 *
 * Окружения:
 *   local      — локальная разработка,  LOG_WEBHOOK_LOCAL
 *   dev        — дев-сервер,            LOG_WEBHOOK_DEV
 *   production — продакшн,              LOG_WEBHOOK_PROD
 */
return [

    'path' => STORAGE_PATH . '/logs',
    'min_level' => env('LOG_LEVEL', 'warning'),
    'format' => env('LOG_FORMAT', 'text'),

    'webhooks' => [
        'local'      => env('LOG_WEBHOOK_LOCAL'),
        'dev'        => env('LOG_WEBHOOK_DEV'),
        'production' => env('LOG_WEBHOOK_PROD'),
    ],

];
