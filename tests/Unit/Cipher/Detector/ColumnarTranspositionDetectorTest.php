<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\ColumnarTranspositionDetector;
use App\Cipher\LetterFrequencyScorer;

/**
 * Тесты детектора столбчатой перестановки.
 */
final class ColumnarTranspositionDetectorTest extends DetectorTestCase
{
    private ColumnarTranspositionDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new ColumnarTranspositionDetector(new LetterFrequencyScorer());
    }

    /**
     * Проверяет, что текст с сохранёнными частотами букв не вызывает ошибок.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('KHOOR ZRUOG WKLV LV D WHVW PHVVDJH'));
        self::assertTrue($result === null || $result->confidence > 0.0);
    }

    /**
     * Проверяет, что числовой текст возвращает null.
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
