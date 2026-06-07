<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\ColumnarTranspositionCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра столбцовой перестановки.
 */
final class ColumnarTranspositionCipherServiceTest extends TestCase
{
    /**
     * Проверяет шифрование классической столбцовой перестановкой.
     */
    public function testEncryptsTextByColumnOrder(): void
    {
        $service = new ColumnarTranspositionCipherService();

        self::assertSame(
            'ACDESEEVROWIRDE',
            $service->process('WEAREDISCOVERED', 'SECRET', 'encrypt')
        );
    }

    /**
     * Проверяет расшифровку классической столбцовой перестановки.
     */
    public function testDecryptsTextByColumnOrder(): void
    {
        $service = new ColumnarTranspositionCipherService();

        self::assertSame(
            'WEAREDISCOVERED',
            $service->process('ACDESEEVROWIRDE', 'SECRET', 'decrypt')
        );
    }

    /**
     * Проверяет round-trip с пробелами и пунктуацией.
     */
    public function testRoundTripPreservesSpacesAndPunctuation(): void
    {
        $service = new ColumnarTranspositionCipherService();
        $plain = 'Attack at dawn!';

        $encrypted = $service->process($plain, 'ZEBRA', 'encrypt');

        self::assertNotSame($plain, $encrypted);
        self::assertSame($plain, $service->process($encrypted, 'ZEBRA', 'decrypt'));
    }

    /**
     * Проверяет round-trip для UTF-8 текста.
     */
    public function testRoundTripSupportsUtf8Text(): void
    {
        $service = new ColumnarTranspositionCipherService();
        $plain = 'Привет, мир!';

        $encrypted = $service->process($plain, 'КЛЮЧ', 'encrypt');

        self::assertSame($plain, $service->process($encrypted, 'КЛЮЧ', 'decrypt'));
    }

    /**
     * Проверяет стабильный порядок столбцов при повторяющихся символах ключа.
     */
    public function testRoundTripSupportsRepeatedKeyCharacters(): void
    {
        $service = new ColumnarTranspositionCipherService();
        $plain = 'BALLOON MESSAGE';

        $encrypted = $service->process($plain, 'BALLOON', 'encrypt');

        self::assertSame($plain, $service->process($encrypted, 'BALLOON', 'decrypt'));
    }

    /**
     * Проверяет нормализацию ключа.
     */
    public function testNormalizesKeyByTrimmingWhitespace(): void
    {
        $service = new ColumnarTranspositionCipherService();

        self::assertSame('SECRET', $service->normalizeKey('  SECRET  '));
    }
}
