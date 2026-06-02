<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\VigenereApiCipherTool;
use App\Cipher\VigenereCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Виженера.
 */
final class VigenereApiCipherToolTest extends TestCase
{
    private VigenereApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new VigenereApiCipherTool(new VigenereCipherService());
    }

    // ── структура инструмента ──────────────────────────────────────────────────────

    /**
     * Проверяет, что action() возвращает строку 'vigenere'.
     */
    public function testActionReturnsVigenere(): void
    {
        self::assertSame('vigenere', $this->tool->action());
    }

    // ── успешные сценарии ──────────────────────────────────────────────────────────

    /**
     * Проверяет корректное шифрование с явными настройками.
     *
     * ATTACK AT DAWN / LEMON → LXFOPV EF RNHR (классический пример из Википедии).
     */
    public function testEncryptWithExplicitSettingsReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ATTACK AT DAWN',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'LEMON'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('LXFOPV EF RNHR', $result['result']);
        self::assertSame('en', $result['alphabet']);
        self::assertSame('LEMON', $result['key']);
        self::assertNull($result['detected_alphabet']);
    }

    /**
     * Проверяет round-trip: шифрование → расшифровка.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'ATTACK AT DAWN',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'LEMON'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'LEMON'],
        ]);

        self::assertSame('ATTACK AT DAWN', $dec['result']);
    }

    /**
     * Проверяет автоопределение алфавита по тексту и ключу.
     */
    public function testAutoDetectsAlphabetForRussianText(): void
    {
        $result = $this->tool->execute([
            'text'      => 'привет',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto', 'key' => 'ключ'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
    }

    /**
     * Проверяет шифрование русского текста с явным алфавитом 'ru'.
     *
     * привет / ключ: п(16)+к(11)=27=ъ; р(17)+л(12)=29=ь; и(9)+ю(31)=7=ж; в(2)+ч(24)=26=щ; е(5)+к(11)=16=п; т(19)+л(12)=31=ю
     */
    public function testEncryptRussianWithExplicitAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'привет',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'ключ'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ъьжщпю', $result['result']);
    }

    // ── валидация ──────────────────────────────────────────────────────────────────

    /**
     * Проверяет, что пустой текст вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'LEMON'],
        ]);
    }

    /**
     * Проверяет, что недопустимое направление вызывает ValidationFailedException.
     */
    public function testThrowsWhenDirectionIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'bad',
            'settings'  => ['alphabet' => 'en', 'key' => 'LEMON'],
        ]);
    }

    /**
     * Проверяет, что пустой ключ вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => ''],
        ]);
    }

    /**
     * Проверяет, что ключ длиннее текста вызывает ValidationFailedException.
     *
     * В VigenereApiCipherTool: если mb_strlen(text) < mb_strlen(key) — ошибка.
     */
    public function testThrowsWhenKeyIsLongerThanText(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HI',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'VERYLONGKEY'],
        ]);
    }

    /**
     * Проверяет, что неизвестный алфавит вызывает ValidationFailedException.
     */
    public function testThrowsWhenAlphabetIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'xx', 'key' => 'LEMON'],
        ]);
    }

    /**
     * Проверяет, что текст без символов выбранного алфавита вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextHasNoAlphabetCharacters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '123 !!!',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'KEY'],
        ]);
    }

    /**
     * Проверяет, что ключ без символов выбранного алфавита вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyHasNoAlphabetCharacters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => '12345'],
        ]);
    }

    /**
     * Проверяет, что при множественных ошибках все поля присутствуют в details.
     */
    public function testValidationErrorsContainAllInvalidFields(): void
    {
        try {
            $this->tool->execute([
                'text'      => '',
                'direction' => 'bad',
                'settings'  => ['alphabet' => 'xx', 'key' => ''],
            ]);
            self::fail('ValidationFailedException ожидался');
        } catch (ValidationFailedException $e) {
            $errors = $e->details()['errors'] ?? [];
            self::assertArrayHasKey('direction', $errors);
            self::assertArrayHasKey('text', $errors);
            self::assertArrayHasKey('settings.alphabet', $errors);
            self::assertArrayHasKey('settings.key', $errors);
        }
    }
}
