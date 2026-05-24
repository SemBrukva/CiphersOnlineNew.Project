<?php

declare(strict_types=1);

return [
    // Классические ключи
    'SIMPLE'         => 'Привет, мир',
    'PARAM_COLON'    => 'Привет, :name!',

    // ICU: plural (ru: one|few|many)
    'ICU_PLURAL'     => 'У вас {count, plural, =0 {нет сообщений} one {# сообщение} few {# сообщения} many {# сообщений} other {# сообщения}}.',

    // choice() — pipe-разделённые формы (one|few|many)
    'CHOICE'         => ':count элемент|:count элемента|:count элементов',
];
