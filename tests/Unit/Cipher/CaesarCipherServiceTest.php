<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\CaesarCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Цезаря.
 */
final class CaesarCipherServiceTest extends TestCase
{
    // ─────────────────────────── encrypt / decrypt ───────────────────────────

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
     * Проверяет шифрование и дешифрование для русского алфавита.
     *
     * п=16+3=19=т, р=17+3=20=у, и=9+3=12=л, в=2+3=5=е, е=5+3=8=з, т=19+3=22=х
     */
    public function testEncryptAndDecryptRussianText(): void
    {
        $service = new CaesarCipherService();

        $encrypted = $service->process('ПРИВЕТ', 'ru', 3, 'encrypt');
        self::assertSame('ТУЛЕЗХ', $encrypted);

        $decrypted = $service->process($encrypted, 'ru', 3, 'decrypt');
        self::assertSame('ПРИВЕТ', $decrypted);
    }

    /**
     * Проверяет сохранение смешанного регистра кириллических символов.
     *
     * Только 'П' заглавная, остальные строчные → только 'Т' заглавная.
     */
    public function testPreservesRussianMixedCase(): void
    {
        $service = new CaesarCipherService();

        self::assertSame('Тулезх', $service->process('Привет', 'ru', 3, 'encrypt'));
    }

    /**
     * Проверяет шифрование, дешифрование и round-trip для всех поддерживаемых алфавитов.
     *
     * @param non-empty-string $alphabet
     * @param non-empty-string $plain
     * @param non-empty-string $expectedCipher
     */
    #[DataProvider('perAlphabetProvider')]
    public function testEncryptDecryptRoundTripPerAlphabet(
        string $alphabet,
        string $plain,
        int $shift,
        string $expectedCipher
    ): void {
        $service = new CaesarCipherService();

        $encrypted = $service->process($plain, $alphabet, $shift, 'encrypt');
        self::assertSame($expectedCipher, $encrypted, "encrypt failed for alphabet '{$alphabet}'");

        $decrypted = $service->process($encrypted, $alphabet, $shift, 'decrypt');
        self::assertSame($plain, $decrypted, "decrypt round-trip failed for alphabet '{$alphabet}'");
    }

    /**
     * @return array<string, array{string, string, int, string}>
     */
    public static function perAlphabetProvider(): array
    {
        return [
            // Spanish: ñ between n and o (27 letters). n=13+1=14=ñ, i+1=j, ñ+1=o, o+1=p.
            'es: niño shift 1' => ['es', 'niño', 1, 'ñjop'],

            // Portuguese: accented vowels + ç (36 letters). i=14+1=15=í, r+1=s, m+1=n, ã=3+1=4=b.
            'pt: irmã shift 1' => ['pt', 'irmã', 1, 'ísnb'],

            // Turkish: ç, ğ, ı, ö, ş, ü (29 letters). ç=3+1=4=d, a+1=b, y=27+1=28=z.
            'tr: çay shift 1'  => ['tr', 'çay', 1, 'dbz'],

            // French: accented variants (40 letters). f=12+1=13=g, ê=10+1=11=ë, t=29+1=30=u, e=7+1=8=é.
            'fr: fête shift 1' => ['fr', 'fête', 1, 'gëué'],

            // German: ä, ö, ü (29 letters). M/m=13+1=14=n→N, ä=1+1=2=b, u=22+1=23=ü, s+1=t, e+1=f.
            'de: Mäuse shift 1' => ['de', 'Mäuse', 1, 'Nbütf'],

            // Italian: identical letters to English (26 letters). c=2+2=4=e, i+2=k, a+2=c, o=14+2=16=q.
            'it: ciao shift 2' => ['it', 'ciao', 2, 'ekcq'],
        ];
    }

    // ────────────────────────── edge / corner cases ──────────────────────────

    /**
     * Проверяет, что сдвиг 0 оставляет текст неизменным.
     */
    public function testShiftZeroReturnsOriginalText(): void
    {
        $service = new CaesarCipherService();

        self::assertSame('Hello World!', $service->process('Hello World!', 'en', 0, 'encrypt'));
        self::assertSame('Hello World!', $service->process('Hello World!', 'en', 0, 'decrypt'));
    }

    /**
     * Проверяет, что сдвиг, равный длине алфавита, эквивалентен нулевому сдвигу (modulo).
     */
    public function testShiftEqualToAlphabetSizeIsIdentity(): void
    {
        $service = new CaesarCipherService();

        // EN: 26 букв — сдвиг 26 ≡ 0
        self::assertSame('Hello', $service->process('Hello', 'en', 26, 'encrypt'));
        // RU: 33 буквы — сдвиг 33 ≡ 0
        self::assertSame('Привет', $service->process('Привет', 'ru', 33, 'encrypt'));
    }

    /**
     * Проверяет перенос с конца алфавита на начало при шифровании.
     */
    public function testWrapAroundAtEndOfAlphabetOnEncrypt(): void
    {
        $service = new CaesarCipherService();

        // EN: Z + 1 → A (z=25, (25+1)%26=0=a)
        self::assertSame('A', $service->process('Z', 'en', 1, 'encrypt'));
        // RU: Я + 1 → А (я=32, (32+1)%33=0=а)
        self::assertSame('А', $service->process('Я', 'ru', 1, 'encrypt'));
        // ES: z + 1 → a (z=26, (26+1)%27=0=a)
        self::assertSame('a', $service->process('z', 'es', 1, 'encrypt'));
    }

    /**
     * Проверяет перенос с начала алфавита в конец при дешифровании.
     */
    public function testWrapAroundAtBeginningOfAlphabetOnDecrypt(): void
    {
        $service = new CaesarCipherService();

        // EN: A - 1 → Z (a=0, (0-1+26)%26=25=z)
        self::assertSame('Z', $service->process('A', 'en', 1, 'decrypt'));
        // RU: А - 1 → Я (а=0, (0-1+33)%33=32=я)
        self::assertSame('Я', $service->process('А', 'ru', 1, 'decrypt'));
    }

    /**
     * Проверяет, что пустая строка возвращается без изменений.
     */
    public function testEmptyStringReturnsEmpty(): void
    {
        $service = new CaesarCipherService();

        self::assertSame('', $service->process('', 'en', 3, 'encrypt'));
        self::assertSame('', $service->process('', 'ru', 5, 'decrypt'));
    }

    /**
     * Проверяет, что строка без букв алфавита пропускается без изменений.
     */
    public function testNonAlphabeticTextIsPassedThrough(): void
    {
        $service = new CaesarCipherService();

        self::assertSame('123 !@#', $service->process('123 !@#', 'en', 3, 'encrypt'));
        self::assertSame('  — …', $service->process('  — …', 'ru', 7, 'encrypt'));
    }

    /**
     * Проверяет, что неизвестный код алфавита fallback-ится к английскому.
     *
     * H=7+3=10=k→K, e+3=h, l+3=o, l+3=o, o=14+3=17=r
     */
    public function testUnknownAlphabetFallsBackToEnglish(): void
    {
        $service = new CaesarCipherService();

        self::assertSame('Khoor', $service->process('Hello', 'xx', 3, 'encrypt'));
    }

    // ────────────────────────── detectAlphabet ───────────────────────────────

    /**
     * Проверяет определение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new CaesarCipherService();

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
        $service = new CaesarCipherService();

        self::assertSame($expected, $service->detectAlphabet($text));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function detectAlphabetProvider(): array
    {
        return [
            // ğ встречается только в турецком
            'Turkish via ğ'    => ['dağ', 'tr'],
            // ä встречается только в немецком
            'German via ä'     => ['Käse', 'de'],
            // ê встречается только во французском
            'French via ê'     => ['forêt', 'fr'],
            // ã встречается только в португальском
            'Portuguese via ã' => ['maçã', 'pt'],
            // ñ встречается только в испанском
            'Spanish via ñ'    => ['niño', 'es'],
        ];
    }

    /**
     * Проверяет, что для пустой строки detectAlphabet возвращает 'en'.
     */
    public function testDetectAlphabetReturnsEnglishForEmptyString(): void
    {
        $service = new CaesarCipherService();

        self::assertSame('en', $service->detectAlphabet(''));
    }

    /**
     * Проверяет, что для текста без букв detectAlphabet возвращает 'en'.
     */
    public function testDetectAlphabetReturnsEnglishForNonAlphabeticText(): void
    {
        $service = new CaesarCipherService();

        self::assertSame('en', $service->detectAlphabet('123 !@# $%^'));
    }

    // ────────────────────────── maxShiftForAlphabet ──────────────────────────

    /**
     * Проверяет ограничение максимального сдвига по алфавиту.
     */
    public function testReturnsMaxShiftForAlphabet(): void
    {
        $service = new CaesarCipherService();

        self::assertSame(25, $service->maxShiftForAlphabet('en')); // 26 букв
        self::assertSame(32, $service->maxShiftForAlphabet('ru')); // 33 буквы
        self::assertSame(35, $service->maxShiftForAlphabet('pt')); // 36 букв
        self::assertSame(25, $service->maxShiftForAlphabet('it')); // 26 букв
    }

    /**
     * Проверяет максимальный сдвиг для языков с расширенными алфавитами.
     */
    public function testReturnsMaxShiftForExtendedAlphabets(): void
    {
        $service = new CaesarCipherService();

        self::assertSame(26, $service->maxShiftForAlphabet('es')); // 27 букв (+ ñ)
        self::assertSame(28, $service->maxShiftForAlphabet('tr')); // 29 букв
        self::assertSame(39, $service->maxShiftForAlphabet('fr')); // 40 букв
        self::assertSame(28, $service->maxShiftForAlphabet('de')); // 29 букв (+ ä, ö, ü)
    }

    // ────────────────────────── hasAlphabetCharacters ────────────────────────

    /**
     * Проверяет, что сервис умеет определять наличие букв выбранного алфавита.
     */
    public function testDetectsAlphabetPresenceInInput(): void
    {
        $service = new CaesarCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
    }

    /**
     * Проверяет корректную работу с уникальными символами разных алфавитов.
     */
    public function testDetectsUniqueAlphabetCharacters(): void
    {
        $service = new CaesarCipherService();

        // ñ входит в испанский, но не в английский
        self::assertTrue($service->hasAlphabetCharacters('ñ', 'es'));
        self::assertFalse($service->hasAlphabetCharacters('ñ', 'en'));

        // Кириллица входит в русский, но не в английский
        self::assertTrue($service->hasAlphabetCharacters('привет', 'ru'));
        self::assertFalse($service->hasAlphabetCharacters('привет', 'en'));
    }

    // ────────────────────────── supportedAlphabetCodes ───────────────────────

    /**
     * Проверяет, что сервис возвращает все 8 поддерживаемых кодов алфавитов.
     */
    public function testSupportedAlphabetCodesReturnsAllLanguages(): void
    {
        $service = new CaesarCipherService();

        $codes = $service->supportedAlphabetCodes();

        self::assertCount(8, $codes);
        foreach (['en', 'ru', 'es', 'pt', 'tr', 'fr', 'de', 'it'] as $expected) {
            self::assertContains($expected, $codes, "Expected code '{$expected}' to be present");
        }
    }
}
