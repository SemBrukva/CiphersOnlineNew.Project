<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\VernamApiCipherTool;
use App\Cipher\VernamCipherService;
use App\Container\Container;
use App\Http\Exception\ValidationFailedException;
use App\I18n\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Вернама.
 *
 * Включает проверку валидации, предупреждений о небезопасном ключе и локализации ошибок.
 */
final class VernamApiCipherToolTest extends TestCase
{
    private VernamApiCipherTool $tool;

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

        $this->tool = new VernamApiCipherTool(new VernamCipherService());
    }

    // ─────────────────────────── успешные сценарии ─────────────────────────────────────

    /**
     * Проверяет, что шифрование возвращает result, key и warning в ответе.
     */
    public function testEncryptReturnsResultAndKey(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ABCDE'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertNotEmpty($result['result']);
        self::assertSame('ABCDE', $result['key']);
        self::assertArrayHasKey('warning', $result);
    }

    /**
     * Проверяет round-trip: зашифрованный текст корректно расшифровывается обратно.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ABCDEFGHIJK'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['key' => 'ABCDEFGHIJK'],
        ]);

        self::assertSame('HELLO WORLD', $dec['result']);
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
            'settings'  => ['key' => 'ABC'],
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
            'settings'  => ['key' => 'ABC'],
        ]);
    }

    /**
     * Проверяет, что при одновременных ошибках все три поля присутствуют в details.
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

    // ─────────────────────────── предупреждения о ключе ────────────────────────────────

    /**
     * Проверяет, что предупреждение отсутствует когда ключ не короче текста.
     */
    public function testNoWarningWhenKeyIsLongEnough(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HI',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ABCDE'],
        ]);

        self::assertNull($result['warning']);
    }

    /**
     * Проверяет, что предупреждение отсутствует когда ключ ровно той же длины, что и текст.
     *
     * Это настоящий OTP — ключ не повторяется.
     */
    public function testNoWarningWhenKeyEqualsMessageLength(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ABCDE'], // 5 байт = 5 байт
        ]);

        self::assertNull($result['warning']);
    }

    /**
     * Проверяет, что предупреждение появляется когда ключ короче текста при шифровании.
     */
    public function testWarningWhenKeyIsShorterThanMessage(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'AB'],
        ]);

        self::assertNotNull($result['warning']);
        self::assertIsString($result['warning']);
        self::assertNotEmpty($result['warning']);
    }

    /**
     * Проверяет, что при шифровании с коротким ключом результат всё равно возвращается.
     */
    public function testShortKeyWarningDoesNotBlockResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'AB'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertNotEmpty($result['result']);
    }

    /**
     * Проверяет, что предупреждение НЕ появляется при дешифровании, даже если ключ короче.
     *
     * Предупреждение актуально только при шифровании — пользователь должен выбрать
     * надёжный ключ заранее, а не когда уже расшифровывает чужой шифртекст.
     */
    public function testNoWarningOnDecryptWithShortKey(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'AB'],
        ]);

        $result = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['key' => 'AB'],
        ]);

        self::assertNull($result['warning']);
        self::assertSame('HELLO WORLD', $result['result']);
    }

    // ─────────────────────────── локализация ───────────────────────────────────────────

    /**
     * Проверяет, что сообщение об ошибке localiz-уется: при локали 'ru' текст содержит кириллицу.
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

    /**
     * Проверяет, что предупреждение о коротком ключе тоже локализуется.
     */
    public function testWarningIsLocalized(): void
    {
        self::$translator->setLocale('de');

        try {
            $result = $this->tool->execute([
                'text'      => 'HELLO WORLD',
                'direction' => 'encrypt',
                'settings'  => ['key' => 'AB'],
            ]);
            self::assertNotNull($result['warning']);
            self::assertStringNotContainsString('will repeat', $result['warning']);
        } finally {
            self::$translator->setLocale('en');
        }
    }

    // ─────────────────────────── структура инструмента ─────────────────────────────────

    /**
     * Проверяет, что action() возвращает строку 'vernam'.
     */
    public function testActionReturnsVernam(): void
    {
        self::assertSame('vernam', $this->tool->action());
    }

    /**
     * Проверяет структуру настроек: тип textarea, id, флаг generateKey, hint.
     */
    public function testGetToolSettingsReturnsExpectedStructure(): void
    {
        $service  = new VernamCipherService();
        $settings = $service->getToolSettings();

        self::assertCount(1, $settings);
        $s = $settings[0];
        self::assertSame('textarea', $s['type']);
        self::assertSame('ciphers-key', $s['id']);
        self::assertSame('ciphers-settings-textarea', $s['class']);
        self::assertTrue((bool) ($s['generateKey'] ?? false));
        self::assertNotEmpty($s['hint'] ?? '');
        self::assertNotEmpty($s['generateKeyLabel'] ?? '');
    }
}
