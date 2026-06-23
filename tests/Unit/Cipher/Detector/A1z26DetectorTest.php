<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\A1z26Detector;

/**
 * Тесты детектора A1Z26-кодировки.
 */
final class A1z26DetectorTest extends DetectorTestCase
{
    private A1z26Detector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new A1z26Detector();
    }

    /**
     * Проверяет, что A1Z26-последовательность определяется с уверенностью > 0.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('8-5-12-12-15'));
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
