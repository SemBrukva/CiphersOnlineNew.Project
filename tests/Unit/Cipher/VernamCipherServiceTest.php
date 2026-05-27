<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\VernamCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Вернама.
 */
final class VernamCipherServiceTest extends TestCase
{
    /**
     * Проверяет шифрование и расшифровку с ключом.
     */
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $service = new VernamCipherService();

        $encrypted = $service->process('HELLO WORLD', 'key', 'encrypt');
        self::assertNotSame('HELLO WORLD', $encrypted);

        $decrypted = $service->process($encrypted, 'key', 'decrypt');
        self::assertSame('HELLO WORLD', $decrypted);
    }

    /**
     * Проверяет, что невалидный base64 при дешифровании возвращает пустую строку.
     */
    public function testReturnsEmptyStringForInvalidBase64OnDecrypt(): void
    {
        $service = new VernamCipherService();

        self::assertSame('', $service->process('%%%%', 'key', 'decrypt'));
    }
}
