<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AlphabetCatalog;
use App\Cipher\AlphabetTool;
use App\Cipher\BifidCipherService;
use App\Cipher\CaseFolder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Бифид.
 */
final class BifidCipherServiceTest extends TestCase
{
    /**
     * Создаёт экземпляр сервиса с реальными зависимостями.
     */
    private function createService(): BifidCipherService
    {
        $catalog    = new AlphabetCatalog();
        $caseFolder = new CaseFolder();

        return new BifidCipherService($catalog, new AlphabetTool($catalog, $caseFolder), $caseFolder);
    }

    /**
     * Проверяет канонический пример: HELLO → FHYCZ с ключом KEYWORD (алфавит EN).
     */
    public function testEncryptHelloWithKeywordKey(): void
    {
        $service = $this->createService();

        self::assertSame('FHYCZ', $service->process('HELLO', 'KEYWORD', 'en', 'encrypt'));
    }

    /**
     * Проверяет дешифровку FHYCZ → HELLO с ключом KEYWORD (алфавит EN).
     */
    public function testDecryptFhyczToHello(): void
    {
        $service = $this->createService();

        self::assertSame('HELLO', $service->process('FHYCZ', 'KEYWORD', 'en', 'decrypt'));
    }

    /**
     * Проверяет round-trip: шифрование и последующая расшифровка дают оригинал.
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
            'EN: HELLO / SECRET'          => ['HELLO',        'SECRET',    'en'],
            'EN: SINGLE / A'              => ['SINGLE',       'A',         'en'],
            'EN: AB / KEY'                => ['AB',           'KEY',       'en'],
            'EN: SINGLE LETTER / KEY'     => ['A',            'KEY',       'en'],
            'IT: CIAO / CHIAVE'           => ['CIAO',         'CHIAVE',    'it'],
            'PT: ATAQUE / SEGREDO'        => ['ATAQUE',       'SEGREDO',   'pt'],
            'RU: ПРИВЕТ / КЛЮЧ'          => ['ПРИВЕТ',       'КЛЮЧ',      'ru'],
            'DE: HALLO / SCHLUESSEL'      => ['HALLO',        'SCHLUESSEL','de'],
            'ES: HOLA / CLAVE'            => ['HOLA',         'CLAVE',     'es'],
            'FR: BONJOUR / CLEF'          => ['BONJOUR',      'CLEF',      'fr'],
            'TR: MERHABA / ANAHTAR'       => ['MERHABA',      'ANAHTAR',   'tr'],
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
     * Проверяет, что вывод не содержит J (J→I в EN-квадрате).
     */
    public function testOutputNeverContainsJ(): void
    {
        $service = $this->createService();

        $result = $service->process('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'KEY', 'en', 'encrypt');

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
     * Проверяет hasAlphabetCharacters(): true, если есть хотя бы один символ алфавита.
     */
    public function testHasAlphabetCharactersTrueForMixedText(): void
    {
        $service = $this->createService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertTrue($service->hasAlphabetCharacters('abc', 'en'));
        self::assertTrue($service->hasAlphabetCharacters('Ataque', 'pt'));
    }

    /**
     * Проверяет hasAlphabetCharacters(): false, если нет ни одного символа алфавита.
     */
    public function testHasAlphabetCharactersFalseForDigitsOnly(): void
    {
        $service = $this->createService();

        self::assertFalse($service->hasAlphabetCharacters('12345 !!!', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('', 'en'));
    }

    /**
     * Проверяет, что ключ без букв алфавита даёт тот же квадрат, что и пустой ключ.
     */
    public function testKeyWithoutLettersUsesDefaultSquare(): void
    {
        $service = $this->createService();

        $withNumberKey = $service->process('HELLO', '12345', 'en', 'encrypt');
        $withEmptyKey  = $service->process('HELLO', '', 'en', 'encrypt');

        self::assertSame($withEmptyKey, $withNumberKey);
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
     * Проверяет стандартный пример из документации: FLEEATAWNHERE с известным ключом.
     *
     * Ключ: BGWKZQPNDSIOAXEFCLUMTHYVR → квадрат из классической литературы.
     * Результат FLEEATAWNHERE → UAEISEIEYWASR.
     */
    public function testClassicLiteratureExample(): void
    {
        $service = $this->createService();

        $encrypted = $service->process('FLEEATAWNHERE', 'BGWKZQPNDSIOAXEFCLUMTHYVR', 'en', 'encrypt');
        self::assertSame('UAEISEIEYWASR', $encrypted);

        $decrypted = $service->process($encrypted, 'BGWKZQPNDSIOAXEFCLUMTHYVR', 'en', 'decrypt');
        self::assertSame('FLEEATAWNHERE', $decrypted);
    }

    /**
     * Проверяет поддерживаемые алфавиты.
     */
    public function testSupportedAlphabetCodes(): void
    {
        $service = $this->createService();

        self::assertSame(['en', 'it', 'pt', 'ru', 'de', 'es', 'tr', 'fr'], $service->supportedAlphabetCodes());
    }

    /**
     * Проверяет detectAlphabet(): возвращает поддерживаемый алфавит для латинского текста.
     */
    public function testDetectAlphabetReturnsSupportedCodeForLatinText(): void
    {
        $service = $this->createService();

        $detected = $service->detectAlphabet('HELLO WORLD');

        self::assertContains($detected, $service->supportedAlphabetCodes());
    }

    /**
     * Проверяет detectAlphabet(): возвращает 'ru' для кириллического текста.
     */
    public function testDetectAlphabetReturnsRuForCyrillicText(): void
    {
        $service = $this->createService();

        self::assertSame('ru', $service->detectAlphabet('ПРИВЕТ МИР'));
    }

    /**
     * Проверяет round-trip для португальского алфавита (6×6 квадрат).
     */
    public function testPortugueseAlphabetRoundTrip(): void
    {
        $service = $this->createService();

        $plaintext = 'ATACAR';
        $encrypted = $service->process($plaintext, 'CHAVE', 'pt', 'encrypt');
        $decrypted = $service->process($encrypted, 'CHAVE', 'pt', 'decrypt');

        self::assertSame($plaintext, $decrypted);
    }

    /**
     * Проверяет round-trip для русского алфавита (6×6 квадрат, 33+3 заполнителя).
     */
    public function testRussianAlphabetRoundTrip(): void
    {
        $service = $this->createService();

        $plaintext = 'ПРИВЕТ';
        $encrypted = $service->process($plaintext, 'КЛЮЧ', 'ru', 'encrypt');
        $decrypted = $service->process($encrypted, 'КЛЮЧ', 'ru', 'decrypt');

        self::assertSame($plaintext, $decrypted);
    }

    /**
     * Проверяет, что шифротекст расширенных алфавитов может содержать цифры-заполнители.
     */
    public function testCiphertextMayContainPaddingDigits(): void
    {
        $service = $this->createService();

        // При tr-алфавите (29+7=36) координаты могут попасть на цифровые позиции
        $enc = $service->process('MERHABA', 'ANAHTAR', 'tr', 'encrypt');

        // Расшифровка должна дать исходный текст без цифр
        $dec = $service->process($enc, 'ANAHTAR', 'tr', 'decrypt');

        self::assertSame('MERHABA', $dec);
        self::assertDoesNotMatchRegularExpression('/\d/', $dec);
    }

    /**
     * Проверяет, что pad-цифры в открытом тексте отбрасываются при шифровании
     * (русский алфавит: '1','2','3' — заполнители).
     */
    public function testPadDigitsAreStrippedFromPlaintextForRussian(): void
    {
        $service = $this->createService();

        $withDigits    = $service->process('ПРИВЕТ 1 2 3', 'КЛЮЧ', 'ru', 'encrypt');
        $withoutDigits = $service->process('ПРИВЕТ', 'КЛЮЧ', 'ru', 'encrypt');

        self::assertSame($withoutDigits, $withDigits);
    }

    /**
     * Проверяет, что текст из одних pad-цифр даёт пустой результат для языков с pad.
     */
    public function testPlaintextOfOnlyPadDigitsReturnsEmptyForPadLanguages(): void
    {
        $service = $this->createService();

        self::assertSame('', $service->process('1 2 3', 'КЛЮЧ', 'ru', 'encrypt'));
        self::assertSame('', $service->process('1234567', 'SCHLUESSEL', 'de', 'encrypt'));
        self::assertSame('', $service->process('123456789', 'CLAVE', 'es', 'encrypt'));
        self::assertSame('', $service->process('1234567', 'ANAHTAR', 'tr', 'encrypt'));
        self::assertSame('', $service->process('123456789', 'CLEF', 'fr', 'encrypt'));
    }

    /**
     * Проверяет, что ключ из одних pad-цифр для русского алфавита эквивалентен пустому ключу.
     */
    public function testKeyWithOnlyPadDigitsUsesDefaultSquareForRussian(): void
    {
        $service = $this->createService();

        $withDigitKey = $service->process('ПРИВЕТ', '123', 'ru', 'encrypt');
        $withEmptyKey = $service->process('ПРИВЕТ', '', 'ru', 'encrypt');

        self::assertSame($withEmptyKey, $withDigitKey);
    }

    /**
     * Проверяет, что расшифрованный текст не содержит pad-цифр
     * (т.к. они были отброшены при шифровании). Турецкий покрыт через CaseFolder.
     */
    public function testDecryptOutputDoesNotContainPadDigits(): void
    {
        $service = $this->createService();

        $cases = [
            'ru' => ['ПРИВЕТМИРОВЫЕЛИДЕРЫ',    'КЛЮЧ'],
            'de' => ['ATTACKATDAWNRENDEZVOUS', 'SCHLUESSEL'],
            'es' => ['ATTACKATDAWNRENDEZVOUS', 'CLAVE'],
            'fr' => ['ATTACKATDAWNRENDEZVOUS', 'CLEF'],
            'tr' => ['ATTACKATDANRENDEZVOUS', 'ANAHTAR'],
        ];

        foreach ($cases as $alphabet => [$plain, $key]) {
            $enc = $service->process($plain, $key, $alphabet, 'encrypt');
            $dec = $service->process($enc, $key, $alphabet, 'decrypt');

            self::assertSame($plain, $dec, "Round-trip mismatch for $alphabet");
            self::assertDoesNotMatchRegularExpression('/\d/', $dec, "Pad digits leaked into decrypt output for $alphabet");
        }
    }

    /**
     * Проверяет round-trip турецкого текста с парами 'İ'/'I'/'ı'/'i':
     * благодаря CaseFolder различение dotted/dotless сохраняется в обе стороны.
     */
    public function testTurkishRoundTripPreservesDottedAndDotlessI(): void
    {
        $service = $this->createService();

        $plain = 'İSTANBULIRMAKİYİ';
        $enc   = $service->process($plain, 'ANAHTAR', 'tr', 'encrypt');
        $dec   = $service->process($enc, 'ANAHTAR', 'tr', 'decrypt');

        self::assertSame($plain, $dec);
    }

    /**
     * Проверяет, что турецкий ключ 'İPEK' (с заглавной точечной 'İ')
     * эквивалентен ключу 'ipek' — обе формы дают одинаковый квадрат.
     */
    public function testTurkishKeyWithDottedCapitalI(): void
    {
        $service = $this->createService();

        $withCapital = $service->process('MERHABA', 'İPEK', 'tr', 'encrypt');
        $withLower   = $service->process('MERHABA', 'ipek', 'tr', 'encrypt');

        self::assertSame($withLower, $withCapital);
    }

    /**
     * Проверяет, что pad-цифры в шифротексте обрабатываются как валидные символы алфавита
     * при дешифровке (восстановление координат с учётом позиций цифр в квадрате).
     */
    public function testDecryptAcceptsPadDigitsInCiphertext(): void
    {
        $service = $this->createService();

        // Длинный текст, чтобы повысить вероятность появления pad-цифр в шифре
        $plain = 'ПРИВЕТМИРОВЫЕЛИДЕРЫСОБРАЛИСЬ';
        $enc   = $service->process($plain, 'КЛЮЧ', 'ru', 'encrypt');

        // Sanity-check: в выбранном примере pad-цифры действительно появились
        self::assertMatchesRegularExpression('/\d/', $enc, 'Ожидаются pad-цифры в шифре для проверки сценария');

        $dec = $service->process($enc, 'КЛЮЧ', 'ru', 'decrypt');
        self::assertSame($plain, $dec);
    }

    /**
     * Проверяет, что все 8 языков возвращают корректный round-trip.
     */
    public function testAllLanguagesRoundTrip(): void
    {
        $service = $this->createService();

        $cases = [
            'en' => ['HELLO',   'KEYWORD'],
            'it' => ['CIAO',    'CHIAVE'],
            'pt' => ['ATACAR',  'CHAVE'],
            'ru' => ['ПРИВЕТ',  'КЛЮЧ'],
            'de' => ['HALLO',   'SCHLUESSEL'],
            'es' => ['HOLA',    'CLAVE'],
            'fr' => ['BONJOUR', 'CLEF'],
            'tr' => ['MERHABA', 'ANAHTAR'],
        ];

        foreach ($cases as $alphabet => [$plain, $key]) {
            $enc = $service->process($plain, $key, $alphabet, 'encrypt');
            $dec = $service->process($enc, $key, $alphabet, 'decrypt');

            self::assertSame($plain, $dec, "Round-trip failed for alphabet: $alphabet");
        }
    }
}
