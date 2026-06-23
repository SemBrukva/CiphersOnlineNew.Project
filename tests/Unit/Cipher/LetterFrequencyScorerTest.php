<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\LetterFrequencyScorer;
use PHPUnit\Framework\TestCase;

/**
 * Тесты скорера частот букв.
 */
final class LetterFrequencyScorerTest extends TestCase
{
    private LetterFrequencyScorer $scorer;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->scorer = new LetterFrequencyScorer();
    }

    /**
     * Проверяет, что текст с явной кириллицей определяется как 'ru'.
     */
    public function testDetectsRussianByCyrillicMajority(): void
    {
        self::assertSame('ru', $this->scorer->detectAlphabet('Привет мир, это длинный русский текст'));
    }

    /**
     * Проверяет, что испанский ñ корректно даёт 'es' даже на коротком тексте,
     * где базовый «max-counter» алгоритм выдавал бы 'en' по дефолту.
     */
    public function testDetectsSpanishByExclusiveCharacter(): void
    {
        self::assertSame('es', $this->scorer->detectAlphabet('mañana'));
    }

    /**
     * Проверяет, что немецкие умляуты и ß дают 'de'.
     */
    public function testDetectsGermanByUmlauts(): void
    {
        self::assertSame('de', $this->scorer->detectAlphabet('die Straße über den Fluß'));
    }

    /**
     * Проверяет, что турецкая `ı` (без точки) даёт 'tr'.
     */
    public function testDetectsTurkishByDotlessI(): void
    {
        self::assertSame('tr', $this->scorer->detectAlphabet('kıyı şehir'));
    }

    /**
     * Проверяет, что базовый латинский текст без акцентов возвращает 'en' как дефолт.
     */
    public function testDefaultsToEnglishWhenNoExclusiveMarks(): void
    {
        self::assertSame('en', $this->scorer->detectAlphabet('hello world'));
    }

    /**
     * Проверяет, что одно случайное русское слово в латинском тексте не «угоняет» алфавит на ru.
     */
    public function testCyrillicMinorityDoesNotHijackAlphabet(): void
    {
        self::assertSame('en', $this->scorer->detectAlphabet('the quick brown fox мир'));
    }
}
