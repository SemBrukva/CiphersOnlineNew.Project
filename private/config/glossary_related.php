<?php

declare(strict_types=1);

/**
 * Обратные ссылки «инструмент → термины глоссария».
 *
 * Ключ — slug инструмента вида «category/alias».
 * Значение — упорядоченный список slug'ов терминов глоссария, показываемых в блоке
 * «Из глоссария» на странице инструмента. Существование терминов проверяется рантаймом
 * (несуществующие пропускаются) и командой `glossary:validate`.
 */
return [
    'text-analysis/frequency-analysis' => [
        'frequency-analysis',
        'cryptanalysis',
        'index-of-coincidence',
    ],
    'text-analysis/vigenere-cracker' => [
        'kasiski-examination',
        'index-of-coincidence',
        'polyalphabetic-cipher',
    ],
    'classical-ciphers/caesar' => [
        'shift-cipher',
        'substitution-cipher',
        'monoalphabetic-cipher',
    ],
    'classical-ciphers/rot13' => [
        'shift-cipher',
        'substitution-cipher',
        'monoalphabetic-cipher',
    ],
    'classical-ciphers/simple-substitution' => [
        'substitution-cipher',
        'monoalphabetic-cipher',
    ],
    'classical-ciphers/affine' => [
        'substitution-cipher',
        'monoalphabetic-cipher',
    ],
    'classical-ciphers/atbash' => [
        'substitution-cipher',
        'monoalphabetic-cipher',
    ],
    'classical-ciphers/columnar-transposition' => [
        'transposition-cipher',
    ],
    'classical-ciphers/rail-fence' => [
        'transposition-cipher',
    ],
    'classical-ciphers/vigenere' => [
        'polyalphabetic-cipher',
        'tabula-recta',
        'keystream',
        'running-key-cipher',
    ],
    'classical-ciphers/autokey' => [
        'polyalphabetic-cipher',
        'keystream',
    ],
    'classical-ciphers/beaufort' => [
        'polyalphabetic-cipher',
        'tabula-recta',
        'keystream',
    ],
    'classical-ciphers/gronsfeld' => [
        'polyalphabetic-cipher',
        'tabula-recta',
        'keystream',
    ],
    'classical-ciphers/vernam' => [
        'keystream',
    ],
    'classical-ciphers/xor-cipher' => [
        'keystream',
    ],
    'classical-ciphers/enigma' => [
        'rotor-machine',
        'polyalphabetic-cipher',
        'substitution-cipher',
    ],
];
