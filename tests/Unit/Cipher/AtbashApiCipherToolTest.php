<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AtbashApiCipherTool;
use App\Cipher\AtbashCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Атбаш.
 */
final class AtbashApiCipherToolTest extends TestCase
{
    private AtbashApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new AtbashApiCipherTool(new AtbashCipherService());
    }

    // ── структура инструмента ──────────────────────────────────────────────────────

    /**
     * Проверяет, что action() возвращает строку 'atbash'.
     */
    public function testActionReturnsAtbash(): void
    {
        self::assertSame('atbash', $this->tool->action());
    }

    // ── успешные сценарии ──────────────────────────────────────────────────────────

    /**
     * Проверяет корректное шифрование с явным алфавитом.
     *
     * H(7)→S(18), E(4)→V(21), L(11)→O(14), O(14)→L(11), W(22)→D(3), R(17)→I(8), D(3)→W(22)
     */
    public function testEncryptWithExplicitAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('SVOOL DLIOW', $result['result']);
        self::assertSame('en', $result['alphabet']);
        self::assertNull($result['detected_alphabet']);
    }

    /**
     * Проверяет reciprocal-свойство Атбаша: шифрование = расшифровка.
     */
    public function testDecryptIsIdenticalToEncrypt(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en'],
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
            'settings'  => ['alphabet' => 'auto'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['detected_alphabet']);
        self::assertSame('ru', $result['alphabet']);
    }

    /**
     * Проверяет шифрование с явным алфавитом 'ru'.
     */
    public function testEncryptRussianWithExplicitAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ПОЦЭЪМ', $result['result']);
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
            'settings'  => ['alphabet' => 'en'],
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
            'settings'  => ['alphabet' => 'en'],
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
            'settings'  => ['alphabet' => 'xx'],
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
            'settings'  => ['alphabet' => 'en'],
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
                'settings'  => ['alphabet' => 'xx'],
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
