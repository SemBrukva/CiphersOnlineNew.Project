<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\CaesarApiCipherTool;
use App\Cipher\CaesarCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Цезаря.
 */
final class CaesarApiCipherToolTest extends TestCase
{
    private CaesarApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new CaesarApiCipherTool(new CaesarCipherService());
    }

    // ── структура инструмента ──────────────────────────────────────────────────────

    /**
     * Проверяет, что action() возвращает строку 'caesar'.
     */
    public function testActionReturnsCaesar(): void
    {
        self::assertSame('caesar', $this->tool->action());
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
            'settings'  => ['alphabet' => 'en', 'shift' => 3],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('KHOOR', $result['result']);
        self::assertSame('en', $result['alphabet']);
        self::assertSame(3, $result['shift']);
        self::assertNull($result['detected_alphabet']);
    }

    /**
     * Проверяет round-trip: шифрование → расшифровка.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'shift' => 5],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'shift' => 5],
        ]);

        self::assertSame('HELLO WORLD', $dec['result']);
    }

    /**
     * Проверяет автоопределение алфавита для кириллического текста при шифровании.
     */
    public function testAutoDetectsAlphabetOnEncrypt(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto', 'shift' => 3],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
    }

    /**
     * Проверяет, что при auto + сдвиг вне диапазона сдвиг зажимается, а не бросает ошибку.
     *
     * RU имеет maxShift = 32. При shift = 100 и alphabet = 'auto' (→ ru) → зажим до 32.
     */
    public function testShiftIsClamped_WhenAutoAlphabetAndShiftOutOfRange(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto', 'shift' => 100],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame(32, $result['shift']);
    }

    /**
     * Проверяет шифрование русского текста с явным алфавитом 'ru'.
     */
    public function testEncryptRussianWithExplicitAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru', 'shift' => 3],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ТУЛЕЗХ', $result['result']);
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
            'settings'  => ['alphabet' => 'en', 'shift' => 3],
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
            'settings'  => ['alphabet' => 'en', 'shift' => 3],
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
            'settings'  => ['alphabet' => 'xx', 'shift' => 3],
        ]);
    }

    /**
     * Проверяет, что сдвиг вне диапазона с явным алфавитом вызывает ValidationFailedException.
     *
     * EN: maxShift = 25. Shift = -1 недопустим при явном алфавите.
     */
    public function testThrowsWhenShiftOutOfRangeWithExplicitAlphabet(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'shift' => -1],
        ]);
    }

    /**
     * Проверяет, что сдвиг больше maxShift с явным алфавитом вызывает ValidationFailedException.
     */
    public function testThrowsWhenShiftExceedsMaxWithExplicitAlphabet(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'shift' => 26],
        ]);
    }

    /**
     * Проверяет, что текст без символов выбранного алфавита вызывает ошибку.
     */
    public function testThrowsWhenTextHasNoAlphabetCharacters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '123 !!!',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'shift' => 3],
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
                'settings'  => ['alphabet' => 'xx', 'shift' => 3],
            ]);
            self::fail('ValidationFailedException ожидался');
        } catch (ValidationFailedException $e) {
            $errors = $e->details()['errors'] ?? [];
            self::assertArrayHasKey('direction', $errors);
            self::assertArrayHasKey('text', $errors);
            self::assertArrayHasKey('settings.alphabet', $errors);
        }
    }
}
