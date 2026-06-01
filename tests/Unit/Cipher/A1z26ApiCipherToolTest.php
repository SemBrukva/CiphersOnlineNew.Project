<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\A1z26ApiCipherTool;
use App\Cipher\A1z26CipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра A1Z26.
 */
final class A1z26ApiCipherToolTest extends TestCase
{
    private A1z26ApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new A1z26ApiCipherTool(new A1z26CipherService());
    }

    // ── структура инструмента ──────────────────────────────────────────────────────

    /**
     * Проверяет, что action() возвращает строку 'a1z26'.
     */
    public function testActionReturnsA1z26(): void
    {
        self::assertSame('a1z26', $this->tool->action());
    }

    // ── успешные сценарии ──────────────────────────────────────────────────────────

    /**
     * Проверяет успешное шифрование с явными настройками.
     */
    public function testEncryptWithExplicitSettingsReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'hello',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'delimiter' => 'dash'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('8-5-12-12-15', $result['result']);
        self::assertSame('en', $result['alphabet']);
        self::assertSame('dash', $result['delimiter']);
        self::assertNull($result['detected_alphabet']);
    }

    /**
     * Проверяет round-trip: зашифрованный текст корректно расшифровывается обратно.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'hello world',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'delimiter' => 'dash'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'delimiter' => 'dash'],
        ]);

        self::assertSame('hello world', $dec['result']);
    }

    /**
     * Проверяет автоопределение алфавита при шифровании кириллического текста.
     */
    public function testAutoDetectsAlphabetForEncrypt(): void
    {
        $result = $this->tool->execute([
            'text'      => 'привет',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto', 'delimiter' => 'dash'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
    }

    /**
     * Проверяет, что при direction='decrypt' + alphabet='auto' алфавит берётся из locale.
     */
    public function testAutoDecryptUsesLocaleForAlphabetSelection(): void
    {
        $result = $this->tool->execute([
            'text'      => '17-18-10',
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'auto', 'delimiter' => 'dash'],
            'locale'    => 'ru',
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
    }

    /**
     * Проверяет, что неизвестная локаль при auto-расшифровке даёт fallback на 'en'.
     */
    public function testAutoDecryptFallsBackToEnglishForUnknownLocale(): void
    {
        $result = $this->tool->execute([
            'text'      => '8-5-12',
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'auto', 'delimiter' => 'dash'],
            'locale'    => 'xx',
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('en', $result['detected_alphabet']);
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
            'settings'  => ['alphabet' => 'en', 'delimiter' => 'dash'],
        ]);
    }

    /**
     * Проверяет, что недопустимое направление вызывает ValidationFailedException.
     */
    public function testThrowsWhenDirectionIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'hello',
            'direction' => 'invalid',
            'settings'  => ['alphabet' => 'en', 'delimiter' => 'dash'],
        ]);
    }

    /**
     * Проверяет, что неизвестный алфавит вызывает ValidationFailedException.
     */
    public function testThrowsWhenAlphabetIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'hello',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'xx', 'delimiter' => 'dash'],
        ]);
    }

    /**
     * Проверяет, что недопустимый разделитель вызывает ValidationFailedException.
     */
    public function testThrowsWhenDelimiterIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'hello',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'delimiter' => 'pipe'],
        ]);
    }

    /**
     * Проверяет, что текст без символов выбранного алфавита вызывает ValidationFailedException при шифровании.
     */
    public function testThrowsWhenTextHasNoAlphabetCharactersOnEncrypt(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '123 !!!',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'delimiter' => 'dash'],
        ]);
    }

    /**
     * Проверяет, что при расшифровке проверка наличия букв алфавита не применяется.
     */
    public function testNoAlphabetCheckOnDecrypt(): void
    {
        $result = $this->tool->execute([
            'text'      => '8-5-12',
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'delimiter' => 'dash'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('hel', $result['result']);
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
                'settings'  => ['alphabet' => 'xx', 'delimiter' => 'pipe'],
            ]);
            self::fail('ValidationFailedException ожидался, но не был брошен');
        } catch (ValidationFailedException $e) {
            $errors = $e->details()['errors'] ?? [];
            self::assertArrayHasKey('direction', $errors);
            self::assertArrayHasKey('text', $errors);
            self::assertArrayHasKey('settings.alphabet', $errors);
            self::assertArrayHasKey('settings.delimiter', $errors);
        }
    }
}
