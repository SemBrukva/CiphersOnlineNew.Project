<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\BaconCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Бэкона.
 */
final class BaconCipherServiceTest extends TestCase
{
    /**
     * Проверяет шифрование и расшифровку для английского алфавита.
     */
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $service = new BaconCipherService();

        $encrypted = $service->process('abc', 'en', 'encrypt');
        self::assertSame('AAAAAAAAABAAABA', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt');
        self::assertSame('abc', $decrypted);
    }

    /**
     * Проверяет автоопределение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new BaconCipherService();

        self::assertSame('ru', $service->detectAlphabet('Привет'));
    }
}
