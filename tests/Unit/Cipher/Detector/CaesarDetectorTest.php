<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\CaesarCipherService;
use App\Cipher\Detector\CaesarDetector;
use App\Cipher\LetterFrequencyScorer;

/**
 * Тесты детектора шифра Цезаря.
 */
final class CaesarDetectorTest extends DetectorTestCase
{
    private CaesarDetector $detector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new CaesarDetector(
            new LetterFrequencyScorer(),
            new CaesarCipherService(),
        );
    }

    /**
     * Проверяет, что Caesar shift=3 на надёжном тексте даёт явный winner и confidence ≥ 0.70.
     */
    public function testReliableCaesarTextProducesHighConfidence(): void
    {
        // "HELLO WORLD THIS IS A TEST MESSAGE FOR CIPHER DETECTION" с shift=3.
        $result = $this->detector->detect($this->ctx('KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ'));

        self::assertNotNull($result);
        self::assertGreaterThanOrEqual(0.70, $result->confidence);
        self::assertSame('caesar-brute-force', $result->bruteForceAction);
        self::assertContains('CID_EV_CHISQ_BEST_SHIFT', $result->evidenceKeys);
        self::assertSame(3, $result->hints['best_shift']);
    }

    /**
     * Проверяет, что короткий Caesar-текст даёт пониженную уверенность (low_sample).
     */
    public function testShortTextIsLowSample(): void
    {
        // 16 букв — IoC уже устойчив, но меньше MIN_LETTERS_FOR_RELIABLE_SCORING.
        $result = $this->detector->detect($this->ctx('KHOOR ZRUOG WKLV LV'));

        self::assertNotNull($result);
        self::assertArrayHasKey('low_sample', $result->hints);
        self::assertLessThan(0.85, $result->confidence);
    }

    /**
     * Проверяет, что числовая строка возвращает null.
     */
    public function testRejectsInvalidInput(): void
    {
        $result = $this->detector->detect($this->ctx('1 2 3 4 5 6 7 8 9 10'));
        self::assertNull($result);
    }

    /**
     * Проверяет, что пустая строка не вызывает ошибок.
     */
    public function testEmptyStringReturnsNull(): void
    {
        $result = $this->detector->detect($this->ctx(''));
        self::assertNull($result);
    }
}
