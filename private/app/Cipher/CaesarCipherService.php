<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Цезаря с поддержкой нескольких алфавитов.
 */
final class CaesarCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра Цезаря.
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
                'label' => trans('CIPHER_TOOL_SETTING_ALPHABET'),
                'class' => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'auto', 'label' => trans('CIPHER_TOOL_SETTING_AUTO'), 'attrs' => ['data-max-shift' => 39], 'selected' => true],
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
                'label' => trans('CIPHER_TOOL_SETTING_SHIFT'),
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
     * Возвращает элементы блока доверия для шифра Цезаря.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('CAESAR_TRUST_TYPE'),
            trans('CIPHER_TOOL_TRUST_MULTI_ALPHA'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Возвращает максимально допустимый сдвиг для алфавита.
     */
    public function maxShiftForAlphabet(string $alphabet): int
    {
        $letters = $this->alphabetCatalog()->alphabet($alphabet);

        return max(0, count($letters) - 1);
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
     * Выполняет шифрование/дешифрование текста по Цезарю.
     */
    public function process(string $text, string $alphabet, int $shift, string $direction): string
    {
        $normalizedAlphabet = mb_strtolower(trim($alphabet));
        $alphabetData = $this->alphabetCatalog()->alphabet($normalizedAlphabet);
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
