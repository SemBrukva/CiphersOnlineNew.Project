<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\HexDetector;

/**
 * Тесты детектора HEX-кодировки.
 */
final class HexDetectorTest extends DetectorTestCase
{
    private HexDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new HexDetector();
    }

    /**
     * Проверяет, что hex-строка определяется с уверенностью > 0.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('48656c6c6f20576f726c64'));
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
     * Проверяет, что hex-строка с непечатными байтами получает пониженный confidence:
     * это даёт XorDetector шанс выйти в лидеры на бинарных данных.
     */
    public function testRandomBytesGetLowConfidence(): void
    {
        // 32 случайных байта (включая управляющие, как у бинарных данных)
        $result = $this->detector->detect($this->ctx('00010203040506070809ff fefdfcfbfa00010203'));
        self::assertNotNull($result);
        self::assertLessThan(0.70, $result->confidence);
    }

    /**
     * Проверяет, что hex-кодированный обычный ASCII получает высокий confidence.
     */
    public function testPrintableHexGetsHighConfidence(): void
    {
        $result = $this->detector->detect($this->ctx('48656c6c6f20576f726c64'));
        self::assertNotNull($result);
        self::assertGreaterThanOrEqual(0.85, $result->confidence);
    }
}
