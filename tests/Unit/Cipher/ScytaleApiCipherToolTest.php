<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\ScytaleApiCipherTool;
use App\Cipher\ScytaleCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Scytale.
 */
final class ScytaleApiCipherToolTest extends TestCase
{
    private ScytaleApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new ScytaleApiCipherTool(new ScytaleCipherService());
    }

    /**
     * Проверяет, что action() возвращает строку 'scytale'.
     */
    public function testActionReturnsScytale(): void
    {
        self::assertSame('scytale', $this->tool->action());
    }

    /**
     * Проверяет шифрование с числом столбцов из поля shift.
     */
    public function testEncryptsWithShiftSettingAsColumns(): void
    {
        $result = $this->tool->execute([
            'text' => 'WEAREDISCOVEREDFLEEATONCE',
            'direction' => 'encrypt',
            'settings' => ['shift' => 4],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('WECRLTEEDOEEOAIVDENRSEFAC', $result['result']);
        self::assertSame(4, $result['columns']);
    }

    /**
     * Проверяет round-trip через API-инструмент.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text' => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings' => ['columns' => 4],
        ]);

        $dec = $this->tool->execute([
            'text' => $enc['result'],
            'direction' => 'decrypt',
            'settings' => ['columns' => 4],
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
            'settings' => ['columns' => 4],
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
            'settings' => ['columns' => 4],
        ]);
    }

    /**
     * Проверяет, что недопустимое количество столбцов вызывает ValidationFailedException.
     */
    public function testThrowsWhenColumnsAreInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['columns' => 1],
        ]);
    }

    /**
     * Проверяет, что при диаметре >= длины текста возвращается предупреждение.
     */
    public function testReturnsWarningWhenColumnsEqualOrExceedTextLength(): void
    {
        $result = $this->tool->execute([
            'text' => 'HI',
            'direction' => 'encrypt',
            'settings' => ['columns' => 2],
        ]);

        self::assertSame('HI', $result['result']);
        self::assertNotNull($result['warning']);
        self::assertIsString($result['warning']);
    }

    /**
     * Проверяет, что при нормальных условиях предупреждение отсутствует.
     */
    public function testNoWarningWhenColumnsBelowTextLength(): void
    {
        $result = $this->tool->execute([
            'text' => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings' => ['columns' => 4],
        ]);

        self::assertNull($result['warning']);
    }
}
