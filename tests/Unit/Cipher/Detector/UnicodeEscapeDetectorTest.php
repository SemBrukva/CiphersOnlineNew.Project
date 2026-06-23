<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\UnicodeEscapeDetector;

/**
 * Тесты детектора Unicode-экранирования.
 */
final class UnicodeEscapeDetectorTest extends DetectorTestCase
{
    private UnicodeEscapeDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new UnicodeEscapeDetector();
    }

    /**
     * Проверяет, что строка с \uXXXX-последовательностями определяется с уверенностью > 0.
     */
    public function testDetectsValidInput(): void
    {
        // Литеральная строка Hello (= Hello в Unicode-escape)
        $result = $this->detector->detect($this->ctx('\\u0048\\u0065\\u006C\\u006C\\u006F'));
        self::assertNotNull($result);
        self::assertGreaterThan(0.0, $result->confidence);
    }

    /**
     * Проверяет, что обычный текст без escape-последовательностей возвращает null.
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
