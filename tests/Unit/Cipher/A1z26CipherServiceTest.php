<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\A1z26CipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра A1Z26.
 */
final class A1z26CipherServiceTest extends TestCase
{
    // ── разделители ────────────────────────────────────────────────────────────────

    /**
     * Проверяет шифрование и расшифровку с разделителем dash.
     */
    public function testEncryptAndDecryptWithDashDelimiter(): void
    {
        $service = new A1z26CipherService();

        $encrypted = $service->process('hello world', 'en', 'encrypt', 'dash');
        self::assertSame('8-5-12-12-15 23-15-18-12-4', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt', 'dash');
        self::assertSame('hello world', $decrypted);
    }

    /**
     * Проверяет шифрование и расшифровку с разделителем space.
     */
    public function testEncryptAndDecryptWithSpaceDelimiter(): void
    {
        $service = new A1z26CipherService();

        $encrypted = $service->process('hi', 'en', 'encrypt', 'space');
        self::assertSame('8 9', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt', 'space');
        self::assertSame('hi', $decrypted);
    }

    /**
     * Проверяет шифрование и расшифровку с разделителем comma.
     */
    public function testEncryptAndDecryptWithCommaDelimiter(): void
    {
        $service = new A1z26CipherService();

        $encrypted = $service->process('hello world', 'en', 'encrypt', 'comma');
        self::assertSame('8,5,12,12,15 23,15,18,12,4', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt', 'comma');
        self::assertSame('hello world', $decrypted);
    }

    /**
     * Проверяет шифрование и расшифровку с разделителем slash.
     */
    public function testEncryptAndDecryptWithSlashDelimiter(): void
    {
        $service = new A1z26CipherService();

        $encrypted = $service->process('hello world', 'en', 'encrypt', 'slash');
        self::assertSame('8/5/12/12/15 23/15/18/12/4', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt', 'slash');
        self::assertSame('hello world', $decrypted);
    }

    /**
     * Проверяет шифрование и расшифровку с разделителем dot.
     */
    public function testEncryptAndDecryptWithDotDelimiter(): void
    {
        $service = new A1z26CipherService();

        $encrypted = $service->process('hello world', 'en', 'encrypt', 'dot');
        self::assertSame('8.5.12.12.15 23.15.18.12.4', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt', 'dot');
        self::assertSame('hello world', $decrypted);
    }

    // ── все алфавиты ───────────────────────────────────────────────────────────────

    /**
     * Проверяет шифрование и round-trip расшифровку для каждого поддерживаемого алфавита.
     *
     * @param non-empty-string $alphabet
     * @param non-empty-string $plain
     * @param non-empty-string $expectedCipher
     */
    #[DataProvider('allAlphabetsProvider')]
    public function testEncryptAndDecryptRoundTripPerAlphabet(
        string $alphabet,
        string $plain,
        string $expectedCipher
    ): void {
        $service = new A1z26CipherService();

        $encrypted = $service->process($plain, $alphabet, 'encrypt', 'dash');
        self::assertSame($expectedCipher, $encrypted, "encrypt не прошёл для алфавита '{$alphabet}'");

        $decrypted = $service->process($encrypted, $alphabet, 'decrypt', 'dash');
        self::assertSame($plain, $decrypted, "decrypt round-trip не прошёл для алфавита '{$alphabet}'");
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function allAlphabetsProvider(): array
    {
        return [
            // EN: h=8, e=5, l=12, l=12, o=15
            'en: hello'  => ['en', 'hello', '8-5-12-12-15'],
            // RU: п=17, р=18, и=10, в=3, е=6, т=20
            'ru: привет' => ['ru', 'привет', '17-18-10-3-6-20'],
            // ES (27 букв с ñ): n=14, i=9, ñ=15, o=16
            'es: niño'   => ['es', 'niño', '14-9-15-16'],
            // PT (36 букв с диакритикой): m=20, a=1, ç=7, ã=4
            'pt: maçã'   => ['pt', 'maçã', '20-1-7-4'],
            // TR (29 букв): ç=4, a=1, y=28
            'tr: çay'    => ['tr', 'çay', '4-1-28'],
            // FR (40 букв): ê=11, t=30, r=28, e=8
            'fr: être'   => ['fr', 'être', '11-30-28-8'],
            // DE (29 букв с умляутами): ü=24, b=3, e=6, r=20
            'de: über'   => ['de', 'über', '24-3-6-20'],
            // IT (26 букв, совпадает с EN): c=3, i=9, a=1, o=15
            'it: ciao'   => ['it', 'ciao', '3-9-1-15'],
        ];
    }

    // ── регистр ────────────────────────────────────────────────────────────────────

    /**
     * Проверяет, что входной текст приводится к нижнему регистру перед шифрованием.
     */
    public function testUppercaseInputIsNormalizedBeforeEncrypt(): void
    {
        $service = new A1z26CipherService();

        self::assertSame('8-5-12-12-15', $service->process('HELLO', 'en', 'encrypt', 'dash'));
    }

    // ── пустая строка ──────────────────────────────────────────────────────────────

    /**
     * Проверяет, что шифрование пустой строки возвращает пустую строку.
     */
    public function testEmptyStringEncryptReturnsEmpty(): void
    {
        $service = new A1z26CipherService();

        self::assertSame('', $service->process('', 'en', 'encrypt', 'dash'));
    }

    /**
     * Проверяет, что расшифровка пустой строки возвращает пустую строку.
     */
    public function testEmptyStringDecryptReturnsEmpty(): void
    {
        $service = new A1z26CipherService();

        self::assertSame('', $service->process('', 'en', 'decrypt', 'dash'));
    }

    // ── небуквенные символы ────────────────────────────────────────────────────────

    /**
     * Проверяет, что символы вне алфавита сохраняются при шифровании и расшифровке.
     */
    public function testNonAlphabetCharactersPreservedDuringEncryptAndDecrypt(): void
    {
        $service = new A1z26CipherService();

        // '!' не входит в алфавит — должен пройти насквозь без изменений
        $encrypted = $service->process('hi!', 'en', 'encrypt', 'dash');
        self::assertSame('8-9-!', $encrypted);

        $decrypted = $service->process($encrypted, 'en', 'decrypt', 'dash');
        self::assertSame('hi!', $decrypted);
    }

    // ── индекс вне диапазона при расшифровке ──────────────────────────────────────

    /**
     * Проверяет, что индекс вне диапазона алфавита при расшифровке заменяется на (index-1).
     *
     * EN содержит индексы 0–25 (26 букв). Число 27 даёт index=26, которого нет → возвращается '26'.
     */
    public function testOutOfRangeIndexInDecryptReturnsFallbackNumber(): void
    {
        $service = new A1z26CipherService();

        self::assertSame('26', $service->process('27', 'en', 'decrypt', 'dash'));
    }

    // ── supportedAlphabetCodes ─────────────────────────────────────────────────────

    /**
     * Проверяет, что сервис возвращает все 8 поддерживаемых кодов алфавитов.
     */
    public function testSupportedAlphabetCodesReturnsAllEightLanguages(): void
    {
        $service = new A1z26CipherService();

        $codes = $service->supportedAlphabetCodes();
        self::assertCount(8, $codes);

        foreach (['en', 'ru', 'es', 'pt', 'tr', 'fr', 'de', 'it'] as $expected) {
            self::assertContains($expected, $codes, "Ожидался код '{$expected}' в списке поддерживаемых алфавитов");
        }
    }

    // ── detectAlphabet ─────────────────────────────────────────────────────────────

    /**
     * Проверяет определение кириллического алфавита (только в 'ru').
     */
    public function testDetectsRussianAlphabetByCyrillicCharacters(): void
    {
        $service = new A1z26CipherService();

        self::assertSame('ru', $service->detectAlphabet('Привет, мир!'));
    }

    /**
     * Проверяет, что для пустой строки detectAlphabet возвращает 'en'.
     */
    public function testDetectsEnglishForEmptyString(): void
    {
        $service = new A1z26CipherService();

        self::assertSame('en', $service->detectAlphabet(''));
    }

    /**
     * Проверяет, что для текста без букв detectAlphabet возвращает 'en'.
     */
    public function testDetectsEnglishForNonAlphabeticText(): void
    {
        $service = new A1z26CipherService();

        self::assertSame('en', $service->detectAlphabet('123 !@#'));
    }

    // ── hasAlphabetCharacters ──────────────────────────────────────────────────────

    /**
     * Проверяет положительное обнаружение символов алфавита в тексте.
     */
    public function testHasAlphabetCharactersReturnsTrueForMatchingAlphabet(): void
    {
        $service = new A1z26CipherService();

        self::assertTrue($service->hasAlphabetCharacters('hello 123', 'en'));
        self::assertTrue($service->hasAlphabetCharacters('привет', 'ru'));
    }

    /**
     * Проверяет, что символы одного алфавита не определяются в несовместимом алфавите.
     */
    public function testHasAlphabetCharactersReturnsFalseForMismatch(): void
    {
        $service = new A1z26CipherService();

        // Цифры и знаки не входят ни в один алфавит
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
        // Кириллица не входит в английский алфавит
        self::assertFalse($service->hasAlphabetCharacters('привет', 'en'));
    }
}
