<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Плейфера с поддержкой нескольких алфавитов.
 */
final class PlayfairCipherService
{
    /**
     * Создаёт экземпляр сервиса шифра Плейфера.
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
                'class' => 'ciphers-settings-select',
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
                $result .= $matrix[$first['row']][($first['col'] + ($isEncrypting ? 1 : $colCount - 1)) % $colCount];
                $result .= $matrix[$second['row']][($second['col'] + ($isEncrypting ? 1 : $colCount - 1)) % $colCount];
                continue;
            }

            if ($first['col'] === $second['col']) {
                $result .= $matrix[($first['row'] + ($isEncrypting ? 1 : $rowCount - 1)) % $rowCount][$first['col']];
                $result .= $matrix[($second['row'] + ($isEncrypting ? 1 : $rowCount - 1)) % $rowCount][$second['col']];
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
