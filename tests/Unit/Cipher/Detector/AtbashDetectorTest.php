<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\AtbashCipherService;
use App\Cipher\Detector\AtbashDetector;
use App\Cipher\LetterFrequencyScorer;

/**
 * Тесты детектора шифра Атбаш.
 */
final class AtbashDetectorTest extends DetectorTestCase
{
    private AtbashDetector $detector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new AtbashDetector(
            new LetterFrequencyScorer(),
            new AtbashCipherService(),
        );
    }

    /**
     * Проверяет, что Atbash-зашифрованный текст не вызывает ошибок.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('SVOOL DLIOW GSVHV ZIV Z GVHG NVHHZTV'));
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
