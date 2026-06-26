<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\AnagramSolver;

use App\Cipher\AlphabetCatalog;
use App\Cipher\AnagramSolver\AnagramEngine;
use App\Cipher\AnagramSolver\AnagramOptions;
use App\Cipher\AnagramSolver\ScrabbleScorer;
use App\Cipher\Dictionary\DictionaryRepository;
use App\Cipher\Dictionary\WordSignature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Smoke-тесты на реальном английском словаре (если он построен).
 * Пропускаются, если индекс ещё не сгенерирован командой `dictionary:build en`.
 */
#[Group('smoke')]
final class AnagramSolverSmokeTest extends TestCase
{
    private ?AnagramEngine $engine = null;

    protected function setUp(): void
    {
        $baseDir = PRIVATE_PATH . '/storage/dictionaries';
        $repo    = new DictionaryRepository($baseDir);
        if (!$repo->hasIndex('en')) {
            self::markTestSkipped('English dictionary index is not built. Run: php bin/console dictionary:build en');
        }
        $signature  = new WordSignature(new AlphabetCatalog());
        $this->engine = new AnagramEngine($repo, $signature, new ScrabbleScorer());
    }

    /**
     * «listen» имеет известные анаграммы: silent, tinsel, enlist.
     */
    public function testFindsClassicListenAnagrams(): void
    {
        $result = $this->engine->findAnagrams('listen', 'en', new AnagramOptions());

        $words = array_map(static fn (array $row): string => $row['word'], $result->results);
        self::assertContains('silent', $words);
        self::assertContains('listen', $words);
    }

    /**
     * Word Finder из букв «cipher» содержит price, rich, hire.
     */
    public function testWordFinderReturnsKnownSubsets(): void
    {
        $options = new AnagramOptions(minLength: 3, maxResults: 200);
        $result  = $this->engine->findSubAnagrams('cipher', 'en', $options);

        $words = array_map(static fn (array $row): string => $row['word'], $result->results);
        self::assertContains('price', $words);
        self::assertContains('rich', $words);
        self::assertContains('hire', $words);
    }

    /**
     * Шаблон `h?llo` должен найти hello.
     */
    public function testPatternFindsHelloMatches(): void
    {
        $result = $this->engine->findByPattern('h?llo', 'en', new AnagramOptions());

        $words = array_map(static fn (array $row): string => $row['word'], $result->results);
        self::assertContains('hello', $words);
    }
}
