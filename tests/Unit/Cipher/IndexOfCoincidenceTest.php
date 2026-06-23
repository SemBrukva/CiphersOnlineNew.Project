<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\IndexOfCoincidence;
use PHPUnit\Framework\TestCase;

/**
 * Тесты вычисления индекса совпадений.
 */
final class IndexOfCoincidenceTest extends TestCase
{
    private IndexOfCoincidence $ioc;

    protected function setUp(): void
    {
        $this->ioc = new IndexOfCoincidence();
    }

    /**
     * Проверяет, что IoC открытого английского текста близок к эталону.
     */
    public function testEnglishPlaintextHasHighIoc(): void
    {
        // Naturalistic English text — non-pangram, high repetition of common letters.
        $text = 'ATTACK AT DAWN THE TROOPS WILL ADVANCE THROUGH THE EASTERN FOREST THE ARTILLERY MUST OPEN FIRE AT THE NORTHERN FRONT THE ENEMY IS NOT EXPECTING AN ASSAULT FROM THIS DIRECTION THE COMMANDER HAS ORDERED THE ADVANCE THE SOLDIERS ARE READY THE NIGHT IS DARK AND THE MEN ARE TIRED BUT DETERMINED';
        $ioc  = $this->ioc->compute($text, 'en');

        self::assertGreaterThan(0.055, $ioc);
        self::assertLessThan(0.090, $ioc);
    }

    /**
     * Проверяет, что текст длиной менее 2 букв возвращает 0.
     */
    public function testShortTextReturnsZero(): void
    {
        self::assertSame(0.0, $this->ioc->compute('A', 'en'));
        self::assertSame(0.0, $this->ioc->compute('', 'en'));
        self::assertSame(0.0, $this->ioc->compute('1234567890', 'en'));
    }

    /**
     * Проверяет, что IoC неравномерного текста выше равномерного.
     */
    public function testNaturalLanguageIocHigherThanUniform(): void
    {
        $natural  = 'THE QUICK BROWN FOX JUMPS OVER THE LAZY DOG AGAIN AND AGAIN THE DOG AND FOX';
        $uniform  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $iocNatural  = $this->ioc->compute($natural, 'en');
        $iocUniform  = $this->ioc->compute($uniform, 'en');

        self::assertGreaterThan($iocUniform, $iocNatural);
    }

    /**
     * Проверяет, что метод возвращает float.
     */
    public function testComputeReturnsFloat(): void
    {
        $result = $this->ioc->compute('HELLO WORLD', 'en');
        self::assertIsFloat($result);
    }

    /**
     * Проверяет константу LANGUAGE_IOC для английского.
     */
    public function testLanguageIocConstantsExist(): void
    {
        self::assertArrayHasKey('en', IndexOfCoincidence::LANGUAGE_IOC);
        self::assertArrayHasKey('ru', IndexOfCoincidence::LANGUAGE_IOC);
        self::assertGreaterThan(0.05, IndexOfCoincidence::LANGUAGE_IOC['en']);
    }
}
