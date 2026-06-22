<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис шифра Альберти (Alberti Cipher Disk, ок. 1467 г.).
 *
 * Принцип работы:
 *  - Внешнее (неподвижное) кольцо: A-Z в алфавитном порядке (открытый текст).
 *  - Внутреннее (вращающееся) кольцо: перемешанный алфавит на основе ключевого слова.
 *  - Начальное смещение (index): буква внешнего кольца, которая выравнивается с позицией 0 внутреннего.
 *
 * Алгоритм шифрования:
 *  - Для каждой буквы открытого текста на позиции p внешнего кольца:
 *    шифртекст = inner[(p − offset + 26) mod 26]
 *
 * Алгоритм дешифрования:
 *  - Для каждой буквы шифртекста на позиции j внутреннего кольца:
 *    открытый текст = outer[(j + offset) mod 26]
 *
 * Регистр входного символа сохраняется.
 */
final readonly class AlbertiCipherService
{
    /** @var string[] Внешнее кольцо: стандартный латинский алфавит. */
    private const array OUTER = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];

    /**
     * Возвращает UI-настройки инструмента для шифра Альберти.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        $indexOptions = [['value' => '', 'label' => '–', 'selected' => false]];
        foreach (range('A', 'Z') as $letter) {
            $indexOptions[] = ['value' => $letter, 'label' => $letter, 'selected' => $letter === 'A'];
        }

        return [
            [
                'type'        => 'text',
                'id'          => 'ciphers-key',
                'label'       => trans('CIPHER_TOOL_SETTING_KEY'),
                'class'       => 'ciphers-settings-input',
                'placeholder' => trans('CIPHER_TOOL_SETTING_KEY_PLACEHOLDER'),
                'value'       => '',
                'shuffleKey'  => false,
            ],
            [
                'type'    => 'select',
                'id'      => 'ciphers-alberti-index',
                'label'   => trans('ALBERTI_SETTING_INDEX'),
                'class'   => 'ciphers-settings-select',
                'options' => $indexOptions,
            ],
        ];
    }

    /**
     * Возвращает элементы блока доверия для шифра Альберти.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('ALBERTI_TRUST_DISK'),
            trans('ALBERTI_TRUST_MIXED_ALPHABET'),
            trans('CIPHER_TOOL_TRUST_NO_STORAGE'),
            $calculationMode === 'api' ? trans('CIPHER_TOOL_TRUST_SERVER') : trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }

    /**
     * Проверяет, содержит ли текст хотя бы одну латинскую букву.
     */
    public function hasLatinCharacters(string $text): bool
    {
        return preg_match('/[a-zA-Z]/', $text) === 1;
    }

    /**
     * Генерирует внутреннее кольцо (перемешанный алфавит) из ключевого слова.
     *
     * Алгоритм: буквы ключа (уникальные, в порядке появления) + остаток алфавита.
     * Если ключевое слово пустое — возвращается обычный алфавит A-Z.
     *
     * @return string[] Массив из 26 букв в нижнем регистре.
     */
    public function generateInnerAlphabet(string $keyword): array
    {
        $used   = [];
        $result = [];

        foreach (mb_str_split(mb_strtolower($keyword)) as $char) {
            if (in_array($char, self::OUTER, true) && !in_array($char, $used, true)) {
                $used[]   = $char;
                $result[] = $char;
            }
        }

        foreach (self::OUTER as $char) {
            if (!in_array($char, $used, true)) {
                $result[] = $char;
            }
        }

        return $result;
    }

    /**
     * Возвращает строку внутреннего кольца (26 символов в верхнем регистре).
     */
    public function innerAlphabetString(string $keyword): string
    {
        return strtoupper(implode('', $this->generateInnerAlphabet($keyword)));
    }

    /**
     * Вычисляет числовое смещение (0-25) для заданной буквы индекса.
     */
    public function computeOffset(string $indexLetter): int
    {
        $letter = strtolower(substr($indexLetter, 0, 1));
        $pos    = array_search($letter, self::OUTER, true);

        return $pos === false ? 0 : (int) $pos;
    }

    /**
     * Выполняет шифрование или дешифрование шифром Альберти.
     *
     * @param string $text      Входной текст.
     * @param string $keyword   Ключевое слово для генерации внутреннего кольца.
     * @param string $index     Буква начального выравнивания (A–Z).
     * @param string $direction 'encrypt' или 'decrypt'.
     */
    public function process(string $text, string $keyword, string $index, string $direction): string
    {
        $inner  = $this->generateInnerAlphabet($keyword);
        $offset = $this->computeOffset($index);

        return $direction === 'decrypt'
            ? $this->decrypt($text, $inner, $offset)
            : $this->encrypt($text, $inner, $offset);
    }

    /**
     * Шифрует текст: каждая буква внешнего кольца заменяется буквой внутреннего.
     *
     * @param string[] $inner  Внутреннее кольцо (26 букв, нижний регистр).
     * @param int      $offset Начальное смещение.
     */
    private function encrypt(string $text, array $inner, int $offset): string
    {
        $outerMap = array_flip(self::OUTER);
        $output   = '';

        foreach (mb_str_split($text) as $char) {
            $lower = mb_strtolower($char);
            if (!isset($outerMap[$lower])) {
                $output .= $char;
                continue;
            }
            $outerPos  = (int) $outerMap[$lower];
            $innerPos  = ($outerPos - $offset + 26) % 26;
            $innerChar = $inner[$innerPos];
            $output   .= $char === $lower ? $innerChar : mb_strtoupper($innerChar);
        }

        return $output;
    }

    /**
     * Дешифрует текст: каждая буква внутреннего кольца заменяется буквой внешнего.
     *
     * @param string[] $inner  Внутреннее кольцо (26 букв, нижний регистр).
     * @param int      $offset Начальное смещение.
     */
    private function decrypt(string $text, array $inner, int $offset): string
    {
        $innerMap = array_flip($inner);
        $output   = '';

        foreach (mb_str_split($text) as $char) {
            $lower = mb_strtolower($char);
            if (!isset($innerMap[$lower])) {
                $output .= $char;
                continue;
            }
            $innerPos  = (int) $innerMap[$lower];
            $outerPos  = ($innerPos + $offset) % 26;
            $outerChar = self::OUTER[$outerPos];
            $output   .= $char === $lower ? $outerChar : mb_strtoupper($outerChar);
        }

        return $output;
    }
}
