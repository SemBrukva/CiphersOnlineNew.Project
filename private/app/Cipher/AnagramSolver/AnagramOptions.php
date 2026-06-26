<?php

declare(strict_types=1);

namespace App\Cipher\AnagramSolver;

/**
 * Параметры поиска анаграмм: фильтры, лимиты, сортировка.
 */
final readonly class AnagramOptions
{
    public const string SORT_LENGTH    = 'length';
    public const string SORT_ALPHA     = 'alpha';
    public const string SORT_SCORE     = 'score';

    /**
     * Создаёт набор опций.
     *
     * @param int    $minLength       Минимальная длина слова в результате.
     * @param int    $maxLength       Максимальная длина слова в результате (0 — без ограничения).
     * @param string $startsWith      Префикс слов (нижний регистр, UTF-8).
     * @param string $endsWith        Суффикс слов (нижний регистр, UTF-8).
     * @param string $contains        Подстрока, которая должна присутствовать в слове.
     * @param int    $maxResults      Максимальное количество результатов (0 — без лимита).
     * @param int    $maxWords        Для multi-word: максимальное число слов в фразе (2 или 3).
     * @param string $sort            Стратегия сортировки результатов.
     */
    public function __construct(
        public int $minLength = 2,
        public int $maxLength = 0,
        public string $startsWith = '',
        public string $endsWith = '',
        public string $contains = '',
        public int $maxResults = 200,
        public int $maxWords = 2,
        public string $sort = self::SORT_LENGTH,
    ) {
    }

    /**
     * Возвращает копию опций с гарантированно валидными значениями.
     */
    public function normalized(): self
    {
        $minLength  = max(1, $this->minLength);
        $maxLength  = $this->maxLength > 0 ? max($minLength, $this->maxLength) : 0;
        $maxResults = max(1, $this->maxResults > 0 ? $this->maxResults : 200);
        $maxWords   = min(3, max(1, $this->maxWords));
        $sort       = in_array($this->sort, [self::SORT_LENGTH, self::SORT_ALPHA, self::SORT_SCORE], true)
            ? $this->sort
            : self::SORT_LENGTH;

        return new self(
            minLength: $minLength,
            maxLength: $maxLength,
            startsWith: mb_strtolower($this->startsWith),
            endsWith: mb_strtolower($this->endsWith),
            contains: mb_strtolower($this->contains),
            maxResults: $maxResults,
            maxWords: $maxWords,
            sort: $sort,
        );
    }

    /**
     * Проверяет, удовлетворяет ли слово фильтрам по длине, префиксу, суффиксу, подстроке.
     */
    public function matches(string $word): bool
    {
        $length = mb_strlen($word);
        if ($length < $this->minLength) {
            return false;
        }
        if ($this->maxLength > 0 && $length > $this->maxLength) {
            return false;
        }
        if ($this->startsWith !== '' && !str_starts_with($word, $this->startsWith)) {
            return false;
        }
        if ($this->endsWith !== '' && !str_ends_with($word, $this->endsWith)) {
            return false;
        }
        if ($this->contains !== '' && !str_contains($word, $this->contains)) {
            return false;
        }

        return true;
    }
}
