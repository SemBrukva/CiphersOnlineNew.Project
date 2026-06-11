<?php

declare(strict_types=1);

/**
 * Ручные привязки связанных инструментов для блока «Related Tools».
 *
 * Ключ — слаг текущего инструмента вида «category/alias».
 * Значение — упорядоченный список слагов связанных инструментов.
 *
 * Логика в CipherController: сначала берёт ручные привязки из этого файла,
 * затем добирает до 6 из той же категории (исключая текущий и уже добавленные).
 */
return [
    'text-analysis/caesar-brute-force' => [
        'classical-ciphers/caesar',
        'classical-ciphers/rot13',
    ],
    'text-analysis/letter-frequency' => [
        'text-analysis/frequency-analysis',
        'classical-ciphers/caesar',
        'classical-ciphers/vigenere',
    ],
    'text-analysis/frequency-analysis' => [
        'text-analysis/letter-frequency',
        'classical-ciphers/vigenere',
        'classical-ciphers/caesar',
    ],
    'classical-ciphers/caesar' => [
        'classical-ciphers/rot13',
        'text-analysis/caesar-brute-force',
        'text-analysis/letter-frequency',
        'classical-ciphers/affine',
    ],
    'classical-ciphers/rot13' => [
        'classical-ciphers/caesar',
        'text-analysis/caesar-brute-force',
    ],
];
