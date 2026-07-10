<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\Rot47ApiCipherTool;
use App\Cipher\Rot47CipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента ROT47.
 */
final class Rot47ApiCipherToolTest extends TestCase
{
    private Rot47ApiCipherTool $tool;

    /**
     * Подготавливает API-инструмент ROT47.
     */
    protected function setUp(): void
    {
        $this->tool = new Rot47ApiCipherTool(new Rot47CipherService());
    }

    /**
     * Проверяет, что action() возвращает строку rot47.
     */
    public function testActionReturnsRot47(): void
    {
        self::assertSame('rot47', $this->tool->action());
    }

    /**
     * Проверяет успешное ROT47-преобразование через API-tool.
     */
    public function testExecuteReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text' => 'Hello, World!',
            'direction' => 'encrypt',
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('w6==@[ (@C=5P', $result['result']);
    }

    /**
     * Проверяет, что дешифрование выполняет то же ROT47-преобразование.
     */
    public function testDecryptUsesSameTransformation(): void
    {
        $result = $this->tool->execute([
            'text' => 'w6==@[ (@C=5P',
            'direction' => 'decrypt',
        ]);

        self::assertSame('Hello, World!', $result['result']);
    }

    /**
     * Проверяет, что цифры и знаки препинания также преобразуются.
     */
    public function testTransformsDigitsAndPunctuation(): void
    {
        $result = $this->tool->execute([
            'text' => '2024!',
            'direction' => 'encrypt',
        ]);

        self::assertSame('a_acP', $result['result']);
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
     * Проверяет, что текст без печатного ASCII вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextHasNoPrintableAscii(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'Привет',
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
