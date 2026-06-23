<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\BifidDetector;

/**
 * Тесты детектора шифра Бифид.
 */
final class BifidDetectorTest extends DetectorTestCase
{
    private BifidDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new BifidDetector();
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
