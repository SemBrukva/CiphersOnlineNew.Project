<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\ScytaleCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Scytale.
 */
final class ScytaleCipherServiceTest extends TestCase
{
    /**
     * Проверяет классический пример с диаметром 4.
     */
    public function testEncryptsClassicFourColumnExample(): void
    {
        $service = new ScytaleCipherService();

        self::assertSame(
            'WECRLTEEDOEEOAIVDENRSEFAC',
            $service->process('WEAREDISCOVEREDFLEEATONCE', 4, 'encrypt')
        );
    }

    /**
     * Проверяет расшифровку классического примера с диаметром 4.
     */
    public function testDecryptsClassicFourColumnExample(): void
    {
        $service = new ScytaleCipherService();

        self::assertSame(
            'WEAREDISCOVEREDFLEEATONCE',
            $service->process('WECRLTEEDOEEOAIVDENRSEFAC', 4, 'decrypt')
        );
    }

    /**
     * Проверяет round-trip с неполной последней строкой (длина не кратна диаметру).
     */
    public function testRoundTripWithPartialLastRow(): void
    {
        $service = new ScytaleCipherService();
        $plain = 'ATTACK AT DAWN';

        $encrypted = $service->process($plain, 5, 'encrypt');

        self::assertNotSame($plain, $encrypted);
        self::assertSame($plain, $service->process($encrypted, 5, 'decrypt'));
    }

    /**
     * Проверяет round-trip с пробелами и пунктуацией.
     */
    public function testRoundTripPreservesSpacesAndPunctuation(): void
    {
        $service = new ScytaleCipherService();
        $plain = 'Attack at dawn!';

        $encrypted = $service->process($plain, 4, 'encrypt');

        self::assertNotSame($plain, $encrypted);
        self::assertSame($plain, $service->process($encrypted, 4, 'decrypt'));
    }

    /**
     * Проверяет round-trip для UTF-8 текста (не латинский алфавит).
     */
    public function testRoundTripSupportsUtf8Text(): void
    {
        $service = new ScytaleCipherService();
        $plain = 'Привет, мир!';

        $encrypted = $service->process($plain, 3, 'encrypt');

        self::assertSame($plain, $service->process($encrypted, 3, 'decrypt'));
    }

    /**
     * Проверяет нормализацию количества столбцов.
     */
    public function testNormalizesColumns(): void
    {
        $service = new ScytaleCipherService();

        self::assertSame(ScytaleCipherService::MIN_COLUMNS, $service->normalizeColumns(1));
        self::assertSame(ScytaleCipherService::MAX_COLUMNS, $service->normalizeColumns(1000));
    }

    /**
     * Проверяет, что при диаметре >= длины текста шифрование — no-op.
     */
    public function testEncryptIsNoOpWhenColumnsEqualOrExceedTextLength(): void
    {
        $service = new ScytaleCipherService();

        self::assertSame('HI', $service->process('HI', 2, 'encrypt'));
        self::assertSame('HI', $service->process('HI', 5, 'encrypt'));
        self::assertSame('ABC', $service->process('ABC', 3, 'encrypt'));
    }

    /**
     * Проверяет, что при диаметре >= длины текста дешифрование — no-op.
     */
    public function testDecryptIsNoOpWhenColumnsEqualOrExceedTextLength(): void
    {
        $service = new ScytaleCipherService();

        self::assertSame('HI', $service->process('HI', 2, 'decrypt'));
        self::assertSame('HI', $service->process('HI', 5, 'decrypt'));
    }
}
