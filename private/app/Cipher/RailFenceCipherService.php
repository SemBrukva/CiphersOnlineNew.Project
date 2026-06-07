<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Rail Fence.
 */
final class RailFenceCipherService
{
    /**
     * Минимальное количество рельсов.
     */
    public const int MIN_RAILS = 2;

    /**
     * Максимальное количество рельсов для UI и API.
     */
    public const int MAX_RAILS = 64;

    /**
     * Создаёт экземпляр сервиса шифра Rail Fence.
     */
    public function __construct()
    {
    }

    /**
     * Возвращает UI-настройки инструмента для шифра Rail Fence.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type' => 'number_stepper',
                'id' => 'ciphers-shift',
                'label' => trans('RAIL_FENCE_SETTING_RAILS'),
                'class' => 'ciphers-settings-shift-input',
                'min' => self::MIN_RAILS,
                'max' => 16,
                'step' => 1,
                'value' => 3,
                'decrementId' => 'ciphers-shift-dec',
                'incrementId' => 'ciphers-shift-inc',
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра Rail Fence.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('RAIL_FENCE_TRUST_TRANSPOSITION'),
            trans('RAIL_FENCE_TRUST_RAILS'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Выполняет шифрование или дешифрование Rail Fence.
     */
    public function process(string $text, int $rails, string $direction): string
    {
        $rails = $this->normalizeRails($rails);

        return $direction === 'decrypt'
            ? $this->decrypt($text, $rails)
            : $this->encrypt($text, $rails);
    }

    /**
     * Нормализует количество рельсов в допустимый диапазон.
     */
    public function normalizeRails(int $rails): int
    {
        return min(max($rails, self::MIN_RAILS), self::MAX_RAILS);
    }

    /**
     * Шифрует текст зигзагом по рельсам.
     */
    private function encrypt(string $text, int $rails): string
    {
        $characters = $this->characters($text);
        $length = count($characters);

        if ($length <= 1 || $rails >= $length) {
            return $text;
        }

        $rows = array_fill(0, $rails, '');
        foreach ($characters as $index => $char) {
            $rows[$this->railForIndex($index, $rails)] .= $char;
        }

        return implode('', $rows);
    }

    /**
     * Дешифрует текст, восстанавливая зигзаговый порядок символов.
     */
    private function decrypt(string $text, int $rails): string
    {
        $cipherCharacters = $this->characters($text);
        $length = count($cipherCharacters);

        if ($length <= 1 || $rails >= $length) {
            return $text;
        }

        $railLengths = array_fill(0, $rails, 0);
        for ($i = 0; $i < $length; $i++) {
            $railLengths[$this->railForIndex($i, $rails)]++;
        }

        $railSlices = [];
        $offset = 0;
        foreach ($railLengths as $rail => $railLength) {
            $railSlices[$rail] = array_slice($cipherCharacters, $offset, $railLength);
            $offset += $railLength;
        }

        $railOffsets = array_fill(0, $rails, 0);
        $plain = '';
        for ($i = 0; $i < $length; $i++) {
            $rail = $this->railForIndex($i, $rails);
            $plain .= $railSlices[$rail][$railOffsets[$rail]];
            $railOffsets[$rail]++;
        }

        return $plain;
    }

    /**
     * Возвращает номер рельса для позиции символа.
     */
    private function railForIndex(int $index, int $rails): int
    {
        $cycle = ($rails - 1) * 2;
        $position = $index % $cycle;

        return $position < $rails ? $position : $cycle - $position;
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
