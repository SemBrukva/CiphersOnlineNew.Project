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
    'codes-and-alphabets/morse-code' => [
        'read-and-write-morse-code',
        'top-15-escape-room-ciphers',
    ],
    'encoding/base64' => [
        'understand-and-decode-base64',
        'decrypt-cipher-without-key',
    ],
    'classical-ciphers/caesar' => [
        'caesar-cipher-manual-decryption',
        'top-15-escape-room-ciphers',
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
    'codes-and-alphabets/pigpen' => [
        'top-15-escape-room-ciphers',
    ],
    'codes-and-alphabets/numbers-to-letters' => [
        'top-15-escape-room-ciphers',
    ],
    'classical-ciphers/atbash' => [
        'top-15-escape-room-ciphers',
    ],
    'classical-ciphers/vigenere' => [
        'top-15-escape-room-ciphers',
    ],
];
