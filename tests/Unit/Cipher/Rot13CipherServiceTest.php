<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\Rot13CipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса ROT13.
 */
final class Rot13CipherServiceTest extends TestCase
{
    /**
     * Проверяет классическое ROT13-преобразование.
     */
    public function testProcessTransformsEnglishLetters(): void
    {
        $service = new Rot13CipherService();

        self::assertSame('URYYB JBEYQ', $service->process('HELLO WORLD'));
    }

    /**
     * Проверяет, что повторное применение ROT13 возвращает исходный текст.
     */
    public function testProcessIsReciprocal(): void
    {
        $service = new Rot13CipherService();
        $encrypted = $service->process('Hello, World!');

        self::assertSame('Hello, World!', $service->process($encrypted));
    }

    /**
     * Проверяет сохранение регистра и небуквенных символов.
     */
    public function testPreservesCaseAndNonLatinCharacters(): void
    {
        $service = new Rot13CipherService();

        self::assertSame('Uryyb, Привет 123!', $service->process('Hello, Привет 123!'));
    }

    /**
     * Проверяет определение латинских символов во входном тексте.
     */
    public function testDetectsLatinCharacters(): void
    {
        $service = new Rot13CipherService();

        self::assertTrue($service->hasLatinCharacters('Привет A'));
        self::assertFalse($service->hasLatinCharacters('Привет 123'));
    }
}
