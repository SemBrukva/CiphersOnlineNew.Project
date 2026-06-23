<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\PolybiusSquareDetector;
use App\Cipher\LetterFrequencyScorer;
use App\Cipher\PolybiusSquareCipherService;

/**
 * Тесты детектора квадрата Полибия.
 */
final class PolybiusSquareDetectorTest extends DetectorTestCase
{
    private PolybiusSquareDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new PolybiusSquareDetector(
            new PolybiusSquareCipherService(),
            new LetterFrequencyScorer(),
        );
    }

    /**
     * Проверяет, что пары цифр квадрата Полибия определяются с уверенностью > 0.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('23 15 31 31 34 52 34 42 31 14'));
        self::assertNotNull($result);
        self::assertGreaterThan(0.0, $result->confidence);
    }

    /**
     * Проверяет, что обычный текст возвращает null.
     */
    public function testRejectsInvalidInput(): void
    {
        $result = $this->detector->detect($this->ctx('HELLO WORLD'));
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
