<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\A1z26CipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра A1Z26.
 */
final class A1z26CipherServiceTest extends TestCase
{
    /**
     * Проверяет шифрование и расшифровку с разделителем dash.
     */
    public function testEncryptAndDecryptWithDashDelimiter(): void
    {
        $service = new A1z26CipherService();

        $encrypted = $service->process('hello world', 'en', 'encrypt', 'dash');
        self::assertSame('8-5-12-12-15 23-15-18-12-4', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt', 'dash');
        self::assertSame('hello world', $decrypted);
    }
}
