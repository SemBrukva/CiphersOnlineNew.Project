<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\PortaApiCipherTool;
use App\Cipher\PortaCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Porta.
 */
final class PortaApiCipherToolTest extends TestCase
{
    private PortaApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new PortaApiCipherTool(new PortaCipherService());
    }

    /**
     * Проверяет, что action() возвращает строку 'porta'.
     */
    public function testActionReturnsPorta(): void
    {
        self::assertSame('porta', $this->tool->action());
    }

    /**
     * Проверяет успешное шифрование через API-инструмент.
     */
    public function testEncryptReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text' => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings' => ['key' => 'PORTA'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('OYTUB CHJUQ', $result['result']);
        self::assertSame('PORTA', $result['key']);
    }

    /**
     * Проверяет reciprocal-дешифрование через API-инструмент.
     */
    public function testDecryptReturnsOriginalText(): void
    {
        $result = $this->tool->execute([
            'text' => 'OYTUB CHJUQ',
            'direction' => 'decrypt',
            'settings' => ['key' => 'PORTA'],
        ]);

        self::assertSame('HELLO WORLD', $result['result']);
    }

    /**
     * Проверяет ошибки валидации для обязательных полей.
     */
    public function testValidationErrorsContainInvalidFields(): void
    {
        try {
            $this->tool->execute([
                'text' => '',
                'direction' => 'bad',
                'settings' => ['key' => ''],
            ]);
            self::fail('ValidationFailedException ожидался');
        } catch (ValidationFailedException $e) {
            $errors = $e->details()['errors'] ?? [];
            self::assertArrayHasKey('direction', $errors);
            self::assertArrayHasKey('text', $errors);
            self::assertArrayHasKey('settings.key', $errors);
        }
    }

    /**
     * Проверяет ошибку, когда текст или ключ не содержит латинских букв.
     */
    public function testThrowsWhenTextOrKeyHasNoLatinLetters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text' => 'Привет',
            'direction' => 'encrypt',
            'settings' => ['key' => 'КЛЮЧ'],
        ]);
    }
}
