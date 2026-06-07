<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра столбцовой перестановки.
 */
final class ColumnarTranspositionCipherService
{
    /**
     * Минимальная длина ключа.
     */
    public const int MIN_KEY_LENGTH = 2;

    /**
     * Максимальная длина ключа для UI и API.
     */
    public const int MAX_KEY_LENGTH = 64;

    /**
     * Создаёт экземпляр сервиса шифра столбцовой перестановки.
     */
    public function __construct()
    {
    }

    /**
     * Возвращает UI-настройки инструмента для шифра столбцовой перестановки.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type' => 'text',
                'id' => 'ciphers-key',
                'label' => trans('COLUMNAR_SETTING_KEY'),
                'class' => 'ciphers-settings-input',
                'placeholder' => trans('COLUMNAR_SETTING_KEY_PLACEHOLDER'),
                'value' => 'SECRET',
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра столбцовой перестановки.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('COLUMNAR_TRUST_TRANSPOSITION'),
            trans('COLUMNAR_TRUST_KEYWORD'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Выполняет шифрование или дешифрование столбцовой перестановкой.
     */
    public function process(string $text, string $key, string $direction): string
    {
        $key = $this->normalizeKey($key);

        return $direction === 'decrypt'
            ? $this->decrypt($text, $key)
            : $this->encrypt($text, $key);
    }

    /**
     * Нормализует ключ, сохраняя пользовательские символы.
     */
    public function normalizeKey(string $key): string
    {
        return trim($key);
    }

    /**
     * Возвращает длину ключа в символах.
     */
    public function keyLength(string $key): int
    {
        return mb_strlen($this->normalizeKey($key));
    }

    /**
     * Шифрует текст чтением столбцов в порядке ключа.
     */
    private function encrypt(string $text, string $key): string
    {
        $characters = $this->characters($text);
        $length = count($characters);
        $columns = $this->keyLength($key);

        if ($length <= 1 || $columns <= 1) {
            return $text;
        }

        $output = '';
        foreach ($this->columnOrder($key) as $column) {
            for ($index = $column; $index < $length; $index += $columns) {
                $output .= $characters[$index];
            }
        }

        return $output;
    }

    /**
     * Дешифрует текст, восстанавливая длины столбцов без padding.
     */
    private function decrypt(string $text, string $key): string
    {
        $cipherCharacters = $this->characters($text);
        $length = count($cipherCharacters);
        $columns = $this->keyLength($key);

        if ($length <= 1 || $columns <= 1) {
            return $text;
        }

        $baseRows = intdiv($length, $columns);
        $remainder = $length % $columns;
        $columnLengths = [];

        for ($column = 0; $column < $columns; $column++) {
            $columnLengths[$column] = $baseRows + ($column < $remainder ? 1 : 0);
        }

        $columnData = array_fill(0, $columns, []);
        $offset = 0;
        foreach ($this->columnOrder($key) as $column) {
            $columnData[$column] = array_slice($cipherCharacters, $offset, $columnLengths[$column]);
            $offset += $columnLengths[$column];
        }

        $output = '';
        for ($index = 0; $index < $length; $index++) {
            $column = $index % $columns;
            $row = intdiv($index, $columns);
            $output .= $columnData[$column][$row] ?? '';
        }

        return $output;
    }

    /**
     * Возвращает порядок чтения столбцов по ключу.
     *
     * @return int[]
     */
    private function columnOrder(string $key): array
    {
        $keyCharacters = $this->characters(mb_strtolower($key));
        $columns = [];

        foreach ($keyCharacters as $index => $character) {
            $columns[] = ['index' => $index, 'character' => $character];
        }

        usort(
            $columns,
            static fn (array $a, array $b): int => $a['character'] <=> $b['character'] ?: $a['index'] <=> $b['index']
        );

        return array_map(static fn (array $column): int => (int) $column['index'], $columns);
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
