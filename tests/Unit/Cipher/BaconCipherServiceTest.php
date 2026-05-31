<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\BaconCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Бэкона.
 */
final class BaconCipherServiceTest extends TestCase
{
    // ─────────────────────────── process() — канонические значения ──────────────────────

    /**
     * Проверяет канонический пример для английского алфавита.
     *
     * a=AAAAA, b=AAAAB, c=AAABA → AAAAAAAAABAAABA
     */
    public function testEncryptEnglishCanonical(): void
    {
        $service = new BaconCipherService();

        self::assertSame('AAAAAAAAABAAABA', $service->process('abc', 'en', 'encrypt'));
    }

    /**
     * Проверяет канонический пример для кириллицы.
     *
     * а=AAAAA, б=AAAAB, в=AAABA → AAAAAAAAABAAABA
     */
    public function testEncryptRussianCanonical(): void
    {
        $service = new BaconCipherService();

        self::assertSame('AAAAAAAAABAAABA', $service->process('абв', 'ru', 'encrypt'));
    }

    /**
     * Проверяет шифрование конца английского алфавита.
     *
     * x=10111=BABBB, y=11000=BBAAA, z=11001=BBAAB
     */
    public function testEncryptEnglishEndOfAlphabet(): void
    {
        $service = new BaconCipherService();

        self::assertSame('BABBBBBAAABBAAB', $service->process('xyz', 'en', 'encrypt'));
    }

    /**
     * Проверяет классический пример HELLO.
     *
     * H=00111=AABBB, E=00100=AABAA, L=01011=ABABB, L=ABABB, O=01110=ABBBA
     */
    public function testEncryptHello(): void
    {
        $service = new BaconCipherService();

        self::assertSame('AABBBAABAAABABBABABBABBBA', $service->process('HELLO', 'en', 'encrypt'));
    }

    // ─────────────────────────── process() — round-trip все алфавиты ─────────────────────

    /**
     * Проверяет обратимость шифрования для всех поддерживаемых алфавитов.
     *
     * @param non-empty-string $alphabet
     * @param non-empty-string $plain
     */
    #[DataProvider('encryptRoundTripProvider')]
    public function testEncryptDecryptRoundTripPerAlphabet(string $alphabet, string $plain): void
    {
        $service = new BaconCipherService();

        $encrypted = $service->process($plain, $alphabet, 'encrypt');
        $decrypted  = $service->process($encrypted, $alphabet, 'decrypt');

        self::assertSame($plain, $decrypted, "Round-trip failed for alphabet '{$alphabet}'");
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function encryptRoundTripProvider(): array
    {
        return [
            'en' => ['en', 'abc'],
            'ru' => ['ru', 'абв'],
            'es' => ['es', 'abc'],
            'pt' => ['pt', 'aáà'],
            'tr' => ['tr', 'abc'],
            'fr' => ['fr', 'aàâ'],
            'de' => ['de', 'aäb'],
            'it' => ['it', 'abc'],
        ];
    }

    // ─────────────────────────── process() — edge cases ─────────────────────────────────

    /**
     * Проверяет, что пробел в тексте сохраняется в выводе.
     *
     * 'a b' → AAAAA (a) + ' ' (пробел) + AAAAB (b)
     */
    public function testSpaceInInputPreservedInOutput(): void
    {
        $service = new BaconCipherService();

        self::assertSame('AAAAA AAAAB', $service->process('a b', 'en', 'encrypt'));
    }

    /**
     * Проверяет, что небуквенные символы пропускаются при шифровании.
     *
     * 'a!b' → a=AAAAA, '!' пропускается, b=AAAAB → AAAAAAAAAB
     */
    public function testNonAlphabeticCharsSkippedOnEncrypt(): void
    {
        $service = new BaconCipherService();

        self::assertSame('AAAAAAAAAB', $service->process('a!b', 'en', 'encrypt'));
    }

    /**
     * Проверяет регистронезависимость входного текста при шифровании.
     *
     * 'hello' и 'HELLO' должны давать одинаковый результат.
     */
    public function testEncryptIsCaseInsensitive(): void
    {
        $service = new BaconCipherService();

        self::assertSame(
            $service->process('hello', 'en', 'encrypt'),
            $service->process('HELLO', 'en', 'encrypt')
        );
    }

    /**
     * Проверяет нормализацию 'ё' → 'е' в русском алфавите.
     *
     * 'ёж' должен давать тот же результат, что 'еж', потому что ё→е при шифровании.
     */
    public function testRussianYoNormalizesToYe(): void
    {
        $service = new BaconCipherService();

        $withYo  = $service->process('ёж', 'ru', 'encrypt');
        $withYe  = $service->process('еж', 'ru', 'encrypt');

        self::assertSame($withYe, $withYo, "'ё' must be normalized to 'е' before encoding");
    }

    /**
     * Проверяет, что текст только из небуквенных символов даёт пробел в шифровании.
     *
     * Единственный пробел в исходном тексте порождает пробел в выводе,
     * все остальные не-буквы игнорируются.
     */
    public function testOnlyNonAlphabeticTextProducesOnlySpaces(): void
    {
        $service = new BaconCipherService();

        self::assertSame(' ', $service->process('!!! 123', 'en', 'encrypt'));
    }

    /**
     * Проверяет, что группа с выходящим за пределы алфавита индексом игнорируется при дешифровании.
     *
     * BBBBB = 11111 = 31, что больше длины английского алфавита (26), поэтому группа пропускается.
     */
    public function testDecryptSkipsOutOfRangeGroup(): void
    {
        $service = new BaconCipherService();

        self::assertSame('', $service->process('BBBBB', 'en', 'decrypt'));
    }

    // ─────────────────────────── detectAlphabet ──────────────────────────────────────────

    /**
     * Проверяет автоопределение алфавита по уникальным символам каждого языка.
     *
     * @param non-empty-string $text
     * @param non-empty-string $expected
     */
    #[DataProvider('detectAlphabetProvider')]
    public function testDetectsAlphabetByUniqueCharacters(string $text, string $expected): void
    {
        $service = new BaconCipherService();

        self::assertSame($expected, $service->detectAlphabet($text));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function detectAlphabetProvider(): array
    {
        return [
            'Russian via ё'    => ['ёжик', 'ru'],
            'Russian via Привет' => ['Привет', 'ru'],
            'German via ä'     => ['Käse', 'de'],
            'French via ê'     => ['forêt', 'fr'],
            'Portuguese via ã' => ['maçã', 'pt'],
            'Spanish via ñ'    => ['niño', 'es'],
            'Turkish via ğ'    => ['dağ', 'tr'],
        ];
    }

    /**
     * Проверяет, что для пустой строки detectAlphabet возвращает 'en'.
     */
    public function testDetectAlphabetReturnsEnglishForEmptyString(): void
    {
        $service = new BaconCipherService();

        self::assertSame('en', $service->detectAlphabet(''));
    }

    /**
     * Проверяет, что для строки без букв detectAlphabet возвращает 'en'.
     */
    public function testDetectAlphabetReturnsEnglishForNonAlphabeticText(): void
    {
        $service = new BaconCipherService();

        self::assertSame('en', $service->detectAlphabet('123 !@#'));
    }

    // ─────────────────────────── hasAlphabetCharacters ───────────────────────────────────

    /**
     * Проверяет наличие и отсутствие символов выбранного алфавита.
     */
    public function testHasAlphabetCharacters(): void
    {
        $service = new BaconCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
        self::assertTrue($service->hasAlphabetCharacters('привет', 'ru'));
        self::assertFalse($service->hasAlphabetCharacters('привет', 'en'));
        self::assertTrue($service->hasAlphabetCharacters('niño', 'es'));
        self::assertFalse($service->hasAlphabetCharacters('ñ', 'en'));
    }

    // ─────────────────────────── supportedAlphabetCodes ──────────────────────────────────

    /**
     * Проверяет, что сервис возвращает все 8 поддерживаемых кодов алфавитов.
     */
    public function testSupportedAlphabetCodesReturnsAllLanguages(): void
    {
        $service = new BaconCipherService();

        $codes = $service->supportedAlphabetCodes();

        self::assertCount(8, $codes);
        foreach (['en', 'ru', 'es', 'pt', 'tr', 'fr', 'de', 'it'] as $expected) {
            self::assertContains($expected, $codes, "Expected code '{$expected}' to be present");
        }
    }

    // ─────────────────────────── isStegoText ─────────────────────────────────────────────

    /**
     * Проверяет, что классический A/B-текст не определяется как стеганографический.
     *
     * @param non-empty-string $text
     */
    #[DataProvider('classicBaconTextProvider')]
    public function testClassicBaconTextNotDetectedAsStego(string $text): void
    {
        $service = new BaconCipherService();

        self::assertFalse($service->isStegoText($text));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function classicBaconTextProvider(): array
    {
        return [
            'uppercase AB'   => ['AABBB AABAA ABABB'],
            'lowercase ab'   => ['aabbb aabaa ababb'],
            'mixed case AB'  => ['AaBbAaBb'],
            'only spaces'    => ['     '],
            'AB no spaces'   => ['AABABAABBB'],
        ];
    }

    /**
     * Проверяет, что текст с буквами не из {A,B} определяется как стеганографический.
     *
     * @param non-empty-string $text
     */
    #[DataProvider('stegoTextProvider')]
    public function testTextWithNonABLettersDetectedAsStego(string $text): void
    {
        $service = new BaconCipherService();

        self::assertTrue($service->isStegoText($text));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function stegoTextProvider(): array
    {
        return [
            'mixed case sentence' => ['The quick brown fox'],
            'stego-like'          => ['tHe QuIcK brOwN'],
            'has digit'           => ['!!! 123'],
            'has cyrillic'        => ['онлайн-ИНстРуМеНт'],
        ];
    }

    /**
     * Проверяет, что пустая строка не является стеганографическим текстом.
     */
    public function testEmptyStringIsNotStegoText(): void
    {
        $service = new BaconCipherService();

        self::assertFalse($service->isStegoText(''));
    }

    // ─────────────────────────── countLetters ────────────────────────────────────────────

    /**
     * Проверяет подсчёт Unicode-букв в строке.
     */
    public function testCountLettersCountsOnlyLetters(): void
    {
        $service = new BaconCipherService();

        self::assertSame(10, $service->countLetters('hello world'));
        self::assertSame(6,  $service->countLetters('привет'));
        self::assertSame(0,  $service->countLetters('!!! 123'));
        self::assertSame(3,  $service->countLetters('a-b-c'));
    }

    // ─────────────────────────── countAlphabetChars ──────────────────────────────────────

    /**
     * Проверяет подсчёт символов, входящих в заданный алфавит.
     */
    public function testCountAlphabetCharsIgnoresNonAlphabeticChars(): void
    {
        $service = new BaconCipherService();

        self::assertSame(5, $service->countAlphabetChars('hello', 'en'));
        self::assertSame(5, $service->countAlphabetChars('hello!!!', 'en'));
        self::assertSame(6, $service->countAlphabetChars('привет', 'ru'));
        self::assertSame(4, $service->countAlphabetChars('niño', 'es'));
        self::assertSame(0, $service->countAlphabetChars('123 !!!', 'en'));
    }

    // ─────────────────────────── encodedBitCount ─────────────────────────────────────────

    /**
     * Проверяет подсчёт A/B-бит для стандартных случаев (5 бит на символ).
     */
    public function testEncodedBitCountFiveBitsPerLetterForStandardAlphabets(): void
    {
        $service = new BaconCipherService();

        self::assertSame(25, $service->encodedBitCount('hello', 'en'));
        self::assertSame(15, $service->encodedBitCount('abc', 'en'));
        self::assertSame(10, $service->encodedBitCount('аб', 'ru'));
        // пробелы в секрете не добавляют биты
        self::assertSame(10, $service->encodedBitCount('a b', 'en'));
    }

    /**
     * Проверяет, что 'я' в русском алфавите кодируется 6 битами (index 32 > 31).
     */
    public function testEncodedBitCountSixBitsForRussianYa(): void
    {
        $service = new BaconCipherService();

        self::assertSame(6, $service->encodedBitCount('я', 'ru'));
    }

    // ────────────────────────── steganographyEncrypt / steganographyDecrypt ──────────────

    /**
     * Проверяет каноническое значение стеганографического шифрования.
     *
     * 'a' → AAAAA (5 бит). totalBits=5. Header: high=0→AAAAA, low=5→AABAB.
     * allBits = AAAAA AABAB AAAAA (15 бит). Первые 15 букв cover-текста получают
     * регистр по битам (A→нижний, B→верхний).
     *
     * Cover 'ABCDE FGHIJ KLMNO':
     *   A→a, B→b, C→c, D→d, E→e (high=AAAAA)
     *   ' ' без изменений
     *   F→f, G→g, H→H, I→i, J→J (low=AABAB)
     *   ' ' без изменений
     *   K→k, L→l, M→m, N→n, O→o (body=AAAAA)
     */
    public function testSteganographyEncryptCanonicalValue(): void
    {
        $service = new BaconCipherService();

        $result = $service->steganographyEncrypt('a', 'ABCDE FGHIJ KLMNO', 'en');

        self::assertSame('abcde fgHiJ klmno', $result);
    }

    /**
     * Проверяет стеганографическую обратимость для всех поддерживаемых алфавитов.
     *
     * @param non-empty-string $alphabet
     * @param non-empty-string $secret
     * @param non-empty-string $cover
     */
    #[DataProvider('steganographyRoundTripProvider')]
    public function testSteganographyRoundTripPerAlphabet(
        string $alphabet,
        string $secret,
        string $cover
    ): void {
        $service = new BaconCipherService();

        $stego   = $service->steganographyEncrypt($secret, $cover, $alphabet);
        $decoded = $service->steganographyDecrypt($stego, $alphabet);

        self::assertSame(mb_strtolower($secret), $decoded, "Stego round-trip failed for alphabet '{$alphabet}'");
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function steganographyRoundTripProvider(): array
    {
        return [
            'en' => [
                'en', 'hello',
                'The quick brown fox jumps over the lazy dog and some more words',
            ],
            'ru' => [
                'ru', 'привет',
                'Онлайн-инструмент для кодирования и декодирования шифром Бэкона с группами символов',
            ],
            'de' => [
                'de', 'über',
                'Die schnelle braune Fuchs springt über den faulen Hund und dann weiter',
            ],
            'es' => [
                'es', 'niño',
                'El rápido zorro marrón salta sobre el perro perezoso y algo más texto',
            ],
            'fr' => [
                'fr', 'être',
                'La forêt est très belle en automne avec les feuilles colorées partout',
            ],
            'it' => [
                'it', 'ciao',
                'La volpe veloce salta sopra il cane pigro e continua a correre velocemente',
            ],
            'pt' => [
                'pt', 'maçã',
                'A raposa veloz pula sobre o cachorro preguiçoso no jardim bonito aqui',
            ],
            'tr' => [
                'tr', 'güç',
                'Çabuk kahverengi tilki tembel köpeğin üzerinden atlıyor ve koşmaya devam ediyor',
            ],
        ];
    }

    /**
     * Проверяет, что декодер останавливается в нужном месте и не захватывает «хвост».
     *
     * Cover-текст значительно длиннее нужного — символы после закодированной части
     * должны быть проигнорированы при декодировании.
     */
    public function testSteganographyDecryptStopsAtEncodedLength(): void
    {
        $service = new BaconCipherService();

        $cover   = 'The quick brown fox jumps over the lazy dog and then some MORE UPPERCASE LETTERS HERE TO TEST TAIL';
        $stego   = $service->steganographyEncrypt('HI', $cover, 'en');
        $decoded = $service->steganographyDecrypt($stego, 'en');

        self::assertSame('hi', $decoded);
    }

    /**
     * Проверяет, что регистр секретного текста не влияет на результат стеганографии.
     *
     * Шифрование 'hello' и 'HELLO' должны давать одинаковый stego-текст.
     */
    public function testSteganographySecretIsCaseInsensitive(): void
    {
        $service = new BaconCipherService();

        $cover = 'The quick brown fox jumps over the lazy dog and some more words here now';

        self::assertSame(
            $service->steganographyEncrypt('hello', $cover, 'en'),
            $service->steganographyEncrypt('HELLO', $cover, 'en')
        );
    }

    /**
     * Проверяет, что небуквенные символы cover-текста сохраняются без изменений.
     *
     * Пунктуация, цифры и пробелы в cover-тексте не участвуют в кодировании
     * и должны присутствовать в stego-тексте на тех же позициях.
     */
    public function testSteganographyPreservesNonLetterCharsInCover(): void
    {
        $service = new BaconCipherService();

        $cover = 'Hello, World! How are you? Fine, thank you very much for asking here.';
        $stego = $service->steganographyEncrypt('abc', $cover, 'en');

        self::assertStringContainsString(',', $stego);
        self::assertStringContainsString('!', $stego);
        self::assertStringContainsString('?', $stego);

        $decoded = $service->steganographyDecrypt($stego, 'en');
        self::assertSame('abc', $decoded);
    }

    /**
     * Проверяет, что буквы cover-текста после закодированной части остаются неизменными.
     */
    public function testSteganographyTailLettersUnchanged(): void
    {
        $service = new BaconCipherService();

        // 'a'=AAAAA (5 бит) + 10 заголовочных = 15 затронутых букв
        $cover  = 'abcde fghij klmno pqrst uvwxy';
        $stego  = $service->steganographyEncrypt('a', $cover, 'en');

        // Символы оригинала и stego совпадают начиная с 16-й буквы (индекс в строке с 21-го символа)
        // Проверяем проще через substr: хвост после пробела после 'klmno'
        $tail      = mb_substr($stego, mb_strpos($stego, 'pqrst'));
        $coverTail = mb_substr($cover, mb_strpos($cover, 'pqrst'));

        self::assertSame($coverTail, $tail);
    }

    /**
     * Проверяет, что декодирование слишком короткого stego-текста возвращает пустую строку.
     */
    public function testSteganographyDecryptTooShortReturnsEmpty(): void
    {
        $service = new BaconCipherService();

        // Меньше 10 букв → нельзя прочитать даже заголовок
        self::assertSame('', $service->steganographyDecrypt('Hi', 'en'));
        self::assertSame('', $service->steganographyDecrypt('', 'en'));
    }

    /**
     * Проверяет стеганографическое шифрование с кириллицей в cover-тексте.
     */
    public function testSteganographyWithCyrillicCoverText(): void
    {
        $service = new BaconCipherService();

        $secret = 'алфавит';
        $cover  = 'Онлайн-инструмент для кодирования и декодирования шифром Бэкона с группами A/B и выбором алфавита.';

        $stego   = $service->steganographyEncrypt($secret, $cover, 'ru');
        $decoded = $service->steganographyDecrypt($stego, 'ru');

        self::assertSame($secret, $decoded);
    }
}
