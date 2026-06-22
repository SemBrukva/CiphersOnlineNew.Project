<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\AlbertiApiCipherTool;
use App\Cipher\AlbertiCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента шифра Альберти.
 */
final class AlbertiApiCipherToolTest extends TestCase
{
    private AlbertiApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new AlbertiApiCipherTool(new AlbertiCipherService());
    }

    /**
     * Проверяет, что action() возвращает строку 'alberti'.
     */
    public function testActionReturnsAlberti(): void
    {
        self::assertSame('alberti', $this->tool->action());
    }

    /**
     * Проверяет успешное шифрование через API-инструмент.
     */
    public function testEncryptReturnsExpectedResult(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ALBERTI', 'alberti_index' => 'A'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('CRHHM WMPHE', $result['result']);
        self::assertArrayHasKey('inner_alphabet', $result);
        self::assertArrayHasKey('index_offset', $result);
        self::assertSame(0, $result['index_offset']);
    }

    /**
     * Проверяет дешифрование через API-инструмент.
     */
    public function testDecryptReturnsOriginalText(): void
    {
        $result = $this->tool->execute([
            'text'      => 'CRHHM WMPHE',
            'direction' => 'decrypt',
            'settings'  => ['key' => 'ALBERTI', 'alberti_index' => 'A'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('HELLO WORLD', $result['result']);
    }

    /**
     * Проверяет, что inner_alphabet содержит 26 прописных букв.
     */
    public function testInnerAlphabetInResponse(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HI',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ZEBRAS', 'alberti_index' => 'A'],
        ]);

        self::assertMatchesRegularExpression('/^[A-Z]{26}$/', $result['inner_alphabet']);
    }

    /**
     * Проверяет ошибку при пустом тексте.
     */
    public function testThrowsWhenTextIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'KEY', 'alberti_index' => 'A'],
        ]);
    }

    /**
     * Проверяет ошибку, если текст не содержит латинских букв.
     */
    public function testThrowsWhenTextHasNoLatinLetters(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'Привет 123',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'KEY', 'alberti_index' => 'A'],
        ]);
    }

    /**
     * Проверяет ошибку при некорректном направлении.
     */
    public function testThrowsWhenDirectionIsInvalid(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'bad_direction',
            'settings'  => ['key' => 'KEY', 'alberti_index' => 'A'],
        ]);
    }

    /**
     * Проверяет, что пустой ключ допускается (стандартный алфавит).
     */
    public function testEmptyKeyIsAllowed(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => '', 'alberti_index' => 'A'],
        ]);

        self::assertTrue((bool) $result['ok']);
        // При пустом ключе внутренний алфавит = ABCDEFGHIJKLMNOPQRSTUVWXYZ
        // И шифрование с индексом A идентично: A→A, E→E, H→H, L→L, O→O
        self::assertSame('HELLO', $result['result']);
    }

    /**
     * Проверяет, что некорректный индекс молча заменяется на 'A'.
     */
    public function testInvalidIndexFallsBackToA(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ALBERTI', 'alberti_index' => '1'],
        ]);

        $resultA = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ALBERTI', 'alberti_index' => 'A'],
        ]);

        self::assertSame($resultA['result'], $result['result']);
    }

    /**
     * Проверяет корректный index_offset для буквы B.
     */
    public function testIndexOffsetForLetterB(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'KEY', 'alberti_index' => 'B'],
        ]);

        self::assertSame(1, $result['index_offset']);
    }

    /**
     * Проверяет, что index_offset для Z равен 25.
     */
    public function testIndexOffsetForLetterZ(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'KEY', 'alberti_index' => 'Z'],
        ]);

        self::assertSame(25, $result['index_offset']);
    }

    /**
     * Проверяет, что строчный индекс 'a' нормализуется в 'A'.
     */
    public function testLowercaseIndexNormalizesToA(): void
    {
        $lower = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ALBERTI', 'alberti_index' => 'a'],
        ]);
        $upper = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ALBERTI', 'alberti_index' => 'A'],
        ]);

        self::assertSame($upper['result'], $lower['result']);
        self::assertSame(0, $lower['index_offset']);
    }

    /**
     * Проверяет, что отсутствие alberti_index в settings дефолтится к 'A'.
     */
    public function testMissingAlbertiIndexDefaultsToA(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ALBERTI'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame(0, $result['index_offset']);
    }

    /**
     * Проверяет, что отсутствие поля settings целиком не вызывает фатальной ошибки.
     */
    public function testMissingSettingsUsesDefaults(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame(0, $result['index_offset']);
    }

    /**
     * Проверяет, что inner_alphabet в ответе соответствует ключу ZEBRAS.
     */
    public function testInnerAlphabetMatchesZebrasKey(): void
    {
        $result = $this->tool->execute([
            'text'      => 'ATTACK AT DAWN',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'ZEBRAS', 'alberti_index' => 'A'],
        ]);

        self::assertStringStartsWith('Z', $result['inner_alphabet']);
        self::assertSame(26, strlen($result['inner_alphabet']));
        self::assertMatchesRegularExpression('/^[A-Z]{26}$/', $result['inner_alphabet']);
    }

    /**
     * Проверяет, что текст из одних пробелов вызывает ошибку (нет латинских букв).
     */
    public function testThrowsWhenTextIsOnlySpaces(): void
    {
        $this->expectException(ValidationFailedException::class);

        $this->tool->execute([
            'text'      => '   ',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'KEY', 'alberti_index' => 'A'],
        ]);
    }

    /**
     * Проверяет, что при пустом тексте И неверном direction возвращаются оба сообщения об ошибке.
     */
    public function testMultipleValidationErrorsReportedTogether(): void
    {
        try {
            $this->tool->execute([
                'text'      => '',
                'direction' => 'bad',
                'settings'  => ['key' => 'KEY', 'alberti_index' => 'A'],
            ]);
            self::fail('Expected ValidationFailedException');
        } catch (ValidationFailedException $e) {
            $details = $e->details();
            self::assertArrayHasKey('text', $details['errors']);
            self::assertArrayHasKey('direction', $details['errors']);
        }
    }

    /**
     * Проверяет, что ключ с цифрами и спецсимволами принимается (нелатинские символы игнорируются).
     */
    public function testKeyWithNonLatinCharsIsAccepted(): void
    {
        $result = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'KEY123!', 'alberti_index' => 'A'],
        ]);

        $resultClean = $this->tool->execute([
            'text'      => 'HELLO',
            'direction' => 'encrypt',
            'settings'  => ['key' => 'KEY', 'alberti_index' => 'A'],
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame($resultClean['result'], $result['result']);
    }
}
