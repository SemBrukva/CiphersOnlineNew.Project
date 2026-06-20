<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AlphabetCatalog;
use App\Cipher\AlphabetTool;
use App\Cipher\CaseFolder;
use App\Cipher\TrifidApiCipherTool;
use App\Cipher\TrifidCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Трифид.
 */
final class TrifidApiCipherToolTest extends TestCase
{
    private TrifidApiCipherTool $tool;

    protected function setUp(): void
    {
        $catalog    = new AlphabetCatalog();
        $caseFolder = new CaseFolder();
        $service    = new TrifidCipherService($catalog, new AlphabetTool($catalog, $caseFolder), $caseFolder);
        $this->tool = new TrifidApiCipherTool($service);
    }

    /**
     * Проверяет, что action() возвращает строку 'trifid'.
     */
    public function testActionReturnsTrifid(): void
    {
        self::assertSame('trifid', $this->tool->action());
    }

    /**
     * Проверяет корректное шифрование с явно заданным алфавитом и ключом.
     */
    public function testEncryptReturnsResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'KEYWORD'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertNotEmpty($result['result']);
        self::assertSame('KEYWORD', $result['key']);
        self::assertSame('en', $result['alphabet']);
    }

    /**
     * Проверяет автоопределение алфавита при alphabet=auto.
     */
    public function testAutoDetectsAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto', 'key' => 'KEY'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertContains($result['alphabet'], ['en', 'it', 'es', 'de', 'tr', 'pt', 'fr']);
        self::assertNotNull($result['detected_alphabet']);
    }

    /**
     * Проверяет round-trip: шифрование и расшифровка дают оригинал.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'ATTACKATDAWN',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'PLAYFAIR'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'PLAYFAIR'],
        ]);

        self::assertSame('ATTACKATDAWN', $dec['result']);
    }

    /**
     * Проверяет шифрование с испанским алфавитом (27 букв без заполнителей).
     */
    public function testSpanishAlphabetRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'HOLA',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'es', 'key' => 'CLAVE'],
        ]);

        self::assertTrue((bool) $enc['ok']);
        self::assertSame('es', $enc['alphabet']);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'es', 'key' => 'CLAVE'],
        ]);

        self::assertSame('HOLA', $dec['result']);
    }

    /**
     * Проверяет шифрование с итальянским алфавитом.
     */
    public function testItalianAlphabetRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'CIAO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'it', 'key' => 'CHIAVE'],
        ]);

        self::assertTrue((bool) $enc['ok']);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'it', 'key' => 'CHIAVE'],
        ]);

        self::assertSame('CIAO', $dec['result']);
    }

    /**
     * Проверяет, что пустой текст вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'KEY'],
        ]);
    }

    /**
     * Проверяет, что текст без символов алфавита вызывает ValidationFailedException.
     */
    public function testThrowsWhenTextHasNoAlphabetChars(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '12345!!!',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'KEY'],
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
     * Проверяет, что ключ без символов алфавита вызывает ValidationFailedException.
     */
    public function testThrowsWhenKeyHasNoAlphabetChars(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => '99999'],
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
            'settings'  => ['alphabet' => 'en', 'key' => 'KEY'],
        ]);
    }

    /**
     * Проверяет, что неподдерживаемый алфавит вызывает ValidationFailedException.
     */
    public function testThrowsWhenAlphabetIsUnsupported(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'KEY'],
        ]);
    }

    /**
     * Проверяет, что отсутствие настроек вызывает ValidationFailedException.
     */
    public function testMissingSettingsThrowsValidation(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
        ]);
    }

    /**
     * Проверяет, что пробелы и знаки препинания во вводе корректно обрабатываются.
     */
    public function testTextWithSpacesIsHandled(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'SECRET'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertNotEmpty($result['result']);
    }
}
