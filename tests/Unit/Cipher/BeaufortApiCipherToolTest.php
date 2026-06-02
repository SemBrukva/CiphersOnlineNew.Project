<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\BeaufortApiCipherTool;
use App\Cipher\BeaufortCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Бофора.
 */
final class BeaufortApiCipherToolTest extends TestCase
{
    private BeaufortApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new BeaufortApiCipherTool(new BeaufortCipherService());
    }

    // ── структура инструмента ──────────────────────────────────────────────────────

    /**
     * Проверяет, что action() возвращает строку 'beaufort'.
     */
    public function testActionReturnsBeaufort(): void
    {
        self::assertSame('beaufort', $this->tool->action());
    }

    // ── успешные сценарии ──────────────────────────────────────────────────────────

    /**
     * Проверяет корректное шифрование с явными настройками.
     *
     * DEFEND THE EAST WALL / FORT → CKMPSL YMB KRBM SRIU (канонический пример Бофора).
     */
    public function testEncryptWithExplicitSettingsReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'DEFEND THE EAST WALL',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'FORT'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('CKMPSL YMB KRBM SRIU', $result['result']);
        self::assertSame('en', $result['alphabet']);
        self::assertSame('FORT', $result['key']);
        self::assertNull($result['detected_alphabet']);
    }

    /**
     * Проверяет reciprocal-свойство Бофора: тот же ключ шифрует и расшифровывает.
     *
     * Повторное применение process() с тем же ключом возвращает исходный текст.
     */
    public function testDecryptIsReciprocalOfEncrypt(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'DEFEND THE EAST WALL',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'FORT'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'FORT'],
        ]);

        self::assertSame('DEFEND THE EAST WALL', $dec['result']);
    }

    /**
     * Проверяет автоопределение алфавита для кириллического текста и ключа.
     */
    public function testAutoDetectsAlphabetForRussianText(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto', 'key' => 'КЛЮЧ'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
    }

    /**
     * Проверяет шифрование русского текста с явным алфавитом 'ru'.
     *
     * ПРИВЕТ / КЛЮЧ → ЫЫХХЁЩ (см. BeaufortCipherServiceTest).
     */
    public function testEncryptRussianWithExplicitAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'КЛЮЧ'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ЫЫХХЁЩ', $result['result']);
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
            'settings'  => ['alphabet' => 'en', 'key' => 'FORT'],
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
            'settings'  => ['alphabet' => 'en', 'key' => 'FORT'],
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
     * Проверяет, что неизвестный алфавит вызывает ValidationFailedException.
     */
    public function testThrowsWhenAlphabetIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'xx', 'key' => 'FORT'],
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
            'settings'  => ['alphabet' => 'en', 'key' => 'FORT'],
        ]);
    }

    /**
     * Проверяет, что ключ без символов выбранного алфавита вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyHasNoAlphabetCharacters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => '123 !!!'],
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
                'settings'  => ['alphabet' => 'xx', 'key' => ''],
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
