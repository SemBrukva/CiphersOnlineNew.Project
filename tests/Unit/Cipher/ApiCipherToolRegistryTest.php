<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\ApiCipherToolRegistry;
use App\Cipher\BeaufortApiCipherTool;
use App\Cipher\BeaufortCipherService;
use App\Cipher\CaesarApiCipherTool;
use App\Cipher\CaesarCipherService;
use App\Cipher\GronsfeldApiCipherTool;
use App\Cipher\GronsfeldCipherService;
use App\Cipher\PlayfairApiCipherTool;
use App\Cipher\PlayfairCipherService;
use App\Cipher\VigenereApiCipherTool;
use App\Cipher\VigenereCipherService;
use App\Cipher\VernamApiCipherTool;
use App\Cipher\VernamCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты реестра API-инструментов шифрования.
 */
final class ApiCipherToolRegistryTest extends TestCase
{
    /**
     * Проверяет, что реестр делегирует выполнение выбранному инструменту.
     */
    public function testExecutesCipherToolByAction(): void
    {
        $registry = $this->makeRegistry();

        $result = $registry->execute('caesar', [
            'text' => 'HELLO',
            'direction' => 'encrypt',
            'settings' => [
                'alphabet' => 'en',
                'shift' => 3,
            ],
        ]);

        self::assertTrue((bool) ($result['ok'] ?? false));
        self::assertSame('KHOOR', (string) ($result['result'] ?? ''));
    }

    /**
     * Создаёт экземпляр реестра API-инструментов для тестов.
     */
    private function makeRegistry(): ApiCipherToolRegistry
    {
        return new ApiCipherToolRegistry(
            new CaesarApiCipherTool(new CaesarCipherService()),
            new PlayfairApiCipherTool(new PlayfairCipherService()),
            new BeaufortApiCipherTool(new BeaufortCipherService()),
            new GronsfeldApiCipherTool(new GronsfeldCipherService()),
            new VigenereApiCipherTool(new VigenereCipherService()),
            new VernamApiCipherTool(new VernamCipherService())
        );
    }
}
