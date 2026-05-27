<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\VigenereCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Виженера.
 */
final class VigenereCipherServiceTest extends TestCase
{
    /**
     * Проверяет шифрование и расшифровку с ключевым словом.
     */
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $service = new VigenereCipherService();

        $encrypted = $service->process('ATTACK AT DAWN', 'LEMON', 'en', 'encrypt');
        self::assertSame('LXFOPV EF RNHR', $encrypted);

        $decrypted = $service->process($encrypted, 'LEMON', 'en', 'decrypt');
        self::assertSame('ATTACK AT DAWN', $decrypted);
    }

    /**
     * Проверяет автоопределение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new VigenereCipherService();

        self::assertSame('ru', $service->detectAlphabet('Привет, мир!'));
    }

    /**
     * Проверяет наличие символов выбранного алфавита.
     */
    public function testDetectsAlphabetCharactersInInput(): void
    {
        $service = new VigenereCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
    }
}
