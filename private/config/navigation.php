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
            'title_key'      => 'MENU_CLASSICAL_CIPHERS',
            'url'            => '/classical-ciphers',
            'icon'           => 'bi bi-unlock2-fill',
            'category_alias' => 'classical-ciphers',
        ],
        [
            'title_key'      => 'MENU_ENCODING',
            'url'            => '/encoding',
            'icon'           => 'bi bi-file-code-fill',
            'category_alias' => 'encoding',
        ],
        [
            'title_key'      => 'MENU_CODES_AND_ALPHABETS',
            'url'            => '/codes-and-alphabets',
            'icon'           => 'bi bi-braces-asterisk',
            'category_alias' => 'codes-and-alphabets',
        ],
        [
            'title_key' => 'MENU_FAVORITES',
            'url'       => '/favorites',
            'icon'      => 'bi-star-fill',
        ],
    ],

];
