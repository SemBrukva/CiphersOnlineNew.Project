<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\AnagramSolver;

use App\Cipher\AlphabetCatalog;
use App\Cipher\AnagramSolver\AnagramEngine;
use App\Cipher\AnagramSolver\AnagramOptions;
use App\Cipher\AnagramSolver\ScrabbleScorer;
use App\Cipher\Dictionary\DictionaryRepository;
use App\Cipher\Dictionary\InMemoryDictionaryStore;
use App\Cipher\Dictionary\WordSignature;
use PHPUnit\Framework\TestCase;

/**
 * Тесты движка поиска анаграмм поверх in-memory словаря.
 */
final class AnagramEngineTest extends TestCase
{
    private AnagramEngine $engine;
    private WordSignature $signature;

    protected function setUp(): void
    {
        $catalog         = new AlphabetCatalog();
        $this->signature = new WordSignature($catalog);

        $store = $this->buildStore('en', [
            'listen', 'silent', 'enlist', 'tinsel',
            'least', 'slate', 'steal', 'stale', 'tales',
            'late', 'tale', 'teal',
            'les',
            'ate', 'eat', 'tea',
            'leap', 'pale', 'peal', 'plea',
            'rate', 'tear',
            'ar', 'at', 're', 'er', 'are', 'ear', 'era',
            'aloe',
        ]);

        // Используем тестовый DictionaryRepository, читающий из заранее
        // подменённой ассоциации «язык → in-memory store».
        $repository = $this->createMock(DictionaryRepository::class);
        $repository->method('load')->willReturn($store);
        $repository->method('hasIndex')->willReturn(true);

        $this->engine = new AnagramEngine(
            $repository,
            $this->signature,
            new ScrabbleScorer(),
        );
    }

    /**
     * Собирает {@see InMemoryDictionaryStore} из плоского списка слов,
     * вычисляя сигнатуры через {@see WordSignature}.
     *
     * @param list<string> $words
     */
    private function buildStore(string $language, array $words): InMemoryDictionaryStore
    {
        $bySignature = [];
        foreach ($words as $word) {
            $sig = $this->signature->compute($word, $language);
            if ($sig === '') {
                continue;
            }
            $bySignature[$sig][$word] = true;
        }
        foreach ($bySignature as $sig => $w) {
            $bySignature[$sig] = array_keys($w);
        }

        return new InMemoryDictionaryStore($bySignature);
    }

    /**
     * Режим anagram возвращает все слова с такой же сигнатурой.
     */
    public function testFindAnagramsReturnsExactMatches(): void
    {
        $result = $this->engine->findAnagrams('listen', 'en', new AnagramOptions());

        $words = array_map(static fn (array $row): string => $row['word'], $result->results);
        sort($words);
        self::assertSame(['enlist', 'listen', 'silent', 'tinsel'], $words);
        self::assertSame('anagram', $result->mode);
        self::assertFalse($result->truncated);
    }

    /**
     * Режим word-finder возвращает все подмножественные слова.
     */
    public function testFindSubAnagramsReturnsSubsets(): void
    {
        $options = new AnagramOptions(minLength: 3);
        $result  = $this->engine->findSubAnagrams('listen', 'en', $options);

        $words = array_map(static fn (array $row): string => $row['word'], $result->results);
        self::assertContains('listen', $words);
        self::assertContains('silent', $words);
        self::assertNotContains('rate', $words); // 'r' нет во входе
        self::assertNotContains('aloe', $words); // 'a','o' нет во входе
    }

    /**
     * Pattern-режим уважает фиксированные позиции и `?`-wildcards.
     */
    public function testFindByPatternMatchesWildcards(): void
    {
        $result = $this->engine->findByPattern('?ate', 'en', new AnagramOptions());

        $words = array_map(static fn (array $row): string => $row['word'], $result->results);
        self::assertContains('late', $words);
        self::assertNotContains('teal', $words); // не подходит позиционно
    }

    /**
     * Шаблон, не дающий совпадений, возвращает пустой результат.
     */
    public function testFindByPatternReturnsEmptyForNoMatch(): void
    {
        $result = $this->engine->findByPattern('zzzz', 'en', new AnagramOptions());
        self::assertSame([], $result->results);
        self::assertSame(0, $result->totalFound);
    }

    /**
     * Multi-word находит фразу «at re» для «rate».
     */
    public function testFindMultiWordSplitsLetters(): void
    {
        $options = new AnagramOptions(minLength: 2, maxWords: 2);
        $result  = $this->engine->findMultiWord('rate', 'en', $options);

        self::assertNotEmpty($result->phrases);
        foreach ($result->phrases as $phrase) {
            self::assertSame(4, $phrase['length']);
            self::assertGreaterThanOrEqual(2, count($phrase['words']));
        }
    }

    /**
     * Фильтры minLength/maxLength/startsWith соблюдаются.
     */
    public function testFiltersAreApplied(): void
    {
        $options = new AnagramOptions(minLength: 4, maxLength: 5, startsWith: 'le');
        $result  = $this->engine->findSubAnagrams('listenap', 'en', $options);

        foreach ($result->results as $row) {
            self::assertGreaterThanOrEqual(4, $row['length']);
            self::assertLessThanOrEqual(5, $row['length']);
            self::assertStringStartsWith('le', $row['word']);
        }
    }

    /**
     * maxResults усекает результат и проставляет truncated=true.
     */
    public function testMaxResultsTruncatesAndFlags(): void
    {
        $options = new AnagramOptions(minLength: 3, maxResults: 2);
        $result  = $this->engine->findSubAnagrams('listenap', 'en', $options);

        self::assertCount(2, $result->results);
        self::assertGreaterThan(2, $result->totalFound);
        self::assertTrue($result->truncated);
    }
}
