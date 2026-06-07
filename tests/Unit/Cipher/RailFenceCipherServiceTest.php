<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\RailFenceCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Rail Fence.
 */
final class RailFenceCipherServiceTest extends TestCase
{
    /**
     * Проверяет классический пример с тремя рельсами.
     */
    public function testEncryptsClassicThreeRailExample(): void
    {
        $service = new RailFenceCipherService();

        self::assertSame(
            'WECRLTEERDSOEEFEAOCAIVDEN',
            $service->process('WEAREDISCOVEREDFLEEATONCE', 3, 'encrypt')
        );
    }

    /**
     * Проверяет расшифровку классического примера с тремя рельсами.
     */
    public function testDecryptsClassicThreeRailExample(): void
    {
        $service = new RailFenceCipherService();

        self::assertSame(
            'WEAREDISCOVEREDFLEEATONCE',
            $service->process('WECRLTEERDSOEEFEAOCAIVDEN', 3, 'decrypt')
        );
    }

    /**
     * Проверяет round-trip с пробелами и пунктуацией.
     */
    public function testRoundTripPreservesSpacesAndPunctuation(): void
    {
        $service = new RailFenceCipherService();
        $plain = 'Attack at dawn!';

        $encrypted = $service->process($plain, 4, 'encrypt');

        self::assertNotSame($plain, $encrypted);
        self::assertSame($plain, $service->process($encrypted, 4, 'decrypt'));
    }

    /**
     * Проверяет round-trip для UTF-8 текста.
     */
    public function testRoundTripSupportsUtf8Text(): void
    {
        $service = new RailFenceCipherService();
        $plain = 'Привет, мир!';

        $encrypted = $service->process($plain, 3, 'encrypt');

        self::assertSame($plain, $service->process($encrypted, 3, 'decrypt'));
    }

    /**
     * Проверяет нормализацию количества рельсов.
     */
    public function testNormalizesRails(): void
    {
        $service = new RailFenceCipherService();

        self::assertSame(RailFenceCipherService::MIN_RAILS, $service->normalizeRails(1));
        self::assertSame(RailFenceCipherService::MAX_RAILS, $service->normalizeRails(1000));
    }

    /**
     * Проверяет, что при рельсах >= длины текста шифрование — no-op.
     */
    public function testEncryptIsNoOpWhenRailsEqualOrExceedTextLength(): void
    {
        $service = new RailFenceCipherService();

        self::assertSame('HI', $service->process('HI', 2, 'encrypt'));
        self::assertSame('HI', $service->process('HI', 5, 'encrypt'));
        self::assertSame('ABC', $service->process('ABC', 3, 'encrypt'));
    }

    /**
     * Проверяет, что при рельсах >= длины текста дешифрование — no-op.
     */
    public function testDecryptIsNoOpWhenRailsEqualOrExceedTextLength(): void
    {
        $service = new RailFenceCipherService();

        self::assertSame('HI', $service->process('HI', 2, 'decrypt'));
        self::assertSame('HI', $service->process('HI', 5, 'decrypt'));
    }
}
