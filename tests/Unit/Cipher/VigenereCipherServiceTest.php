<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\VigenereCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Виженера.
 */
final class VigenereCipherServiceTest extends TestCase
{
    // ─────────────────────────── process() — основные случаи ────────────────────────────

    /**
     * Проверяет канонический пример шифрования и дешифрования для английского алфавита.
     *
     * ATTACK AT DAWN / LEMON → LXFOPV EF RNHR (из Википедии).
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
     * Проверяет шифрование и дешифрование для русского алфавита.
     *
     * п(16)+к(11)=27=ъ; р(17)+л(12)=29=ь; и(9)+ю(31)=7=ж; в(2)+ч(24)=26=щ; е(5)+к(11)=16=п; т(19)+л(12)=31=ю
     */
    public function testProcessRussianAlphabetRoundTrip(): void
    {
        $service = new VigenereCipherService();

        $encrypted = $service->process('привет', 'ключ', 'ru', 'encrypt');
        self::assertSame('ъьжщпю', $encrypted);

        $decrypted = $service->process($encrypted, 'ключ', 'ru', 'decrypt');
        self::assertSame('привет', $decrypted);
    }

    /**
     * Проверяет шифрование и обратимость для всех поддерживаемых алфавитов.
     *
     * @param non-empty-string $alphabet
     * @param non-empty-string $plain
     * @param non-empty-string $key
     * @param non-empty-string $expectedCipher
     */
    #[DataProvider('perAlphabetProvider')]
    public function testProcessRoundTripPerAlphabet(
        string $alphabet,
        string $plain,
        string $key,
        string $expectedCipher
    ): void {
        $service = new VigenereCipherService();

        $encrypted = $service->process($plain, $key, $alphabet, 'encrypt');
        self::assertSame($expectedCipher, $encrypted, "encrypt failed for alphabet '{$alphabet}'");

        $decrypted = $service->process($encrypted, $key, $alphabet, 'decrypt');
        self::assertSame($plain, $decrypted, "round-trip failed for alphabet '{$alphabet}'");
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function perAlphabetProvider(): array
    {
        return [
            // EN 26 букв. h(7)+l(11)=18=s; e(4)+e(4)=8=i; l(11)+m(12)=23=x; l(11)+o(14)=25=z; o(14)+n(13)=1=b
            'en: hello / lemon'  => ['en', 'hello', 'lemon', 'sixzb'],

            // RU 33 буквы. п(16)+к(11)=27=ъ; р(17)+л(12)=29=ь; и(9)+ю(31)=7=ж; в(2)+ч(24)=26=щ; е(5)+к(11)=16=п; т(19)+л(12)=31=ю
            'ru: привет / ключ'  => ['ru', 'привет', 'ключ', 'ъьжщпю'],

            // ES 27 букв (ñ=14 между n и o).
            // n(13)+s(19)=32%27=5=f; i(8)+o(15)=23=w; ñ(14)+l(11)=25=y; o(15)+s(19)=34%27=7=h
            'es: niño / sol'     => ['es', 'niño', 'sol', 'fwyh'],

            // PT 36 букв. m(19)+s(27)=46%36=10=ê; a(0)+o(21)=21=o; ç(6)+l(18)=24=p; ã(3)+s(27)=30=ú
            'pt: maçã / sol'     => ['pt', 'maçã', 'sol', 'êopú'],

            // TR 29 букв. g(7)+t(23)=30%29=1=b; ü(25)+a(0)=25=ü; ç(3)+ş(22)=25=ü
            'tr: güç / taş'      => ['tr', 'güç', 'taş', 'büü'],

            // FR 40 букв. ê(10)+j(18)=28=s; t(29)+o(23)=52%40=12=f; r(27)+i(15)=42%40=2=â; e(7)+e(7)=14=h
            'fr: être / joie'    => ['fr', 'être', 'joie', 'sfâh'],

            // DE 29 букв. ü(23)+w(25)=48%29=19=r; b(2)+a(0)=2=b; e(5)+l(12)=17=p; r(19)+d(4)=23=ü
            'de: über / wald'    => ['de', 'über', 'wald', 'rbpü'],

            // IT 26 букв (те же, что EN). c(2)+a(0)=2=c; i(8)+m(12)=20=u; a(0)+i(8)=8=i; o(14)+c(2)=16=q
            'it: ciao / amici'   => ['it', 'ciao', 'amici', 'cuiq'],
        ];
    }

    // ─────────────────────────── process() — edge / corner cases ────────────────────────

    /**
     * Проверяет сохранение регистра и пропуск небуквенных символов.
     *
     * H(7)+k(10)=17=r→R; e(4)+e(4)=8=i; l(11)+y(24)=9=j; l(11)+k(10)=21=v; o(14)+e(4)=18=s
     * Запятая и пробел пропускаются (ключ не сдвигается).
     * W(22)+y(24)=20=u→U; o(14)+k(10)=24=y; r(17)+e(4)=21=v; l(11)+y(24)=9=j; d(3)+k(10)=13=n
     */
    public function testPreservesCaseAndNonAlphabeticCharacters(): void
    {
        $service = new VigenereCipherService();

        $result = $service->process('Hello, World!', 'key', 'en', 'encrypt');
        self::assertSame('Rijvs, Uyvjn!', $result);

        $decrypted = $service->process('Rijvs, Uyvjn!', 'key', 'en', 'decrypt');
        self::assertSame('Hello, World!', $decrypted);
    }

    /**
     * Проверяет, что пустая строка возвращается без изменений.
     */
    public function testEmptyStringReturnsEmpty(): void
    {
        $service = new VigenereCipherService();

        self::assertSame('', $service->process('', 'LEMON', 'en', 'encrypt'));
        self::assertSame('', $service->process('', 'LEMON', 'en', 'decrypt'));
        self::assertSame('', $service->process('', 'КЛЮЧ', 'ru', 'encrypt'));
    }

    /**
     * Проверяет, что пустой ключ возвращает исходный текст.
     */
    public function testEmptyKeyReturnsOriginalText(): void
    {
        $service = new VigenereCipherService();

        self::assertSame('HELLO', $service->process('HELLO', '', 'en', 'encrypt'));
        self::assertSame('HELLO', $service->process('HELLO', '', 'en', 'decrypt'));
        self::assertSame('ПРИВЕТ', $service->process('ПРИВЕТ', '', 'ru', 'encrypt'));
    }

    /**
     * Проверяет нечувствительность ключа к регистру.
     *
     * "LEMON" и "lemon" должны давать одинаковый результат.
     */
    public function testKeyIsCaseInsensitive(): void
    {
        $service = new VigenereCipherService();

        $withUpper = $service->process('ATTACK AT DAWN', 'LEMON', 'en', 'encrypt');
        $withLower = $service->process('ATTACK AT DAWN', 'lemon', 'en', 'encrypt');

        self::assertSame($withUpper, $withLower);
    }

    /**
     * Проверяет цикличность ключа: при длине ключа меньше длины текста ключ повторяется.
     *
     * EN / key "xy" (x=23,y=24): a(0)+x=23=x; b(1)+y=25=z; c(2)+x=25=z; d(3)+y=1=b; e(4)+x=1=b
     */
    public function testKeyLengthCyclesWhenShorterThanText(): void
    {
        $service = new VigenereCipherService();

        $result = $service->process('abcde', 'xy', 'en', 'encrypt');
        self::assertSame('xzzbb', $result);

        $decrypted = $service->process('xzzbb', 'xy', 'en', 'decrypt');
        self::assertSame('abcde', $decrypted);
    }

    /**
     * Проверяет корректный перенос при выходе за конец алфавита.
     *
     * EN / key "b" (b=1): x(23)+1=24=y; y(24)+1=25=z; z(25)+1=26%26=0=a
     */
    public function testWrapAroundAtEndOfAlphabet(): void
    {
        $service = new VigenereCipherService();

        self::assertSame('yza', $service->process('xyz', 'bbb', 'en', 'encrypt'));
        self::assertSame('xyz', $service->process('yza', 'bbb', 'en', 'decrypt'));
    }

    /**
     * Проверяет корректный перенос при дешифровании ниже нуля.
     *
     * EN / key "b" (b=1): a(0)-1=-1+26=25=z; b(1)-1=0=a; c(2)-1=1=b
     */
    public function testWrapAroundAtBeginningOnDecrypt(): void
    {
        $service = new VigenereCipherService();

        self::assertSame('zab', $service->process('abc', 'bbb', 'en', 'decrypt'));
        self::assertSame('abc', $service->process('zab', 'bbb', 'en', 'encrypt'));
    }

    /**
     * Проверяет, что строка только из небуквенных символов проходит без изменений.
     */
    public function testNonAlphabeticTextPassedThrough(): void
    {
        $service = new VigenereCipherService();

        self::assertSame('123 !@#', $service->process('123 !@#', 'key', 'en', 'encrypt'));
        self::assertSame('  — …', $service->process('  — …', 'КЛЮЧ', 'ru', 'encrypt'));
    }

    /**
     * Проверяет, что при небуквенных символах в тексте ключ не смещается.
     *
     * EN / key "xb": a(0)+x(23)=23=x; ' ' — пропуск (ключ не сдвигается); b(1)+b(1)=2=c → "x c"
     * Если бы ключ сдвигался на пробеле, то b получил бы ключ x, а не b: b(1)+x(23)=24=y.
     */
    public function testKeyDoesNotAdvanceOnNonAlphabeticCharacters(): void
    {
        $service = new VigenereCipherService();

        self::assertSame('x c', $service->process('a b', 'xb', 'en', 'encrypt'));
        self::assertSame('a b', $service->process('x c', 'xb', 'en', 'decrypt'));
    }

    /**
     * Проверяет, что неизвестный код алфавита fallback-ится к английскому.
     *
     * H(7)+L(11)=18=S; E(4)+E(4)=8=I; L(11)+M(12)=23=X; L(11)+O(14)=25=Z; O(14)+N(13)=1=B
     */
    public function testUnknownAlphabetFallsBackToEnglish(): void
    {
        $service = new VigenereCipherService();

        self::assertSame('SIXZB', $service->process('HELLO', 'LEMON', 'xx', 'encrypt'));
    }

    /**
     * Проверяет шифрование и дешифрование смешанного регистра с кириллицей.
     *
     * RU / key "КЛЮЧ": П(upper,16)+к(11)=27→Ъ; р(17)+л(12)=29→ь; и(9)+ю(31)=7→ж; в(2)+ч(24)=26→щ; е(5)+к(11)=16→п; т(19)+л(12)=31→ю
     */
    public function testMixedCaseRussianRoundTrip(): void
    {
        $service = new VigenereCipherService();

        $encrypted = $service->process('Привет', 'КЛЮЧ', 'ru', 'encrypt');
        self::assertSame('Ъьжщпю', $encrypted);

        $decrypted = $service->process('Ъьжщпю', 'КЛЮЧ', 'ru', 'decrypt');
        self::assertSame('Привет', $decrypted);
    }

    // ─────────────────────────── detectAlphabet ──────────────────────────────────────

    /**
     * Проверяет автоопределение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new VigenereCipherService();

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
        $service = new VigenereCipherService();

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
            // кириллица — только в русский
            'Russian via ё'    => ['ёжик', 'ru'],
        ];
    }

    /**
     * Проверяет, что для пустой строки detectAlphabet возвращает 'en'.
     */
    public function testDetectAlphabetReturnsEnglishForEmptyString(): void
    {
        $service = new VigenereCipherService();

        self::assertSame('en', $service->detectAlphabet(''));
    }

    /**
     * Проверяет, что для текста без букв detectAlphabet возвращает 'en'.
     */
    public function testDetectAlphabetReturnsEnglishForNonAlphabeticText(): void
    {
        $service = new VigenereCipherService();

        self::assertSame('en', $service->detectAlphabet('123 !@# $%^'));
    }

    // ─────────────────────────── hasAlphabetCharacters ──────────────────────────────

    /**
     * Проверяет наличие символов выбранного алфавита.
     */
    public function testDetectsAlphabetCharactersInInput(): void
    {
        $service = new VigenereCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
    }

    /**
     * Проверяет корректную работу с уникальными символами разных алфавитов.
     */
    public function testDetectsUniqueAlphabetCharacters(): void
    {
        $service = new VigenereCipherService();

        // ñ входит в испанский, но не в английский
        self::assertTrue($service->hasAlphabetCharacters('ñ', 'es'));
        self::assertFalse($service->hasAlphabetCharacters('ñ', 'en'));

        // ğ входит в турецкий, но не в немецкий
        self::assertTrue($service->hasAlphabetCharacters('ğ', 'tr'));
        self::assertFalse($service->hasAlphabetCharacters('ğ', 'de'));

        // кириллица входит в русский, но не в английский
        self::assertTrue($service->hasAlphabetCharacters('привет', 'ru'));
        self::assertFalse($service->hasAlphabetCharacters('привет', 'en'));
    }

    // ─────────────────────────── supportedAlphabetCodes ─────────────────────────────

    /**
     * Проверяет, что сервис возвращает все 8 поддерживаемых кодов алфавитов.
     */
    public function testSupportedAlphabetCodesReturnsAllLanguages(): void
    {
        $service = new VigenereCipherService();

        $codes = $service->supportedAlphabetCodes();

        self::assertCount(8, $codes);
        foreach (['en', 'ru', 'es', 'pt', 'tr', 'fr', 'de', 'it'] as $expected) {
            self::assertContains($expected, $codes, "Expected code '{$expected}' to be present");
        }
    }
}
