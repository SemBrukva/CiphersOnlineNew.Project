<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Атбаш с поддержкой нескольких алфавитов.
 */
final class AtbashCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра Атбаш.
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
     * Возвращает UI-настройки инструмента для шифра Атбаш.
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
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра Атбаш.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('ATBASH_TRUST_RECIPROCAL'),
            trans('ATBASH_TRUST_KEYLESS'),
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
     * Выполняет преобразование текста по Атбашу.
     */
    public function process(string $text, string $alphabet): string
    {
        $alphabetData = $this->alphabetCatalog()->alphabet(mb_strtolower(trim($alphabet)));
        $size = count($alphabetData);
        $indexMap = array_flip($alphabetData);
        $result = '';
        $length = mb_strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($text, $i, 1);
            $lower = mb_strtolower($char);

            if (!isset($indexMap[$lower])) {
                $result .= $char;
                continue;
            }

            $index = (int) $indexMap[$lower];
            $mirrored = $size - $index - 1;
            $nextChar = $alphabetData[$mirrored];
            $result .= $char === mb_strtoupper($char) ? mb_strtoupper($nextChar) : $nextChar;
        }

        return $result;
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
