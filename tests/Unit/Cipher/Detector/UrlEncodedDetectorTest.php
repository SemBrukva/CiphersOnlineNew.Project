<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\UrlEncodedDetector;

/**
 * Тесты детектора URL-кодировки.
 */
final class UrlEncodedDetectorTest extends DetectorTestCase
{
    private UrlEncodedDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new UrlEncodedDetector();
    }

    /**
     * Проверяет, что URL-закодированная строка определяется с уверенностью > 0.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('Hello%20World%21%20How%20are%20you'));
        self::assertNotNull($result);
        self::assertGreaterThan(0.0, $result->confidence);
    }

    /**
     * Проверяет, что обычный текст без % возвращает null.
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
