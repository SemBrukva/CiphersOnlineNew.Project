<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cache\NullCache;
use App\Cipher\A1z26ApiCipherTool;
use App\Cipher\A1z26CipherService;
use App\Cipher\AffineApiCipherTool;
use App\Cipher\AffineBruteForceApiCipherTool;
use App\Cipher\AffineCipherService;
use App\Cipher\AlbertiApiCipherTool;
use App\Cipher\AlbertiCipherService;
use App\Cipher\AlphabetCatalog;
use App\Cipher\AlphabetTool;
use App\Cipher\ApiCipherToolExecutorInterface;
use App\Cipher\ApiCipherToolRegistry;
use App\Cipher\AtbashApiCipherTool;
use App\Cipher\AtbashCipherService;
use App\Cipher\AutokeyApiCipherTool;
use App\Cipher\AutokeyCipherService;
use App\Cipher\BaconApiCipherTool;
use App\Cipher\BaconCipherService;
use App\Cipher\BeaufortApiCipherTool;
use App\Cipher\BeaufortCipherService;
use App\Cipher\BifidApiCipherTool;
use App\Cipher\BifidCipherService;
use App\Cipher\BigramFrequencyScorer;
use App\Cipher\CaesarApiCipherTool;
use App\Cipher\CaesarBruteForceApiCipherTool;
use App\Cipher\CaesarCipherService;
use App\Cipher\CaseFolder;
use App\Cipher\CipherIdentifierApiCipherTool;
use App\Cipher\CipherIdentifierService;
use App\Cipher\ColumnarTranspositionApiCipherTool;
use App\Cipher\ColumnarTranspositionCipherService;
use App\Cipher\GronsfeldApiCipherTool;
use App\Cipher\GronsfeldCipherService;
use App\Cipher\HillApiCipherTool;
use App\Cipher\HillCipherService;
use App\Cipher\IndexOfCoincidence;
use App\Cipher\LetterFrequencyScorer;
use App\Cipher\PlayfairApiCipherTool;
use App\Cipher\PlayfairCipherService;
use App\Cipher\PolybiusSquareApiCipherTool;
use App\Cipher\PolybiusSquareCipherService;
use App\Cipher\PortaApiCipherTool;
use App\Cipher\PortaCipherService;
use App\Cipher\RailFenceApiCipherTool;
use App\Cipher\RailFenceCipherService;
use App\Cipher\Rot13ApiCipherTool;
use App\Cipher\Rot13CipherService;
use App\Cipher\SimpleSubstitutionApiCipherTool;
use App\Cipher\SimpleSubstitutionCipherService;
use App\Cipher\TrifidApiCipherTool;
use App\Cipher\TrifidCipherService;
use App\Cipher\VernamApiCipherTool;
use App\Cipher\VernamCipherService;
use App\Cipher\VigenereApiCipherTool;
use App\Cipher\VigenereCipherService;
use App\Cipher\VigenereCrackerApiCipherTool;
use App\Cipher\XorApiCipherTool;
use App\Cipher\XorCipherService;
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
     * Проверяет, что реестр выполняет API-инструмент Autokey по action.
     */
    public function testExecutesAutokeyCipherToolByAction(): void
    {
        $registry = $this->makeRegistry();

        $result = $registry->execute('autokey', [
            'text' => 'QNXEPV YT WTWP',
            'direction' => 'decrypt',
            'settings' => [
                'alphabet' => 'en',
                'key' => 'QUEENLY',
            ],
        ]);

        self::assertTrue((bool) ($result['ok'] ?? false));
        self::assertSame('ATTACK AT DAWN', (string) ($result['result'] ?? ''));
    }

    /**
     * Проверяет, что реестр выполняет API-инструмент Porta по action.
     */
    public function testExecutesPortaCipherToolByAction(): void
    {
        $registry = $this->makeRegistry();

        $result = $registry->execute('porta', [
            'text' => 'HELLO WORLD',
            'direction' => 'encrypt',
            'settings' => [
                'key' => 'PORTA',
            ],
        ]);

        self::assertTrue((bool) ($result['ok'] ?? false));
        self::assertSame('OYTUB CHJUQ', (string) ($result['result'] ?? ''));
    }

    /**
     * Создаёт экземпляр реестра API-инструментов для тестов.
     *
     * CipherIdentifierApiCipherTool использует mock реестра как inner registry,
     * чтобы разорвать circular dependency в тестах.
     */
    private function makeRegistry(): ApiCipherToolRegistry
    {
        $scorer  = new LetterFrequencyScorer();
        $ioc     = new IndexOfCoincidence();
        $caesar  = new CaesarCipherService();
        $catalog = new AlphabetCatalog();
        $folder  = new CaseFolder();
        $cache   = new NullCache();
        $bigram  = new BigramFrequencyScorer();

        // Mock исполнителя для CipherIdentifierApiCipherTool.
        // Auto-dispatch не тестируется здесь, поэтому mock достаточен.
        /** @var ApiCipherToolExecutorInterface $mockRegistry */
        $mockRegistry = $this->createMock(ApiCipherToolExecutorInterface::class);

        $cipherIdentifierTool = new CipherIdentifierApiCipherTool(
            new CipherIdentifierService([], $scorer, $ioc, $bigram),
            $mockRegistry,
        );

        return new ApiCipherToolRegistry(
            new AffineApiCipherTool(new AffineCipherService()),
            new CaesarApiCipherTool($caesar),
            new AtbashApiCipherTool(new AtbashCipherService()),
            new PlayfairApiCipherTool(new PlayfairCipherService()),
            new BeaufortApiCipherTool(new BeaufortCipherService()),
            new PortaApiCipherTool(new PortaCipherService()),
            new AutokeyApiCipherTool(new AutokeyCipherService()),
            new GronsfeldApiCipherTool(new GronsfeldCipherService()),
            new VigenereApiCipherTool(new VigenereCipherService()),
            new VernamApiCipherTool(new VernamCipherService()),
            new BaconApiCipherTool(new BaconCipherService()),
            new Rot13ApiCipherTool(new Rot13CipherService()),
            new A1z26ApiCipherTool(new A1z26CipherService()),
            new RailFenceApiCipherTool(new RailFenceCipherService()),
            new ColumnarTranspositionApiCipherTool(new ColumnarTranspositionCipherService()),
            new PolybiusSquareApiCipherTool(new PolybiusSquareCipherService()),
            new HillApiCipherTool(new HillCipherService()),
            new CaesarBruteForceApiCipherTool($caesar, $scorer),
            new AffineBruteForceApiCipherTool(new AffineCipherService(), $scorer, $catalog, $bigram, $cache),
            new SimpleSubstitutionApiCipherTool(new SimpleSubstitutionCipherService()),
            new XorApiCipherTool(new XorCipherService()),
            new VigenereCrackerApiCipherTool(new VigenereCipherService(), $scorer, $catalog, $bigram, $cache),
            new BifidApiCipherTool(new BifidCipherService($catalog, new AlphabetTool($catalog, $folder), $folder)),
            new TrifidApiCipherTool(new TrifidCipherService($catalog, new AlphabetTool($catalog, $folder), $folder)),
            new AlbertiApiCipherTool(new AlbertiCipherService()),
            $cipherIdentifierTool,
        );
    }
}
