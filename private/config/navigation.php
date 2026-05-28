<?php

declare(strict_types=1);

// Элементы навигационного меню; передаются в шаблоны через ShareViewDataMiddleware.

return [

    'main' => [
        [
            'title_key' => 'MENU_HOME',
            'url' => '/',
            'icon' => 'bi bi-house-fill',
        ],
        [
            'title_key' => 'MENU_CLASSICAL_CIPHERS',
            'url' => '/classical-ciphers',
            'icon' => 'bi bi-unlock2-fill'],
        [
            'title_key' => 'MENU_ENCODING',
            'url' => '/encoding',
            'icon' => 'bi bi-file-code-fill',
        ],
    ],

];
