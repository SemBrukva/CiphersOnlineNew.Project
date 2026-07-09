<?php

declare(strict_types=1);

/**
 * Обратные ссылки «инструмент → гайды/статьи».
 *
 * Ключ — slug инструмента вида «category/alias».
 * Значение — упорядоченный список slug'ов статей, показываемых в блоке
 * «Из наших гайдов» на странице инструмента. Существование статей проверяется
 * рантаймом (несуществующие/черновики пропускаются) и командой `guides:validate`.
 */
return [
    'classical-ciphers/caesar' => [
        'caesar-cipher-manual-decryption',
    ],
    'classical-ciphers/rot13' => [
        'caesar-cipher-manual-decryption',
    ],
    'text-analysis/caesar-brute-force' => [
        'caesar-cipher-manual-decryption',
        'decrypt-cipher-without-key',
    ],
    'text-analysis/frequency-analysis' => [
        'caesar-cipher-manual-decryption',
        'decrypt-cipher-without-key',
    ],
    'text-analysis/vigenere-cracker' => [
        'decrypt-cipher-without-key',
    ],
    'classical-ciphers/simple-substitution' => [
        'solve-substitution-cipher',
        'decrypt-cipher-without-key',
    ],
    'text-analysis/letter-frequency' => [
        'solve-substitution-cipher',
    ],
];
