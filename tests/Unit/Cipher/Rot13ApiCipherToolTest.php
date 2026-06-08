<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\Rot13ApiCipherTool;
use App\Cipher\Rot13CipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента ROT13.
 */
final class Rot13ApiCipherToolTest extends TestCase
{
    private Rot13ApiCipherTool $tool;

    /**
     * Подготавливает API-инструмент ROT13.
     */
    protected function setUp(): void
    {
        $this->tool = new Rot13ApiCipherTool(new Rot13CipherService());
    }

    /**
     * Проверяет, что action() возвращает строку rot13.
     */
    public function testActionReturnsRot13(): void
    {
        self::assertSame('rot13', $this->tool->action());
    }

    /**
     * Проверяет успешное ROT13-преобразование через API-tool.
     */
    public function testExecuteReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text' => 'Hello, World!',
            'direction' => 'encrypt',
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('Uryyb, Jbeyq!', $result['result']);
    }

    /**
     * Проверяет, что дешифрование выполняет то же ROT13-преобразование.
     */
    public function testDecryptUsesSameTransformation(): void
    {
        $result = $this->tool->execute([
            'text' => 'Uryyb, Jbeyq!',
            'direction' => 'decrypt',
        ]);

        self::assertSame('Hello, World!', $result['result']);
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
        ]);
    }

    /**
     * Проверяет, что текст без латиницы вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextHasNoLatinCharacters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'Привет 123!',
            'direction' => 'encrypt',
        ]);
    }

    /**
     * Проверяет, что недопустимое направление вызывает ValidationFailedException.
     */
    public function testThrowsWhenDirectionIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'Hello',
            'direction' => 'bad',
        ]);
    }
}
