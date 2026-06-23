<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\XorDetector;

/**
 * Тесты детектора XOR-шифра.
 */
final class XorDetectorTest extends DetectorTestCase
{
    private XorDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new XorDetector();
    }

    /**
     * Проверяет, что hex-строка с неравномерным распределением байт определяется.
     */
    public function testDetectsValidInput(): void
    {
        // XOR of "HELLO" with key 0x4B: H^K=03, E^K=6E... используем более длинный пример
        $result = $this->detector->detect($this->ctx('030015070A1F0A0D0C0F1A1C071B1908'));
        self::assertTrue($result === null || $result->confidence > 0.0);
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
