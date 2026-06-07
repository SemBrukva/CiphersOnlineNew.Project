<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AffineApiCipherTool;
use App\Cipher\AffineCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента аффинного шифра.
 */
final class AffineApiCipherToolTest extends TestCase
{
    private AffineApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new AffineApiCipherTool(new AffineCipherService());
    }

    /**
     * Проверяет, что action() возвращает строку 'affine'.
     */
    public function testActionReturnsAffine(): void
    {
        self::assertSame('affine', $this->tool->action());
    }

    /**
     * Проверяет шифрование с ключами из key и shift.
     */
    public function testEncryptsWithUiCompatibleSettings(): void
    {
        $result = $this->tool->execute([
            'text' => 'AFFINE CIPHER',
            'direction' => 'encrypt',
            'settings' => [
                'alphabet' => 'en',
                'key' => '5',
                'shift' => 8,
            ],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('IHHWVC SWFRCP', $result['result']);
        self::assertSame(5, $result['a']);
        self::assertSame(8, $result['b']);
    }

    /**
     * Проверяет расшифрование с альтернативными именами настроек a и b.
     */
    public function testDecryptsWithExplicitAffineSettings(): void
    {
        $result = $this->tool->execute([
            'text' => 'IHHWVC SWFRCP',
            'direction' => 'decrypt',
            'settings' => [
                'alphabet' => 'en',
                'a' => 5,
                'b' => 8,
            ],
        ]);

        self::assertSame('AFFINE CIPHER', $result['result']);
    }

    /**
     * Проверяет, что недопустимый множитель вызывает ValidationFailedException.
     */
    public function testThrowsWhenMultiplierIsNotCoprime(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => [
                'alphabet' => 'en',
                'key' => '13',
                'shift' => 8,
            ],
        ]);
    }

    /**
     * Проверяет, что недопустимый сдвиг вызывает ValidationFailedException.
     */
    public function testThrowsWhenShiftIsOutOfRange(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => [
                'alphabet' => 'en',
                'key' => '5',
                'shift' => 26,
            ],
        ]);
    }

    /**
     * Проверяет, что отрицательный сдвиг вызывает ValidationFailedException.
     */
    public function testThrowsWhenShiftIsNegative(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'key' => '5', 'shift' => -1],
        ]);
    }

    /**
     * Проверяет, что нулевой множитель вызывает ValidationFailedException.
     */
    public function testThrowsWhenMultiplierIsZero(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'key' => '0', 'shift' => 0],
        ]);
    }

    /**
     * Проверяет, что отрицательный множитель вызывает ValidationFailedException.
     */
    public function testThrowsWhenMultiplierIsNegative(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'key' => '-1', 'shift' => 0],
        ]);
    }

    /**
     * Проверяет, что множитель, равный размеру алфавита, вызывает ValidationFailedException.
     */
    public function testThrowsWhenMultiplierEqualsAlphabetSize(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'key' => '26', 'shift' => 0],
        ]);
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
            'settings' => ['alphabet' => 'en', 'key' => '5', 'shift' => 8],
        ]);
    }

    /**
     * Проверяет, что текст без символов алфавита вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextHasNoAlphabetCharacters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => '12345!@#',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'key' => '5', 'shift' => 8],
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
            'direction' => 'ENCRYPT',
            'settings' => ['alphabet' => 'en', 'key' => '5', 'shift' => 8],
        ]);
    }

    /**
     * Проверяет, что неподдерживаемый алфавит вызывает ValidationFailedException.
     */
    public function testThrowsWhenAlphabetIsUnsupported(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'xyz', 'key' => '5', 'shift' => 8],
        ]);
    }

    /**
     * Проверяет автоопределение алфавита и поле detected_alphabet в ответе.
     * Русский текст однозначно определяется как 'ru'.
     */
    public function testAutoDetectsAlphabet(): void
    {
        $result = $this->tool->execute([
            'text' => 'ПРИВЕТ МИР',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'auto', 'key' => '5', 'shift' => 8],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
        self::assertNotSame('ПРИВЕТ МИР', $result['result']);
    }

    /**
     * Проверяет, что множитель a=1 является допустимым (вырожденный случай Цезаря).
     */
    public function testMultiplierOneIsValid(): void
    {
        $result = $this->tool->execute([
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => ['alphabet' => 'en', 'key' => '1', 'shift' => 3],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('KHOOR', $result['result']);
    }
}
