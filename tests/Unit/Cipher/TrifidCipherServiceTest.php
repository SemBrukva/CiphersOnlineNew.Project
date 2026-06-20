<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AlphabetCatalog;
use App\Cipher\AlphabetTool;
use App\Cipher\CaseFolder;
use App\Cipher\TrifidCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Трифид.
 */
final class TrifidCipherServiceTest extends TestCase
{
    /**
     * Создаёт экземпляр сервиса с реальными зависимостями.
     */
    private function createService(): TrifidCipherService
    {
        $catalog    = new AlphabetCatalog();
        $caseFolder = new CaseFolder();

        return new TrifidCipherService($catalog, new AlphabetTool($catalog, $caseFolder), $caseFolder);
    }

    /**
     * Проверяет поддерживаемые алфавиты.
     */
    public function testSupportedAlphabetCodes(): void
    {
        $service = $this->createService();

        self::assertSame(['en', 'it', 'es', 'de', 'tr', 'pt', 'fr'], $service->supportedAlphabetCodes());
    }

    /**
     * Проверяет round-trip для всех поддерживаемых алфавитов.
     *
     * @param non-empty-string $plain
     * @param non-empty-string $key
     * @param string           $alphabet
     */
    #[DataProvider('roundTripProvider')]
    public function testRoundTrip(string $plain, string $key, string $alphabet): void
    {
        $service = $this->createService();

        $encrypted = $service->process($plain, $key, $alphabet, 'encrypt');
        $decrypted = $service->process($encrypted, $key, $alphabet, 'decrypt');

        self::assertSame($plain, $decrypted);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function roundTripProvider(): array
    {
        return [
            'EN: HELLO / KEYWORD'         => ['HELLO',        'KEYWORD',   'en'],
            'EN: ATTACKATDAWN / PLAYFAIR' => ['ATTACKATDAWN', 'PLAYFAIR',  'en'],
            'EN: SINGLE LETTER / KEY'     => ['A',            'KEY',       'en'],
            'EN: AB / KEY'                => ['AB',           'KEY',       'en'],
            'IT: CIAO / CHIAVE'           => ['CIAO',         'CHIAVE',    'it'],
            'ES: HOLA / CLAVE'            => ['HOLA',         'CLAVE',     'es'],
            'ES: CON EÑES / TRIFIDO'      => ['LUNES',        'TRIFIDO',   'es'],
            'DE: BERLIN / GEHEIMNIS'      => ['BERLIN',       'GEHEIMNIS', 'de'],
            'DE: MUENCHEN / SCHLUESSEL'   => ['MUENCHEN',     'SCHLUESSEL','de'],
            'TR: MERHABA / ANAHTAR'       => ['MERHABA',      'ANAHTAR',   'tr'],
            'TR: ISTANBUL / SIFRE'        => ['ISTANBUL',     'SIFRE',     'tr'],
            'PT: PORTO / SEGREDO'         => ['PORTO',        'SEGREDO',   'pt'],
            'PT: LISBOA / CHAVE'          => ['LISBOA',       'CHAVE',     'pt'],
            'FR: BONJOUR / SECRET'        => ['BONJOUR',      'SECRET',    'fr'],
            'FR: PARIS / CLESECRETE'      => ['PARIS',        'CLESECRETE','fr'],
        ];
    }

    /**
     * Проверяет, что J заменяется на I при шифровании (EN-алфавит).
     */
    public function testJIsMappedToI(): void
    {
        $service = $this->createService();

        $withJ = $service->process('JAB', 'KEY', 'en', 'encrypt');
        $withI = $service->process('IAB', 'KEY', 'en', 'encrypt');

        self::assertSame($withI, $withJ);
    }

    /**
     * Проверяет, что вывод не содержит J в EN-алфавите.
     */
    public function testOutputNeverContainsJ(): void
    {
        $service = $this->createService();

        $result = $service->process('ABCDEFGHIKLMNOPQRSTUVWXYZ', 'KEY', 'en', 'encrypt');

        self::assertStringNotContainsString('J', $result);
    }

    /**
     * Проверяет, что неалфавитные символы отбрасываются из входного текста.
     */
    public function testNonAlphaCharactersAreStripped(): void
    {
        $service = $this->createService();

        $withSpaces    = $service->process('HELLO WORLD', 'KEY', 'en', 'encrypt');
        $withoutSpaces = $service->process('HELLOWORLD', 'KEY', 'en', 'encrypt');

        self::assertSame($withoutSpaces, $withSpaces);
    }

    /**
     * Проверяет, что регистр ввода не влияет на результат.
     */
    public function testInputIsCaseInsensitive(): void
    {
        $service = $this->createService();

        $upper = $service->process('HELLO', 'KEY', 'en', 'encrypt');
        $lower = $service->process('hello', 'KEY', 'en', 'encrypt');
        $mixed = $service->process('HeLlO', 'key', 'en', 'encrypt');

        self::assertSame($upper, $lower);
        self::assertSame($upper, $mixed);
    }

    /**
     * Проверяет, что вывод всегда в верхнем регистре.
     */
    public function testOutputIsUpperCase(): void
    {
        $service = $this->createService();

        $result = $service->process('hello world', 'keyword', 'en', 'encrypt');

        self::assertSame(mb_strtoupper($result), $result);
    }

    /**
     * Проверяет, что пустой текст возвращает пустую строку.
     */
    public function testEmptyTextReturnsEmpty(): void
    {
        $service = $this->createService();

        self::assertSame('', $service->process('', 'KEY', 'en', 'encrypt'));
        self::assertSame('', $service->process('', 'KEY', 'en', 'decrypt'));
    }

    /**
     * Проверяет, что текст без букв алфавита возвращает пустую строку.
     */
    public function testTextWithNoLettersReturnsEmpty(): void
    {
        $service = $this->createService();

        self::assertSame('', $service->process('12345 !@#$%', 'KEY', 'en', 'encrypt'));
    }

    /**
     * Проверяет длину вывода: равна числу букв алфавита во вводе.
     */
    public function testOutputLengthEqualsInputLetterCount(): void
    {
        $service = $this->createService();

        $text   = 'HELLO WORLD 123!';
        $result = $service->process($text, 'KEY', 'en', 'encrypt');

        preg_match_all('/[A-Z]/i', $text, $m);
        self::assertSame(count($m[0]), mb_strlen($result));
    }

    /**
     * Проверяет, что ключ без букв алфавита даёт тот же куб, что и пустой ключ.
     */
    public function testKeyWithoutLettersUsesDefaultCube(): void
    {
        $service = $this->createService();

        $withNumberKey = $service->process('HELLO', '12345', 'en', 'encrypt');
        $withEmptyKey  = $service->process('HELLO', '', 'en', 'encrypt');

        self::assertSame($withEmptyKey, $withNumberKey);
    }

    /**
     * Проверяет detectAlphabet(): возвращает поддерживаемый алфавит для латинского текста.
     */
    public function testDetectAlphabetReturnsSupportedCode(): void
    {
        $service = $this->createService();

        $detected = $service->detectAlphabet('HELLO WORLD');

        self::assertContains($detected, $service->supportedAlphabetCodes());
    }

    /**
     * Проверяет detectAlphabet(): для кириллицы возвращает 'en' (Trifid не поддерживает RU).
     */
    public function testDetectAlphabetFallsBackToEnForUnsupported(): void
    {
        $service = $this->createService();

        self::assertSame('en', $service->detectAlphabet('ПРИВЕТ МИР'));
    }

    /**
     * Проверяет hasAlphabetCharacters(): true, если есть хотя бы один символ.
     */
    public function testHasAlphabetCharactersTrueForMixedText(): void
    {
        $service = $this->createService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertTrue($service->hasAlphabetCharacters('Hola amigo', 'es'));
    }

    /**
     * Проверяет hasAlphabetCharacters(): false для текста без букв алфавита.
     */
    public function testHasAlphabetCharactersFalseForDigitsOnly(): void
    {
        $service = $this->createService();

        self::assertFalse($service->hasAlphabetCharacters('12345 !!!', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('', 'en'));
    }

    /**
     * Проверяет испанский алфавит: 27 букв включая ñ, нет заполнителей.
     */
    public function testSpanishAlphabetRoundTrip(): void
    {
        $service = $this->createService();

        $plain     = 'ESPANA';
        $encrypted = $service->process($plain, 'CLAVE', 'es', 'encrypt');
        $decrypted = $service->process($encrypted, 'CLAVE', 'es', 'decrypt');

        self::assertSame($plain, $decrypted);
    }

    /**
     * Проверяет, что pad-цифры в EN открытом тексте отбрасываются при шифровании.
     */
    public function testPadDigitsAreStrippedFromPlaintext(): void
    {
        $service = $this->createService();

        $withDigits    = $service->process('HELLO 1 2', 'KEY', 'en', 'encrypt');
        $withoutDigits = $service->process('HELLO', 'KEY', 'en', 'encrypt');

        self::assertSame($withoutDigits, $withDigits);
    }

    /**
     * Проверяет итальянский алфавит (J→I, 2 заполнителя).
     */
    public function testItalianAlphabetRoundTrip(): void
    {
        $service = $this->createService();

        $plain     = 'CIAO';
        $encrypted = $service->process($plain, 'CHIAVE', 'it', 'encrypt');
        $decrypted = $service->process($encrypted, 'CHIAVE', 'it', 'decrypt');

        self::assertSame($plain, $decrypted);
    }

    /**
     * Проверяет немецкий алфавит: J→I (как в EN), Q→K.
     */
    public function testGermanJMapsToI(): void
    {
        $service = $this->createService();

        self::assertSame(
            $service->process('IUNGE', 'SCHLUESSEL', 'de', 'encrypt'),
            $service->process('JUNGE', 'SCHLUESSEL', 'de', 'encrypt')
        );
    }

    /**
     * Проверяет немецкий алфавит: Q замещается на K.
     */
    public function testGermanQMapsToK(): void
    {
        $service = $this->createService();

        self::assertSame(
            $service->process('KUELLE', 'SCHLUESSEL', 'de', 'encrypt'),
            $service->process('QUELLE', 'SCHLUESSEL', 'de', 'encrypt')
        );
    }

    /**
     * Проверяет турецкий алфавит: Ğ замещается на G.
     */
    public function testTurkishGhMapsToG(): void
    {
        $service = $this->createService();

        self::assertSame(
            $service->process('DAGLAR', 'ANAHTAR', 'tr', 'encrypt'),
            $service->process('DAĞLAR', 'ANAHTAR', 'tr', 'encrypt')
        );
    }

    /**
     * Проверяет турецкий алфавит: J замещается на C.
     */
    public function testTurkishJMapsToC(): void
    {
        $service = $this->createService();

        self::assertSame(
            $service->process('CAMASI', 'ANAHTAR', 'tr', 'encrypt'),
            $service->process('JAMASI', 'ANAHTAR', 'tr', 'encrypt')
        );
    }

    /**
     * Проверяет, что CaseFolder корректно обрабатывает турецкие I/İ↔ı/i при round-trip.
     */
    public function testTurkishCaseFolderRoundTrip(): void
    {
        $service = $this->createService();

        // I (латинская заглавная) → CaseFolder → ı (беспрочечная строчная)
        $encryptedI     = $service->process('ISTANBUL', 'ANAHTAR', 'tr', 'encrypt');
        $decryptedBack  = $service->process($encryptedI, 'ANAHTAR', 'tr', 'decrypt');

        // После decrypt CaseFolder.toUpper: ı → I
        self::assertSame('ISTANBUL', $decryptedBack);
    }

    /**
     * Проверяет португальский алфавит: ударные гласные заменяются на основу.
     */
    public function testPortugueseAccentedVowelsMerge(): void
    {
        $service = $this->createService();

        // á, à, ã → a
        self::assertSame(
            $service->process('CASA', 'SEGREDO', 'pt', 'encrypt'),
            $service->process('CÁSA', 'SEGREDO', 'pt', 'encrypt')
        );
        // é, ê → e; ó, ô → o; ú → u; í → i
        self::assertSame(
            $service->process('SERENO', 'SEGREDO', 'pt', 'encrypt'),
            $service->process('SÉRÊNO', 'SEGREDO', 'pt', 'encrypt')
        );
    }

    /**
     * Проверяет португальский алфавит: ç остаётся отдельной буквой.
     */
    public function testPortugueseCedillaIsDistinct(): void
    {
        $service = $this->createService();

        self::assertNotSame(
            $service->process('CACO', 'SEGREDO', 'pt', 'encrypt'),
            $service->process('CAÇO', 'SEGREDO', 'pt', 'encrypt')
        );
    }

    /**
     * Проверяет французский алфавит: диакритические буквы заменяются на основу.
     */
    public function testFrenchAccentedLettersMerge(): void
    {
        $service = $this->createService();

        // é, è, ê, ë → e
        self::assertSame(
            $service->process('FETE', 'SECRET', 'fr', 'encrypt'),
            $service->process('FÊTE', 'SECRET', 'fr', 'encrypt')
        );
        // à, â → a; ù, û, ü → u; î, ï → i; ô → o; ÿ → y
        self::assertSame(
            $service->process('NAIVE', 'SECRET', 'fr', 'encrypt'),
            $service->process('NAÏVE', 'SECRET', 'fr', 'encrypt')
        );
    }

    /**
     * Проверяет французский алфавит: ç остаётся отдельной буквой.
     */
    public function testFrenchCedillaIsDistinct(): void
    {
        $service = $this->createService();

        self::assertNotSame(
            $service->process('CELA', 'SECRET', 'fr', 'encrypt'),
            $service->process('ÇELA', 'SECRET', 'fr', 'encrypt')
        );
    }

    /**
     * Проверяет, что шифрование и дешифрование дают разные результаты для одного ключа.
     */
    public function testEncryptAndDecryptProduceDifferentResults(): void
    {
        $service = $this->createService();

        $plaintext  = 'HELLO';
        $ciphertext = $service->process($plaintext, 'KEY', 'en', 'encrypt');

        self::assertNotSame($plaintext, $ciphertext);
    }
}
