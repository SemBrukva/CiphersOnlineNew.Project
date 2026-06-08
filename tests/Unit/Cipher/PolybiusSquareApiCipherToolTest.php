<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\PolybiusSquareApiCipherTool;
use App\Cipher\PolybiusSquareCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра квадрата Полибия.
 */
final class PolybiusSquareApiCipherToolTest extends TestCase
{
    private PolybiusSquareApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new PolybiusSquareApiCipherTool(new PolybiusSquareCipherService());
    }

    /**
     * Проверяет, что action() возвращает строку 'polybius-square'.
     */
    public function testActionReturnsPolybiusSquare(): void
    {
        self::assertSame('polybius-square', $this->tool->action());
    }

    /**
     * Проверяет успешное шифрование с явными настройками.
     */
    public function testEncryptWithExplicitSettingsReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text' => 'hello',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'delimiter' => 'space'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('23 15 31 31 34', $result['result']);
        self::assertSame('en', $result['alphabet']);
        self::assertSame('space', $result['delimiter']);
        self::assertNull($result['detected_alphabet']);
    }

    /**
     * Проверяет автоопределение алфавита при шифровании.
     */
    public function testAutoDetectsAlphabetForEncrypt(): void
    {
        $result = $this->tool->execute([
            'text' => 'привет',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'auto', 'delimiter' => 'dash'],
        ]);

        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
    }

    /**
     * Проверяет, что при auto-расшифровке алфавит берётся из locale.
     */
    public function testAutoDecryptUsesLocaleForAlphabetSelection(): void
    {
        $result = $this->tool->execute([
            'text' => '41-42-25',
            'direction' => 'decrypt',
            'settings' => ['alphabet' => 'auto', 'delimiter' => 'dash'],
            'locale' => 'ru',
        ]);

        self::assertSame('ru', $result['detected_alphabet']);
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
            'settings' => ['alphabet' => 'en', 'delimiter' => 'space'],
        ]);
    }

    /**
     * Проверяет, что недопустимый разделитель вызывает ValidationFailedException.
     */
    public function testThrowsWhenDelimiterIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'hello',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'delimiter' => 'pipe'],
        ]);
    }

    /**
     * Проверяет, что текст без букв выбранного алфавита отклоняется при шифровании.
     */
    public function testThrowsWhenTextHasNoAlphabetCharactersOnEncrypt(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => '123 !!!',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'delimiter' => 'space'],
        ]);
    }
}
