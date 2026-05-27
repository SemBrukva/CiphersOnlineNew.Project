<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\BeaufortCipherService;
use App\Cipher\AtbashCipherService;
use App\Cipher\BaconCipherService;
use App\Cipher\CaesarCipherService;
use App\Cipher\GronsfeldCipherService;
use App\Cipher\PlayfairCipherService;
use App\Cipher\ToolRegistry;
use App\Cipher\VigenereCipherService;
use App\Cipher\VernamCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты реестра инструментов шифрования/декодирования.
 */
final class ToolRegistryTest extends TestCase
{
    /**
     * Проверяет, что API-экшены возвращаются для канонических slug и их алиасов.
     */
    public function testReturnsApiActionForCanonicalSlugAndAlias(): void
    {
        $registry = $this->makeRegistry();

        self::assertSame('playfair', $registry->apiAction('classical-ciphers/playfair'));
        self::assertSame('playfair', $registry->apiAction('classical-ciphers/plejfera'));
        self::assertSame('playfair', $registry->apiAction('classical-ciphers/shifr-plejfera'));
        self::assertSame('atbash', $registry->apiAction('classical-ciphers/shifr-atbash'));
        self::assertSame('beaufort', $registry->apiAction('classical-ciphers/shifr-bofora'));
        self::assertSame('vigenere', $registry->apiAction('classical-ciphers/shifr-vizhenera'));
        self::assertSame('vernam', $registry->apiAction('classical-ciphers/shifr-vernama'));
        self::assertSame('bacon', $registry->apiAction('classical-ciphers/shifr-behkona'));
    }

    /**
     * Проверяет, что примеры для алиасов совпадают с каноническим инструментом.
     */
    public function testReturnsSameExamplesForAliasAndCanonicalSlug(): void
    {
        $registry = $this->makeRegistry();

        self::assertSame(
            $registry->exampleChips('classical-ciphers/playfair'),
            $registry->exampleChips('classical-ciphers/plejfera')
        );
    }

    /**
     * Проверяет, что настройки API-шифров доступны через реестр.
     */
    public function testReturnsSettingsForApiCipherTool(): void
    {
        $registry = $this->makeRegistry();

        self::assertNotSame([], $registry->settings('classical-ciphers/caesar'));
        self::assertNotSame([], $registry->settings('classical-ciphers/shifr-gronsfelda'));
    }

    /**
     * Создаёт экземпляр реестра для тестов.
     */
    private function makeRegistry(): ToolRegistry
    {
        return new ToolRegistry(
            new AtbashCipherService(),
            new BeaufortCipherService(),
            new CaesarCipherService(),
            new GronsfeldCipherService(),
            new PlayfairCipherService(),
            new VigenereCipherService(),
            new VernamCipherService(),
            new BaconCipherService()
        );
    }
}
