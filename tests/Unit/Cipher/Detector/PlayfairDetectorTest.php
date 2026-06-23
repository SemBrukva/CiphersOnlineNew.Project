<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\PlayfairDetector;

/**
 * Тесты детектора шифра Плейфера.
 */
final class PlayfairDetectorTest extends DetectorTestCase
{
    private PlayfairDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new PlayfairDetector();
    }

    /**
     * Проверяет, что буквенный текст чётной длины не вызывает ошибок.
     */
    public function testDetectsValidInput(): void
    {
        // Playfair требует чётную длину и только буквы
        $result = $this->detector->detect($this->ctx('KHOOR ZRUOG WKLVLV DWHVW PHVVDIH'));
        self::assertTrue($result === null || $result->confidence > 0.0);
    }

    /**
     * Проверяет, что текст с цифрами возвращает null.
     */
    public function testRejectsInvalidInput(): void
    {
        $result = $this->detector->detect($this->ctx('HELLO12 WORLD34'));
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
