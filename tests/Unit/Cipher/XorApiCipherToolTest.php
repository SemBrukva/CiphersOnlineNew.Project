<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\XorApiCipherTool;
use App\Cipher\XorCipherService;
use App\Container\Container;
use App\Http\Exception\ValidationFailedException;
use App\I18n\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента XOR-шифра.
 */
final class XorApiCipherToolTest extends TestCase
{
    private XorApiCipherTool $tool;

    /** @var Translator|null Экземпляр Translator, разделяемый между тестами класса. */
    private static ?Translator $translator = null;

    protected function setUp(): void
    {
        if (self::$translator === null) {
            self::$translator = new Translator([
                'locale'    => 'en',
                'locales'   => ['en', 'ru', 'de', 'es', 'fr', 'it', 'pt', 'tr'],
                'path'      => PRIVATE_PATH . '/translates',
                'multilang' => false,
            ]);

            global $container;
            if ($container instanceof Container) {
                $container->instance(Translator::class, self::$translator);
            }
        } else {
            self::$translator->setLocale('en');
        }

        $this->tool = new XorApiCipherTool(new XorCipherService());
    }

    // ─────────────────────────── успешные сценарии ─────────────────────────────────────

    /**
     * Проверяет, что шифрование возвращает uppercase hex-результат.
     */
    public function testEncryptReturnsHexResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'KEY'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('030015070A', $result['result']);
        self::assertSame('KEY', $result['key']);
    }

    /**
     * Проверяет round-trip: зашифрованный текст корректно расшифровывается обратно.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'SECRET'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['key' => 'SECRET'],
        ]);

        self::assertSame('HELLO WORLD', $dec['result']);
    }

    /**
     * Проверяет, что action() возвращает строку 'xor'.
     */
    public function testActionReturnsXor(): void
    {
        self::assertSame('xor', $this->tool->action());
    }

    // ─────────────────────────── ошибки валидации ──────────────────────────────────────

    /**
     * Проверяет, что пустой текст вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'KEY'],
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
            'settings'  => ['key' => ''],
        ]);
    }

    /**
     * Проверяет, что невалидное направление вызывает ValidationFailedException.
     */
    public function testThrowsWhenDirectionIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'foo',
            'settings'  => ['key' => 'KEY'],
        ]);
    }

    /**
     * Проверяет, что невалидный hex при дешифровании вызывает ValidationFailedException.
     */
    public function testThrowsWhenDecryptTextIsNotValidHex(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'ZZZZ',
            'direction' => 'decrypt',
            'settings'  => ['key' => 'KEY'],
        ]);
    }

    /**
     * Проверяет, что при нескольких ошибках все поля присутствуют в details.
     */
    public function testValidationErrorsContainAllInvalidFields(): void
    {
        try {
            $this->tool->execute([
                'text'      => '',
                'direction' => 'bad',
                'settings'  => ['key' => ''],
            ]);
            self::fail('ValidationFailedException expected');
        } catch (ValidationFailedException $e) {
            $errors = $e->details()['errors'] ?? [];
            self::assertArrayHasKey('direction', $errors);
            self::assertArrayHasKey('text', $errors);
            self::assertArrayHasKey('settings.key', $errors);
        }
    }

    // ─────────────────────────── локализация ───────────────────────────────────────────

    /**
     * Проверяет, что сообщения об ошибках локализуются на русский язык.
     */
    public function testErrorMessagesAreLocalized(): void
    {
        self::$translator->setLocale('ru');

        try {
            $this->tool->execute([
                'text'      => 'HELLO',
                'direction' => 'encrypt',
                'settings'  => ['key' => ''],
            ]);
            self::fail('ValidationFailedException expected');
        } catch (ValidationFailedException $e) {
            $errors    = $e->details()['errors'] ?? [];
            $keyErrors = $errors['settings.key'] ?? [];
            self::assertNotEmpty($keyErrors);
            self::assertMatchesRegularExpression('/[А-Яа-яёЁ]/u', $keyErrors[0]);
        } finally {
            self::$translator->setLocale('en');
        }
    }

    // ─────────────────────────── структура инструмента ─────────────────────────────────

    /**
     * Проверяет структуру настроек: textarea + select формата ключа.
     */
    public function testGetToolSettingsReturnsExpectedStructure(): void
    {
        $service  = new XorCipherService();
        $settings = $service->getToolSettings();

        self::assertCount(2, $settings);

        $textarea = $settings[0];
        self::assertSame('textarea', $textarea['type']);
        self::assertSame('ciphers-key', $textarea['id']);
        self::assertSame('ciphers-settings-textarea', $textarea['class']);
        self::assertNotEmpty($textarea['hint'] ?? '');

        $select = $settings[1];
        self::assertSame('select', $select['type']);
        self::assertSame('ciphers-xor-key-format', $select['id']);
        $values = array_column($select['options'], 'value');
        self::assertContains('text', $values);
        self::assertContains('hex', $values);
    }

    // ─────────────────────────── hex-формат ключа ──────────────────────────────────────

    /**
     * Проверяет round-trip с hex-ключом через API.
     */
    public function testHexKeyRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => '37', 'xor_key_format' => 'hex'],
        ]);

        self::assertTrue((bool) $enc['ok']);
        self::assertSame('hex', $enc['key_format']);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['key' => '37', 'xor_key_format' => 'hex'],
        ]);

        self::assertSame('HELLO', $dec['result']);
    }

    /**
     * Проверяет, что невалидный hex-ключ (нечётная длина) вызывает ValidationFailedException.
     */
    public function testThrowsWhenHexKeyIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'DEA', 'xor_key_format' => 'hex'],
        ]);
    }

    /**
     * Проверяет, что неизвестный формат ключа нормализуется к 'text'.
     */
    public function testUnknownKeyFormatFallsBackToText(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'KEY', 'xor_key_format' => 'unknown'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('030015070A', $result['result']);
        self::assertSame('text', $result['key_format']);
    }

    /**
     * Проверяет, что getTrustItems возвращает 4 элемента.
     */
    public function testGetTrustItemsReturnsFourItems(): void
    {
        $service = new XorCipherService();
        $items   = $service->getTrustItems('api');

        self::assertCount(4, $items);
        foreach ($items as $item) {
            self::assertIsString($item);
            self::assertNotEmpty($item);
        }
    }
}
