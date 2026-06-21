<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Porta для латинского алфавита.
 */
final readonly class PortaCipherService
{
    /** @var string[] Латинский алфавит, используемый исторической таблицей Porta. */
    private const array ALPHABET = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];

    /**
     * Возвращает UI-настройки инструмента для шифра Porta.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type' => 'text',
                'id' => 'ciphers-key',
                'label' => trans('CIPHER_TOOL_SETTING_KEY'),
                'class' => 'ciphers-settings-input',
                'placeholder' => trans('CIPHER_TOOL_SETTING_KEY_PLACEHOLDER'),
                'value' => '',
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра Porta.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('CIPHER_TOOL_TRUST_POLYALPHA'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Проверяет, содержит ли текст хотя бы одну латинскую букву.
     */
    public function hasLatinCharacters(string $text): bool
    {
        return preg_match('/[a-z]/i', $text) === 1;
    }

    /**
     * Выполняет шифрование или дешифрование текста шифром Porta.
     */
    public function process(string $text, string $key): string
    {
        $keyShifts = $this->keyShifts($key);
        if ($keyShifts === []) {
            return $text;
        }

        $alphabet = self::ALPHABET;
        $indexMap = array_flip($alphabet);
        $shiftsCount = count($keyShifts);
        $output = '';
        $keyIndex = 0;

        foreach (mb_str_split($text) as $char) {
            $lowerChar = mb_strtolower($char);

            if (!isset($indexMap[$lowerChar])) {
                $output .= $char;
                continue;
            }

            $position = (int) $indexMap[$lowerChar];
            $shift = $keyShifts[$keyIndex % $shiftsCount];
            $resultChar = $alphabet[$this->portaPosition($position, $shift)];

            $output .= $char === $lowerChar ? $resultChar : mb_strtoupper($resultChar);
            $keyIndex++;
        }

        return $output;
    }

    /**
     * Возвращает позицию символа после применения строки таблицы Porta.
     */
    private function portaPosition(int $position, int $shift): int
    {
        if ($position < 13) {
            return 13 + (($position + $shift) % 13);
        }

        return ($position - 13 - $shift + 13) % 13;
    }

    /**
     * Преобразует буквы ключа в номера пар AB, CD, ..., YZ.
     *
     * @return int[]
     */
    private function keyShifts(string $key): array
    {
        $indexMap = array_flip(self::ALPHABET);
        $shifts = [];

        foreach (mb_str_split(mb_strtolower($key)) as $char) {
            if (isset($indexMap[$char])) {
                $shifts[] = intdiv((int) $indexMap[$char], 2);
            }
        }

        return $shifts;
    }
}
