<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\VernamCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Вернама.
 */
final class VernamCipherServiceTest extends TestCase
{
    // ─────────────────────────── process() — основные случаи ────────────────────────────

    /**
     * Проверяет базовое шифрование и расшифровку с ASCII-текстом.
     */
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $service = new VernamCipherService();

        $encrypted = $service->process('HELLO WORLD', 'key', 'encrypt');
        self::assertNotSame('HELLO WORLD', $encrypted);

        $decrypted = $service->process($encrypted, 'key', 'decrypt');
        self::assertSame('HELLO WORLD', $decrypted);
    }

    /**
     * Проверяет round-trip для UTF-8 текстов всех восьми поддерживаемых локалей.
     *
     * Шифр Вернама работает на уровне байт, поэтому UTF-8 строки обрабатываются корректно.
     *
     * @param non-empty-string $plain
     * @param non-empty-string $key
     */
    #[DataProvider('localeRoundTripProvider')]
    public function testRoundTripAcrossLocales(string $plain, string $key): void
    {
        $service = new VernamCipherService();

        $encrypted = $service->process($plain, $key, 'encrypt');
        self::assertNotEmpty($encrypted);

        $decrypted = $service->process($encrypted, $key, 'decrypt');
        self::assertSame($plain, $decrypted);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function localeRoundTripProvider(): array
    {
        return [
            'en: ASCII sentence'     => ['Hello, World!',       'ABCDEFGHIJKLM'],
            'ru: Cyrillic text'      => ['Привет, мир!',        'КЛЮЧКЛЮЧ'],
            'de: German umlauts'     => ['Über den Dächern',    'GEHEIM'],
            'es: Spanish accents'    => ['¡Hola, niño!',        'SECRETO'],
            'fr: French accents'     => ['Être ou ne pas être', 'MOTDEPASSE'],
            'it: Italian text'       => ['Ciao, mondo!',        'CHIAVE'],
            'pt: Portuguese accents' => ['Olá, mundo!',         'CHAVE'],
            'tr: Turkish characters' => ['Merhaba, dünya!',     'ANAHTAR'],
        ];
    }

    // ─────────────────────────── process() — edge / corner cases ────────────────────────

    /**
     * Проверяет, что невалидный base64 при дешифровании возвращает пустую строку.
     */
    public function testReturnsEmptyStringForInvalidBase64OnDecrypt(): void
    {
        $service = new VernamCipherService();

        self::assertSame('', $service->process('%%%%', 'key', 'decrypt'));
    }

    /**
     * Проверяет, что пустой текст возвращает пустую строку для обоих направлений.
     */
    public function testEmptyTextReturnsEmpty(): void
    {
        $service = new VernamCipherService();

        self::assertSame('', $service->process('', 'somekey', 'encrypt'));
        self::assertSame('', $service->process('', 'somekey', 'decrypt'));
    }

    /**
     * Проверяет, что пустой ключ возвращает исходный текст без изменений.
     */
    public function testEmptyKeyReturnsTextUnchanged(): void
    {
        $service = new VernamCipherService();

        self::assertSame('HELLO', $service->process('HELLO', '', 'encrypt'));
        self::assertSame('HELLO', $service->process('HELLO', '', 'decrypt'));
        self::assertSame('Привет', $service->process('Привет', '', 'encrypt'));
    }

    /**
     * Проверяет, что результат шифрования является валидной base64-строкой.
     */
    public function testEncryptResultIsValidBase64(): void
    {
        $service = new VernamCipherService();

        $encrypted = $service->process('HELLO', 'ABCDE', 'encrypt');
        self::assertNotFalse(base64_decode($encrypted, true));
    }

    /**
     * Проверяет, что зашифрованный результат отличается от исходного текста.
     */
    public function testEncryptResultDiffersFromPlaintext(): void
    {
        $service = new VernamCipherService();

        $encrypted = $service->process('HELLO WORLD', 'SECRET', 'encrypt');
        self::assertNotSame('HELLO WORLD', $encrypted);
    }

    /**
     * Проверяет round-trip при ключе ровно той же длины в байтах, что и текст.
     *
     * Это соответствует настоящему одноразовому блокноту (OTP): ключ не повторяется.
     */
    public function testKeyEqualLengthRoundTrip(): void
    {
        $service = new VernamCipherService();
        $text = 'HELLO';
        $key  = 'ABCDE'; // 5 байт = 5 байт текста

        $encrypted = $service->process($text, $key, 'encrypt');
        self::assertSame($text, $service->process($encrypted, $key, 'decrypt'));
    }

    /**
     * Проверяет round-trip при ключе длиннее текста.
     *
     * Лишние байты ключа игнорируются — шифрование проходит корректно.
     */
    public function testKeyLongerThanTextRoundTrip(): void
    {
        $service = new VernamCipherService();
        $text = 'HI';
        $key  = 'ABCDEFGHIJKLMNOP'; // намного длиннее

        $encrypted = $service->process($text, $key, 'encrypt');
        self::assertSame($text, $service->process($encrypted, $key, 'decrypt'));
    }

    /**
     * Проверяет round-trip при ключе короче текста (ключ повторяется циклически).
     */
    public function testShortKeyCyclesAndRoundTrips(): void
    {
        $service = new VernamCipherService();
        $text = 'HELLO WORLD'; // 11 байт
        $key  = 'AB';           // 2 байта → цикличный повтор

        $encrypted = $service->process($text, $key, 'encrypt');
        self::assertSame($text, $service->process($encrypted, $key, 'decrypt'));
    }

    /**
     * Проверяет round-trip для одиночного символа.
     */
    public function testSingleByteRoundTrip(): void
    {
        $service = new VernamCipherService();

        $encrypted = $service->process('A', 'Z', 'encrypt');
        self::assertSame('A', $service->process($encrypted, 'Z', 'decrypt'));
    }

    /**
     * Проверяет, что XOR текста с тем же ключом (text = key) даёт нулевые байты.
     *
     * A(65) XOR A(65) = 0, B(66) XOR B(66) = 0, C(67) XOR C(67) = 0.
     * Зашифрованный текст — base64 от трёх нулевых байт.
     * Дешифрование возвращает исходный текст.
     */
    public function testKeyIdenticalToTextProducesNullBytes(): void
    {
        $service = new VernamCipherService();
        $text    = 'ABC';

        $encrypted = $service->process($text, $text, 'encrypt');
        $decoded   = base64_decode($encrypted, true);

        self::assertNotFalse($decoded);
        self::assertSame(3, strlen($decoded));
        foreach (str_split($decoded) as $byte) {
            self::assertSame(0, ord($byte), 'Expected zero byte from text XOR key when text === key');
        }
        self::assertSame($text, $service->process($encrypted, $text, 'decrypt'));
    }

    /**
     * Проверяет, что разные ключи дают разные зашифрованные тексты для одного и того же input.
     */
    public function testDifferentKeysProduceDifferentCiphertexts(): void
    {
        $service = new VernamCipherService();
        $text    = 'HELLO WORLD';

        $cipher1 = $service->process($text, 'AAAAA', 'encrypt');
        $cipher2 = $service->process($text, 'BBBBB', 'encrypt');

        self::assertNotSame($cipher1, $cipher2);
    }

}
