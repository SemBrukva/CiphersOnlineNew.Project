<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\MorseCodeDetector;

/**
 * Тесты детектора кода Морзе.
 */
final class MorseCodeDetectorTest extends DetectorTestCase
{
    private MorseCodeDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new MorseCodeDetector();
    }

    /**
     * Проверяет, что валидный код Морзе определяется с уверенностью > 0.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('.... . .-.. .-.. --- / .-- --- .-. .-.. -...'));
        self::assertNotNull($result);
        self::assertGreaterThan(0.0, $result->confidence);
    }

    /**
     * Проверяет, что неподходящий ввод возвращает null.
     */
    public function testRejectsInvalidInput(): void
    {
        $result = $this->detector->detect($this->ctx('HELLO WORLD'));
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
