<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\BeaufortCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Бофора.
 */
final class BeaufortCipherServiceTest extends TestCase
{
    // ─────────────────────────── process() — основные случаи ────────────────────────────

    /**
     * Проверяет канонический пример шифра Бофора и свойство обратимости.
     *
     * Формула Бофора: C = (K − P + N) mod N, симметрична — один и тот же ключ шифрует и дешифрует.
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
     * Проверяет обработку русского текста и свойство обратимости для кириллицы.
     *
     * П=16,Р=17,И=9,В=2,Е=5,Т=19 / К=11,Л=12,Ю=31,Ч=24 (ключ циклится)
     * П(16)К(11): (11−16+33)%33=28=Ы; Р(17)Л(12): (12−17+33)%33=28=Ы;
     * И(9)Ю(31): (31−9)%33=22=Х; В(2)Ч(24): (24−2)%33=22=Х;
     * Е(5)К(11): (11−5)%33=6=Ё; Т(19)Л(12): (12−19+33)%33=26=Щ
     */
    public function testProcessRussianAlphabetIsReciprocal(): void
    {
        $service = new BeaufortCipherService();

        $encrypted = $service->process('ПРИВЕТ', 'КЛЮЧ', 'ru');
        self::assertSame('ЫЫХХЁЩ', $encrypted);

        $decrypted = $service->process($encrypted, 'КЛЮЧ', 'ru');
        self::assertSame('ПРИВЕТ', $decrypted);
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
    public function testProcessReciprocalPerAlphabet(
        string $alphabet,
        string $plain,
        string $key,
        string $expectedCipher
    ): void {
        $service = new BeaufortCipherService();

        $encrypted = $service->process($plain, $key, $alphabet);
        self::assertSame($expectedCipher, $encrypted, "encrypt failed for alphabet '{$alphabet}'");

        $decrypted = $service->process($encrypted, $key, $alphabet);
        self::assertSame($plain, $decrypted, "reciprocal round-trip failed for alphabet '{$alphabet}'");
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function perAlphabetProvider(): array
    {
        return [
            // EN: 26 букв. D(3)F(5):(5−3)%26=2=C; E(4)O(14):(14−4)%26=10=K; ... → CKMPSL YMB KRBM SRIU
            'en: DEFEND / FORT'  => ['en', 'DEFEND THE EAST WALL', 'FORT', 'CKMPSL YMB KRBM SRIU'],

            // RU: 33 буквы. П(16)К(11):(11−16+33)%33=28=Ы и т.д. → ЫЫХХЁЩ
            'ru: ПРИВЕТ / КЛЮЧ'  => ['ru', 'ПРИВЕТ', 'КЛЮЧ', 'ЫЫХХЁЩ'],

            // ES: 27 букв, ñ между n(13) и o(15).
            // n(13)s(19):(19−13)%27=6=g; i(8)o(15):(15−8)%27=7=h;
            // ñ(14)l(11):(11−14+27)%27=24=x; o(15)s(19):(19−15)%27=4=e
            'es: niño / sol'     => ['es', 'niño', 'sol', 'ghxe'],

            // PT: 36 букв. m(19)s(27):(27−19)%36=8=e; a(0)o(21):21=o;
            // ç(6)l(18):(18−6)%36=12=g; ã(3)s(27):(27−3)%36=24=p
            'pt: maçã / sol'     => ['pt', 'maçã', 'sol', 'eogp'],

            // TR: 29 букв. g(7)t(23):(23−7)%29=16=n; ü(25)a(0):(0−25+29)%29=4=d;
            // ç(3)ş(22):(22−3)%29=19=p
            'tr: güç / taş'      => ['tr', 'güç', 'taş', 'ndp'],

            // FR: 40 букв. ê(10)j(18):(18−10)%40=8=é; t(29)o(23):(23−29+40)%40=34=v;
            // r(27)i(15):(15−27+40)%40=28=s; e(7)e(7):0=a
            'fr: être / joie'    => ['fr', 'être', 'joie', 'évsa'],

            // DE: 29 букв. ü(23)w(25):(25−23)%29=2=b; b(2)a(0):(0−2+29)%29=27=y;
            // e(5)l(12):(12−5)%29=7=g; r(19)d(4):(4−19+29)%29=14=n
            'de: über / wald'    => ['de', 'über', 'wald', 'bygn'],

            // IT: 26 букв (те же, что EN). c(2)a(0):(0−2+26)%26=24=y;
            // i(8)m(12):(12−8)%26=4=e; a(0)i(8):8=i; o(14)c(2):(2−14+26)%26=14=o
            'it: ciao / amici'   => ['it', 'ciao', 'amici', 'yeio'],
        ];
    }

    // ─────────────────────────── process() — edge / corner cases ────────────────────────

    /**
     * Проверяет сохранение регистра и пропуск небуквенных символов.
     *
     * H(7)k(10):(10−7)%26=3=d→D; e(4)e(4):0=a; l(11)y(24):13=n; l(11)k(10):25=z; o(14)e(4):16=q
     */
    public function testPreservesCaseAndNonAlphabeticCharacters(): void
    {
        $service = new BeaufortCipherService();

        $result = $service->process('Hello, World!', 'key', 'en');
        self::assertSame('Danzq, Cwnnh!', $result);
    }

    /**
     * Проверяет, что пустая строка возвращается без изменений.
     */
    public function testEmptyStringReturnsEmpty(): void
    {
        $service = new BeaufortCipherService();

        self::assertSame('', $service->process('', 'FORT', 'en'));
        self::assertSame('', $service->process('', 'КЛЮЧ', 'ru'));
    }

    /**
     * Проверяет, что пустой ключ возвращает исходный текст.
     */
    public function testEmptyKeyReturnsOriginalText(): void
    {
        $service = new BeaufortCipherService();

        self::assertSame('HELLO', $service->process('HELLO', '', 'en'));
        self::assertSame('ПРИВЕТ', $service->process('ПРИВЕТ', '', 'ru'));
    }

    /**
     * Проверяет нечувствительность ключа к регистру.
     *
     * "FORT" и "fort" должны давать одинаковый результат.
     */
    public function testKeyIsCaseInsensitive(): void
    {
        $service = new BeaufortCipherService();

        $withUpper = $service->process('DEFEND', 'FORT', 'en');
        $withLower = $service->process('DEFEND', 'fort', 'en');

        self::assertSame($withUpper, $withLower);
    }

    /**
     * Проверяет цикличность ключа: при длине ключа меньше длины текста ключ повторяется.
     *
     * EN / key "xy" (x=23,y=24). Для каждой буквы: a(0)x(23):23=x; b(1)y(24):23=x;
     * c(2)x(23):(23−2)%26=21=v; d(3)y(24):(24−3)%26=21=v; e(4)x(23):(23−4)%26=19=t
     */
    public function testKeyLengthCyclesWhenShorterThanText(): void
    {
        $service = new BeaufortCipherService();

        $result = $service->process('abcde', 'xy', 'en');
        self::assertSame('xxvvt', $result);

        $decrypted = $service->process('xxvvt', 'xy', 'en');
        self::assertSame('abcde', $decrypted);
    }

    /**
     * Проверяет корректный перенос при K < P (отрицательный cipherPos + N).
     *
     * z(25)+a(0): (0−25+26)%26=1=b. Взаимность: b(1)+a(0):(0−1+26)%26=25=z.
     */
    public function testWrapAroundWhenKeyPositionLessThanTextPosition(): void
    {
        $service = new BeaufortCipherService();

        self::assertSame('b', $service->process('z', 'a', 'en'));
        self::assertSame('z', $service->process('b', 'a', 'en'));
    }

    /**
     * Проверяет, что строка только из небуквенных символов проходит без изменений.
     */
    public function testNonAlphabeticTextPassedThrough(): void
    {
        $service = new BeaufortCipherService();

        self::assertSame('123 !@#', $service->process('123 !@#', 'key', 'en'));
        self::assertSame('  — …', $service->process('  — …', 'КЛЮЧ', 'ru'));
    }

    /**
     * Проверяет, что при небуквенных символах в тексте ключ не смещается.
     *
     * "ab cd" / "xy": a→x, b→x, пробел пропускается (ключ не сдвигается),
     * c→v, d→v. Итого "xx vv".
     */
    public function testKeyDoesNotAdvanceOnNonAlphabeticCharacters(): void
    {
        $service = new BeaufortCipherService();

        self::assertSame('xx vv', $service->process('ab cd', 'xy', 'en'));
    }

    /**
     * Проверяет, что неизвестный код алфавита fallback-ится к английскому.
     *
     * H(7)F(5):(5−7+26)%26=24=X; E(4)O(14):10=K; L(11)R(17):6=G; L(11)T(19):8=I; O(14)F(5):17=R
     */
    public function testUnknownAlphabetFallsBackToEnglish(): void
    {
        $service = new BeaufortCipherService();

        self::assertSame('YKGIR', $service->process('HELLO', 'FORT', 'xx'));
    }

    // ─────────────────────────── detectAlphabet ──────────────────────────────────────

    /**
     * Проверяет автоопределение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new BeaufortCipherService();

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
        $service = new BeaufortCipherService();

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
        $service = new BeaufortCipherService();

        self::assertSame('en', $service->detectAlphabet(''));
    }

    /**
     * Проверяет, что для текста без букв detectAlphabet возвращает 'en'.
     */
    public function testDetectAlphabetReturnsEnglishForNonAlphabeticText(): void
    {
        $service = new BeaufortCipherService();

        self::assertSame('en', $service->detectAlphabet('123 !@# $%^'));
    }

    // ─────────────────────────── hasAlphabetCharacters ──────────────────────────────

    /**
     * Проверяет, что сервис определяет наличие символов выбранного алфавита.
     */
    public function testDetectsAlphabetCharactersInInput(): void
    {
        $service = new BeaufortCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
    }

    /**
     * Проверяет корректную работу с уникальными символами разных алфавитов.
     */
    public function testDetectsUniqueAlphabetCharacters(): void
    {
        $service = new BeaufortCipherService();

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

    // ─────────────────────────── supportedAlphabetCodes ─────────────────────────────

    /**
     * Проверяет, что сервис возвращает все 8 поддерживаемых кодов алфавитов.
     */
    public function testSupportedAlphabetCodesReturnsAllLanguages(): void
    {
        $service = new BeaufortCipherService();

        $codes = $service->supportedAlphabetCodes();

        self::assertCount(8, $codes);
        foreach (['en', 'ru', 'es', 'pt', 'tr', 'fr', 'de', 'it'] as $expected) {
            self::assertContains($expected, $codes, "Expected code '{$expected}' to be present");
        }
    }
}
