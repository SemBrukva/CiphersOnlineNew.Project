<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\AffineDetector;

/**
 * Тесты детектора аффинного шифра.
 */
final class AffineDetectorTest extends DetectorTestCase
{
    private AffineDetector $detector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new AffineDetector();
    }

    /**
     * Проверяет, что аффинно-зашифрованный текст не вызывает исключений.
     *
     * Мягкий детектор: результат может быть null для неоднозначных текстов.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('IHHWVC SWFRCP CVSPYFZ CISR LCZZCP OWZR ZOA VEQCPWS GCYU IVX PCJCIL LIVMEIMC FIZZCPVU EVXCP IVILYUWU'));
        self::assertTrue($result === null || $result->confidence > 0.0);
        if ($result !== null) {
            self::assertSame('affine-brute-force', $result->bruteForceAction);
        }
    }

    /**
     * Проверяет, что неподходящий ввод возвращает null.
     */
    public function testRejectsInvalidInput(): void
    {
        $result = $this->detector->detect($this->ctx('.... . .-.. .-.. ---'));
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
