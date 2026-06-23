<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\BaconCipherService;
use App\Cipher\Detector\BaconDetector;

/**
 * Тесты детектора шифра Бэкона.
 */
final class BaconDetectorTest extends DetectorTestCase
{
    private BaconDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new BaconDetector(new BaconCipherService());
    }

    /**
     * Проверяет, что Bacon-кодировка определяется с уверенностью > 0.
     */
    public function testDetectsValidInput(): void
    {
        // HELLO в Bacon: H=AABBB E=AABAA L=ABABA L=ABABA O=ABBAB
        $result = $this->detector->detect($this->ctx('AABBB AABAA ABABA ABABA ABBAB'));
        self::assertNotNull($result);
        self::assertGreaterThan(0.0, $result->confidence);
    }

    /**
     * Проверяет, что обычный текст возвращает null.
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
