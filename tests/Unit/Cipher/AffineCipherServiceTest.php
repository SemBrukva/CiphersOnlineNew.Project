<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AffineCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса аффинного шифра.
 */
final class AffineCipherServiceTest extends TestCase
{
    /**
     * Проверяет классический пример шифрования Affine.
     */
    public function testEncryptsClassicExample(): void
    {
        $service = new AffineCipherService();

        self::assertSame('IHHWVC SWFRCP', $service->process('AFFINE CIPHER', 'en', 5, 8, 'encrypt'));
    }

    /**
     * Проверяет обратимость шифрования и расшифрования.
     */
    public function testDecryptsClassicExample(): void
    {
        $service = new AffineCipherService();

        self::assertSame('AFFINE CIPHER', $service->process('IHHWVC SWFRCP', 'en', 5, 8, 'decrypt'));
    }

    /**
     * Проверяет сохранение регистра и символов вне алфавита.
     */
    public function testPreservesCaseAndPunctuation(): void
    {
        $service = new AffineCipherService();

        self::assertSame('Rclla, Oaplx!', $service->process('Hello, World!', 'en', 5, 8, 'encrypt'));
    }

    /**
     * Проверяет валидацию множителя относительно размера алфавита.
     */
    public function testValidatesMultiplierCoprimeWithAlphabetSize(): void
    {
        $service = new AffineCipherService();

        self::assertTrue($service->isValidMultiplier(5, 'en'));
        self::assertFalse($service->isValidMultiplier(13, 'en'));
    }

    /**
     * Проверяет работу с русским алфавитом.
     */
    public function testProcessesRussianAlphabet(): void
    {
        $service = new AffineCipherService();
        $encrypted = $service->process('Привет, мир!', 'ru', 5, 8, 'encrypt');

        self::assertNotSame('Привет, мир!', $encrypted);
        self::assertSame('Привет, мир!', $service->process($encrypted, 'ru', 5, 8, 'decrypt'));
    }

    /**
     * Проверяет, что нулевой множитель недопустим.
     */
    public function testMultiplierZeroIsInvalid(): void
    {
        $service = new AffineCipherService();

        self::assertFalse($service->isValidMultiplier(0, 'en'));
    }

    /**
     * Проверяет, что отрицательный множитель недопустим.
     */
    public function testNegativeMultiplierIsInvalid(): void
    {
        $service = new AffineCipherService();

        self::assertFalse($service->isValidMultiplier(-1, 'en'));
    }

    /**
     * Проверяет, что множитель, равный размеру алфавита, недопустим.
     */
    public function testMultiplierEqualToAlphabetSizeIsInvalid(): void
    {
        $service = new AffineCipherService();

        self::assertFalse($service->isValidMultiplier(26, 'en'));
    }

    /**
     * Проверяет, что множитель a=1 допустим (вырожденный Caesar).
     */
    public function testMultiplierOneIsValid(): void
    {
        $service = new AffineCipherService();

        self::assertTrue($service->isValidMultiplier(1, 'en'));
    }

    /**
     * Проверяет, что последний допустимый множитель (25 для en) принимается.
     */
    public function testMultiplierTwentyFiveIsValidForEnglish(): void
    {
        $service = new AffineCipherService();

        self::assertTrue($service->isValidMultiplier(25, 'en'));
    }

    /**
     * Проверяет правильный размер алфавита для английского языка.
     */
    public function testAlphabetSizeForEnglish(): void
    {
        $service = new AffineCipherService();

        self::assertSame(26, $service->alphabetSize('en'));
    }

    /**
     * Проверяет правильный размер алфавита для русского языка.
     */
    public function testAlphabetSizeForRussian(): void
    {
        $service = new AffineCipherService();

        self::assertSame(33, $service->alphabetSize('ru'));
    }

    /**
     * Проверяет, что a=1 и b=3 ведёт себя как шифр Цезаря со сдвигом 3.
     */
    public function testMultiplierOneActsLikeCaesar(): void
    {
        $service = new AffineCipherService();

        self::assertSame('KHOOR', $service->process('HELLO', 'en', 1, 3, 'encrypt'));
        self::assertSame('HELLO', $service->process('KHOOR', 'en', 1, 3, 'decrypt'));
    }

    /**
     * Проверяет, что текст из одних не-алфавитных символов возвращается без изменений.
     */
    public function testNonAlphabetTextIsPreserved(): void
    {
        $service = new AffineCipherService();

        self::assertSame('123!@#', $service->process('123!@#', 'en', 5, 8, 'encrypt'));
    }
}
