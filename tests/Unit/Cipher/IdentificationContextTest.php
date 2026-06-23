<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;
use App\Cipher\LetterFrequencyScorer;
use PHPUnit\Framework\TestCase;

/**
 * Тесты контекста идентификации шифра.
 */
final class IdentificationContextTest extends TestCase
{
    private LetterFrequencyScorer $scorer;

    private IndexOfCoincidence $ioc;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->scorer = new LetterFrequencyScorer();
        $this->ioc    = new IndexOfCoincidence();
    }

    /**
     * Проверяет, что cleanedText/cleanedLength считают текст без whitespace.
     */
    public function testCleanedTextStripsWhitespace(): void
    {
        $ctx = new IdentificationContext("HELLO\tWORLD  \n", null, $this->scorer, $this->ioc);
        self::assertSame('HELLOWORLD', $ctx->cleanedText());
        self::assertSame(10, $ctx->cleanedLength());
    }

    /**
     * Проверяет, что effectiveAlphabet возвращает явно заданный, если он есть.
     */
    public function testEffectiveAlphabetUsesExplicit(): void
    {
        $ctx = new IdentificationContext('hello', 'en', $this->scorer, $this->ioc);
        self::assertSame('en', $ctx->effectiveAlphabet());
    }

    /**
     * Проверяет, что effectiveAlphabet падает на автоопределение при null.
     */
    public function testEffectiveAlphabetFallsBackToDetected(): void
    {
        $ctx = new IdentificationContext('hello world', null, $this->scorer, $this->ioc);
        self::assertSame('en', $ctx->effectiveAlphabet());
    }

    /**
     * Проверяет, что letterCount и iocFor кэшируются (повторный вызов не считает заново).
     */
    public function testRepeatedCallsAreCached(): void
    {
        $ctx = new IdentificationContext('THE QUICK BROWN FOX', null, $this->scorer, $this->ioc);

        $first  = $ctx->letterCount('en');
        $second = $ctx->letterCount('en');
        self::assertSame($first, $second);

        $iocFirst  = $ctx->iocFor('en');
        $iocSecond = $ctx->iocFor('en');
        self::assertSame($iocFirst, $iocSecond);
    }

    /**
     * Проверяет, что letterRatio считается относительно cleanedLength.
     */
    public function testLetterRatio(): void
    {
        $ctx = new IdentificationContext('ABC123', null, $this->scorer, $this->ioc);
        // 3 буквы из 6 символов без пробелов.
        self::assertEqualsWithDelta(0.5, $ctx->letterRatio('en'), 0.0001);
    }

    /**
     * Проверяет, что hasReliableSample возвращает false при коротком тексте.
     */
    public function testHasReliableSampleFalseOnShortText(): void
    {
        $ctx = new IdentificationContext('HELLO', null, $this->scorer, $this->ioc);
        self::assertFalse($ctx->hasReliableSample('en'));
    }

    /**
     * Проверяет, что hasReliableSample возвращает true при длинном тексте.
     */
    public function testHasReliableSampleTrueOnLongText(): void
    {
        $ctx = new IdentificationContext(
            'THE QUICK BROWN FOX JUMPS OVER THE LAZY DOG AGAIN',
            null,
            $this->scorer,
            $this->ioc,
        );
        self::assertTrue($ctx->hasReliableSample('en'));
    }
}
