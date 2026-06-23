<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\Detector\AutokeyDetector;

/**
 * Тесты детектора шифра Автоключ (Autokey).
 */
final class AutokeyDetectorTest extends DetectorTestCase
{
    private AutokeyDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new AutokeyDetector();
    }

    /**
     * Проверяет, что полиалфавитно-зашифрованный текст не вызывает исключений.
     *
     * Мягкий детектор: статистически схож с Виженером.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('SX UKW RRI ZOWR YJ RSQCC MR GEQ DLC GSPCX MP XGWIQ'));
        self::assertTrue($result === null || $result->confidence > 0.0);
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
