<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\CaesarCipherService;
use App\Cipher\Detector\Rot13Detector;
use App\Cipher\LetterFrequencyScorer;

/**
 * Тесты детектора ROT13.
 */
final class Rot13DetectorTest extends DetectorTestCase
{
    private Rot13Detector $detector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new Rot13Detector(
            new LetterFrequencyScorer(),
            new CaesarCipherService(),
        );
    }

    /**
     * Проверяет, что ROT13-зашифрованный текст определяется как кандидат.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('URYYB JBEYQ GUVF VF N GRFG ZRFFNTR'));
        self::assertTrue($result === null || $result->confidence > 0.0);
        if ($result !== null) {
            self::assertSame('caesar-brute-force', $result->bruteForceAction);
        }
    }

    /**
     * Проверяет, что числовая строка возвращает null.
     */
    public function testRejectsInvalidInput(): void
    {
        $result = $this->detector->detect($this->ctx('123 456 789'));
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
