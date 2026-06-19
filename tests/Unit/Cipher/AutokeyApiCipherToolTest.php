<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AutokeyApiCipherTool;
use App\Cipher\AutokeyCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Autokey.
 */
final class AutokeyApiCipherToolTest extends TestCase
{
    private AutokeyApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new AutokeyApiCipherTool(new AutokeyCipherService());
    }

    /**
     * Проверяет, что action() возвращает строку 'autokey'.
     */
    public function testActionReturnsAutokey(): void
    {
        self::assertSame('autokey', $this->tool->action());
    }

    /**
     * Проверяет корректное шифрование с явными настройками.
     */
    public function testEncryptWithExplicitSettingsReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text' => 'ATTACK AT DAWN',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'key' => 'QUEENLY'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('QNXEPV YT WTWP', $result['result']);
        self::assertSame('en', $result['alphabet']);
        self::assertSame('QUEENLY', $result['key']);
        self::assertNull($result['detected_alphabet']);
    }

    /**
     * Проверяет round-trip: шифрование и расшифровка.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text' => 'ATTACK AT DAWN',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'key' => 'QUEENLY'],
        ]);

        $dec = $this->tool->execute([
            'text' => $enc['result'],
            'direction' => 'decrypt',
            'settings' => ['alphabet' => 'en', 'key' => 'QUEENLY'],
        ]);

        self::assertSame('ATTACK AT DAWN', $dec['result']);
    }

    /**
     * Проверяет автоопределение алфавита по тексту и ключу.
     */
    public function testAutoDetectsAlphabetForRussianText(): void
    {
        $result = $this->tool->execute([
            'text' => 'привет',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'auto', 'key' => 'ключ'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
    }

    /**
     * Проверяет, что пустой текст вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => '',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'key' => 'QUEENLY'],
        ]);
    }

    /**
     * Проверяет, что недопустимое направление вызывает ValidationFailedException.
     */
    public function testThrowsWhenDirectionIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'bad',
            'settings' => ['alphabet' => 'en', 'key' => 'KEY'],
        ]);
    }

    /**
     * Проверяет, что пустой ключ вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'key' => ''],
        ]);
    }

    /**
     * Проверяет, что неизвестный алфавит вызывает ValidationFailedException.
     */
    public function testThrowsWhenAlphabetIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'xx', 'key' => 'KEY'],
        ]);
    }

    /**
     * Проверяет, что ключ без символов выбранного алфавита вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyHasNoAlphabetCharacters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'key' => '12345'],
        ]);
    }
}
