<?php

declare(strict_types=1);

namespace App\Cipher\Dictionary;

use App\Cipher\AlphabetCatalog;

/**
 * Вычисляет канонический «отпечаток» слова для поиска анаграмм.
 *
 * Сигнатура — это отсортированный по UTF-8 байтовому представлению набор
 * букв слова, нормализованных к нижнему регистру. Слова, являющиеся
 * анаграммами друг друга, имеют одинаковую сигнатуру.
 */
final class WordSignature
{
    /**
     * Создаёт нормализатор сигнатур.
     */
    public function __construct(private readonly AlphabetCatalog $alphabets)
    {
    }

    /**
     * Преобразует слово в массив отдельных букв (UTF-8), нижний регистр.
     * Все символы, не принадлежащие алфавиту, отбрасываются.
     *
     * @return string[]
     */
    public function letters(string $word, string $language): array
    {
        $allowed   = array_flip($this->alphabets->alphabet($language));
        $lowercase = mb_strtolower($word);
        $chars     = preg_split('//u', $lowercase, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $result    = [];
        foreach ($chars as $char) {
            if (isset($allowed[$char])) {
                $result[] = $char;
            }
        }

        return $result;
    }

    /**
     * Возвращает сигнатуру слова для языка.
     *
     * Сигнатура — это конкатенация отсортированных букв слова.
     * Слова с одинаковой сигнатурой являются взаимными анаграммами.
     */
    public function compute(string $word, string $language): string
    {
        $letters = $this->letters($word, $language);
        sort($letters, SORT_STRING);

        return implode('', $letters);
    }

    /**
     * Возвращает мультимножество букв в виде ассоциативного массива «буква → количество».
     *
     * @return array<string, int>
     */
    public function multiset(string $word, string $language): array
    {
        $result = [];
        foreach ($this->letters($word, $language) as $letter) {
            $result[$letter] = ($result[$letter] ?? 0) + 1;
        }

        return $result;
    }

    /**
     * Проверяет, является ли сигнатура $candidate подмножеством сигнатуры $source.
     * Обе строки должны быть валидными сигнатурами (отсортированные UTF-8 буквы).
     */
    public function isSubsetSignature(string $candidate, string $source): bool
    {
        if ($candidate === '') {
            return true;
        }

        $candidateLen = mb_strlen($candidate);
        $sourceLen    = mb_strlen($source);
        if ($candidateLen > $sourceLen) {
            return false;
        }

        $i = 0;
        $j = 0;
        while ($i < $candidateLen && $j < $sourceLen) {
            $candidateChar = mb_substr($candidate, $i, 1);
            $sourceChar    = mb_substr($source, $j, 1);
            $cmp           = strcmp($candidateChar, $sourceChar);
            if ($cmp === 0) {
                $i++;
                $j++;
            } elseif ($cmp > 0) {
                $j++;
            } else {
                return false;
            }
        }

        return $i === $candidateLen;
    }

    /**
     * Вычитает буквы сигнатуры $taken из сигнатуры $source и возвращает остаток.
     * Если $taken не является подмножеством $source — возвращает null.
     */
    public function subtractSignature(string $source, string $taken): ?string
    {
        if ($taken === '') {
            return $source;
        }

        $sourceChars = preg_split('//u', $source, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $takenChars  = preg_split('//u', $taken, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $remaining = $sourceChars;
        foreach ($takenChars as $char) {
            $index = array_search($char, $remaining, true);
            if ($index === false) {
                return null;
            }
            unset($remaining[$index]);
        }

        return implode('', $remaining);
    }
}
