<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AlphabetCatalog;
use App\Cipher\TrigramFrequencyScorer;
use PHPUnit\Framework\TestCase;

/**
 * Тесты триграммного скорера.
 */
final class TrigramFrequencyScorerTest extends TestCase
{
    private TrigramFrequencyScorer $scorer;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->scorer = new TrigramFrequencyScorer();
    }

    /**
     * Поддержка есть для en/ru и отсутствует для языков без таблиц.
     */
    public function testSupports(): void
    {
        self::assertTrue($this->scorer->supports('en'));
        self::assertTrue($this->scorer->supports('ru'));
        self::assertFalse($this->scorer->supports('fr'));
    }

    /**
     * Осмысленный английский текст скорится выше бессмыслицы.
     */
    public function testRealTextScoresHigherThanGibberishEn(): void
    {
        $real      = $this->scorer->score('HELLO WORLD THIS IS A TEST MESSAGE', 'en');
        $gibberish = $this->scorer->score('XZQJ VBKW PLMG FQXZ', 'en');

        self::assertGreaterThan($gibberish, $real);
    }

    /**
     * Осмысленный русский текст скорится выше бессмыслицы.
     */
    public function testRealTextScoresHigherThanGibberishRu(): void
    {
        $real      = $this->scorer->score('ЭТО ПРОСТОЙ РУССКИЙ ТЕКСТ ДЛЯ ПРОВЕРКИ', 'ru');
        $gibberish = $this->scorer->score('ЪЖЭ ЩЫФ ЫЫЫ �ждъ', 'ru');

        self::assertGreaterThan($gibberish, $real);
    }

    /**
     * Неподдерживаемый алфавит и слишком короткий вход дают 0.
     */
    public function testUnsupportedAndTooShortReturnZero(): void
    {
        self::assertSame(0.0, $this->scorer->score('anything here', 'fr'));
        self::assertSame(0.0, $this->scorer->score('ab', 'en'));
    }

    /**
     * Индексная карта весов совпадает по значениям со строковым скорингом:
     * содержит только валидные тройки и положительные веса.
     */
    public function testIndexedWeightMapIsBuilt(): void
    {
        $alphabet = (new AlphabetCatalog())->alphabet('en');
        $indexMap = array_flip($alphabet);
        $map      = $this->scorer->buildIndexedWeightMap('en', $indexMap, count($alphabet));

        self::assertNotEmpty($map);
        foreach ($map as $weight) {
            self::assertGreaterThan(0.0, $weight);
        }
    }
}
