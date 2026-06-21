<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\PortaCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса шифра Porta.
 */
final class PortaCipherServiceTest extends TestCase
{
    /**
     * Проверяет каноническую reciprocal-таблицу Porta.
     */
    public function testProcessIsReciprocal(): void
    {
        $service = new PortaCipherService();

        $encrypted = $service->process('HELLO WORLD', 'PORTA');

        self::assertSame('OYTUB CHJUQ', $encrypted);
        self::assertSame('HELLO WORLD', $service->process($encrypted, 'PORTA'));
    }

    /**
     * Проверяет пары ключевых букв AB, CD и YZ.
     */
    public function testUsesPortaKeyPairs(): void
    {
        $service = new PortaCipherService();

        self::assertSame('NOPQRSTUVWXYZABCDEFGHIJKLM', $service->process('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'A'));
        self::assertSame('OPQRSTUVWXYZNMABCDEFGHIJKL', $service->process('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'C'));
        self::assertSame('ZNOPQRSTUVWXYBCDEFGHIJKLMA', $service->process('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'Z'));
    }

    /**
     * Проверяет сохранение регистра и пропуск нелатинских символов.
     */
    public function testPreservesCaseAndSkipsNonLatinCharacters(): void
    {
        $service = new PortaCipherService();

        self::assertSame('Oytub, Мир! 123', $service->process('Hello, Мир! 123', 'PORTA'));
    }

    /**
     * Проверяет, что неалфавитные символы ключа игнорируются.
     */
    public function testIgnoresNonLettersInKey(): void
    {
        $service = new PortaCipherService();

        self::assertSame(
            $service->process('HELLO', 'PORTA'),
            $service->process('HELLO', 'P-0 O! R? T. A')
        );
    }

    /**
     * Проверяет, что буквы из одной пары ключа (AB, CD, ..., YZ) дают идентичный результат.
     */
    public function testKeyPairsAreEquivalent(): void
    {
        $service = new PortaCipherService();
        $text    = 'THE QUICK BROWN FOX JUMPS OVER THE LAZY DOG';

        $pairs = [['A', 'B'], ['C', 'D'], ['K', 'L'], ['Y', 'Z']];

        foreach ($pairs as [$first, $second]) {
            self::assertSame(
                $service->process($text, $first),
                $service->process($text, $second),
                sprintf('Ключи "%s" и "%s" должны давать одинаковый результат', $first, $second)
            );
        }
    }

    /**
     * Проверяет циклическое использование ключа, когда текст длиннее ключа.
     */
    public function testKeyRepeatsWhenShorterThanText(): void
    {
        $service = new PortaCipherService();

        $longCiphertext = $service->process('ATTACK AT DAWN ON FRIDAY MORNING', 'KEY');
        $repeated       = $service->process('ATTACK AT DAWN ON FRIDAY MORNING', 'KEYKEYKEYKEYKEYKEYKEYKEY');

        self::assertSame($repeated, $longCiphertext);
        self::assertSame('ATTACK AT DAWN ON FRIDAY MORNING', $service->process($longCiphertext, 'KEY'));
    }

    /**
     * Проверяет, что текст возвращается без изменений, когда ключ не содержит латинских букв.
     */
    public function testReturnsTextUnchangedWhenKeyHasNoLatinLetters(): void
    {
        $service = new PortaCipherService();

        self::assertSame('HELLO WORLD', $service->process('HELLO WORLD', '123 !?'));
        self::assertSame('HELLO WORLD', $service->process('HELLO WORLD', ''));
    }

    /**
     * Проверяет, что регистр букв ключа не влияет на результат.
     */
    public function testKeyCaseDoesNotAffectResult(): void
    {
        $service = new PortaCipherService();

        $expected = $service->process('HELLO WORLD', 'PORTA');

        self::assertSame($expected, $service->process('HELLO WORLD', 'porta'));
        self::assertSame($expected, $service->process('HELLO WORLD', 'PoRtA'));
    }
}
