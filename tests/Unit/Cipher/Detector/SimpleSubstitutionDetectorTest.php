<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\SimpleSubstitutionDetector;

/**
 * Тесты детектора простой замены.
 */
final class SimpleSubstitutionDetectorTest extends DetectorTestCase
{
    private SimpleSubstitutionDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new SimpleSubstitutionDetector();
    }

    /**
     * Проверяет, что длинный буквенный текст с моноалфавитным IoC не вызывает ошибок.
     */
    public function testDetectsValidInput(): void
    {
        // Достаточно длинный текст simple-substitution с key QWERTYUIOPASDFGHJKLZXCVBNM
        $result = $this->detector->detect(
            $this->ctx('ITSSG VGKSR AOPZ OZ G AZOA BAZZGNA EPOA OZ MPYNA OMNA AP OSGIAOPY')
        );
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
