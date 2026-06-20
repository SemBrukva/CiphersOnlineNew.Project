<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AlphabetCatalog;
use App\Cipher\AlphabetTool;
use App\Cipher\BifidApiCipherTool;
use App\Cipher\BifidCipherService;
use App\Cipher\CaseFolder;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Бифид.
 */
final class BifidApiCipherToolTest extends TestCase
{
    private BifidApiCipherTool $tool;

    protected function setUp(): void
    {
        $catalog    = new AlphabetCatalog();
        $caseFolder = new CaseFolder();
        $service    = new BifidCipherService($catalog, new AlphabetTool($catalog, $caseFolder), $caseFolder);
        $this->tool = new BifidApiCipherTool($service);
    }

    /**
     * Проверяет, что action() возвращает строку 'bifid'.
     */
    public function testActionReturnsBifid(): void
    {
        self::assertSame('bifid', $this->tool->action());
    }

    /**
     * Проверяет корректное шифрование с явно заданным алфавитом и ключом.
     */
    public function testEncryptReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'KEYWORD'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('FHYCZ', $result['result']);
        self::assertSame('KEYWORD', $result['key']);
        self::assertSame('en', $result['alphabet']);
    }

    /**
     * Проверяет автоопределение алфавита при alphabet=auto.
     */
    public function testAutoDetectsAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto', 'key' => 'КЛЮЧ'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['alphabet']);
        self::assertSame('ru', $result['detected_alphabet']);
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
     * Проверяет шифрование с португальским алфавитом (6×6 квадрат).
     */
    public function testPortugueseAlphabetRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'ATACAR',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'pt', 'key' => 'CHAVE'],
        ]);

        self::assertTrue((bool) $enc['ok']);
        self::assertSame('pt', $enc['alphabet']);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'pt', 'key' => 'CHAVE'],
        ]);

        self::assertSame('ATACAR', $dec['result']);
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
     * Проверяет шифрование с русским алфавитом (6×6 квадрат с цифрами-заполнителями).
     */
    public function testRussianAlphabetRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'КЛЮЧ'],
        ]);

        self::assertTrue((bool) $enc['ok']);
        self::assertSame('ru', $enc['alphabet']);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'КЛЮЧ'],
        ]);

        self::assertSame('ПРИВЕТ', $dec['result']);
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
            'settings'  => ['alphabet' => 'zh', 'key' => 'KEY'],
        ]);
    }

    /**
     * Проверяет, что отсутствие настроек обрабатывается без ошибки PHP.
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
     * Проверяет, что цифры-заполнители в открытом тексте игнорируются
     * (для языков с pad), а результат идентичен тексту без цифр.
     */
    public function testEncryptIgnoresPadDigitsInPlaintextForRussian(): void
    {
        $withDigits = $this->tool->execute([
            'text'      => 'ПРИВЕТ 1 2 3',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'КЛЮЧ'],
        ]);

        $withoutDigits = $this->tool->execute([
            'text'      => 'ПРИВЕТ',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'КЛЮЧ'],
        ]);

        self::assertSame($withoutDigits['result'], $withDigits['result']);
    }
}
