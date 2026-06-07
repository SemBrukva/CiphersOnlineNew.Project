<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\A1z26ApiCipherTool;
use App\Cipher\A1z26CipherService;
use App\Cipher\AffineApiCipherTool;
use App\Cipher\AffineCipherService;
use App\Cipher\ApiCipherToolRegistry;
use App\Cipher\AtbashApiCipherTool;
use App\Cipher\AtbashCipherService;
use App\Cipher\BaconApiCipherTool;
use App\Cipher\BaconCipherService;
use App\Cipher\BeaufortApiCipherTool;
use App\Cipher\BeaufortCipherService;
use App\Cipher\CaesarApiCipherTool;
use App\Cipher\CaesarCipherService;
use App\Cipher\ColumnarTranspositionApiCipherTool;
use App\Cipher\ColumnarTranspositionCipherService;
use App\Cipher\GronsfeldApiCipherTool;
use App\Cipher\GronsfeldCipherService;
use App\Cipher\PlayfairApiCipherTool;
use App\Cipher\PlayfairCipherService;
use App\Cipher\RailFenceApiCipherTool;
use App\Cipher\RailFenceCipherService;
use App\Cipher\VernamApiCipherTool;
use App\Cipher\VernamCipherService;
use App\Cipher\VigenereApiCipherTool;
use App\Cipher\VigenereCipherService;
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
            new AffineApiCipherTool(new AffineCipherService()),
            new CaesarApiCipherTool(new CaesarCipherService()),
            new AtbashApiCipherTool(new AtbashCipherService()),
            new PlayfairApiCipherTool(new PlayfairCipherService()),
            new BeaufortApiCipherTool(new BeaufortCipherService()),
            new GronsfeldApiCipherTool(new GronsfeldCipherService()),
            new VigenereApiCipherTool(new VigenereCipherService()),
            new VernamApiCipherTool(new VernamCipherService()),
            new BaconApiCipherTool(new BaconCipherService()),
            new A1z26ApiCipherTool(new A1z26CipherService()),
            new RailFenceApiCipherTool(new RailFenceCipherService()),
            new ColumnarTranspositionApiCipherTool(new ColumnarTranspositionCipherService())
        );
    }
}
