<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Хилла с поддержкой нескольких алфавитов.
 */
final readonly class HillCipherService
{
    public const MIN_MATRIX_SIZE = 2;
    public const MAX_MATRIX_SIZE = 5;

    /**
     * Создаёт экземпляр сервиса шифра Хилла.
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
     * Возвращает UI-настройки инструмента для шифра Хилла.
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
                    ['value' => 'en', 'label' => trans('LANG_EN'), 'attrs' => ['data-alphabet-size' => 26]],
                    ['value' => 'ru', 'label' => trans('LANG_RU'), 'attrs' => ['data-alphabet-size' => 33]],
                    ['value' => 'es', 'label' => trans('LANG_ES'), 'attrs' => ['data-alphabet-size' => 27]],
                    ['value' => 'pt', 'label' => trans('LANG_PT'), 'attrs' => ['data-alphabet-size' => 36]],
                    ['value' => 'tr', 'label' => trans('LANG_TR'), 'attrs' => ['data-alphabet-size' => 29]],
                    ['value' => 'fr', 'label' => trans('LANG_FR'), 'attrs' => ['data-alphabet-size' => 40]],
                    ['value' => 'de', 'label' => trans('LANG_DE'), 'attrs' => ['data-alphabet-size' => 30]],
                    ['value' => 'it', 'label' => trans('LANG_IT'), 'attrs' => ['data-alphabet-size' => 26]],
                ],
            ],
            [
                'type' => 'matrix',
                'id' => 'ciphers-key',
                'label' => trans('HILL_SETTING_MATRIX'),
                'sizeLabel' => trans('HILL_SETTING_MATRIX_SIZE'),
                'statusLabel' => trans('HILL_SETTING_MATRIX_STATUS'),
                'determinantLabel' => trans('HILL_SETTING_DETERMINANT'),
                'validLabel' => trans('HILL_SETTING_MATRIX_VALID'),
                'invalidLabel' => trans('HILL_SETTING_MATRIX_INVALID'),
                'class' => 'ciphers-settings-matrix',
                'sizes' => [2, 3, 4, 5],
                'size' => 2,
                'value' => '3 3; 2 5',
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра Хилла.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('HILL_TRUST_MATRIX'),
            trans('HILL_TRUST_INVERTIBLE'),
            trans('CIPHER_TOOL_TRUST_MULTI_ALPHA'),
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
     * Возвращает размер выбранного алфавита.
     */
    public function alphabetSize(string $alphabet): int
    {
        return count($this->alphabetCatalog()->alphabet($alphabet));
    }

    /**
     * Разбирает матрицу ключа из строки.
     *
     * @return int[][]
     */
    public function parseMatrix(string $key): array
    {
        $rows = preg_split('/\s*;\s*/u', trim($key)) ?: [];
        $matrix = [];

        foreach ($rows as $row) {
            if ($row === '') {
                continue;
            }

            preg_match_all('/-?\d+/u', $row, $matches);
            $values = array_map('intval', $matches[0] ?? []);
            if ($values !== []) {
                $matrix[] = $values;
            }
        }

        if (count($matrix) === 1) {
            $values = $matrix[0];
            $size = (int) sqrt(count($values));
            if ($size * $size === count($values)) {
                return array_chunk($values, $size);
            }
        }

        return $matrix;
    }

    /**
     * Проверяет, является ли матрица квадратной и поддерживаемой.
     *
     * @param int[][] $matrix Матрица ключа.
     */
    public function isSupportedMatrix(array $matrix): bool
    {
        $size = count($matrix);
        if ($size < self::MIN_MATRIX_SIZE || $size > self::MAX_MATRIX_SIZE) {
            return false;
        }

        foreach ($matrix as $row) {
            if (count($row) !== $size) {
                return false;
            }
        }

        return true;
    }

    /**
     * Проверяет, обратима ли матрица по модулю размера алфавита.
     *
     * @param int[][] $matrix Матрица ключа.
     */
    public function isInvertibleMatrix(array $matrix, int $modulus): bool
    {
        $determinant = $this->mod($this->determinant($matrix), $modulus);

        return $this->gcd($determinant, $modulus) === 1;
    }

    /**
     * Выполняет шифрование/дешифрование текста шифром Хилла.
     *
     * @param int[][] $matrix Матрица ключа.
     */
    public function process(string $text, string $alphabet, array $matrix, string $direction): string
    {
        $alphabetData = $this->alphabetCatalog()->alphabet($alphabet);
        $alphabetSize = count($alphabetData);
        $upperAlphabetData = array_map('mb_strtoupper', $alphabetData);
        $indexMapLower = array_flip($alphabetData);
        $indexMapUpper = array_flip($upperAlphabetData);
        $workingMatrix = $direction === 'decrypt'
            ? $this->inverseMatrix($matrix, $alphabetSize)
            : $matrix;
        $size = count($workingMatrix);
        $chars = $this->splitChars($text);
        $letterPositions = [];
        $vectors = [];

        foreach ($chars as $position => $char) {
            $lower = mb_strtolower($char);
            if (!isset($indexMapLower[$lower])) {
                continue;
            }

            $letterPositions[] = $position;
            $vectors[] = (int) $indexMapLower[$lower];
        }

        $padIndex = (int) ($indexMapLower['x'] ?? 0);
        $padding = $direction === 'encrypt' ? (count($vectors) % $size) : 0;
        if ($padding > 0) {
            $padding = $size - $padding;
            for ($i = 0; $i < $padding; $i++) {
                $vectors[] = $padIndex;
            }
        }

        $converted = [];
        foreach (array_chunk($vectors, $size) as $block) {
            if (count($block) !== $size) {
                $converted = array_merge($converted, $block);
                continue;
            }

            foreach ($workingMatrix as $row) {
                $sum = 0;
                foreach ($row as $index => $value) {
                    $sum += $value * $block[$index];
                }
                $converted[] = $this->mod($sum, $alphabetSize);
            }
        }

        foreach ($letterPositions as $index => $position) {
            $sourceChar = $chars[$position];
            $nextIndex = $converted[$index] ?? null;
            if ($nextIndex === null) {
                continue;
            }

            $chars[$position] = isset($indexMapUpper[$sourceChar])
                ? $upperAlphabetData[$nextIndex]
                : $alphabetData[$nextIndex];
        }

        for ($i = count($letterPositions); $i < count($converted); $i++) {
            $chars[] = $upperAlphabetData[$converted[$i]];
        }

        return implode('', $chars);
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

    /**
     * Делит строку на массив символов.
     *
     * @return string[]
     */
    private function splitChars(string $text): array
    {
        return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Возвращает положительный остаток по модулю.
     */
    private function mod(int $value, int $modulus): int
    {
        return (($value % $modulus) + $modulus) % $modulus;
    }

    /**
     * Вычисляет наибольший общий делитель.
     */
    private function gcd(int $left, int $right): int
    {
        $left = abs($left);
        $right = abs($right);

        while ($right !== 0) {
            $next = $left % $right;
            $left = $right;
            $right = $next;
        }

        return $left;
    }

    /**
     * Возвращает модульную обратную величину.
     */
    private function modInverse(int $value, int $modulus): int
    {
        $value = $this->mod($value, $modulus);

        for ($candidate = 1; $candidate < $modulus; $candidate++) {
            if (($value * $candidate) % $modulus === 1) {
                return $candidate;
            }
        }

        return 0;
    }

    /**
     * Вычисляет определитель матрицы.
     *
     * @param int[][] $matrix Матрица.
     */
    private function determinant(array $matrix): int
    {
        $size = count($matrix);
        if ($size === 1) {
            return (int) $matrix[0][0];
        }

        if ($size === 2) {
            return ((int) $matrix[0][0] * (int) $matrix[1][1]) - ((int) $matrix[0][1] * (int) $matrix[1][0]);
        }

        $determinant = 0;
        foreach ($matrix[0] as $col => $value) {
            $sign = $col % 2 === 0 ? 1 : -1;
            $determinant += $sign * (int) $value * $this->determinant($this->minor($matrix, 0, $col));
        }

        return $determinant;
    }

    /**
     * Возвращает минор матрицы.
     *
     * @param int[][] $matrix Матрица.
     * @return int[][]
     */
    private function minor(array $matrix, int $skipRow, int $skipCol): array
    {
        $minor = [];
        foreach ($matrix as $rowIndex => $row) {
            if ($rowIndex === $skipRow) {
                continue;
            }

            $minorRow = [];
            foreach ($row as $colIndex => $value) {
                if ($colIndex !== $skipCol) {
                    $minorRow[] = (int) $value;
                }
            }
            $minor[] = $minorRow;
        }

        return $minor;
    }

    /**
     * Возвращает обратную матрицу по модулю.
     *
     * @param int[][] $matrix Матрица.
     * @return int[][]
     */
    private function inverseMatrix(array $matrix, int $modulus): array
    {
        $size = count($matrix);
        $determinant = $this->mod($this->determinant($matrix), $modulus);
        $determinantInverse = $this->modInverse($determinant, $modulus);
        $inverse = [];

        for ($row = 0; $row < $size; $row++) {
            $inverse[$row] = [];
            for ($col = 0; $col < $size; $col++) {
                $sign = (($row + $col) % 2 === 0) ? 1 : -1;
                $cofactor = $sign * $this->determinant($this->minor($matrix, $col, $row));
                $inverse[$row][$col] = $this->mod($determinantInverse * $cofactor, $modulus);
            }
        }

        return $inverse;
    }
}
