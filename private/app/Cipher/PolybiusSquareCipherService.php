<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра квадрата Полибия с поддержкой нескольких алфавитов.
 */
final readonly class PolybiusSquareCipherService
{
    /**
     * Создаёт экземпляр сервиса квадрата Полибия.
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
     * Возвращает UI-настройки инструмента для квадрата Полибия.
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
                'type' => 'select',
                'id' => 'ciphers-delimiter',
                'label' => trans('CIPHER_TOOL_SETTING_DELIMITER'),
                'class' => 'ciphers-settings-select',
                'options' => [
                    ['value' => 'space', 'label' => trans('CIPHER_TOOL_SETTING_SPACE'), 'selected' => true],
                    ['value' => 'dash', 'label' => '-'],
                    ['value' => 'comma', 'label' => ','],
                    ['value' => 'slash', 'label' => '/'],
                    ['value' => 'dot', 'label' => '.'],
                ],
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для квадрата Полибия.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('CIPHER_TOOL_TRUST_MULTI_ALPHA'),
            trans('CIPHER_TOOL_TRUST_CUSTOM_DELIMITER'),
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
     * Выполняет шифрование или расшифровку квадратом Полибия.
     */
    public function process(string $text, string $alphabet, string $direction, string $delimiter): string
    {
        return $direction === 'decrypt'
            ? $this->decrypt($text, $alphabet, $delimiter)
            : $this->encrypt($text, $alphabet, $delimiter);
    }

    /**
     * Кодирует буквы в координаты квадрата.
     */
    private function encrypt(string $text, string $alphabet, string $delimiter): string
    {
        [, $positions] = $this->buildSquare($alphabet);
        $separator = $this->normalizeDelimiter($delimiter);
        $normalized = mb_strtolower($text);
        $length = mb_strlen($normalized);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $char = $this->normalizeChar(mb_substr($normalized, $i, 1), $alphabet);

            if (!isset($positions[$char])) {
                $result .= mb_substr($text, $i, 1);
                continue;
            }

            $position = $positions[$char];
            $result .= (string) ($position['row'] + 1) . (string) ($position['col'] + 1);

            if ($i < $length - 1) {
                $nextChar = $this->normalizeChar(mb_substr($normalized, $i + 1, 1), $alphabet);
                if (isset($positions[$nextChar])) {
                    $result .= $separator;
                }
            }
        }

        return trim($result);
    }

    /**
     * Декодирует координаты квадрата в буквы.
     */
    private function decrypt(string $text, string $alphabet, string $delimiter): string
    {
        [$square] = $this->buildSquare($alphabet);
        $separator = $this->normalizeDelimiter($delimiter);

        if ($separator === ' ') {
            return $this->decryptTokens(preg_split('/\s+/u', trim($text)) ?: [], $square);
        }

        $words = explode(' ', $text);
        $decodedWords = [];

        foreach ($words as $word) {
            $decodedWords[] = $this->decryptTokens(explode($separator, $word), $square);
        }

        return trim(implode(' ', $decodedWords));
    }

    /**
     * Декодирует набор координатных токенов.
     *
     * @param  string[]                    $tokens Координаты вида 11..99.
     * @param  array<int, array<int, string>> $square Квадрат Полибия.
     */
    private function decryptTokens(array $tokens, array $square): string
    {
        $result = '';

        foreach ($tokens as $token) {
            if (!preg_match('/^[1-9][1-9]$/', $token)) {
                $result .= $token;
                continue;
            }

            $row = ((int) $token[0]) - 1;
            $col = ((int) $token[1]) - 1;
            $result .= $square[$row][$col] ?? $token;
        }

        return $result;
    }

    /**
     * Строит квадрат Полибия и карту координат.
     *
     * @return array{0: array<int, array<int, string>>, 1: array<string, array{row: int, col: int}>}
     */
    private function buildSquare(string $alphabet): array
    {
        $letters = $this->alphabetCatalog()->alphabet($alphabet);
        if ($alphabet === 'en' || $alphabet === 'it') {
            $letters = array_values(array_filter($letters, static fn (string $letter): bool => $letter !== 'j'));
        }

        $square = array_chunk($letters, 5);
        $positions = [];

        foreach ($square as $rowIndex => $row) {
            foreach ($row as $colIndex => $letter) {
                $positions[$letter] = ['row' => $rowIndex, 'col' => $colIndex];
            }
        }

        if ($alphabet === 'en' || $alphabet === 'it') {
            $positions['j'] = $positions['i'];
        }

        return [$square, $positions];
    }

    /**
     * Нормализует символ с учётом исторического правила I/J.
     */
    private function normalizeChar(string $char, string $alphabet): string
    {
        if (($alphabet === 'en' || $alphabet === 'it') && $char === 'j') {
            return 'i';
        }

        return $char;
    }

    /**
     * Возвращает строковый разделитель координат.
     */
    private function normalizeDelimiter(string $delimiter): string
    {
        return match ($delimiter) {
            'dash' => '-',
            'comma' => ',',
            'slash' => '/',
            'dot' => '.',
            default => ' ',
        };
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
