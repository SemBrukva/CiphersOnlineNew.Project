<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\HillApiCipherTool;
use App\Cipher\HillCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Хилла.
 */
final class HillApiCipherToolTest extends TestCase
{
    private HillApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new HillApiCipherTool(new HillCipherService());
    }

    /**
     * Проверяет, что action() возвращает строку 'hill'.
     */
    public function testActionReturnsHill(): void
    {
        self::assertSame('hill', $this->tool->action());
    }

    /**
     * Проверяет шифрование с UI-совместимым ключом.
     */
    public function testEncryptsWithUiCompatibleSettings(): void
    {
        $result = $this->tool->execute([
            'text' => 'HELP',
            'direction' => 'encrypt',
            'settings' => [
                'alphabet' => 'en',
                'key' => '3 3; 2 5',
            ],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('HIAT', $result['result']);
        self::assertSame([[3, 3], [2, 5]], $result['matrix']);
    }

    /**
     * Проверяет расшифрование с альтернативным именем matrix.
     */
    public function testDecryptsWithMatrixSetting(): void
    {
        $result = $this->tool->execute([
            'text' => 'HIAT',
            'direction' => 'decrypt',
            'settings' => [
                'alphabet' => 'en',
                'matrix' => '3 3; 2 5',
            ],
        ]);

        self::assertSame('HELP', $result['result']);
    }

    /**
     * Проверяет автоопределение алфавита.
     */
    public function testAutoDetectsAlphabet(): void
    {
        $result = $this->tool->execute([
            'text' => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings' => [
                'alphabet' => 'auto',
                'key' => '1 2; 3 5',
            ],
        ]);

        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
    }

    /**
     * Проверяет ошибку для неинвертируемой матрицы.
     */
    public function testThrowsWhenMatrixIsNotInvertible(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELP',
            'direction' => 'encrypt',
            'settings' => [
                'alphabet' => 'en',
                'key' => '2 4; 2 4',
            ],
        ]);
    }

    /**
     * Проверяет ошибку для неквадратной матрицы.
     */
    public function testThrowsWhenMatrixShapeIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELP',
            'direction' => 'encrypt',
            'settings' => [
                'alphabet' => 'en',
                'key' => '1 2 3; 4 5 6',
            ],
        ]);
    }

    /**
     * Проверяет ошибку для длины шифротекста, не кратной размеру матрицы.
     */
    public function testThrowsWhenDecryptLengthIsNotDivisibleByMatrixSize(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'ABC',
            'direction' => 'decrypt',
            'settings' => [
                'alphabet' => 'en',
                'key' => '3 3; 2 5',
            ],
        ]);
    }
}
