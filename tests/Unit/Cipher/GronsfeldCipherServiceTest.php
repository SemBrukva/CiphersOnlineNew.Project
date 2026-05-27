<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\GronsfeldCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Гронсфельда.
 */
final class GronsfeldCipherServiceTest extends TestCase
{
    /**
     * Проверяет шифрование и расшифровку с числовым ключом.
     */
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $service = new GronsfeldCipherService();

        $encrypted = $service->process('HELLO WORLD', '314159', 'en', 'encrypt');
        self::assertSame('KFPMT ZPVMI', $encrypted);

        $decrypted = $service->process($encrypted, '314159', 'en', 'decrypt');
        self::assertSame('HELLO WORLD', $decrypted);
    }

    /**
     * Проверяет валидацию числового ключа.
     */
    public function testValidatesNumericKey(): void
    {
        $service = new GronsfeldCipherService();

        self::assertTrue($service->isValidNumericKey('12345'));
        self::assertFalse($service->isValidNumericKey('12ab'));
        self::assertFalse($service->isValidNumericKey(''));
    }

    /**
     * Проверяет автоопределение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('ru', $service->detectAlphabet('Привет, мир!'));
    }
}
