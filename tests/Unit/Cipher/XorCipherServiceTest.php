<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\XorCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса XOR-шифра.
 */
final class XorCipherServiceTest extends TestCase
{
    // ─────────────────────────── process() — основные случаи ────────────────────────────

    /**
     * Проверяет базовый round-trip: шифрование и последующее дешифрование возвращает исходный текст.
     */
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $service = new XorCipherService();

        $encrypted = $service->process('HELLO', 'KEY', 'encrypt');
        self::assertSame('030015070A', $encrypted);

        $decrypted = $service->process($encrypted, 'KEY', 'decrypt');
        self::assertSame('HELLO', $decrypted);
    }

    /**
     * Проверяет, что зашифрованный результат — валидная uppercase hex-строка.
     */
    public function testEncryptOutputIsUppercaseHex(): void
    {
        $service   = new XorCipherService();
        $encrypted = $service->process('HELLO WORLD', 'key', 'encrypt');

        self::assertMatchesRegularExpression('/^[0-9A-F]+$/', $encrypted);
    }

    /**
     * Проверяет round-trip для UTF-8 строк различных языков.
     *
     * XOR работает на уровне байт, поэтому корректно обрабатывает многобайтовые символы.
     *
     * @param non-empty-string $plain
     * @param non-empty-string $key
     */
    #[DataProvider('localeRoundTripProvider')]
    public function testRoundTripAcrossLocales(string $plain, string $key): void
    {
        $service = new XorCipherService();

        $encrypted = $service->process($plain, $key, 'encrypt');
        self::assertNotEmpty($encrypted);
        self::assertMatchesRegularExpression('/^[0-9A-F]+$/', $encrypted);

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
            'ru: Cyrillic text'      => ['Привет, мир!',        'КЛЮЧ'],
            'de: German umlauts'     => ['Über den Dächern',    'GEHEIM'],
            'es: Spanish accents'    => ['¡Hola, niño!',        'SECRETO'],
            'fr: French accents'     => ['Être ou ne pas être', 'MOTDEPASSE'],
            'it: Italian text'       => ['Ciao, mondo!',        'CHIAVE'],
            'pt: Portuguese accents' => ['Olá, mundo!',         'CHAVE'],
            'tr: Turkish characters' => ['Merhaba, dünya!',     'ANAHTAR'],
        ];
    }

    // ─────────────────────────── XOR-свойства ───────────────────────────────────────────

    /**
     * Проверяет, что XOR является инволюцией: encrypt(encrypt(text, key), key) == text.
     */
    public function testXorIsInvolution(): void
    {
        $service = new XorCipherService();
        $text    = 'HELLO WORLD';
        $key     = 'SECRET';

        $encrypted = $service->process($text, $key, 'encrypt');
        // Для дешифрования передаём hex-строку
        $decrypted = $service->process($encrypted, $key, 'decrypt');
        self::assertSame($text, $decrypted);
    }

    /**
     * Проверяет корректный XOR результат для конкретного примера: HELLO XOR KEY.
     */
    public function testKnownOutputHelloXorKey(): void
    {
        $service = new XorCipherService();

        // H(72)^K(75)=03, E(69)^E(69)=00, L(76)^Y(89)=15, L(76)^K(75)=07, O(79)^E(69)=0A
        self::assertSame('030015070A', $service->process('HELLO', 'KEY', 'encrypt'));
    }

    /**
     * Проверяет, что XOR с тем же текстом как ключом даёт нулевые байты (в hex — нули).
     */
    public function testKeyIdenticalToTextProducesZeroBytes(): void
    {
        $service = new XorCipherService();

        $encrypted = $service->process('ABC', 'ABC', 'encrypt');
        self::assertSame('000000', $encrypted);
    }

    /**
     * Проверяет, что разные ключи дают разные шифртексты для одного и того же входа.
     */
    public function testDifferentKeysProduceDifferentCiphertexts(): void
    {
        $service = new XorCipherService();
        $text    = 'HELLO WORLD';

        $cipher1 = $service->process($text, 'KEY1', 'encrypt');
        $cipher2 = $service->process($text, 'KEY2', 'encrypt');

        self::assertNotSame($cipher1, $cipher2);
    }

    // ─────────────────────────── дешифрование — форматы hex ────────────────────────────

    /**
     * Проверяет, что дешифрование принимает hex с пробелами (очищает их).
     */
    public function testDecryptToleratesSpacesInHex(): void
    {
        $service   = new XorCipherService();
        $encrypted = $service->process('HELLO', 'KEY', 'encrypt'); // 030015070A

        // С пробелами
        $withSpaces = implode(' ', str_split($encrypted, 2));
        self::assertSame('HELLO', $service->process($withSpaces, 'KEY', 'decrypt'));
    }

    /**
     * Проверяет, что невалидный hex при дешифровании возвращает пустую строку.
     */
    public function testDecryptReturnsEmptyForInvalidHex(): void
    {
        $service = new XorCipherService();

        self::assertSame('', $service->process('ZZZZ', 'KEY', 'decrypt'));
    }

    /**
     * Проверяет, что hex нечётной длины при дешифровании возвращает пустую строку.
     */
    public function testDecryptReturnsEmptyForOddLengthHex(): void
    {
        $service = new XorCipherService();

        self::assertSame('', $service->process('03001', 'KEY', 'decrypt'));
    }

    // ─────────────────────────── edge cases ─────────────────────────────────────────────

    /**
     * Проверяет, что пустой ключ возвращает текст без изменений.
     */
    public function testEmptyKeyReturnsTextUnchanged(): void
    {
        $service = new XorCipherService();

        self::assertSame('HELLO', $service->process('HELLO', '', 'encrypt'));
        self::assertSame('HELLO', $service->process('HELLO', '', 'decrypt'));
    }

    /**
     * Проверяет round-trip при ключе короче текста (ключ повторяется).
     */
    public function testShortKeyCyclesAndRoundTrips(): void
    {
        $service = new XorCipherService();
        $text    = 'HELLO WORLD';
        $key     = 'AB';

        $encrypted = $service->process($text, $key, 'encrypt');
        self::assertSame($text, $service->process($encrypted, $key, 'decrypt'));
    }

    /**
     * Проверяет round-trip при ключе длиннее текста (лишние байты игнорируются).
     */
    public function testKeyLongerThanTextRoundTrips(): void
    {
        $service = new XorCipherService();
        $text    = 'HI';
        $key     = 'AVERYLONGKEYINDEED';

        $encrypted = $service->process($text, $key, 'encrypt');
        self::assertSame($text, $service->process($encrypted, $key, 'decrypt'));
    }

    /**
     * Проверяет round-trip для одиночного символа.
     */
    public function testSingleByteRoundTrip(): void
    {
        $service = new XorCipherService();

        $encrypted = $service->process('A', 'Z', 'encrypt');
        self::assertSame('A', $service->process($encrypted, 'Z', 'decrypt'));
    }

    // ─────────────────────────── hex-ключ ───────────────────────────────────────────────

    /**
     * Проверяет round-trip с hex-ключом: один байт 0x37.
     *
     * H(72)^0x37(55)=0111 1111=0x7F, E(69)^0x37=0101 0010=0x52, ...
     */
    public function testHexKeyRoundTrip(): void
    {
        $service   = new XorCipherService();
        $text      = 'HELLO';
        $hexKey    = '37'; // один байт 0x37

        $encrypted = $service->process($text, $hexKey, 'encrypt', 'hex');
        self::assertMatchesRegularExpression('/^[0-9A-F]+$/', $encrypted);

        $decrypted = $service->process($encrypted, $hexKey, 'decrypt', 'hex');
        self::assertSame($text, $decrypted);
    }

    /**
     * Проверяет round-trip с многобайтовым hex-ключом (DEADBEEF).
     */
    public function testMultiByteHexKeyRoundTrip(): void
    {
        $service = new XorCipherService();
        $text    = 'ATTACK AT DAWN';

        $encrypted = $service->process($text, 'DEADBEEF', 'encrypt', 'hex');
        $decrypted = $service->process($encrypted, 'DEADBEEF', 'decrypt', 'hex');
        self::assertSame($text, $decrypted);
    }

    /**
     * Проверяет, что hex-ключ с пробелами (DE AD BE EF) корректно обрабатывается.
     */
    public function testHexKeyWithSpacesIsNormalized(): void
    {
        $service = new XorCipherService();
        $text    = 'HELLO';

        $enc1 = $service->process($text, 'DEADBEEF',    'encrypt', 'hex');
        $enc2 = $service->process($text, 'DE AD BE EF', 'encrypt', 'hex');
        self::assertSame($enc1, $enc2);
    }

    /**
     * Проверяет, что hex-ключ нечётной длины (после очистки) возвращает пустой результат.
     */
    public function testOddLengthHexKeyReturnsEmpty(): void
    {
        $service = new XorCipherService();

        self::assertSame('', $service->process('HELLO', 'DEA', 'encrypt', 'hex'));
    }

    /**
     * Проверяет resolveKeyBytes: text-формат возвращает строку как есть.
     */
    public function testResolveKeyBytesTextFormat(): void
    {
        $service = new XorCipherService();

        self::assertSame('KEY', $service->resolveKeyBytes('KEY', 'text'));
    }

    /**
     * Проверяет resolveKeyBytes: hex-формат декодирует пары в байты.
     */
    public function testResolveKeyBytesHexFormat(): void
    {
        $service = new XorCipherService();

        // 41 = 'A', 42 = 'B'
        self::assertSame("\x41\x42", $service->resolveKeyBytes('4142', 'hex'));
        self::assertSame("\xDE\xAD\xBE\xEF", $service->resolveKeyBytes('DEADBEEF', 'hex'));
    }
}
