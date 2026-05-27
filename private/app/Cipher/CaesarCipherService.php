<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Цезаря с поддержкой нескольких алфавитов.
 */
final class CaesarCipherService
{
    /** @var array<string, string[]> Поддерживаемые алфавиты для шифра Цезаря. */
    private const array ALPHABETS = [
        'en' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'],
        'ru' => ['а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я'],
        'es' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'ñ', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'],
        'pt' => ['a', 'á', 'à', 'ã', 'b', 'c', 'ç', 'd', 'e', 'é', 'ê', 'f', 'g', 'h', 'i', 'í', 'j', 'k', 'l', 'm', 'n', 'o', 'ó', 'ô', 'p', 'q', 'r', 's', 't', 'u', 'ú', 'v', 'w', 'x', 'y', 'z'],
        'tr' => ['a', 'b', 'c', 'ç', 'd', 'e', 'f', 'g', 'ğ', 'h', 'ı', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'ö', 'p', 'r', 's', 'ş', 't', 'u', 'ü', 'v', 'y', 'z'],
        'fr' => ['a', 'à', 'â', 'b', 'c', 'ç', 'd', 'e', 'é', 'è', 'ê', 'ë', 'f', 'g', 'h', 'i', 'î', 'ï', 'j', 'k', 'l', 'm', 'n', 'o', 'ô', 'p', 'q', 'r', 's', 't', 'u', 'ù', 'û', 'ü', 'v', 'w', 'x', 'y', 'ÿ', 'z'],
        'de' => ['a', 'ä', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'ö', 'p', 'q', 'r', 's', 'ß', 't', 'u', 'ü', 'v', 'w', 'x', 'y', 'z'],
        'it' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'],
    ];

    /**
     * Возвращает список поддерживаемых кодов алфавитов.
     *
     * @return string[]
     */
    public function supportedAlphabetCodes(): array
    {
        return array_keys(self::ALPHABETS);
    }

    /**
     * Возвращает UI-настройки инструмента для шифра Цезаря.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type' => 'select',
                'id' => 'ciphers-alphabet',
                'label' => locale() === 'ru' ? 'Алфавит' : 'Alphabet',
                'class' => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'auto', 'label' => locale() === 'ru' ? 'Авто' : 'Auto', 'attrs' => ['data-max-shift' => 39], 'selected' => true],
                    ['value' => 'en', 'label' => 'English', 'attrs' => ['data-max-shift' => 25]],
                    ['value' => 'ru', 'label' => 'Русский', 'attrs' => ['data-max-shift' => 32]],
                    ['value' => 'es', 'label' => 'Español', 'attrs' => ['data-max-shift' => 26]],
                    ['value' => 'pt', 'label' => 'Português', 'attrs' => ['data-max-shift' => 35]],
                    ['value' => 'tr', 'label' => 'Türkçe', 'attrs' => ['data-max-shift' => 28]],
                    ['value' => 'fr', 'label' => 'Français', 'attrs' => ['data-max-shift' => 39]],
                    ['value' => 'de', 'label' => 'Deutsch', 'attrs' => ['data-max-shift' => 29]],
                    ['value' => 'it', 'label' => 'Italiano', 'attrs' => ['data-max-shift' => 25]],
                ],
            ],
            [
                'type' => 'number_stepper',
                'id' => 'ciphers-shift',
                'label' => locale() === 'ru' ? 'Сдвиг' : 'Shift',
                'class' => 'ciphers-settings-shift-input',
                'min' => 0,
                'max' => 39,
                'step' => 1,
                'value' => 3,
                'decrementId' => 'ciphers-shift-dec',
                'incrementId' => 'ciphers-shift-inc',
            ],
        ];
    }

    /**
     * Возвращает максимально допустимый сдвиг для алфавита.
     */
    public function maxShiftForAlphabet(string $alphabet): int
    {
        $normalized = mb_strtolower(trim($alphabet));
        $letters = self::ALPHABETS[$normalized] ?? self::ALPHABETS['en'];

        return max(0, count($letters) - 1);
    }

    /**
     * Проверяет, содержит ли текст хотя бы один символ выбранного алфавита.
     */
    public function hasAlphabetCharacters(string $text, string $alphabet): bool
    {
        $normalized = mb_strtolower(trim($alphabet));
        $letters = self::ALPHABETS[$normalized] ?? self::ALPHABETS['en'];
        $set = array_flip($letters);

        $length = mb_strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_strtolower(mb_substr($text, $i, 1));
            if (isset($set[$char])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Автоопределяет алфавит по количеству совпадений букв в тексте.
     */
    public function detectAlphabet(string $text): string
    {
        $scores = [];
        foreach (self::ALPHABETS as $code => $letters) {
            $set = array_flip($letters);
            $scores[$code] = 0;

            $length = mb_strlen($text);
            for ($i = 0; $i < $length; $i++) {
                $char = mb_strtolower(mb_substr($text, $i, 1));
                if (isset($set[$char])) {
                    $scores[$code]++;
                }
            }
        }

        $maxScore = max($scores);
        if ($maxScore === 0) {
            return 'en';
        }

        foreach (['ru', 'tr', 'de', 'fr', 'pt', 'es', 'it', 'en'] as $code) {
            if (($scores[$code] ?? 0) === $maxScore) {
                return $code;
            }
        }

        arsort($scores);
        return (string) array_key_first($scores);
    }

    /**
     * Выполняет шифрование/дешифрование текста по Цезарю.
     */
    public function process(string $text, string $alphabet, int $shift, string $direction): string
    {
        $normalizedAlphabet = mb_strtolower(trim($alphabet));
        $alphabetData = self::ALPHABETS[$normalizedAlphabet] ?? self::ALPHABETS['en'];
        $alphabetSize = count($alphabetData);
        $indexMap = array_flip($alphabetData);
        $output = '';
        $length = mb_strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1);
            $lower = mb_strtolower($char);

            if (!isset($indexMap[$lower])) {
                $output .= $char;
                continue;
            }

            $index = (int) $indexMap[$lower];
            if ($direction === 'encrypt') {
                $nextIndex = ($index + $shift) % $alphabetSize;
            } else {
                $nextIndex = ($index - $shift + $alphabetSize) % $alphabetSize;
            }

            $nextChar = $alphabetData[$nextIndex];
            $output .= $char === mb_strtoupper($char) ? mb_strtoupper($nextChar) : $nextChar;
        }

        return $output;
    }
}
