<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\ColumnarTranspositionApiCipherTool;
use App\Cipher\ColumnarTranspositionCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра столбцовой перестановки.
 */
final class ColumnarTranspositionApiCipherToolTest extends TestCase
{
    private ColumnarTranspositionApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new ColumnarTranspositionApiCipherTool(new ColumnarTranspositionCipherService());
    }

    /**
     * Проверяет, что action() возвращает строку 'columnar-transposition'.
     */
    public function testActionReturnsColumnarTransposition(): void
    {
        self::assertSame('columnar-transposition', $this->tool->action());
    }

    /**
     * Проверяет шифрование с ключом из настроек.
     */
    public function testEncryptsWithKeySetting(): void
    {
        $result = $this->tool->execute([
            'text' => 'WEAREDISCOVERED',
            'direction' => 'encrypt',
            'settings' => ['key' => 'SECRET'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ACDESEEVROWIRDE', $result['result']);
        self::assertSame('SECRET', $result['key']);
    }

    /**
     * Проверяет round-trip через API-инструмент.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text' => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings' => ['key' => 'ZEBRA'],
        ]);

        $dec = $this->tool->execute([
            'text' => $enc['result'],
            'direction' => 'decrypt',
            'settings' => ['key' => 'ZEBRA'],
        ]);

        self::assertSame('HELLO WORLD', $dec['result']);
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
            'settings' => ['key' => 'SECRET'],
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
            'settings' => ['key' => 'SECRET'],
        ]);
    }

    /**
     * Проверяет, что слишком короткий ключ вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyIsTooShort(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['key' => 'A'],
        ]);
    }

    /**
     * Проверяет, что слишком длинный ключ вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyIsTooLong(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['key' => str_repeat('A', ColumnarTranspositionCipherService::MAX_KEY_LENGTH + 1)],
        ]);
    }
}
