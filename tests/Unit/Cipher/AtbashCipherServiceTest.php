<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AtbashCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Атбаш.
 */
final class AtbashCipherServiceTest extends TestCase
{
    // ─────────────────────────── process() — основные случаи ────────────────────────────

    /**
     * Проверяет симметричность преобразования Атбаш для английского алфавита.
     *
     * H(7)→S(18), E(4)→V(21), L(11)→O(14), O(14)→L(11), W(22)→D(3), R(17)→I(8), D(3)→W(22)
     */
    public function testProcessEnglishRoundTrip(): void
    {
        $service = new AtbashCipherService();

        $encrypted = $service->process('HELLO WORLD', 'en');
        self::assertSame('SVOOL DLIOW', $encrypted);

        $decrypted = $service->process($encrypted, 'en');
        self::assertSame('HELLO WORLD', $decrypted);
    }

    /**
     * Проверяет шифрование и обратимость для всех поддерживаемых алфавитов.
     *
     * @param non-empty-string $alphabet
     * @param non-empty-string $plain
     * @param non-empty-string $expectedCipher
     */
    #[DataProvider('perAlphabetProvider')]
    public function testProcessRoundTripPerAlphabet(
        string $alphabet,
        string $plain,
        string $expectedCipher
    ): void {
        $service = new AtbashCipherService();

        $encrypted = $service->process($plain, $alphabet);
        self::assertSame($expectedCipher, $encrypted, "process failed for alphabet '{$alphabet}'");

        $decrypted = $service->process($encrypted, $alphabet);
        self::assertSame($plain, $decrypted, "round-trip failed for alphabet '{$alphabet}'");
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function perAlphabetProvider(): array
    {
        return [
            // EN 26 букв. H(7)→S(18); E(4)→V(21); L(11)→O(14); O(14)→L(11); W(22)→D(3); R(17)→I(8); D(3)→W(22)
            'en: HELLO WORLD' => ['en', 'HELLO WORLD', 'SVOOL DLIOW'],

            // RU 33 буквы. П(16)→П(16); Р(17)→О(15); И(9)→Ц(23); В(2)→Э(30); Е(5)→Ъ(27); Т(19)→М(13)
            'ru: ПРИВЕТ'      => ['ru', 'ПРИВЕТ', 'ПОЦЭЪМ'],

            // ES 27 букв (ñ=14). n(13)→n(13); i(8)→r(18); ñ(14)→m(12); o(15)→l(11)
            'es: niño'        => ['es', 'niño', 'nrml'],

            // PT 36 букв. a(0)→z(35); m(19)→j(16); o(21)→i(14); r(26)→é(9)
            'pt: amor'        => ['pt', 'amor', 'zjié'],

            // TR 29 букв. m(15)→k(13); e(5)→t(23); r(20)→ğ(8); h(9)→p(19); a(0)→z(28); b(1)→y(27)
            'tr: merhaba'     => ['tr', 'merhaba', 'ktğpzyz'],

            // FR 40 букв. b(3)→x(36); o(23)→î(16); n(22)→ï(17); j(18)→m(21); u(30)→è(9); r(27)→f(12)
            'fr: bonjour'     => ['fr', 'bonjour', 'xîïmîèf'],

            // DE 29 букв. d(4)→v(24); e(5)→ü(23); u(22)→f(6); t(21)→g(7); s(20)→h(8); c(3)→w(25); h(8)→s(20)
            'de: deutsch'     => ['de', 'deutsch', 'vüfghws'],

            // IT 26 букв (идентичен EN). c(2)→x(23); i(8)→r(17); a(0)→z(25); o(14)→l(11)
            'it: ciao'        => ['it', 'ciao', 'xrzl'],
        ];
    }

    // ─────────────────────────── process() — edge / corner cases ────────────────────────────

    /**
     * Проверяет сохранение регистра: заглавные→заглавные, строчные→строчные.
     *
     * 'Hello': H→S; e→v; l→o; l→o; o→l
     */
    public function testPreservesCase(): void
    {
        $service = new AtbashCipherService();

        self::assertSame('Svool', $service->process('Hello', 'en'));
        self::assertSame('svool', $service->process('hello', 'en'));
        self::assertSame('SVOOL', $service->process('HELLO', 'en'));
    }

    /**
     * Проверяет сохранение регистра для кириллических символов.
     *
     * 'Привет': П→П; р→о; и→ц; в→э; е→ъ; т→м
     */
    public function testPreservesCaseRussian(): void
    {
        $service = new AtbashCipherService();

        self::assertSame('Поцэъм', $service->process('Привет', 'ru'));
        self::assertSame('поцэъм', $service->process('привет', 'ru'));
        self::assertSame('ПОЦЭЪМ', $service->process('ПРИВЕТ', 'ru'));
    }

    /**
     * Проверяет, что символы, не входящие в алфавит, пропускаются без изменений.
     */
    public function testNonAlphabeticCharactersPassThrough(): void
    {
        $service = new AtbashCipherService();

        self::assertSame('Sr! 123', $service->process('Hi! 123', 'en'));
        self::assertSame('Поцэъм, 2024!', $service->process('Привет, 2024!', 'ru'));
        self::assertSame('nrml — 123!', $service->process('niño — 123!', 'es'));
    }

    /**
     * Проверяет, что пустая строка возвращается без изменений.
     */
    public function testEmptyStringReturnsEmpty(): void
    {
        $service = new AtbashCipherService();

        self::assertSame('', $service->process('', 'en'));
        self::assertSame('', $service->process('', 'ru'));
    }

    /**
     * Проверяет, что строка только из небуквенных символов возвращается без изменений.
     */
    public function testPureNonAlphabeticStringIsUnchanged(): void
    {
        $service = new AtbashCipherService();

        self::assertSame('123 !@# — …', $service->process('123 !@# — …', 'en'));
        self::assertSame('  —  …  ', $service->process('  —  …  ', 'ru'));
    }

    /**
     * Проверяет самоотображение центральной буквы в алфавитах нечётной длины.
     *
     * RU 33 буквы: п (index 16) → 33−16−1=16 → п
     * ES 27 букв:  n (index 13) → 27−13−1=13 → n
     * TR 29 букв:  l (index 14) → 29−14−1=14 → l
     * DE 29 букв:  n (index 14) → 29−14−1=14 → n
     */
    public function testSelfMappingMiddleLetter(): void
    {
        $service = new AtbashCipherService();

        self::assertSame('П', $service->process('П', 'ru'));
        self::assertSame('п', $service->process('п', 'ru'));
        self::assertSame('n', $service->process('n', 'es'));
        self::assertSame('l', $service->process('l', 'tr'));
        self::assertSame('n', $service->process('n', 'de'));
    }

    /**
     * Проверяет, что неизвестный код алфавита fallback-ится к английскому.
     *
     * H(7)→S(18); e(4)→v(21); l(11)→o(14); l→o; o(14)→l(11)
     */
    public function testUnknownAlphabetFallsBackToEnglish(): void
    {
        $service = new AtbashCipherService();

        self::assertSame('Svool', $service->process('Hello', 'xx'));
        self::assertSame('SVOOL', $service->process('HELLO', 'zz'));
    }

    // ─────────────────────────── detectAlphabet ──────────────────────────────────

    /**
     * Проверяет автоопределение алфавита по уникальным символам.
     *
     * @param non-empty-string $text
     * @param non-empty-string $expected
     */
    #[DataProvider('detectAlphabetProvider')]
    public function testDetectsAlphabetByUniqueCharacters(string $text, string $expected): void
    {
        $service = new AtbashCipherService();

        self::assertSame($expected, $service->detectAlphabet($text));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function detectAlphabetProvider(): array
    {
        return [
            // Кириллица уникальна для русского алфавита
            'Russian via Cyrillic' => ['привет', 'ru'],
            // ğ встречается только в турецком
            'Turkish via ğ'        => ['dağ', 'tr'],
            // ä встречается только в немецком
            'German via ä'         => ['käse', 'de'],
            // ê встречается во французском и португальском; французский приоритетнее
            'French via ê'         => ['forêt', 'fr'],
            // ã уникальна для португальского
            'Portuguese via ã'     => ['maçã', 'pt'],
            // ñ уникальна для испанского
            'Spanish via ñ'        => ['niño', 'es'],
        ];
    }

    /**
     * Проверяет, что для пустой строки detectAlphabet возвращает 'en'.
     */
    public function testDetectAlphabetReturnsEnglishForEmptyString(): void
    {
        $service = new AtbashCipherService();

        self::assertSame('en', $service->detectAlphabet(''));
    }

    /**
     * Проверяет, что для строки без букв detectAlphabet возвращает 'en'.
     */
    public function testDetectAlphabetReturnsEnglishForNonAlphabeticInput(): void
    {
        $service = new AtbashCipherService();

        self::assertSame('en', $service->detectAlphabet('123 !@# $%^'));
    }

    // ─────────────────────────── hasAlphabetCharacters ───────────────────────────

    /**
     * Проверяет обнаружение букв выбранного алфавита в тексте.
     */
    public function testHasAlphabetCharactersBasicCases(): void
    {
        $service = new AtbashCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('', 'en'));
    }

    /**
     * Проверяет, что уникальные символы одного алфавита не обнаруживаются в другом.
     */
    public function testHasAlphabetCharactersWithUniqueSymbols(): void
    {
        $service = new AtbashCipherService();

        // ñ входит в испанский, но не в английский
        self::assertTrue($service->hasAlphabetCharacters('ñ', 'es'));
        self::assertFalse($service->hasAlphabetCharacters('ñ', 'en'));

        // Кириллица — только в русском
        self::assertTrue($service->hasAlphabetCharacters('привет', 'ru'));
        self::assertFalse($service->hasAlphabetCharacters('привет', 'en'));

        // ğ — только в турецком, не входит в английский
        self::assertTrue($service->hasAlphabetCharacters('ğ', 'tr'));
        self::assertFalse($service->hasAlphabetCharacters('ğ', 'en'));
    }

    // ─────────────────────────── supportedAlphabetCodes ──────────────────────────

    /**
     * Проверяет, что сервис возвращает все 8 поддерживаемых кодов алфавитов.
     */
    public function testSupportedAlphabetCodesReturnsAllLanguages(): void
    {
        $service = new AtbashCipherService();

        $codes = $service->supportedAlphabetCodes();

        self::assertCount(8, $codes);
        foreach (['en', 'ru', 'es', 'pt', 'tr', 'fr', 'de', 'it'] as $expected) {
            self::assertContains($expected, $codes, "Expected code '{$expected}' to be present");
        }
    }
}
