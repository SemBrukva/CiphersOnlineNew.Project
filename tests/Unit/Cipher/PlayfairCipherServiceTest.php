<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\PlayfairCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Плейфера.
 */
final class PlayfairCipherServiceTest extends TestCase
{
    /**
     * Проверяет, что шифрование и обратное дешифрование дают исходный текст с заполнителем.
     */
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $service = new PlayfairCipherService();

        $encrypted = $service->process('HELLO WORLD', 'KEYWORD', 'en', 'encrypt');
        self::assertSame('IKICMWORWNAB', $encrypted);

        $decrypted = $service->process($encrypted, 'KEYWORD', 'en', 'decrypt');
        self::assertSame('HELALOWORLDA', $decrypted);
    }

    /**
     * Проверяет автоопределение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new PlayfairCipherService();

        self::assertSame('ru', $service->detectAlphabet('Привет, мир!'));
    }

    /**
     * Проверяет, что сервис определяет наличие символов выбранного алфавита.
     */
    public function testDetectsAlphabetCharactersInInput(): void
    {
        $service = new PlayfairCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
    }
}
