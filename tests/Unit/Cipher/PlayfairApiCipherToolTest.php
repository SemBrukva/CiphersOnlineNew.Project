<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\PlayfairApiCipherTool;
use App\Cipher\PlayfairCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Плейфера.
 */
final class PlayfairApiCipherToolTest extends TestCase
{
    private PlayfairApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new PlayfairApiCipherTool(new PlayfairCipherService());
    }

    // ── структура инструмента ──────────────────────────────────────────────────────

    /**
     * Проверяет, что action() возвращает строку 'playfair'.
     */
    public function testActionReturnsPlayfair(): void
    {
        self::assertSame('playfair', $this->tool->action());
    }

    // ── успешные сценарии ──────────────────────────────────────────────────────────

    /**
     * Проверяет корректное шифрование с явными настройками.
     */
    public function testEncryptWithExplicitSettingsReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'KEYWORD'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertNotEmpty($result['result']);
        self::assertSame('en', $result['alphabet']);
        self::assertSame('KEYWORD', $result['key']);
        self::assertNull($result['detected_alphabet']);
    }

    /**
     * Проверяет round-trip: шифрование → расшифровка возвращает исходный текст.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'MAPS',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'KEYWORD'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'KEYWORD'],
        ]);

        self::assertSame('MAPS', $dec['result']);
    }

    /**
     * Проверяет автоопределение алфавита по кириллическому тексту и ключу.
     */
    public function testAutoDetectsAlphabetForRussianText(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto', 'key' => 'КЛЮЧ'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
    }

    /**
     * Проверяет шифрование русского текста с явным алфавитом 'ru'.
     */
    public function testEncryptRussianWithExplicitAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'КЛЮЧ'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('РСЗГМЩ', $result['result']);
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
            'settings'  => ['alphabet' => 'en', 'key' => 'KEYWORD'],
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
            'settings'  => ['alphabet' => 'en', 'key' => 'KEYWORD'],
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
     * Проверяет, что неизвестный алфавит вызывает ValidationFailedException.
     */
    public function testThrowsWhenAlphabetIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'xx', 'key' => 'KEY'],
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
            'settings'  => ['alphabet' => 'en', 'key' => '123 !!!'],
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
