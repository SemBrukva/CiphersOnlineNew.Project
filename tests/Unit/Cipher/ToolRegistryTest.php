<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\A1z26CipherService;
use App\Cipher\AffineBruteForceService;
use App\Cipher\AffineCipherService;
use App\Cipher\AlbertiCipherService;
use App\Cipher\AlphabetCatalog;
use App\Cipher\AlphabetTool;
use App\Cipher\AnagramSolverService;
use App\Cipher\AtbashCipherService;
use App\Cipher\AutokeyCipherService;
use App\Cipher\BaconCipherService;
use App\Cipher\BeaufortCipherService;
use App\Cipher\BifidCipherService;
use App\Cipher\BigramFrequencyScorer;
use App\Cipher\CaesarBruteForceService;
use App\Cipher\CaesarCipherService;
use App\Cipher\CaseFolder;
use App\Cipher\CipherIdentifierService;
use App\Cipher\ColumnarTranspositionCipherService;
use App\Cipher\EnigmaCipherService;
use App\Cipher\FrequencyAnalysisService;
use App\Cipher\GronsfeldCipherService;
use App\Cipher\HillCipherService;
use App\Cipher\HtmlEncodeCipherService;
use App\Cipher\IndexOfCoincidence;
use App\Cipher\JsonFormatterCipherService;
use App\Cipher\LetterFrequencyScorer;
use App\Cipher\LetterFrequencyService;
use App\Cipher\MorseCipherService;
use App\Cipher\NumbersToLettersService;
use App\Cipher\PlayfairCipherService;
use App\Cipher\PolybiusSquareCipherService;
use App\Cipher\PortaCipherService;
use App\Cipher\RailFenceCipherService;
use App\Cipher\Rot13CipherService;
use App\Cipher\SimpleSubstitutionCipherService;
use App\Cipher\TimestampConverterCipherService;
use App\Cipher\ToolRegistry;
use App\Cipher\TrifidCipherService;
use App\Cipher\VernamCipherService;
use App\Cipher\VigenereCipherService;
use App\Cipher\VigenereCrackerService;
use App\Cipher\XorCipherService;
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
        self::assertSame('affine', $registry->apiAction('classical-ciphers/affine'));
        self::assertSame('affine', $registry->apiAction('classical-ciphers/affinnyj-shifr'));
        self::assertSame('playfair', $registry->apiAction('classical-ciphers/plejfera'));
        self::assertSame('playfair', $registry->apiAction('classical-ciphers/shifr-plejfera'));
        self::assertSame('atbash', $registry->apiAction('classical-ciphers/shifr-atbash'));
        self::assertSame('autokey', $registry->apiAction('classical-ciphers/autokey'));
        self::assertSame('autokey', $registry->apiAction('classical-ciphers/autokey-cipher'));
        self::assertSame('beaufort', $registry->apiAction('classical-ciphers/shifr-bofora'));
        self::assertSame('porta', $registry->apiAction('classical-ciphers/porta'));
        self::assertSame('porta', $registry->apiAction('classical-ciphers/porta-cipher'));
        self::assertSame('vigenere', $registry->apiAction('classical-ciphers/shifr-vizhenera'));
        self::assertSame('vernam', $registry->apiAction('classical-ciphers/shifr-vernama'));
        self::assertSame('bacon', $registry->apiAction('classical-ciphers/shifr-behkona'));
        self::assertSame('rot13', $registry->apiAction('classical-ciphers/rot13'));
        self::assertSame('rot13', $registry->apiAction('classical-ciphers/rot-13'));
        self::assertSame('a1z26', $registry->apiAction('classical-ciphers/shifr-a1z26'));
        self::assertSame('rail-fence', $registry->apiAction('classical-ciphers/rail-fence'));
        self::assertSame('rail-fence', $registry->apiAction('classical-ciphers/railfence'));
        self::assertSame('columnar-transposition', $registry->apiAction('classical-ciphers/columnar-transposition'));
        self::assertSame('columnar-transposition', $registry->apiAction('classical-ciphers/stolbcovyj-shifr-perestanovki'));
        self::assertSame('polybius-square', $registry->apiAction('classical-ciphers/polybius-square'));
        self::assertSame('polybius-square', $registry->apiAction('classical-ciphers/kvadrat-polibiya'));
        self::assertSame('hill', $registry->apiAction('classical-ciphers/hill'));
        self::assertSame('hill', $registry->apiAction('classical-ciphers/shifr-hilla'));
        self::assertSame('xor', $registry->apiAction('classical-ciphers/xor-cipher'));
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
     * Проверяет, что Autokey отдаёт decode-пример для кнопки Use example.
     */
    public function testAutokeyExamplesContainDecodeChip(): void
    {
        $registry = $this->makeRegistry();

        $decode = array_values(array_filter(
            $registry->exampleChips('classical-ciphers/autokey'),
            static fn (array $chip): bool => ($chip['direction'] ?? '') === 'decrypt'
        ));

        self::assertCount(1, $decode);
        self::assertSame('QNXEPV YT WTWP', $decode[0]['value']);
        self::assertSame('QUEENLY', $decode[0]['key']);
        self::assertSame('en', $decode[0]['alphabet']);
    }

    /**
     * Создаёт экземпляр реестра для тестов.
     */
    private function makeRegistry(): ToolRegistry
    {
        $catalog      = new AlphabetCatalog();
        $caseFolder   = new CaseFolder();
        $alphabetTool = new AlphabetTool($catalog, $caseFolder);

        return new ToolRegistry(
            new AffineCipherService(),
            new AtbashCipherService(),
            new BeaufortCipherService(),
            new PortaCipherService(),
            new AutokeyCipherService(),
            new CaesarCipherService(),
            new GronsfeldCipherService(),
            new PlayfairCipherService(),
            new VigenereCipherService(),
            new VernamCipherService(),
            new BaconCipherService(),
            new Rot13CipherService(),
            new A1z26CipherService(),
            new RailFenceCipherService(),
            new ColumnarTranspositionCipherService(),
            new PolybiusSquareCipherService(),
            new HillCipherService(),
            new MorseCipherService(),
            new FrequencyAnalysisService(),
            new CaesarBruteForceService(),
            new LetterFrequencyService(),
            new NumbersToLettersService(),
            new HtmlEncodeCipherService(),
            new JsonFormatterCipherService(),
            new TimestampConverterCipherService(),
            new SimpleSubstitutionCipherService(),
            new XorCipherService(),
            new VigenereCrackerService(),
            new AffineBruteForceService(),
            new BifidCipherService($catalog, $alphabetTool, $caseFolder),
            new TrifidCipherService($catalog, $alphabetTool, $caseFolder),
            new AlbertiCipherService(),
            new EnigmaCipherService(),
            new CipherIdentifierService([], new LetterFrequencyScorer(), new IndexOfCoincidence(), new BigramFrequencyScorer()),
            new AnagramSolverService()
        );
    }
}
