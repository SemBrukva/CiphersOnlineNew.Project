<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\BeaufortCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Бофора.
 */
final class BeaufortCipherServiceTest extends TestCase
{
    /**
     * Проверяет, что преобразование Бофора симметрично при повторном применении.
     */
    public function testProcessIsReciprocal(): void
    {
        $service = new BeaufortCipherService();

        $encrypted = $service->process('DEFEND THE EAST WALL', 'FORT', 'en');
        self::assertSame('CKMPSL YMB KRBM SRIU', $encrypted);

        $decrypted = $service->process($encrypted, 'FORT', 'en');
        self::assertSame('DEFEND THE EAST WALL', $decrypted);
    }

    /**
     * Проверяет автоопределение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new BeaufortCipherService();

        self::assertSame('ru', $service->detectAlphabet('Привет, мир!'));
    }

    /**
     * Проверяет, что сервис определяет наличие символов выбранного алфавита.
     */
    public function testDetectsAlphabetCharactersInInput(): void
    {
        $service = new BeaufortCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
    }
}
