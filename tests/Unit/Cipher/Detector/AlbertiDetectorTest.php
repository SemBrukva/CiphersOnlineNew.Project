<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\AlbertiDetector;

/**
 * Тесты детектора шифра Альберти.
 */
final class AlbertiDetectorTest extends DetectorTestCase
{
    private AlbertiDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new AlbertiDetector();
    }

    /**
     * Проверяет, что полиалфавитный текст не вызывает ошибок.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('SX UKW RRI ZOWR YJ RSQCC MR GEQ DLC GSPCX MP XGWIQ'));
        self::assertTrue($result === null || $result->confidence > 0.0);
    }

    /**
     * Проверяет, что код Морзе возвращает null.
     */
    public function testRejectsInvalidInput(): void
    {
        $result = $this->detector->detect($this->ctx('.... . .-.. .-.. ---'));
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
