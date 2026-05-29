<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра A1Z26 с поддержкой нескольких алфавитов.
 */
final class A1z26CipherService
{
    /**
     * Создаёт экземпляр сервиса шифра A1Z26.
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
     * Возвращает UI-настройки инструмента для шифра A1Z26.
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
                'type' => 'select',
                'id' => 'ciphers-delimiter',
                'label' => locale() === 'ru' ? 'Разделитель' : 'Delimiter',
                'class' => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'dash', 'label' => '-'],
                    ['value' => 'space', 'label' => locale() === 'ru' ? 'Пробел' : 'Space'],
                ],
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра A1Z26.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('A1Z26_TRUST_POSITIONAL'),
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
     * Выполняет шифрование/дешифрование текста по A1Z26.
     */
    public function process(string $text, string $alphabet, string $direction, string $delimiter): string
    {
        return $direction === 'decrypt'
            ? $this->decrypt($text, $alphabet, $delimiter)
            : $this->encrypt($text, $alphabet, $delimiter);
    }

    /**
     * Кодирует текст в числовые индексы букв.
     */
    private function encrypt(string $text, string $alphabet, string $delimiter): string
    {
        $alphabetData = $this->alphabetCatalog()->alphabet(mb_strtolower(trim($alphabet)));
        $indexMap = array_flip($alphabetData);
        $normalizedDelimiter = $delimiter === 'space' ? ' ' : '-';
        $result = '';
        $lower = mb_strtolower($text);
        $length = mb_strlen($lower);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($lower, $i, 1);

            if (!isset($indexMap[$char])) {
                $result .= $char;
                continue;
            }

            $result .= (string) ((int) $indexMap[$char] + 1);

            if ($i === $length - 1) {
                continue;
            }

            $nextChar = mb_substr($lower, $i + 1, 1);
            if ($nextChar !== ' ') {
                $result .= $normalizedDelimiter;
            }
        }

        return trim($result);
    }

    /**
     * Декодирует числовые индексы в текст.
     */
    private function decrypt(string $text, string $alphabet, string $delimiter): string
    {
        $alphabetData = $this->alphabetCatalog()->alphabet(mb_strtolower(trim($alphabet)));
        $normalizedDelimiter = $delimiter === 'space' ? ' ' : '-';
        $words = $normalizedDelimiter === ' '
            ? [$text]
            : explode(' ', $text);
        $result = '';

        foreach ($words as $word) {
            $parts = explode($normalizedDelimiter, $word);

            foreach ($parts as $part) {
                if (!ctype_digit($part)) {
                    $result .= $part;
                    continue;
                }

                $index = ((int) $part) - 1;
                if (!array_key_exists($index, $alphabetData)) {
                    $result .= (string) $index;
                    continue;
                }

                $result .= $alphabetData[$index];
            }

            $result .= ' ';
        }

        return trim($result);
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
