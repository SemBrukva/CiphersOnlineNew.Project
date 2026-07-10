<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Scytale (скитала).
 */
final class ScytaleCipherService
{
    /**
     * Минимальное количество столбцов (диаметр жезла).
     */
    public const int MIN_COLUMNS = 2;

    /**
     * Максимальное количество столбцов для UI и API.
     */
    public const int MAX_COLUMNS = 64;

    /**
     * Создаёт экземпляр сервиса шифра Scytale.
     */
    public function __construct()
    {
    }

    /**
     * Возвращает UI-настройки инструмента для шифра Scytale.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type' => 'number_stepper',
                'id' => 'ciphers-shift',
                'label' => trans('SCYTALE_SETTING_COLUMNS'),
                'class' => 'ciphers-settings-shift-input',
                'min' => self::MIN_COLUMNS,
                'max' => 16,
                'step' => 1,
                'value' => 4,
                'decrementId' => 'ciphers-shift-dec',
                'incrementId' => 'ciphers-shift-inc',
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра Scytale.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('SCYTALE_TRUST_TRANSPOSITION'),
            trans('SCYTALE_TRUST_COLUMNS'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Выполняет шифрование или дешифрование Scytale.
     */
    public function process(string $text, int $columns, string $direction): string
    {
        $columns = $this->normalizeColumns($columns);

        return $direction === 'decrypt'
            ? $this->decrypt($text, $columns)
            : $this->encrypt($text, $columns);
    }

    /**
     * Нормализует количество столбцов в допустимый диапазон.
     */
    public function normalizeColumns(int $columns): int
    {
        return min(max($columns, self::MIN_COLUMNS), self::MAX_COLUMNS);
    }

    /**
     * Шифрует текст: запись по строкам в сетку из N столбцов, чтение по столбцам.
     */
    private function encrypt(string $text, int $columns): string
    {
        $characters = $this->characters($text);
        $length = count($characters);

        if ($length <= 1 || $columns >= $length) {
            return $text;
        }

        $result = '';
        for ($column = 0; $column < $columns; $column++) {
            for ($index = $column; $index < $length; $index += $columns) {
                $result .= $characters[$index];
            }
        }

        return $result;
    }

    /**
     * Дешифрует текст: восстанавливает сетку по длинам столбцов и читает по строкам.
     */
    private function decrypt(string $text, int $columns): string
    {
        $cipherCharacters = $this->characters($text);
        $length = count($cipherCharacters);

        if ($length <= 1 || $columns >= $length) {
            return $text;
        }

        $columnLengths = $this->columnLengths($length, $columns);

        $columnSlices = [];
        $offset = 0;
        foreach ($columnLengths as $column => $columnLength) {
            $columnSlices[$column] = array_slice($cipherCharacters, $offset, $columnLength);
            $offset += $columnLength;
        }

        $columnOffsets = array_fill(0, $columns, 0);
        $plain = '';
        for ($index = 0; $index < $length; $index++) {
            $column = $index % $columns;
            $plain .= $columnSlices[$column][$columnOffsets[$column]];
            $columnOffsets[$column]++;
        }

        return $plain;
    }

    /**
     * Вычисляет длины столбцов при записи текста по строкам в сетку из N столбцов.
     *
     * @return int[]
     */
    private function columnLengths(int $length, int $columns): array
    {
        $baseLength = intdiv($length, $columns);
        $remainder = $length % $columns;

        $lengths = [];
        for ($column = 0; $column < $columns; $column++) {
            $lengths[$column] = $baseLength + ($column < $remainder ? 1 : 0);
        }

        return $lengths;
    }

    /**
     * Разбивает UTF-8 строку на символы.
     *
     * @return string[]
     */
    private function characters(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $characters = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (is_array($characters)) {
            return $characters;
        }

        return str_split($text);
    }
}
