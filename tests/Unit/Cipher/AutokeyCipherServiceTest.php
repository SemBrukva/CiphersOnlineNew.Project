<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AutokeyCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Autokey.
 */
final class AutokeyCipherServiceTest extends TestCase
{
    /**
     * Проверяет канонический пример шифрования и дешифрования для английского алфавита.
     */
    public function testEncryptAndDecryptRoundTrip(): void
    {
        $service = new AutokeyCipherService();

        $encrypted = $service->process('ATTACK AT DAWN', 'QUEENLY', 'en', 'encrypt');
        self::assertSame('QNXEPV YT WTWP', $encrypted);

        $decrypted = $service->process($encrypted, 'QUEENLY', 'en', 'decrypt');
        self::assertSame('ATTACK AT DAWN', $decrypted);
    }

    /**
     * Проверяет шифрование и дешифрование для русского алфавита.
     */
    public function testProcessRussianAlphabetRoundTrip(): void
    {
        $service = new AutokeyCipherService();

        $encrypted = $service->process('привет', 'ключ', 'ru', 'encrypt');
        self::assertSame('ъьжщфг', $encrypted);

        $decrypted = $service->process($encrypted, 'ключ', 'ru', 'decrypt');
        self::assertSame('привет', $decrypted);
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
    public function testProcessRoundTripPerAlphabet(
        string $alphabet,
        string $plain,
        string $key,
        string $expectedCipher
    ): void {
        $service = new AutokeyCipherService();

        $encrypted = $service->process($plain, $key, $alphabet, 'encrypt');
        self::assertSame($expectedCipher, $encrypted, "encrypt failed for alphabet '{$alphabet}'");

        $decrypted = $service->process($encrypted, $key, $alphabet, 'decrypt');
        self::assertSame($plain, $decrypted, "round-trip failed for alphabet '{$alphabet}'");
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function perAlphabetProvider(): array
    {
        return [
            'en: hello / key'  => ['en', 'hello', 'key', 'rijss'],
            'ru: привет / ключ' => ['ru', 'привет', 'ключ', 'ъьжщфг'],
            'es: niño / sol' => ['es', 'niño', 'sol', 'fwyb'],
            'pt: maçã / sol' => ['pt', 'maçã', 'sol', 'êopó'],
            'tr: güç / taş' => ['tr', 'güç', 'taş', 'büü'],
            'fr: être / joie' => ['fr', 'être', 'joie', 'sfâh'],
            'de: über / wald' => ['de', 'über', 'wald', 'rbpü'],
            'it: ciao / key' => ['it', 'ciao', 'key', 'mmyq'],
        ];
    }

    /**
     * Проверяет сохранение регистра и пропуск небуквенных символов.
     */
    public function testPreservesCaseAndNonAlphabeticCharacters(): void
    {
        $service = new AutokeyCipherService();

        $encrypted = $service->process('Hello, World!', 'key', 'en', 'encrypt');
        self::assertSame('Rijss, Hzfhr!', $encrypted);

        $decrypted = $service->process($encrypted, 'key', 'en', 'decrypt');
        self::assertSame('Hello, World!', $decrypted);
    }

    /**
     * Проверяет, что пустая строка возвращается без изменений.
     */
    public function testEmptyStringReturnsEmpty(): void
    {
        $service = new AutokeyCipherService();

        self::assertSame('', $service->process('', 'KEY', 'en', 'encrypt'));
        self::assertSame('', $service->process('', 'KEY', 'en', 'decrypt'));
    }

    /**
     * Проверяет, что пустой ключ возвращает исходный текст.
     */
    public function testEmptyKeyReturnsOriginalText(): void
    {
        $service = new AutokeyCipherService();

        self::assertSame('HELLO', $service->process('HELLO', '', 'en', 'encrypt'));
        self::assertSame('HELLO', $service->process('HELLO', '', 'en', 'decrypt'));
    }

    /**
     * Проверяет, что небуквенные символы не сдвигают ключевой поток.
     */
    public function testKeyStreamDoesNotAdvanceOnNonAlphabeticCharacters(): void
    {
        $service = new AutokeyCipherService();

        self::assertSame('x b', $service->process('a b', 'x', 'en', 'encrypt'));
        self::assertSame('a b', $service->process('x b', 'x', 'en', 'decrypt'));
    }

    /**
     * Проверяет автоопределение алфавита для кириллицы.
     */
    public function testDetectsRussianAlphabet(): void
    {
        $service = new AutokeyCipherService();

        self::assertSame('ru', $service->detectAlphabet('Привет, мир!'));
    }

    /**
     * Проверяет наличие символов выбранного алфавита.
     */
    public function testDetectsAlphabetCharactersInInput(): void
    {
        $service = new AutokeyCipherService();

        self::assertTrue($service->hasAlphabetCharacters('Hello 123', 'en'));
        self::assertFalse($service->hasAlphabetCharacters('123 !!!', 'en'));
    }

    /**
     * Проверяет, что сервис возвращает все поддерживаемые коды алфавитов.
     */
    public function testSupportedAlphabetCodesReturnsAllLanguages(): void
    {
        $service = new AutokeyCipherService();

        foreach (['en', 'ru', 'es', 'pt', 'tr', 'fr', 'de', 'it'] as $expected) {
            self::assertContains($expected, $service->supportedAlphabetCodes());
        }
    }
}
