<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Плейфера с поддержкой нескольких алфавитов.
 */
final readonly class PlayfairCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра Плейфера.
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
     * Возвращает UI-настройки инструмента для шифра Плейфера.
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
     * Возвращает элементы блока доверия для шифра Плейфера.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('PLAYFAIR_TRUST_CLIENT_SIDE'),
            trans('PLAYFAIR_TRUST_PRIVATE'),
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
     * Выполняет шифрование/дешифрование текста по Плейферу.
     */
    public function process(string $text, string $key, string $alphabet, string $direction): string
    {
        [$matrix, $positions] = $this->generateSquare($key, $alphabet);
        $isEncrypting = $direction === 'encrypt';
        $preparedText = $this->normalizeInput($text, $alphabet);
        $bigrams = $this->buildBigrams($preparedText, $alphabet);

        $rowCount = count($matrix);
        $colCount = count($matrix[0]);
        $result = '';

        foreach ($bigrams as [$a, $b]) {
            $first = $positions[$a];
            $second = $positions[$b];

            if ($first['row'] === $second['row']) {
                $rowLen = count($matrix[$first['row']]);
                $step   = $isEncrypting ? 1 : $rowLen - 1;
                $result .= $matrix[$first['row']][($first['col'] + $step) % $rowLen];
                $result .= $matrix[$second['row']][($second['col'] + $step) % $rowLen];
                continue;
            }

            if ($first['col'] === $second['col']) {
                $step     = $isEncrypting ? 1 : $rowCount - 1;
                $nextRow1 = ($first['row'] + $step) % $rowCount;
                while (!array_key_exists($first['col'], $matrix[$nextRow1])) {
                    $nextRow1 = ($nextRow1 + $step) % $rowCount;
                }
                $nextRow2 = ($second['row'] + $step) % $rowCount;
                while (!array_key_exists($second['col'], $matrix[$nextRow2])) {
                    $nextRow2 = ($nextRow2 + $step) % $rowCount;
                }
                $result .= $matrix[$nextRow1][$first['col']];
                $result .= $matrix[$nextRow2][$second['col']];
                continue;
            }

            $result .= $matrix[$first['row']][$second['col']];
            $result .= $matrix[$second['row']][$first['col']];
        }

        return $result;
    }

    /**
     * Очищает и нормализует входной текст под выбранный алфавит.
     */
    private function normalizeInput(string $text, string $alphabet): string
    {
        $upperAlphabet = array_map('mb_strtoupper', $this->alphabetCatalog()->alphabet($alphabet));
        $allowed = implode('', $upperAlphabet);
        $input = mb_strtoupper($text);

        return (string) preg_replace('/[^' . preg_quote($allowed, '/') . ']/u', '', $input);
    }

    /**
     * Строит биграммы для алгоритма Плейфера с заполнителем.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function buildBigrams(string $text, string $alphabet): array
    {
        $letters = $this->alphabetCatalog()->alphabet($alphabet);
        $filler = mb_strtoupper($letters[0] ?? 'X');
        $pairs = [];
        $index = 0;

        while ($index < mb_strlen($text)) {
            $first = mb_substr($text, $index, 1);
            $second = mb_substr($text, $index + 1, 1);

            if ($first === $second) {
                $pairs[] = [$first, $filler];
                $index += 1;
                continue;
            }

            if ($second === '') {
                $pairs[] = [$first, $filler];
                $index += 1;
                continue;
            }

            $pairs[] = [$first, $second];
            $index += 2;
        }

        return $pairs;
    }

    /**
     * Генерирует квадрат Плейфера и карту позиций символов.
     *
     * @return array{0: array<int, array<int, string>>, 1: array<string, array{row: int, col: int}>}
     */
    private function generateSquare(string $key, string $alphabet): array
    {
        $letters = $this->alphabetCatalog()->alphabet($alphabet);
        $upperLetters = array_map('mb_strtoupper', $letters);
        $allowed = implode('', $upperLetters);
        $normalizedKey = mb_strtoupper($key);
        $normalizedKey = (string) preg_replace('/[^' . preg_quote($allowed, '/') . ']/u', '', $normalizedKey);
        $keyChars = mb_str_split($normalizedKey);

        $used = [];
        $square = [];

        foreach ($keyChars as $char) {
            if (isset($used[$char])) {
                continue;
            }

            $used[$char] = true;
            $square[] = $char;
        }

        foreach ($upperLetters as $char) {
            if (isset($used[$char])) {
                continue;
            }

            $used[$char] = true;
            $square[] = $char;
        }

        $size = (int) ceil(sqrt(count($square)));
        $matrix = array_chunk($square, $size);
        $positions = [];

        foreach ($matrix as $row => $line) {
            foreach ($line as $col => $char) {
                $positions[$char] = ['row' => $row, 'col' => $col];
            }
        }

        return [$matrix, $positions];
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
