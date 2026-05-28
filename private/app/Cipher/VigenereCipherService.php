<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Виженера с поддержкой нескольких алфавитов.
 */
final class VigenereCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра Виженера.
     */
    public function __construct(
        private readonly ?AlphabetCatalog $catalog = null,
        private readonly ?AlphabetTool $alphabetTool = null
    ) {
    }

    /**
     * Возвращает список поддерживаемых кодов алфавитов.
     *
     * @return string[]
     */
    public function supportedAlphabetCodes(): array
    {
        return $this->alphabetCatalog()->codes();
    }

    /**
     * Возвращает UI-настройки инструмента для шифра Виженера.
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
                'class' => 'ciphers-settings-input',
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
        return $this->tool()->hasAlphabetCharacters($text, $alphabet);
    }

    /**
     * Автоопределяет алфавит по количеству совпадений букв в тексте.
     */
    public function detectAlphabet(string $text): string
    {
        return $this->tool()->detectAlphabet($text);
    }

    /**
     * Выполняет шифрование/дешифрование текста по Виженеру.
     */
    public function process(string $text, string $key, string $alphabet, string $direction): string
    {
        $normalizedAlphabet = mb_strtolower(trim($alphabet));
        $alphabetData = $this->alphabetCatalog()->alphabet($normalizedAlphabet);
        $alphabetSize = count($alphabetData);
        $upperAlphabetData = array_map('mb_strtoupper', $alphabetData);
        $indexMapLower = array_flip($alphabetData);
        $indexMapUpper = array_flip($upperAlphabetData);
        $filteredKeyChars = $this->extractKeyCharacters($key, $indexMapLower);
        $keyLength = count($filteredKeyChars);
        $output = '';
        $textLength = mb_strlen($text);
        $keyIndex = 0;

        if ($keyLength === 0) {
            return $text;
        }

        for ($i = 0; $i < $textLength; $i++) {
            $char = mb_substr($text, $i, 1);
            $lowerChar = mb_strtolower($char);

            if (isset($indexMapLower[$lowerChar])) {
                $textPosition = (int) $indexMapLower[$lowerChar];
                $keyPosition = (int) $indexMapLower[$filteredKeyChars[$keyIndex]];
                $shift = $direction === 'decrypt' ? -$keyPosition : $keyPosition;
                $newPosition = ($textPosition + $shift + $alphabetSize) % $alphabetSize;

                if (isset($indexMapUpper[$char])) {
                    $output .= $upperAlphabetData[$newPosition];
                } else {
                    $output .= $alphabetData[$newPosition];
                }

                $keyIndex++;
                if ($keyIndex >= $keyLength) {
                    $keyIndex = 0;
                }
                continue;
            }

            $output .= $char;
        }

        return $output;
    }

    /**
     * Возвращает символы ключа, присутствующие в выбранном алфавите.
     *
     * @param  array<string, int> $indexMapLower Карта индексов строчных символов алфавита.
     * @return string[]
     */
    private function extractKeyCharacters(string $key, array $indexMapLower): array
    {
        $chars = mb_str_split(mb_strtolower($key));

        return array_values(array_filter(
            $chars,
            static fn (string $char): bool => isset($indexMapLower[$char])
        ));
    }

    /**
     * Возвращает каталог алфавитов.
     */
    private function alphabetCatalog(): AlphabetCatalog
    {
        return $this->catalog ?? new AlphabetCatalog();
    }

    /**
     * Возвращает утилиту общих операций с алфавитами.
     */
    private function tool(): AlphabetTool
    {
        return $this->alphabetTool ?? new AlphabetTool($this->alphabetCatalog());
    }
}
