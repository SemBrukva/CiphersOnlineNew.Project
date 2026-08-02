<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AdfgvxApiCipherTool;
use App\Cipher\AdfgvxCipherService;
use App\Cipher\AlphabetCatalog;
use App\Cipher\AlphabetTool;
use App\Cipher\CaseFolder;
use App\Cipher\ColumnarTranspositionCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра ADFGVX.
 */
final class AdfgvxApiCipherToolTest extends TestCase
{
    private AdfgvxApiCipherTool $tool;

    protected function setUp(): void
    {
        $catalog    = new AlphabetCatalog();
        $caseFolder = new CaseFolder();
        $service    = new AdfgvxCipherService(
            $catalog,
            new AlphabetTool($catalog, $caseFolder),
            $caseFolder,
            new ColumnarTranspositionCipherService()
        );
        $this->tool = new AdfgvxApiCipherTool($service);
    }

    /**
     * Проверяет, что action() возвращает строку 'adfgvx'.
     */
    public function testActionReturnsAdfgvx(): void
    {
        self::assertSame('adfgvx', $this->tool->action());
    }

    /**
     * Проверяет корректное шифрование с двумя ключами.
     */
    public function testEncryptReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ATTACK AT DAWN',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'PRIVACY', 'transposition_key' => 'BATTLE'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('VVVVAAAAGFFXGFDFGAGGGXGX', $result['result']);
        self::assertSame('PRIVACY', $result['key']);
        self::assertSame('en', $result['alphabet']);
    }

    /**
     * Проверяет round-trip: шифрование и расшифровка возвращают оригинал.
     */
    public function testDecryptRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'ATTACKATDAWN',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'PRIVACY', 'transposition_key' => 'BATTLE'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'PRIVACY', 'transposition_key' => 'BATTLE'],
        ]);

        self::assertSame('ATTACKATDAWN', $dec['result']);
    }

    /**
     * Проверяет, что цифры шифруются (особенность квадрата 6×6).
     */
    public function testDigitsAreEncrypted(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'AGENT 007',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'SECRET', 'transposition_key' => 'GERMAN'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'SECRET', 'transposition_key' => 'GERMAN'],
        ]);

        self::assertSame('AGENT007', $dec['result']);
    }

    /**
     * Проверяет автоопределение алфавита при alphabet=auto для шифрования.
     */
    public function testAutoDetectsAlphabet(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ПРИВЕТ МИР',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'auto', 'key' => 'КЛЮЧ', 'transposition_key' => 'ШИФР'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('ru', $result['alphabet']);
        self::assertSame('ru', $result['detected_alphabet']);
    }

    /**
     * Проверяет round-trip для русского алфавита (квадрат 6×6 с pad-цифрами).
     */
    public function testRussianRoundTrip(): void
    {
        $enc = $this->tool->execute([
            'text'      => 'ПРИВЕТ МИР',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'КЛЮЧ', 'transposition_key' => 'ШИФР'],
        ]);

        $dec = $this->tool->execute([
            'text'      => $enc['result'],
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'ru', 'key' => 'КЛЮЧ', 'transposition_key' => 'ШИФР'],
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
            'settings'  => ['alphabet' => 'en', 'key' => 'KEY', 'transposition_key' => 'MOT'],
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
            'settings'  => ['alphabet' => 'en', 'key' => '', 'transposition_key' => 'MOT'],
        ]);
    }

    /**
     * Проверяет, что пустой ключ транспозиции вызывает ValidationFailedException.
     */
    public function testThrowsWhenTranspositionKeyIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'KEY', 'transposition_key' => ''],
        ]);
    }

    /**
     * Проверяет, что расшифровка текста без меток ADFGVX вызывает ошибку.
     */
    public function testThrowsWhenDecryptTextHasNoLabels(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '12345',
            'direction' => 'decrypt',
            'settings'  => ['alphabet' => 'en', 'key' => 'PRIVACY', 'transposition_key' => 'BATTLE'],
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
            'settings'  => ['alphabet' => 'en', 'key' => 'KEY', 'transposition_key' => 'MOT'],
        ]);
    }

    /**
     * Проверяет, что неподдерживаемый алфавит вызывает ValidationFailedException.
     */
    public function testThrowsWhenAlphabetIsUnsupported(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'BONJOUR',
            'direction' => 'encrypt',
            'settings'  => ['alphabet' => 'fr', 'key' => 'CLE', 'transposition_key' => 'MOT'],
        ]);
    }
}
