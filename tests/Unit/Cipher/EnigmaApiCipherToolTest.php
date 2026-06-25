<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\EnigmaApiCipherTool;
use App\Cipher\EnigmaCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента симулятора Enigma.
 */
final class EnigmaApiCipherToolTest extends TestCase
{
    private EnigmaApiCipherTool $tool;

    protected function setUp(): void
    {
        $this->tool = new EnigmaApiCipherTool(new EnigmaCipherService());
    }

    /**
     * Проверяет, что action() возвращает 'enigma'.
     */
    public function testActionReturnsEnigma(): void
    {
        self::assertSame('enigma', $this->tool->action());
    }

    /**
     * Проверяет, что вызов с минимальными настройками работает.
     */
    public function testEncryptWithDefaults(): void
    {
        $result = $this->tool->execute([
            'text' => 'AAAAA',
        ]);

        self::assertTrue((bool) $result['ok']);
        self::assertSame('BDZGO', $result['result']);
        self::assertSame(5, $result['letters_processed']);
        self::assertSame('F', $result['final_positions']['right']);
    }

    /**
     * Проверяет ошибку при пустом тексте.
     */
    public function testThrowsWhenTextIsEmpty(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->tool->execute(['text' => '']);
    }

    /**
     * Проверяет ошибку при неверном рефлекторе.
     */
    public function testThrowsOnInvalidReflector(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->tool->execute([
            'text'     => 'HELLO',
            'settings' => ['enigma_reflector' => 'Z'],
        ]);
    }

    /**
     * Проверяет ошибку при повторе ротора.
     */
    public function testThrowsOnDuplicateRotors(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->tool->execute([
            'text'     => 'HELLO',
            'settings' => [
                'enigma_rotor_left'   => 'I',
                'enigma_rotor_middle' => 'I',
                'enigma_rotor_right'  => 'III',
            ],
        ]);
    }

    /**
     * Проверяет ошибку при неверном ротор-имени.
     */
    public function testThrowsOnInvalidRotorName(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->tool->execute([
            'text'     => 'HELLO',
            'settings' => [
                'enigma_rotor_left'   => 'VI', // не существует в Enigma I
                'enigma_rotor_middle' => 'II',
                'enigma_rotor_right'  => 'III',
            ],
        ]);
    }

    /**
     * Проверяет нормализацию plugboard-строки в ответе.
     */
    public function testNormalizesPlugboardInResponse(): void
    {
        $result = $this->tool->execute([
            'text'     => 'HELLO',
            'settings' => ['enigma_plugboard' => 'a-b c,d'],
        ]);

        self::assertSame('AB CD', $result['plugboard_normalized']);
    }

    /**
     * Проверяет ошибку plugboard с нечётным числом букв.
     */
    public function testThrowsOnOddPlugboard(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->tool->execute([
            'text'     => 'HELLO',
            'settings' => ['enigma_plugboard' => 'ABC'],
        ]);
    }

    /**
     * Реципрокность через API: encrypt → decrypt одной и той же конфигурацией.
     */
    public function testApiReciprocity(): void
    {
        $settings = [
            'enigma_reflector'    => 'B',
            'enigma_rotor_left'   => 'I',
            'enigma_rotor_middle' => 'II',
            'enigma_rotor_right'  => 'III',
            'enigma_ring_left'    => 'A',
            'enigma_ring_middle'  => 'A',
            'enigma_ring_right'   => 'A',
            'enigma_pos_left'     => 'M',
            'enigma_pos_middle'   => 'C',
            'enigma_pos_right'    => 'K',
            'enigma_plugboard'    => 'AB CD EF',
        ];

        $enc = $this->tool->execute(['text' => 'ATTACK AT DAWN', 'settings' => $settings]);
        $dec = $this->tool->execute(['text' => $enc['result'], 'settings' => $settings]);
        self::assertSame('ATTACK AT DAWN', $dec['result']);
    }

    /**
     * Проверяет, что некорректная буква-позиция молча сводится к 'A'.
     */
    public function testInvalidPositionFallsBackToA(): void
    {
        $result = $this->tool->execute([
            'text'     => 'HELLO',
            'settings' => ['enigma_pos_left' => '!'],
        ]);
        self::assertTrue((bool) $result['ok']);
    }
}
