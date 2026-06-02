<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\GronsfeldApiCipherTool;
use App\Cipher\GronsfeldCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Гронсфельда.
 */
final class GronsfeldApiCipherToolTest extends TestCase
{
    private GronsfeldApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new GronsfeldApiCipherTool(new GronsfeldCipherService());
    }

    // ── структура инструмента ──────────────────────────────────────────────────────

    /**
     * Проверяет, что action() возвращает строку 'gronsfeld'.
     */
    public function testActionReturnsGronsfeld(): void
    {
        self::assertSame('gronsfeld', $this->tool->action());
    }

    // ── успешные сценарии ──────────────────────────────────────────────────────────

    /**
     * Проверяет корректное шифрование с явными настройками.
     *
     * HELLO WORLD / 314159 → KFPMT ZPVMI
     */
    public function testEncryptWithExplicitSettingsReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => '314159'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('KFPMT ZPVMI', $result['result']);
        self::assertSame('en', $result['alphabet']);
        self::assertSame('314159', $result['key']);
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
            'settings'  => ['alphabet' => 'en', 'key' => '31415'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'key' => '31415'],
        ]);

        self::assertSame('HELLO WORLD', $dec['result']);
    }

    /**
     * Проверяет автоопределение алфавита для кириллического текста.
     */
    public function testAutoDetectsAlphabetForRussianText(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto', 'key' => '31'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
    }

    /**
     * Проверяет шифрование русского текста с явным алфавитом 'ru'.
     *
     * Привет / 31: п(16)+3=19=т→Т; р(17)+1=18=с; и(9)+3=12=л; в(2)+1=3=г; е(5)+3=8=з; т(19)+1=20=у
     */
    public function testEncryptRussianWithExplicitAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'Привет',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => '31'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('Тслгзу', $result['result']);
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
            'settings'  => ['alphabet' => 'en', 'key' => '31'],
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
            'settings'  => ['alphabet' => 'en', 'key' => '31'],
        ]);
    }

    /**
     * Проверяет, что нечисловой ключ вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyIsNotNumeric(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'ABC'],
        ]);
    }

    /**
     * Проверяет, что пустой ключ вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => ''],
        ]);
    }

    /**
     * Проверяет, что ключ длиннее 32 символов вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyExceedsMaxLength(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => str_repeat('1', 33)],
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
            'settings'  => ['alphabet' => 'xx', 'key' => '31'],
        ]);
    }

    /**
     * Проверяет, что текст без символов выбранного алфавита вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextHasNoAlphabetCharacters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '123 !!!',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => '31'],
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
                'settings'  => ['alphabet' => 'xx', 'key' => 'abc'],
            ]);
            self::fail('ValidationFailedException ожидался');
        } catch (ValidationFailedException $e) {
            $errors = $e->details()['errors'] ?? [];
            self::assertArrayHasKey('direction', $errors);
            self::assertArrayHasKey('text', $errors);
            self::assertArrayHasKey('settings.alphabet', $errors);
            self::assertArrayHasKey('settings.key', $errors);
        }
    }
}
