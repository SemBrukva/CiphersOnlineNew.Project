<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Бэкона с поддержкой нескольких алфавитов.
 */
final class BaconCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра Бэкона.
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
     * Возвращает UI-настройки инструмента для шифра Бэкона.
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
     * Возвращает элементы блока доверия для шифра Бэкона.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('BACON_TRUST_STEGO'),
            trans('BACON_TRUST_BINARY'),
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
     * Выполняет шифрование/дешифрование текста по Бэкону.
     */
    public function process(string $text, string $alphabet, string $direction): string
    {
        return $direction === 'decrypt'
            ? $this->decrypt($text, $alphabet)
            : $this->encrypt($text, $alphabet);
    }

    /**
     * Кодирует текст в группы A/B.
     */
    private function encrypt(string $text, string $alphabet): string
    {
        $alphabetData = $this->alphabetCatalog()->alphabet(mb_strtolower(trim($alphabet)));
        $indexMap = array_flip($alphabetData);
        $chars = mb_str_split($text);
        $result = '';

        foreach ($chars as $char) {
            $normalizedChar = mb_strtolower($char === 'ё' ? 'е' : $char);

            if (isset($indexMap[$normalizedChar])) {
                $binary = str_pad(decbin((int) $indexMap[$normalizedChar]), 5, '0', STR_PAD_LEFT);
                $result .= strtr($binary, ['0' => 'A', '1' => 'B']);
                continue;
            }

            if (preg_match('/\s/u', $char) === 1) {
                $result .= ' ';
            }
        }

        return $result;
    }

    /**
     * Декодирует группы A/B в текст.
     */
    private function decrypt(string $text, string $alphabet): string
    {
        $alphabetData = $this->alphabetCatalog()->alphabet(mb_strtolower(trim($alphabet)));
        $chars = mb_str_split(mb_strtoupper($text));
        $groups = [];
        $buffer = '';
        $pendingSpace = false;

        foreach ($chars as $char) {
            if ($char === 'A' || $char === 'B') {
                if (mb_strlen($buffer) === 5) {
                    $groups[] = $buffer;
                    $buffer = '';

                    if ($pendingSpace) {
                        $groups[] = ' ';
                        $pendingSpace = false;
                    }
                }

                $buffer .= $char;
                continue;
            }

            if (preg_match('/\s/u', $char) === 1) {
                $pendingSpace = true;
            }
        }

        if (mb_strlen($buffer) === 5) {
            $groups[] = $buffer;
        }

        $result = '';
        $maxIndex = count($alphabetData) - 1;

        foreach ($groups as $group) {
            if ($group === ' ') {
                $result .= ' ';
                continue;
            }

            $index = bindec(strtr($group, ['A' => '0', 'B' => '1']));
            if ($index < 0 || $index > $maxIndex) {
                continue;
            }

            $result .= $alphabetData[$index];
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
