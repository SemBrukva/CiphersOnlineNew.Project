<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\PlayfairCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Плейфера.
 */
final class PlayfairCipherServiceTest extends TestCase
{
    // ──────────────────────────── round-trip ─────────────────────────────

    /**
     * Базовый round-trip для английского алфавита (с фиксированным ожидаемым шифротекстом).
     */
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $service = new PlayfairCipherService();

        $encrypted = $service->process('HELLO WORLD', 'KEYWORD', 'en', 'encrypt');
        self::assertSame('IKICMWORWNAB', $encrypted);

        $decrypted = $service->process($encrypted, 'KEYWORD', 'en', 'decrypt');
        self::assertSame('HELALOWORLDA', $decrypted);
    }

    /**
     * Round-trip для русского алфавита с фиксированным шифротекстом.
     */
    public function testRussianEncryptDecryptRoundTrip(): void
    {
        $service = new PlayfairCipherService();

        $encrypted = $service->process('ПРИВЕТ', 'КЛЮЧ', 'ru', 'encrypt');
        self::assertSame('РСЗГМЩ', $encrypted);

        $decrypted = $service->process($encrypted, 'КЛЮЧ', 'ru', 'decrypt');
        self::assertSame('ПРИВЕТ', $decrypted);
    }

    /**
     * Round-trip для испанского алфавита (включает букву Ñ).
     */
    public function testSpanishEncryptDecryptRoundTrip(): void
    {
        $service = new PlayfairCipherService();

        $encrypted = $service->process('NIÑO', 'CLAVE', 'es', 'encrypt');
        self::assertSame('OGOP', $encrypted);

        $decrypted = $service->process($encrypted, 'CLAVE', 'es', 'decrypt');
        self::assertSame('NIÑO', $decrypted);
    }

    /**
     * Round-trip для всех поддерживаемых алфавитов.
     *
     * @dataProvider roundTripProvider
     */
    public function testRoundTripAllAlphabets(
        string $alphabet,
        string $text,
        string $key,
        string $expectedEncrypted,
        string $expectedDecrypted
    ): void {
        $service = new PlayfairCipherService();

        $encrypted = $service->process($text, $key, $alphabet, 'encrypt');
        self::assertSame($expectedEncrypted, $encrypted, "encrypt failed for alphabet=$alphabet");

        $decrypted = $service->process($encrypted, $key, $alphabet, 'decrypt');
        self::assertSame($expectedDecrypted, $decrypted, "decrypt failed for alphabet=$alphabet");
    }

    /**
     * @return array<string, array{string, string, string, string, string}>
     */
    public static function roundTripProvider(): array
    {
        return [
            // Португальский: полная 6×6 матрица (36 букв), без неполных строк.
            'pt BRASIL' => ['pt', 'BRASIL', 'CHAVE', 'ÇQEQFN', 'BRASIL'],
            // Турецкий: i(с точкой) и ı(без точки) различаются; нечётный текст → заполнитель A.
            'tr MERHABA' => ['tr', 'MERHABA', 'ANAHTAR', 'PCBTNANN', 'MERHABAA'],
            // Французский: матрица 7 столбцов (40 букв); нечётный текст → заполнитель A.
            'fr BONJOUR' => ['fr', 'BONJOUR', 'MOT', 'MTÔKTSÙM', 'BONJOURA'],
            // Немецкий: ß удалён из алфавита; чёткий round-trip для текста без ß.
            'de HAUS' => ['de', 'HAUS', 'BERLIN', 'PHÜT', 'HAUS'],
            // Итальянский: стандартные 26 латинских букв.
            'it BUON' => ['it', 'BUON', 'CHIAVE', 'GRPO', 'BUON'],
        ];
    }

    // ──────────────────────── заполнитель и биграммы ─────────────────────

    /**
     * При расшифровке шифротекста с повторяющимися символами (NN) биграммы строятся
     * прямым разбиением по 2, а не с вставкой заполнителя.
     * До исправления: MERHABA→PCBTNANN→MERHABABAB (баг buildBigrams при дешифровке).
     */
    public function testDecryptDoesNotInsertFillerIntoCiphertext(): void
    {
        $service = new PlayfairCipherService();

        // Зашифрованный текст PCBTNANN содержит NN на конце.
        // До исправления buildBigrams разбивал NN → [N,A]+[N,A], добавляя лишние символы.
        $decrypted = $service->process('PCBTNANN', 'ANAHTAR', 'tr', 'decrypt');
        self::assertSame('MERHABAA', $decrypted);
        // 8 зашифрованных символов → ровно 8 расшифрованных символов.
        self::assertSame(8, mb_strlen($decrypted));
    }

    /**
     * Двойная буква разбивается заполнителем (первая буква алфавита).
     * Для русского заполнитель — А; ЙЙ → [Й,А]+[Й,А].
     */
    public function testDoubleLetterIsFilledWithFirstAlphabetLetter(): void
    {
        $service = new PlayfairCipherService();

        $encrypted = $service->process('ЙЙ', 'КЛЮЧ', 'ru', 'encrypt');
        // 2 биграммы [Й,А][Й,А] → 4 зашифрованных символа.
        self::assertSame(4, mb_strlen($encrypted));

        $decrypted = $service->process($encrypted, 'КЛЮЧ', 'ru', 'decrypt');
        // После расшифровки заполнитель А виден: ЙАЙА.
        self::assertSame('ЙАЙА', $decrypted);
    }

    /**
     * Нечётный текст получает заполнитель в последней биграмме.
     */
    public function testOddLengthTextReceivesFiller(): void
    {
        $service = new PlayfairCipherService();

        // NIÑ — 3 буквы, последняя биграмма [Ñ, A(заполнитель)].
        $encrypted = $service->process('NIÑ', 'CLAVE', 'es', 'encrypt');
        self::assertSame(4, mb_strlen($encrypted));

        $decrypted = $service->process($encrypted, 'CLAVE', 'es', 'decrypt');
        // Заполнитель A виден как последний символ.
        self::assertStringStartsWith('NIÑ', $decrypted);
    }

    // ───────────────────────── специфика алфавитов ───────────────────────

    /**
     * Турецкие буквы «i с точкой» (İ) и «ı без точки» (I) различаются в матрице
     * и дают разные шифротексты.
     * До исправления обе → «I» (коллизия), алфавит терял 29-ю букву.
     */
    public function testTurkishDottedAndDotlessIProduceDifferentCiphertexts(): void
    {
        $service = new PlayfairCipherService();

        $encI    = $service->process('BIR',  'ANAHTAR', 'tr', 'encrypt'); // I без точки (ı)
        $encIdot = $service->process('BİR',  'ANAHTAR', 'tr', 'encrypt'); // İ с точкой

        self::assertNotSame($encI, $encIdot, 'BIR и BİR должны давать разные шифротексты');
    }

    /**
     * Турецкое I (dotless ı) корректно шифруется и восстанавливается при round-trip.
     */
    public function testTurkishDotlessIRoundTrip(): void
    {
        $service = new PlayfairCipherService();

        self::assertSame('NLBN', $service->process('BIR', 'ANAHTAR', 'tr', 'encrypt'));
        // Нечётный текст → заполнитель A в конце.
        self::assertSame('BIRA', $service->process('NLBN', 'ANAHTAR', 'tr', 'decrypt'));
    }

    /**
     * Турецкое İ (dotted i) корректно шифруется и восстанавливается при round-trip.
     */
    public function testTurkishDottedIRoundTrip(): void
    {
        $service = new PlayfairCipherService();

        self::assertSame('HLBN', $service->process('BİR', 'ANAHTAR', 'tr', 'encrypt'));
        self::assertSame('BİRA', $service->process('HLBN', 'ANAHTAR', 'tr', 'decrypt'));
    }

    /**
     * Символ ß удалён из немецкого алфавита (mb_strtoupper('ß')='SS' — двухсимвольная ячейка матрицы).
     * Текст с ß нормализуется в SS, при round-trip видны биграммные заполнители.
     */
    public function testGermanEszettNormalizesToSS(): void
    {
        $service = new PlayfairCipherService();

        $encrypted = $service->process('GROß', 'BERLIN', 'de', 'encrypt');
        $decrypted = $service->process($encrypted, 'BERLIN', 'de', 'decrypt');

        // GROß → GROSS при нормализации (ß→SS), SS разбивается заполнителем A.
        self::assertStringStartsWith('GROSS', $decrypted);
    }

    /**
     * Французский алфавит — единственный с матрицей 7 столбцов (40 букв).
     * Акцентированные буквы (é, â и т.д.) корректно шифруются и дешифруются.
     */
    public function testFrenchAccentedLettersRoundTrip(): void
    {
        $service = new PlayfairCipherService();

        $encrypted = $service->process('CAFÉ', 'MOT', 'fr', 'encrypt');
        self::assertSame('EMIÇ', $encrypted);

        $decrypted = $service->process($encrypted, 'MOT', 'fr', 'decrypt');
        self::assertSame('CAFÉ', $decrypted);
    }

    // ─────────────────────── определение алфавита ────────────────────────

    /**
     * detectAlphabet корректно определяет язык для каждого поддерживаемого алфавита.
     *
     * @dataProvider detectAlphabetProvider
     */
    public function testDetectAlphabetForVariousTexts(string $text, string $expectedAlphabet): void
    {
        $service = new PlayfairCipherService();

        self::assertSame($expectedAlphabet, $service->detectAlphabet($text));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function detectAlphabetProvider(): array
    {
        return [
            'Russian' => ['Привет, мир!',   'ru'],
            'Spanish' => ['¡Hola, niño!',    'es'],
            'French'  => ['Bonjour café',    'fr'],
            'German'  => ['Guten Tag, Ä',    'de'],
            // English не добавляем: его буквы входят во все латинские алфавиты,
            // и detectAlphabet вернёт 'de' (стоит раньше в цепочке приоритетов).
        ];
    }

    /**
     * Проверяет, что сервис определяет наличие символов выбранного алфавита.
     */
    public function testDetectsAlphabetCharactersInInput(): void
    {
        $service = new PlayfairCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
    }

    /**
     * hasAlphabetCharacters корректно работает для нелатинских и расширенных алфавитов.
     *
     * @dataProvider hasAlphabetCharactersProvider
     */
    public function testHasAlphabetCharactersAllAlphabets(
        string $text,
        string $alphabet,
        bool $expected
    ): void {
        $service = new PlayfairCipherService();

        self::assertSame($expected, $service->hasAlphabetCharacters($text, $alphabet));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function hasAlphabetCharactersProvider(): array
    {
        return [
            'Russian yes'         => ['Привет',    'ru', true],
            'Russian no'          => ['Hello!',    'ru', false],
            'Spanish Ñ'           => ['niño',      'es', true],
            'Spanish without Ñ'   => ['hola',      'es', true],
            'Turkish Ğ'           => ['Ğ sesi',    'tr', true],
            'Turkish Ş'           => ['Şeker',     'tr', true],
            'French accented'     => ['café',      'fr', true],
            'German Ä'            => ['Äpfel',     'de', true],
            'Portuguese Ã'        => ['irmã',      'pt', true],
            'Italian no accents'  => ['ciao',      'it', true],
        ];
    }

    // ────────────────────────── граничные случаи ─────────────────────────

    /**
     * Ключ с нелатинскими символами и пробелами очищается перед построением матрицы.
     */
    public function testKeyWithNonAlphabetCharsIsStripped(): void
    {
        $service = new PlayfairCipherService();

        // Ключ "K E Y 123 W O R D" после очистки → "KEYWORD".
        $encClean = $service->process('HELLO', 'KEYWORD', 'en', 'encrypt');
        $encDirty = $service->process('HELLO', 'K E Y 1 2 3 W O R D!', 'en', 'encrypt');

        self::assertSame($encClean, $encDirty);
    }

    /**
     * Входной текст, состоящий только из символов вне алфавита, даёт пустую строку.
     */
    public function testInputWithOnlyNonAlphabetCharsGivesEmptyOutput(): void
    {
        $service = new PlayfairCipherService();

        self::assertSame('', $service->process('123 !!!', 'KEYWORD', 'en', 'encrypt'));
        self::assertSame('', $service->process('ñ ç Ä', 'KEY', 'en', 'encrypt'));
    }

    /**
     * При пустом ключе матрица строится в порядке букв алфавита.
     * Шифрование и дешифрование дают корректный round-trip.
     * Используем текст без двойных букв, чтобы заполнитель не вставлялся.
     */
    public function testEmptyKeyProducesValidRoundTrip(): void
    {
        $service = new PlayfairCipherService();

        // MAPS — чётная длина, нет повторяющихся соседних букв → заполнитель не нужен.
        $encrypted = $service->process('MAPS', '', 'en', 'encrypt');
        $decrypted = $service->process($encrypted, '', 'en', 'decrypt');

        self::assertSame('MAPS', $decrypted);
    }

    /**
     * Ключ в нижнем регистре эквивалентен ключу в верхнем регистре.
     */
    public function testKeyIsCaseInsensitive(): void
    {
        $service = new PlayfairCipherService();

        $upper = $service->process('HELLO', 'KEYWORD', 'en', 'encrypt');
        $lower = $service->process('HELLO', 'keyword', 'en', 'encrypt');

        self::assertSame($upper, $lower);
    }

    /**
     * Входной текст в нижнем регистре даёт тот же шифротекст, что в верхнем.
     */
    public function testInputIsCaseInsensitive(): void
    {
        $service = new PlayfairCipherService();

        $upper = $service->process('HELLO', 'KEYWORD', 'en', 'encrypt');
        $lower = $service->process('hello', 'KEYWORD', 'en', 'encrypt');

        self::assertSame($upper, $lower);
    }

    // ─────────────────────── существующие регрессии ──────────────────────

    /**
     * Биграмм OW/WO в одном столбце с неполной последней строкой матрицы.
     * До исправления: PHP Warning "Undefined array key 5".
     */
    public function testSameColumnBigramWithIncompleteLastRow(): void
    {
        $service = new PlayfairCipherService();

        $encrypted = $service->process('HELLO WORLD', 'PLAYFAIR', 'en', 'encrypt');
        self::assertNotEmpty($encrypted);

        $decrypted = $service->process($encrypted, 'PLAYFAIR', 'en', 'decrypt');
        self::assertNotEmpty($decrypted);
        self::assertSame('HELALOWORLDA', $decrypted);
    }

    /**
     * Биграмм в неполной последней строке для операции «одна строка».
     * Перенос по строке должен использовать длину именно этой строки.
     */
    public function testSameRowWrapInIncompleteLastRow(): void
    {
        $service = new PlayfairCipherService();

        $encrypted = $service->process('XZ', 'PLAYFAIR', 'en', 'encrypt');
        self::assertSame(2, mb_strlen($encrypted));

        $decrypted = $service->process($encrypted, 'PLAYFAIR', 'en', 'decrypt');
        self::assertSame('XZ', $decrypted);
    }
}
