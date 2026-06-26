<?php

declare(strict_types=1);

namespace App\Cipher\AnagramSolver;

use App\Cipher\Dictionary\DictionaryRepository;
use App\Cipher\Dictionary\DictionaryStore;
use App\Cipher\Dictionary\WordSignature;

/**
 * Поиск анаграмм, подмножеств, шаблонов и многословных перестановок по словарю.
 *
 * Содержит четыре независимых режима:
 *   anagram      — слова, использующие все буквы входа;
 *   word-finder  — слова, использующие подмножество букв (Scrabble-стиль);
 *   pattern      — слова, соответствующие шаблону с `?` как wildcard;
 *   multi-word   — фразы из 2–3 слов, использующие все буквы.
 */
final class AnagramEngine
{
    public const string MODE_ANAGRAM     = 'anagram';
    public const string MODE_WORD_FINDER = 'word-finder';
    public const string MODE_PATTERN     = 'pattern';
    public const string MODE_MULTI_WORD  = 'multi-word';

    /** Максимальная длина входа в символах. */
    public const int MAX_INPUT_LENGTH = 32;

    /** Максимальная длина шаблона. */
    public const int MAX_PATTERN_LENGTH = 24;

    /** Максимум кандидатов для multi-word, чтобы избежать комбинаторного взрыва. */
    public const int MULTI_WORD_HARD_LIMIT = 1000;

    /**
     * Создаёт движок поиска.
     */
    public function __construct(
        private readonly DictionaryRepository $dictionaries,
        private readonly WordSignature $signature,
        private readonly ScrabbleScorer $scorer,
    ) {
    }

    /**
     * Находит все слова, являющиеся анаграммой строки $text (используют ровно те же буквы).
     */
    public function findAnagrams(string $text, string $language, AnagramOptions $options): AnagramResult
    {
        $options    = $options->normalized();
        $dictionary = $this->dictionaries->load($language);
        $sig        = $this->signature->compute($text, $language);

        if ($sig === '') {
            return new AnagramResult(self::MODE_ANAGRAM, [], [], 0, false, $language);
        }

        $candidates = $dictionary->wordsForSignature($sig);
        $filtered   = $this->filterAndScore($candidates, $language, $options);

        return $this->buildResult(self::MODE_ANAGRAM, $filtered, $options, $language);
    }

    /**
     * Находит все слова, использующие подмножество букв строки $text.
     */
    public function findSubAnagrams(string $text, string $language, AnagramOptions $options): AnagramResult
    {
        $options    = $options->normalized();
        $dictionary = $this->dictionaries->load($language);
        $sourceSig  = $this->signature->compute($text, $language);

        if ($sourceSig === '') {
            return new AnagramResult(self::MODE_WORD_FINDER, [], [], 0, false, $language);
        }

        $sourceLen   = mb_strlen($sourceSig);
        $maxLen      = $options->maxLength > 0 ? min($options->maxLength, $sourceLen) : $sourceLen;
        $minLen      = max($options->minLength, 1);
        $matched     = [];

        for ($length = $minLen; $length <= $maxLen; $length++) {
            foreach ($dictionary->signaturesOfLength($length) as $sig) {
                if (!$this->signature->isSubsetSignature($sig, $sourceSig)) {
                    continue;
                }
                foreach ($dictionary->wordsForSignature($sig) as $word) {
                    $matched[] = $word;
                }
            }
        }

        $filtered = $this->filterAndScore($matched, $language, $options);

        return $this->buildResult(self::MODE_WORD_FINDER, $filtered, $options, $language);
    }

    /**
     * Находит слова, соответствующие шаблону с `?` как wildcard.
     */
    public function findByPattern(string $pattern, string $language, AnagramOptions $options): AnagramResult
    {
        $options    = $options->normalized();
        $dictionary = $this->dictionaries->load($language);
        $normalized = mb_strtolower($pattern);
        $patternLen = mb_strlen($normalized);
        if ($patternLen === 0 || $patternLen > self::MAX_PATTERN_LENGTH) {
            return new AnagramResult(self::MODE_PATTERN, [], [], 0, false, $language);
        }

        $patternChars = preg_split('//u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $matched      = [];

        foreach ($dictionary->signaturesOfLength($patternLen) as $sig) {
            foreach ($dictionary->wordsForSignature($sig) as $word) {
                if (mb_strlen($word) !== $patternLen) {
                    continue;
                }
                if ($this->matchesPattern($word, $patternChars)) {
                    $matched[] = $word;
                }
            }
        }

        $filtered = $this->filterAndScore($matched, $language, $options);

        return $this->buildResult(self::MODE_PATTERN, $filtered, $options, $language);
    }

    /**
     * Находит фразы из нескольких слов, использующих все буквы строки $text.
     */
    public function findMultiWord(string $text, string $language, AnagramOptions $options): AnagramResult
    {
        $options    = $options->normalized();
        $dictionary = $this->dictionaries->load($language);
        $sourceSig  = $this->signature->compute($text, $language);

        if ($sourceSig === '') {
            return new AnagramResult(self::MODE_MULTI_WORD, [], [], 0, false, $language);
        }

        $phrases = [];
        $this->collectPhrases(
            $sourceSig,
            [],
            $dictionary,
            $options,
            $phrases,
            self::MULTI_WORD_HARD_LIMIT,
            $language,
        );

        $unique  = $this->dedupePhrases($phrases);
        $shaped  = $this->shapePhrases($unique, $language, $options);
        $total   = count($shaped);
        $sliced  = array_slice($shaped, 0, $options->maxResults);

        return new AnagramResult(
            mode: self::MODE_MULTI_WORD,
            results: [],
            phrases: $sliced,
            totalFound: $total,
            truncated: $total > $options->maxResults,
            language: $language,
        );
    }

    /**
     * Возвращает true, если слово соответствует шаблону, где `?` — любая буква.
     *
     * @param list<string> $patternChars Разбитый на UTF-8 символы шаблон.
     */
    private function matchesPattern(string $word, array $patternChars): bool
    {
        $wordChars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($wordChars) !== count($patternChars)) {
            return false;
        }
        foreach ($patternChars as $idx => $expected) {
            if ($expected === '?') {
                continue;
            }
            if ($wordChars[$idx] !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * Применяет фильтры и считает Scrabble-балл, возвращая список слов с метаданными.
     *
     * @param list<string>                                       $words
     * @return list<array{word: string, length: int, score: int}>
     */
    private function filterAndScore(array $words, string $language, AnagramOptions $options): array
    {
        $unique = [];
        $result = [];
        foreach ($words as $word) {
            if (isset($unique[$word])) {
                continue;
            }
            $unique[$word] = true;
            if (!$options->matches($word)) {
                continue;
            }
            $result[] = [
                'word'   => $word,
                'length' => mb_strlen($word),
                'score'  => $this->scorer->score($word, $language),
            ];
        }

        return $result;
    }

    /**
     * Сортирует, ограничивает количество результатов и возвращает финальный объект.
     *
     * @param list<array{word: string, length: int, score: int}> $items
     */
    private function buildResult(string $mode, array $items, AnagramOptions $options, string $language): AnagramResult
    {
        usort($items, $this->comparatorFor($options->sort));
        $total      = count($items);
        $sliced     = array_slice($items, 0, $options->maxResults);

        return new AnagramResult(
            mode: $mode,
            results: $sliced,
            phrases: [],
            totalFound: $total,
            truncated: $total > $options->maxResults,
            language: $language,
        );
    }

    /**
     * Возвращает компаратор для сортировки слов по выбранной стратегии.
     */
    private function comparatorFor(string $sort): callable
    {
        return match ($sort) {
            AnagramOptions::SORT_ALPHA => static fn (array $a, array $b): int => strcmp($a['word'], $b['word']),
            AnagramOptions::SORT_SCORE => static function (array $a, array $b): int {
                return $b['score'] <=> $a['score'] ?: strcmp($a['word'], $b['word']);
            },
            default => static function (array $a, array $b): int {
                return $b['length'] <=> $a['length'] ?: strcmp($a['word'], $b['word']);
            },
        };
    }

    /**
     * Рекурсивно собирает фразы, разбивая оставшуюся сигнатуру на слова словаря.
     *
     * @param list<string>                                       $current  Текущий набор слов фразы.
     * @param list<list<string>>                                 $phrases  Аккумулятор результата.
     */
    private function collectPhrases(
        string $remainingSig,
        array $current,
        DictionaryStore $dictionary,
        AnagramOptions $options,
        array &$phrases,
        int $hardLimit,
        string $language,
    ): void {
        if (count($phrases) >= $hardLimit) {
            return;
        }

        if ($remainingSig === '') {
            if (count($current) >= 2) {
                $phrases[] = $current;
            }
            return;
        }

        if (count($current) >= $options->maxWords) {
            return;
        }

        $remainingLen = mb_strlen($remainingSig);
        $maxWordLen   = $options->maxLength > 0 ? min($options->maxLength, $remainingLen) : $remainingLen;
        $minWordLen   = max($options->minLength, 1);

        $isLastWord = count($current) + 1 === $options->maxWords;

        for ($length = $minWordLen; $length <= $maxWordLen; $length++) {
            if ($isLastWord && $length !== $remainingLen) {
                continue;
            }

            foreach ($dictionary->signaturesOfLength($length) as $sig) {
                if (count($phrases) >= $hardLimit) {
                    return;
                }
                if (!$this->signature->isSubsetSignature($sig, $remainingSig)) {
                    continue;
                }
                $rest = $this->signature->subtractSignature($remainingSig, $sig);
                if ($rest === null) {
                    continue;
                }
                foreach ($dictionary->wordsForSignature($sig) as $word) {
                    if (!$options->matches($word)) {
                        continue;
                    }
                    $next   = $current;
                    $next[] = $word;
                    $this->collectPhrases($rest, $next, $dictionary, $options, $phrases, $hardLimit, $language);
                    if (count($phrases) >= $hardLimit) {
                        return;
                    }
                }
            }
        }
    }

    /**
     * Удаляет фразы-дубликаты, состоящие из одних и тех же слов в разном порядке.
     *
     * @param list<list<string>> $phrases
     * @return list<list<string>>
     */
    private function dedupePhrases(array $phrases): array
    {
        $seen   = [];
        $result = [];
        foreach ($phrases as $phrase) {
            $sorted = $phrase;
            sort($sorted, SORT_STRING);
            $key = implode('|', $sorted);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $result[]   = $phrase;
        }

        return $result;
    }

    /**
     * Преобразует фразы в массивы с длиной и баллами, сортирует.
     *
     * @param list<list<string>> $phrases
     * @return list<array{words: list<string>, length: int, score: int}>
     */
    private function shapePhrases(array $phrases, string $language, AnagramOptions $options): array
    {
        $shaped = [];
        foreach ($phrases as $phrase) {
            $length = 0;
            $score  = 0;
            foreach ($phrase as $word) {
                $length += mb_strlen($word);
                $score  += $this->scorer->score($word, $language);
            }
            $shaped[] = [
                'words'  => $phrase,
                'length' => $length,
                'score'  => $score,
            ];
        }

        usort($shaped, static function (array $a, array $b) use ($options): int {
            if ($options->sort === AnagramOptions::SORT_SCORE) {
                return $b['score'] <=> $a['score'] ?: count($a['words']) <=> count($b['words']);
            }
            return count($a['words']) <=> count($b['words'])
                ?: strcmp(implode(' ', $a['words']), implode(' ', $b['words']));
        });

        return $shaped;
    }
}
