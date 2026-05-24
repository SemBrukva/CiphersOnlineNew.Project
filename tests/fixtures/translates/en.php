<?php

declare(strict_types=1);

return [
    // Классические ключи
    'SIMPLE'         => 'Hello World',
    'PARAM_COLON'    => 'Hello, :name!',
    'MISSING_STAYS'  => 'No replacement here.',

    // ICU: простая переменная
    'ICU_SIMPLE'     => 'Hello, {name}!',
    'ICU_TWO_PARAMS' => '{greeting}, {name}!',

    // ICU: plural (en: one|other)
    'ICU_PLURAL'     => 'You have {count, plural, =0 {no messages} one {# message} other {# messages}}.',
    'ICU_PLURAL_NESTED' => '{count, plural, one {One {type}} other {# {type}s}}',

    // ICU: select
    'ICU_SELECT'     => '{gender, select, male {He} female {She} other {They}} arrived.',

    // choice() — pipe-разделённые формы (one|other)
    'CHOICE'         => ':count item|:count items',
    'CHOICE_CURLY'   => '{count} item|{count} items',

    // Строка без подстановки — не должна триггерить ICU
    'LITERAL_BRACE'  => 'Use $_var in code.',
];
