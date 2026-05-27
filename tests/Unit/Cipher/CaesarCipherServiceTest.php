<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\CaesarCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Цезаря.
 */
final class CaesarCipherServiceTest extends TestCase
{
    /**
     * Проверяет шифрование и дешифрование для английского алфавита.
     */
    public function testEncryptAndDecryptEnglishText(): void
    {
        $service = new CaesarCipherService();

        $encrypted = $service->process('HELLO WORLD', 'en', 3, 'encrypt');
        self::assertSame('KHOOR ZRUOG', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 3, 'decrypt');
        self::assertSame('HELLO WORLD', $decrypted);
    }

    /**
     * Проверяет, что регистр и небуквенные символы сохраняются.
     */
    public function testPreservesCaseAndNonAlphabeticCharacters(): void
    {
        $service = new CaesarCipherService();

        $result = $service->process('Abc-XYZ 123!', 'en', 2, 'encrypt');
        self::assertSame('Cde-ZAB 123!', $result);
    }

    /**
     * Проверяет определение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new CaesarCipherService();

        self::assertSame('ru', $service->detectAlphabet('Привет, мир!'));
    }

    /**
     * Проверяет ограничение максимального сдвига по алфавиту.
     */
    public function testReturnsMaxShiftForAlphabet(): void
    {
        $service = new CaesarCipherService();

        self::assertSame(25, $service->maxShiftForAlphabet('en'));
        self::assertSame(32, $service->maxShiftForAlphabet('ru'));
        self::assertSame(35, $service->maxShiftForAlphabet('pt'));
        self::assertSame(25, $service->maxShiftForAlphabet('it'));
    }

    /**
     * Проверяет, что сервис умеет определять наличие букв выбранного алфавита.
     */
    public function testDetectsAlphabetPresenceInInput(): void
    {
        $service = new CaesarCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
    }
}
