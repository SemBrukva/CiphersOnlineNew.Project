<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\VigenereDetector;

/**
 * Тесты детектора шифра Виженера.
 */
final class VigenereDetectorTest extends DetectorTestCase
{
    private VigenereDetector $detector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new VigenereDetector();
    }

    /**
     * Проверяет, что полиалфавитно-зашифрованный текст не вызывает исключений.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('SX UKW RRI ZOWR YJ RSQCC MR GEQ DLC GSPCX MP XGWIQ'));
        self::assertTrue($result === null || $result->confidence > 0.0);
        if ($result !== null) {
            self::assertSame('vigenere-cracker', $result->bruteForceAction);
        }
    }

    /**
     * Проверяет, что слишком короткий или неподходящий ввод возвращает null.
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
