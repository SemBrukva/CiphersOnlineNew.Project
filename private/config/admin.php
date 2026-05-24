<?php

declare(strict_types=1);

// Настройки панели администратора: URL-префикс и список ID администраторов.

return [

    'path' => env('ADMIN_PATH', '/admin'),

    'ids' => array_values(array_filter(
        array_map('intval', explode(',', (string) env('ADMIN_IDS', ''))),
        fn (int $id): bool => $id > 0
    )),

];
