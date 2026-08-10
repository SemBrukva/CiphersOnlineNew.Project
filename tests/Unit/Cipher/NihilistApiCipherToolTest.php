<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AlphabetCatalog;
use App\Cipher\AlphabetTool;
use App\Cipher\CaseFolder;
use App\Cipher\NihilistApiCipherTool;
use App\Cipher\NihilistCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра нигилистов.
 */
final class NihilistApiCipherToolTest extends TestCase
{
    private NihilistApiCipherTool $tool;

    protected function setUp(): void
    {
        $catalog    = new AlphabetCatalog();
        $caseFolder = new CaseFolder();
        $service    = new NihilistCipherService(
            $catalog,
            new AlphabetTool($catalog, $caseFolder),
            $caseFolder
        );
        $this->tool = new NihilistApiCipherTool($service);
    }

    /**
     * Проверяет, что action() возвращает строку 'nihilist'.
     */
    public function testActionReturnsNihilist(): void
    {
        self::assertSame('nihilist', $this->tool->action());
    }

    /**
     * Проверяет корректное шифрование канонического примера с двумя ключами.
     */
    public function testEncryptReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'DYNAMITE',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'ZEBRAS', 'additive_key' => 'RUSSIAN'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('37 106 62 36 67 47 86 26', $result['result']);
        self::assertSame('ZEBRAS', $result['key']);
        self::assertSame('en', $result['alphabet']);
        self::assertSame(5, $result['square_size']);
        self::assertNotEmpty($result['steps']);
    }

    /**
     * Проверяет round-trip: шифрование и расшифровка возвращают оригинал.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'ATTACKATDAWN',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'ZEBRAS', 'additive_key' => 'RUSSIAN'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'ZEBRAS', 'additive_key' => 'RUSSIAN'],
        ]);

        self::assertSame('ATTACKATDAWN', $dec['result']);
    }

    /**
     * Проверяет автоопределение алфавита при alphabet=auto для шифрования.
     */
    public function testAutoDetectsAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ МИР',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto', 'key' => 'КЛЮЧ', 'additive_key' => 'МОСКВА'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['alphabet']);
        self::assertSame('ru', $result['detected_alphabet']);
    }

    /**
     * Проверяет round-trip для русского алфавита (квадрат 6×6).
     */
    public function testRussianRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'ПРИВЕТ МИР',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'КЛЮЧ', 'additive_key' => 'МОСКВА'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'КЛЮЧ', 'additive_key' => 'МОСКВА'],
        ]);

        self::assertSame('ПРИВЕТМИР', $dec['result']);
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
            'settings'  => ['alphabet' => 'en', 'key' => 'ZEBRAS', 'additive_key' => 'RUSSIAN'],
        ]);
    }

    /**
     * Проверяет, что пустой ключ квадрата вызывает ValidationFailedException.
     */
    public function testThrowsWhenSquareKeyIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => '', 'additive_key' => 'RUSSIAN'],
        ]);
    }

    /**
     * Проверяет, что пустой аддитивный ключ вызывает ValidationFailedException.
     */
    public function testThrowsWhenAdditiveKeyIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'ZEBRAS', 'additive_key' => ''],
        ]);
    }

    /**
     * Проверяет, что расшифровка текста без чисел вызывает ошибку.
     */
    public function testThrowsWhenDecryptTextHasNoNumbers(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'ZEBRAS', 'additive_key' => 'RUSSIAN'],
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
            'settings'  => ['alphabet' => 'en', 'key' => 'ZEBRAS', 'additive_key' => 'RUSSIAN'],
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
            'settings'  => ['alphabet' => 'el', 'key' => 'ZEBRAS', 'additive_key' => 'RUSSIAN'],
        ]);
    }
}
