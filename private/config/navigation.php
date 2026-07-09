<?php

declare(strict_types=1);

// Элементы навигационного меню; передаются в шаблоны через ShareViewDataMiddleware.
//
// Типы пунктов:
//   - обычная ссылка          — есть 'url', нет 'category_alias';
//   - пункт-категория         — есть 'category_alias' (дети — сервисы категории из БД);
//   - группа ('type' => 'group') — дропдаун без своей страницы; дети — пункты-категории,
//     раскрываются flyout-подменю (третий уровень).

return [

    'main' => [
        [
            'title_key' => 'MENU_CIPHER_SOLVER',
            'url'       => '/cipher-solver',
            'icon'      => 'bi bi-magic',
        ],
        [
            'title_key'      => 'MENU_CLASSICAL_CIPHERS',
            'url'            => '/classical-ciphers',
            'icon'           => 'bi bi-unlock2-fill',
            'category_alias' => 'classical-ciphers',
        ],
        [
            'title_key'      => 'MENU_TEXT_ANALYSIS',
            'url'            => '/text-analysis',
            'icon'           => 'bi bi-bar-chart-fill',
            'category_alias' => 'text-analysis',
        ],
        [
            'title_key'      => 'MENU_CODES_AND_ALPHABETS',
            'url'            => '/codes-and-alphabets',
            'icon'           => 'bi bi-braces-asterisk',
            'category_alias' => 'codes-and-alphabets',
        ],
        [
            'title_key' => 'MENU_TOOLS',
            'icon'      => 'bi bi-tools',
            'type'      => 'group',
            'children'  => [
                [
                    'title_key'      => 'MENU_ENCODING',
                    'url'            => '/encoding',
                    'icon'           => 'bi bi-file-code-fill',
                    'category_alias' => 'encoding',
                ],
                [
                    'title_key'      => 'MENU_HASHING',
                    'url'            => '/hashing',
                    'icon'           => 'bi bi-hash',
                    'category_alias' => 'hashing',
                ],
            ],
        ],
    ],

];
