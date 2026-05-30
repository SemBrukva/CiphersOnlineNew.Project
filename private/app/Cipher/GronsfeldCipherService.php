<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Гронсфельда с поддержкой нескольких алфавитов.
 */
final readonly class GronsfeldCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра Гронсфельда.
     */
    public function __construct(
        private ?AlphabetCatalog $catalog = null,
        private ?AlphabetTool    $alphabetTool = null
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
     * Возвращает UI-настройки инструмента для шифра Гронсфельда.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type' => 'select',
                'id' => 'ciphers-alphabet',
                'label' => trans('CIPHER_TOOL_SETTING_ALPHABET'),
                'class' => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'auto', 'label' => trans('CIPHER_TOOL_SETTING_AUTO'), 'selected' => true],
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
                'label' => trans('CIPHER_TOOL_SETTING_KEY'),
                'class' => 'ciphers-settings-input',
                'placeholder' => trans('CIPHER_TOOL_SETTING_KEY_EXAMPLE'),
                'value' => '',
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра Гронсфельда.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('GRONSFELD_TRUST_NUMERIC'),
            trans('CIPHER_TOOL_TRUST_MULTI_ALPHA'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
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
     * Проверяет цифровой ключ шифра Гронсфельда.
     */
    public function isValidNumericKey(string $key): bool
    {
        return $key !== '' && ctype_digit($key) && mb_strlen($key) <= 32;
    }

    /**
     * Выполняет шифрование/дешифрование текста по Гронсфельду.
     */
    public function process(string $text, string $key, string $alphabet, string $direction): string
    {
        $normalizedAlphabet = mb_strtolower(trim($alphabet));
        $alphabetData = $this->alphabetCatalog()->alphabet($normalizedAlphabet);
        $alphabetSize = count($alphabetData);
        $upperAlphabetData = array_map('mb_strtoupper', $alphabetData);
        $indexMapLower = array_flip($alphabetData);
        $indexMapUpper = array_flip($upperAlphabetData);
        $keyLength = mb_strlen($key);
        $output = '';
        $textLength = mb_strlen($text);

        if ($keyLength === 0) {
            return $text;
        }

        for ($i = 0; $i < $textLength; $i++) {
            $char = mb_substr($text, $i, 1);
            $keyDigit = (int) mb_substr($key, $i % $keyLength, 1);
            $shift = $direction === 'decrypt' ? -$keyDigit : $keyDigit;

            if (isset($indexMapLower[$char])) {
                $position = (int) $indexMapLower[$char];
                $newPosition = ($position + $shift + $alphabetSize) % $alphabetSize;
                $output .= $alphabetData[$newPosition];
                continue;
            }

            if (isset($indexMapUpper[$char])) {
                $position = (int) $indexMapUpper[$char];
                $newPosition = ($position + $shift + $alphabetSize) % $alphabetSize;
                $output .= $upperAlphabetData[$newPosition];
                continue;
            }

            $output .= $char;
        }

        return $output;
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
