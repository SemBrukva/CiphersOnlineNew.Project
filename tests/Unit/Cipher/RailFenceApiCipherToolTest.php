<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\RailFenceApiCipherTool;
use App\Cipher\RailFenceCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Rail Fence.
 */
final class RailFenceApiCipherToolTest extends TestCase
{
    private RailFenceApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new RailFenceApiCipherTool(new RailFenceCipherService());
    }

    /**
     * Проверяет, что action() возвращает строку 'rail-fence'.
     */
    public function testActionReturnsRailFence(): void
    {
        self::assertSame('rail-fence', $this->tool->action());
    }

    /**
     * Проверяет шифрование с числом рельсов из поля shift.
     */
    public function testEncryptsWithShiftSettingAsRails(): void
    {
        $result = $this->tool->execute([
            'text' => 'WEAREDISCOVEREDFLEEATONCE',
            'direction' => 'encrypt',
            'settings' => ['shift' => 3],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('WECRLTEERDSOEEFEAOCAIVDEN', $result['result']);
        self::assertSame(3, $result['rails']);
    }

    /**
     * Проверяет round-trip через API-инструмент.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text' => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings' => ['rails' => 4],
        ]);

        $dec = $this->tool->execute([
            'text' => $enc['result'],
            'direction' => 'decrypt',
            'settings' => ['rails' => 4],
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
            'settings' => ['rails' => 3],
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
            'settings' => ['rails' => 3],
        ]);
    }

    /**
     * Проверяет, что недопустимое количество рельсов вызывает ValidationFailedException.
     */
    public function testThrowsWhenRailsAreInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['rails' => 1],
        ]);
    }

    /**
     * Проверяет, что при рельсах >= длины текста возвращается предупреждение.
     */
    public function testReturnsWarningWhenRailsEqualOrExceedTextLength(): void
    {
        $result = $this->tool->execute([
            'text' => 'HI',
            'direction' => 'encrypt',
            'settings' => ['rails' => 2],
        ]);

        self::assertSame('HI', $result['result']);
        self::assertNotNull($result['warning']);
        self::assertIsString($result['warning']);
    }

    /**
     * Проверяет, что при нормальных условиях предупреждение отсутствует.
     */
    public function testNoWarningWhenRailsBelowTextLength(): void
    {
        $result = $this->tool->execute([
            'text' => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings' => ['rails' => 3],
        ]);

        self::assertNull($result['warning']);
    }
}
