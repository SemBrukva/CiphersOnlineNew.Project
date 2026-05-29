<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Бофора с поддержкой нескольких алфавитов.
 */
final class BeaufortCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра Бофора.
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
                'placeholder' => trans('CIPHER_TOOL_SETTING_KEY_PLACEHOLDER'),
                'value' => '',
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра Бофора.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('CIPHER_TOOL_TRUST_POLYALPHA'),
            trans('BEAUFORT_TRUST_RECIPROCAL'),
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
     * Выполняет преобразование текста по шифру Бофора.
     */
    public function process(string $text, string $key, string $alphabet): string
    {
        $normalizedAlphabet = mb_strtolower(trim($alphabet));
        $alphabetData = $this->alphabetCatalog()->alphabet($normalizedAlphabet);
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
