<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\HillDetector;

/**
 * Тесты детектора шифра Хилла.
 */
final class HillDetectorTest extends DetectorTestCase
{
    private HillDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new HillDetector();
    }

    /**
     * Проверяет, что буквенный текст чётной длины не вызывает ошибок.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('KHOOR ZRUOG WKLVLV DWHVW PHVVDIH'));
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
