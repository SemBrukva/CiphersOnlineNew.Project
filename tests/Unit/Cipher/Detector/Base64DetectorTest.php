<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\Base64Detector;

/**
 * Тесты детектора Base64-кодировки.
 */
final class Base64DetectorTest extends DetectorTestCase
{
    private Base64Detector $detector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new Base64Detector();
    }

    /**
     * Проверяет, что валидный Base64 определяется с уверенностью > 0.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('SGVsbG8gV29ybGQh'));
        self::assertNotNull($result);
        self::assertGreaterThan(0.0, $result->confidence);
    }

    /**
     * Проверяет, что неподходящий ввод возвращает null.
     */
    public function testRejectsInvalidInput(): void
    {
        $result = $this->detector->detect($this->ctx('HELLO WORLD!'));
        self::assertNull($result);
    }

    /**
     * Проверяет, что пустая строка не вызывает ошибок и возвращает null.
     */
    public function testEmptyStringReturnsNull(): void
    {
        $result = $this->detector->detect($this->ctx(''));
        self::assertNull($result);
    }
}
