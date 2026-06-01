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

    /**
     * Проверяет шифрование и расшифровку с разделителем space.
     */
    public function testEncryptAndDecryptWithSpaceDelimiter(): void
    {
        $service = new A1z26CipherService();

        $encrypted = $service->process('hi', 'en', 'encrypt', 'space');
        self::assertSame('8 9', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt', 'space');
        self::assertSame('hi', $decrypted);
    }

    /**
     * Проверяет шифрование и расшифровку с разделителем comma.
     */
    public function testEncryptAndDecryptWithCommaDelimiter(): void
    {
        $service = new A1z26CipherService();

        $encrypted = $service->process('hello world', 'en', 'encrypt', 'comma');
        self::assertSame('8,5,12,12,15 23,15,18,12,4', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt', 'comma');
        self::assertSame('hello world', $decrypted);
    }

    /**
     * Проверяет шифрование и расшифровку с разделителем slash.
     */
    public function testEncryptAndDecryptWithSlashDelimiter(): void
    {
        $service = new A1z26CipherService();

        $encrypted = $service->process('hello world', 'en', 'encrypt', 'slash');
        self::assertSame('8/5/12/12/15 23/15/18/12/4', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt', 'slash');
        self::assertSame('hello world', $decrypted);
    }

    /**
     * Проверяет шифрование и расшифровку с разделителем dot.
     */
    public function testEncryptAndDecryptWithDotDelimiter(): void
    {
        $service = new A1z26CipherService();

        $encrypted = $service->process('hello world', 'en', 'encrypt', 'dot');
        self::assertSame('8.5.12.12.15 23.15.18.12.4', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt', 'dot');
        self::assertSame('hello world', $decrypted);
    }
}
