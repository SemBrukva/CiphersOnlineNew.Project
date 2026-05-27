<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Бофора с поддержкой нескольких алфавитов.
 */
final class BeaufortCipherService
{
    /** @var array<string, string[]> Поддерживаемые алфавиты для шифра Бофора. */
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
     * Возвращает UI-настройки инструмента для шифра Бофора.
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
                    ['value' => 'auto', 'label' => locale() === 'ru' ? 'Авто' : 'Auto', 'selected' => true],
                    ['value' => 'en', 'label' => 'English'],
                    ['value' => 'ru', 'label' => 'Русский'],
                    ['value' => 'es', 'label' => 'Español'],
                    ['value' => 'pt', 'label' => 'Português'],
                    ['value' => 'tr', 'label' => 'Türkçe'],
                    ['value' => 'fr', 'label' => 'Français'],
                    ['value' => 'de', 'label' => 'Deutsch'],
                    ['value' => 'it', 'label' => 'Italiano'],
                ],
            ],
            [
                'type' => 'text',
                'id' => 'ciphers-key',
                'label' => locale() === 'ru' ? 'Ключ' : 'Key',
                'class' => 'ciphers-settings-select',
                'placeholder' => locale() === 'ru' ? 'Введите ключ' : 'Enter key',
                'value' => '',
            ],
        ];
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
     * Выполняет преобразование текста по шифру Бофора.
     */
    public function process(string $text, string $key, string $alphabet): string
    {
        $normalizedAlphabet = mb_strtolower(trim($alphabet));
        $alphabetData = self::ALPHABETS[$normalizedAlphabet] ?? self::ALPHABETS['en'];
        $alphabetSize = count($alphabetData);
        $indexMap = array_flip($alphabetData);
        $keyChars = mb_str_split(mb_strtolower($key));
        $keyLength = count($keyChars);
        $keyIndex = 0;
        $output = '';
        $textLength = mb_strlen($text);

        if ($keyLength === 0) {
            return $text;
        }

        for ($i = 0; $i < $textLength; $i++) {
            $char = mb_substr($text, $i, 1);
            $lowerChar = mb_strtolower($char);

            if (!isset($indexMap[$lowerChar])) {
                $output .= $char;
                continue;
            }

            while ($keyIndex < $keyLength && !isset($indexMap[$keyChars[$keyIndex]])) {
                $keyIndex++;
                if ($keyIndex >= $keyLength) {
                    $keyIndex = 0;
                }
            }

            $keySymbol = $keyChars[$keyIndex];
            $textPos = (int) $indexMap[$lowerChar];
            $keyPos = (int) $indexMap[$keySymbol];
            $cipherPos = $keyPos - $textPos;

            if ($cipherPos < 0) {
                $cipherPos += $alphabetSize;
            }

            $resultChar = $alphabetData[$cipherPos];
            $output .= $char === mb_strtoupper($char) ? mb_strtoupper($resultChar) : $resultChar;

            $keyIndex++;
            if ($keyIndex >= $keyLength) {
                $keyIndex = 0;
            }
        }

        return $output;
    }
}
