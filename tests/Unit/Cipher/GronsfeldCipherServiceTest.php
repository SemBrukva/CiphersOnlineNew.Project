<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\GronsfeldCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Гронсфельда.
 */
final class GronsfeldCipherServiceTest extends TestCase
{
    // ─────────────────────────── process() — основные случаи ─────────────────────────────

    /**
     * Проверяет канонический пример шифрования и дешифрования.
     *
     * EN / key "314159". Пробел сдвигает индекс ключа, как и буква:
     * H(7)+3=K; E(4)+1=F; L(11)+4=P; L(11)+1=M; O(14)+5=T; [i=5,k=9,пробел]; W(22)+3=Z; O(14)+1=P; R(17)+4=V; L(11)+1=M; D(3)+5=I
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
     * Проверяет шифрование и дешифрование для всех поддерживаемых алфавитов.
     *
     * Все тесты используют ключ «31» (циклический): i→[3,1,3,1,…].
     *
     * @param non-empty-string $alphabet
     * @param non-empty-string $plain
     * @param non-empty-string $expectedCipher
     */
    #[DataProvider('perAlphabetProvider')]
    public function testProcessRoundTripPerAlphabet(
        string $alphabet,
        string $plain,
        string $expectedCipher,
    ): void {
        $service = new GronsfeldCipherService();

        $encrypted = $service->process($plain, '31', $alphabet, 'encrypt');
        self::assertSame($expectedCipher, $encrypted, "encrypt failed for alphabet '{$alphabet}'");

        $decrypted = $service->process($encrypted, '31', $alphabet, 'decrypt');
        self::assertSame($plain, $decrypted, "round-trip failed for alphabet '{$alphabet}'");
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function perAlphabetProvider(): array
    {
        return [
            // EN 26 букв. key[0]=3, key[1]=1. a(0)+3=3=d; b(1)+1=2=c; c(2)+3=5=f
            'en: abc / 31'        => ['en', 'abc', 'dcf'],

            // RU 33 буквы. а(0)+3=3=г; б(1)+1=2=в; в(2)+3=5=е
            'ru: абв / 31'        => ['ru', 'абв', 'гве'],

            // ES 27 букв (ñ=14 между n и o).
            // n(13)+3=16=p; i(8)+1=9=j; ñ(14)+3=17=q; o(15)+1=16=p
            'es: niño / 31'       => ['es', 'niño', 'pjqp'],

            // PT 36 букв. m(19)+3=22=ó; a(0)+1=1=á; ç(6)+3=9=é; ã(3)+1=4=b
            'pt: maçã / 31'       => ['pt', 'maçã', 'óáéb'],

            // TR 29 букв. g(7)+3=10=ı; ü(25)+1=26=v; ç(3)+3=6=f
            'tr: güç / 31'        => ['tr', 'güç', 'ıvf'],

            // FR 40 букв. ê(10)+3=13=g; t(29)+1=30=u; r(27)+3=30=u; e(7)+1=8=é
            'fr: être / 31'       => ['fr', 'être', 'guué'],

            // DE 29 букв. ü(23)+3=26=x; b(2)+1=3=c; e(5)+3=8=h; r(19)+1=20=s
            'de: über / 31'       => ['de', 'über', 'xchs'],

            // IT 26 букв (как EN). c(2)+3=5=f; i(8)+1=9=j; a(0)+3=3=d; o(14)+1=15=p
            'it: ciao / 31'       => ['it', 'ciao', 'fjdp'],
        ];
    }

    // ─────────────────────────── process() — edge / corner cases ─────────────────────────

    /**
     * Проверяет, что пустая строка возвращается без изменений.
     */
    public function testEmptyStringReturnsEmpty(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('', $service->process('', '31415', 'en', 'encrypt'));
        self::assertSame('', $service->process('', '31415', 'en', 'decrypt'));
        self::assertSame('', $service->process('', '31415', 'ru', 'encrypt'));
    }

    /**
     * Проверяет, что пустой ключ возвращает исходный текст.
     */
    public function testEmptyKeyReturnsOriginalText(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('HELLO', $service->process('HELLO', '', 'en', 'encrypt'));
        self::assertSame('ПРИВЕТ', $service->process('ПРИВЕТ', '', 'ru', 'encrypt'));
    }

    /**
     * Проверяет сохранение регистра и пропуск небуквенных символов.
     *
     * EN / key "1". H(7)+1=8=I→I; e(4)+1=5=f; l(11)+1=12=m; l(11)+1=12=m; o(14)+1=15=p
     * Пробел: пропускается, но НЕ сдвигает позицию ключа (ключ=1, всегда одно и то же).
     */
    public function testPreservesCaseAndNonAlphabeticCharacters(): void
    {
        $service = new GronsfeldCipherService();

        // key "1" — один символ, shift всегда 1
        $result = $service->process('Hello, World!', '1', 'en', 'encrypt');
        self::assertSame('Ifmmp, Xpsme!', $result);

        $decrypted = $service->process('Ifmmp, Xpsme!', '1', 'en', 'decrypt');
        self::assertSame('Hello, World!', $decrypted);
    }

    /**
     * Проверяет, что строка только из небуквенных символов не изменяется.
     */
    public function testNonAlphabeticTextPassedThrough(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('123 !@#', $service->process('123 !@#', '31415', 'en', 'encrypt'));
        self::assertSame('  — …', $service->process('  — …', '31415', 'ru', 'decrypt'));
    }

    /**
     * Проверяет цикличность ключа: при длине ключа меньше длины текста ключ повторяется.
     *
     * EN / key "31". a(0)+3=d; b(1)+1=c; c(2)+3=f; d(3)+1=e; e(4)+3=h
     */
    public function testKeyLengthCyclesWhenShorterThanText(): void
    {
        $service = new GronsfeldCipherService();

        $result = $service->process('abcde', '31', 'en', 'encrypt');
        self::assertSame('dcfeh', $result);

        $decrypted = $service->process('dcfeh', '31', 'en', 'decrypt');
        self::assertSame('abcde', $decrypted);
    }

    /**
     * Проверяет корректный перенос при выходе за пределы алфавита (wrap-around).
     *
     * EN / key "3": x(23)+3=26%26=0=a; y(24)+3=27%26=1=b; z(25)+3=28%26=2=c
     */
    public function testWrapAroundAtEndOfAlphabet(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('abc', $service->process('xyz', '3', 'en', 'encrypt'));
        self::assertSame('xyz', $service->process('abc', '3', 'en', 'decrypt'));
    }

    /**
     * Проверяет корректный перенос при дешифровании ниже нуля.
     *
     * EN / key "3": a(0)-3=-3+26=23=x; b(1)-3=-2+26=24=y; c(2)-3=-1+26=25=z
     */
    public function testWrapAroundAtBeginningOfAlphabetOnDecrypt(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('xyz', $service->process('abc', '3', 'en', 'decrypt'));
        self::assertSame('abc', $service->process('xyz', '3', 'en', 'encrypt'));
    }

    /**
     * Проверяет, что сдвиг 0 оставляет символ без изменений.
     *
     * Ключ "000" — все цифры нулевые, шифрование тождественно.
     */
    public function testZeroDigitKeyIsIdentity(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('HELLO', $service->process('HELLO', '000', 'en', 'encrypt'));
        self::assertSame('HELLO', $service->process('HELLO', '000', 'en', 'decrypt'));
        self::assertSame('ПРИВЕТ', $service->process('ПРИВЕТ', '0', 'ru', 'encrypt'));
    }

    /**
     * Проверяет, что неизвестный код алфавита fallback-ится к английскому.
     *
     * EN / key "3": H(7)+3=K; E(4)+3=H; L(11)+3=O; L(11)+3=O; O(14)+3=R
     */
    public function testUnknownAlphabetFallsBackToEnglish(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('KHOOR', $service->process('HELLO', '3', 'xx', 'encrypt'));
    }

    /**
     * Проверяет, что ключ продвигается даже на позиции небуквенных символов.
     *
     * EN / key "12". "A B": i=0→A(0)+1=B; i=1→' '(ключ сдвигается на 2, пробел пропускается); i=2→B(1)+key[2%2=0]=1=C
     * Итого "B C", а не "B D" (как если бы ключ не сдвигался на небуквенных позициях).
     */
    public function testKeyAdvancesOnNonAlphabeticCharacterPositions(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('B C', $service->process('A B', '12', 'en', 'encrypt'));
        self::assertSame('A B', $service->process('B C', '12', 'en', 'decrypt'));
    }

    /**
     * Проверяет шифрование и дешифрование смешанного регистра с кириллицей.
     *
     * RU / key "31": П(16)+3=Р(19)... нет, а=0..я=32.
     * п(16)+3=19=т; р(17)+1=18=с; и(9)+3=12=л; в(2)+1=3=г; е(5)+3=8=з; т(19)+1=20=у
     * верхний регистр: П→Т; Р→С
     */
    public function testMixedCaseRussianRoundTrip(): void
    {
        $service = new GronsfeldCipherService();

        $encrypted = $service->process('Привет', '31', 'ru', 'encrypt');
        self::assertSame('Тслгзу', $encrypted);

        $decrypted = $service->process('Тслгзу', '31', 'ru', 'decrypt');
        self::assertSame('Привет', $decrypted);
    }

    // ─────────────────────────── isValidNumericKey ───────────────────────────────────────

    /**
     * Проверяет валидацию числового ключа.
     */
    public function testValidatesNumericKey(): void
    {
        $service = new GronsfeldCipherService();

        self::assertTrue($service->isValidNumericKey('12345'));
        self::assertTrue($service->isValidNumericKey('0'));
        self::assertTrue($service->isValidNumericKey('00000'));
        self::assertTrue($service->isValidNumericKey('0123456789'));
        self::assertFalse($service->isValidNumericKey('12ab'));
        self::assertFalse($service->isValidNumericKey(''));
        self::assertFalse($service->isValidNumericKey('1.5'));
        self::assertFalse($service->isValidNumericKey('-1'));
        self::assertFalse($service->isValidNumericKey('1 2'));
    }

    /**
     * Проверяет ограничение длины ключа: 32 символа допустимо, 33 — нет.
     */
    public function testValidatesMaxKeyLength(): void
    {
        $service = new GronsfeldCipherService();

        self::assertTrue($service->isValidNumericKey(str_repeat('1', 32)));
        self::assertFalse($service->isValidNumericKey(str_repeat('1', 33)));
    }

    // ─────────────────────────── detectAlphabet ──────────────────────────────────────────

    /**
     * Проверяет автоопределение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('ru', $service->detectAlphabet('Привет, мир!'));
    }

    /**
     * Проверяет автоопределение языка по уникальным символам каждого алфавита.
     *
     * @param non-empty-string $text
     * @param non-empty-string $expected
     */
    #[DataProvider('detectAlphabetProvider')]
    public function testDetectsAlphabetByUniqueCharacters(string $text, string $expected): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame($expected, $service->detectAlphabet($text));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function detectAlphabetProvider(): array
    {
        return [
            // ğ входит только в турецкий
            'Turkish via ğ'    => ['dağ', 'tr'],
            // ä входит только в немецкий
            'German via ä'     => ['Käse', 'de'],
            // ê входит только во французский
            'French via ê'     => ['forêt', 'fr'],
            // ã входит только в португальский
            'Portuguese via ã' => ['maçã', 'pt'],
            // ñ входит только в испанский
            'Spanish via ñ'    => ['niño', 'es'],
        ];
    }

    /**
     * Проверяет, что для пустой строки detectAlphabet возвращает 'en'.
     */
    public function testDetectAlphabetReturnsEnglishForEmptyString(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('en', $service->detectAlphabet(''));
    }

    /**
     * Проверяет, что для текста без букв detectAlphabet возвращает 'en'.
     */
    public function testDetectAlphabetReturnsEnglishForNonAlphabeticText(): void
    {
        $service = new GronsfeldCipherService();

        self::assertSame('en', $service->detectAlphabet('123 !@# $%^'));
    }

    // ─────────────────────────── hasAlphabetCharacters ──────────────────────────────────

    /**
     * Проверяет, что сервис определяет наличие символов выбранного алфавита.
     */
    public function testDetectsAlphabetCharactersInInput(): void
    {
        $service = new GronsfeldCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
    }

    /**
     * Проверяет корректную работу с уникальными символами разных алфавитов.
     */
    public function testDetectsUniqueAlphabetCharacters(): void
    {
        $service = new GronsfeldCipherService();

        // ñ входит в испанский, но не в английский
        self::assertTrue($service->hasAlphabetCharacters('ñ', 'es'));
        self::assertFalse($service->hasAlphabetCharacters('ñ', 'en'));

        // ğ входит в турецкий, но не в немецкий
        self::assertTrue($service->hasAlphabetCharacters('ğ', 'tr'));
        self::assertFalse($service->hasAlphabetCharacters('ğ', 'de'));

        // Кириллица входит в русский, но не в английский
        self::assertTrue($service->hasAlphabetCharacters('привет', 'ru'));
        self::assertFalse($service->hasAlphabetCharacters('привет', 'en'));
    }

    // ─────────────────────────── supportedAlphabetCodes ─────────────────────────────────

    /**
     * Проверяет, что сервис возвращает все 8 поддерживаемых кодов алфавитов.
     */
    public function testSupportedAlphabetCodesReturnsAllLanguages(): void
    {
        $service = new GronsfeldCipherService();

        $codes = $service->supportedAlphabetCodes();

        self::assertCount(8, $codes);
        foreach (['en', 'ru', 'es', 'pt', 'tr', 'fr', 'de', 'it'] as $expected) {
            self::assertContains($expected, $codes, "Expected code '{$expected}' to be present");
        }
    }
}
