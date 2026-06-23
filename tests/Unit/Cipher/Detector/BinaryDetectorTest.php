<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\BinaryDetector;

/**
 * Тесты детектора двоичного представления.
 */
final class BinaryDetectorTest extends DetectorTestCase
{
    private BinaryDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new BinaryDetector();
    }

    /**
     * Проверяет, что бинарная строка определяется с уверенностью > 0.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('01001000 01100101 01101100 01101100 01101111'));
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

    /**
     * Проверяет, что бинарная строка из непечатных байт получает пониженный confidence.
     */
    public function testNonPrintableBytesGetLowConfidence(): void
    {
        $result = $this->detector->detect($this->ctx('00000001 11111110 10000000 01111111'));
        self::assertNotNull($result);
        self::assertLessThan(0.70, $result->confidence);
    }

    /**
     * Проверяет, что 8-битное представление обычного ASCII получает высокий confidence.
     */
    public function testPrintableAsciiGetsHighConfidence(): void
    {
        $result = $this->detector->detect($this->ctx('01001000 01100101 01101100 01101100 01101111'));
        self::assertNotNull($result);
        self::assertGreaterThanOrEqual(0.85, $result->confidence);
    }
}
