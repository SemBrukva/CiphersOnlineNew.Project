<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\CaseFolder;
use PHPUnit\Framework\TestCase;

/**
 * Тесты локально-чувствительного преобразования регистра.
 */
final class CaseFolderTest extends TestCase
{
    /**
     * Проверяет дефолтное Unicode-поведение для не-турецких алфавитов.
     */
    public function testDefaultCaseFoldingForEnglish(): void
    {
        $folder = new CaseFolder();

        self::assertSame('hello', $folder->toLower('Hello', 'en'));
        self::assertSame('HELLO', $folder->toUpper('Hello', 'en'));
        self::assertSame('i', $folder->toLower('I', 'en'));
        self::assertSame('I', $folder->toUpper('i', 'en'));
    }

    /**
     * Проверяет турецкое отображение I → ı, İ → i при toLower.
     */
    public function testTurkishToLowerMapsDottedAndDotlessI(): void
    {
        $folder = new CaseFolder();

        self::assertSame('ı', $folder->toLower('I', 'tr'));
        self::assertSame('i', $folder->toLower('İ', 'tr'));
        // Уже строчные остаются как есть
        self::assertSame('ı', $folder->toLower('ı', 'tr'));
        self::assertSame('i', $folder->toLower('i', 'tr'));
    }

    /**
     * Проверяет турецкое отображение ı → I, i → İ при toUpper.
     */
    public function testTurkishToUpperMapsDottedAndDotlessI(): void
    {
        $folder = new CaseFolder();

        self::assertSame('I', $folder->toUpper('ı', 'tr'));
        self::assertSame('İ', $folder->toUpper('i', 'tr'));
        // Уже заглавные остаются как есть
        self::assertSame('I', $folder->toUpper('I', 'tr'));
        self::assertSame('İ', $folder->toUpper('İ', 'tr'));
    }

    /**
     * Проверяет, что для tr двойная свёртка через нижний регистр и обратно сохраняет
     * различение пар I↔ı и İ↔i (текст изначально полностью в верхнем регистре).
     */
    public function testTurkishCaseRoundTripPreservesDottedAndDotlessI(): void
    {
        $folder = new CaseFolder();

        $original = 'İSTANBUL IRMAK İYİ';
        $lower    = $folder->toLower($original, 'tr');
        $upper    = $folder->toUpper($lower, 'tr');

        self::assertSame('istanbul ırmak iyi', $lower);
        self::assertSame($original, $upper);
    }

    /**
     * Проверяет, что прочие турецкие буквы (ç, ş, ğ, ü, ö) обрабатываются дефолтно.
     */
    public function testTurkishHandlesOtherDiacritics(): void
    {
        $folder = new CaseFolder();

        self::assertSame('çığ şöyle ü', $folder->toLower('ÇIĞ ŞÖYLE Ü', 'tr'));
        self::assertSame('ÇIĞ ŞÖYLE Ü', $folder->toUpper('çığ şöyle ü', 'tr'));
    }

    /**
     * Проверяет, что пустая строка остаётся пустой.
     */
    public function testEmptyString(): void
    {
        $folder = new CaseFolder();

        self::assertSame('', $folder->toLower('', 'tr'));
        self::assertSame('', $folder->toUpper('', 'tr'));
        self::assertSame('', $folder->toLower('', 'en'));
        self::assertSame('', $folder->toUpper('', 'en'));
    }

    /**
     * Проверяет, что неизвестный код алфавита работает как дефолтный Unicode case-fold.
     */
    public function testUnknownAlphabetFallsBackToDefault(): void
    {
        $folder = new CaseFolder();

        self::assertSame('hello', $folder->toLower('HELLO', 'zz'));
        self::assertSame('HELLO', $folder->toUpper('hello', 'zz'));
    }
}
