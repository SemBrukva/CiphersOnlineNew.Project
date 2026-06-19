<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Autokey с поддержкой нескольких алфавитов.
 */
final readonly class AutokeyCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра Autokey.
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
     * Возвращает UI-настройки инструмента для шифра Autokey.
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
                    ['value' => 'en', 'label' => trans('LANG_EN')],
                    ['value' => 'ru', 'label' => trans('LANG_RU')],
                    ['value' => 'es', 'label' => trans('LANG_ES')],
                    ['value' => 'pt', 'label' => trans('LANG_PT')],
                    ['value' => 'tr', 'label' => trans('LANG_TR')],
                    ['value' => 'fr', 'label' => trans('LANG_FR')],
                    ['value' => 'de', 'label' => trans('LANG_DE')],
                    ['value' => 'it', 'label' => trans('LANG_IT')],
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
     * Возвращает элементы блока доверия для шифра Autokey.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('CIPHER_TOOL_TRUST_POLYALPHA'),
            trans('AUTOKEY_TRUST_KEY_STREAM'),
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
     * Выполняет шифрование/дешифрование текста шифром Autokey.
     */
    public function process(string $text, string $key, string $alphabet, string $direction): string
    {
        $normalizedAlphabet = mb_strtolower(trim($alphabet));
        $alphabetData = $this->alphabetCatalog()->alphabet($normalizedAlphabet);
        $alphabetSize = count($alphabetData);
        $upperAlphabetData = array_map('mb_strtoupper', $alphabetData);
        $indexMapLower = array_flip($alphabetData);
        $indexMapUpper = array_flip($upperAlphabetData);
        $keyStream = $this->extractKeyPositions($key, $indexMapLower);
        $output = '';
        $textLength = mb_strlen($text);
        $streamIndex = 0;

        if ($keyStream === []) {
            return $text;
        }

        for ($i = 0; $i < $textLength; $i++) {
            $char = mb_substr($text, $i, 1);
            $lowerChar = mb_strtolower($char);

            if (!isset($indexMapLower[$lowerChar])) {
                $output .= $char;
                continue;
            }

            $textPosition = (int) $indexMapLower[$lowerChar];
            $keyPosition = $keyStream[$streamIndex] ?? 0;
            $newPosition = $direction === 'decrypt'
                ? ($textPosition - $keyPosition + $alphabetSize) % $alphabetSize
                : ($textPosition + $keyPosition) % $alphabetSize;
            $resultChar = isset($indexMapUpper[$char])
                ? $upperAlphabetData[$newPosition]
                : $alphabetData[$newPosition];

            $output .= $resultChar;
            $keyStream[] = $direction === 'decrypt' ? $newPosition : $textPosition;
            $streamIndex++;
        }

        return $output;
    }

    /**
     * Возвращает позиции символов ключа, присутствующих в выбранном алфавите.
     *
     * @param  array<string, int> $indexMapLower Карта индексов строчных символов алфавита.
     * @return int[]
     */
    private function extractKeyPositions(string $key, array $indexMapLower): array
    {
        $positions = [];

        foreach (mb_str_split(mb_strtolower($key)) as $char) {
            if (isset($indexMapLower[$char])) {
                $positions[] = (int) $indexMapLower[$char];
            }
        }

        return $positions;
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
