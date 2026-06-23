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
use App\Cipher\Detector\A1z26Detector;
use App\Cipher\Detector\AffineDetector;
use App\Cipher\Detector\AlbertiDetector;
use App\Cipher\Detector\AtbashDetector;
use App\Cipher\Detector\AutokeyDetector;
use App\Cipher\Detector\BaconDetector;
use App\Cipher\Detector\Base64Detector;
use App\Cipher\Detector\BeaufortDetector;
use App\Cipher\Detector\BifidDetector;
use App\Cipher\Detector\BinaryDetector;
use App\Cipher\Detector\CaesarDetector;
use App\Cipher\Detector\ColumnarTranspositionDetector;
use App\Cipher\Detector\GronsfeldDetector;
use App\Cipher\Detector\HexDetector;
use App\Cipher\Detector\HillDetector;
use App\Cipher\Detector\JwtDetector;
use App\Cipher\Detector\MorseCodeDetector;
use App\Cipher\Detector\PlayfairDetector;
use App\Cipher\Detector\PolybiusSquareDetector;
use App\Cipher\Detector\RailFenceDetector;
use App\Cipher\Detector\Rot13Detector;
use App\Cipher\Detector\SimpleSubstitutionDetector;
use App\Cipher\Detector\TrifidDetector;
use App\Cipher\Detector\UnicodeEscapeDetector;
use App\Cipher\Detector\UrlEncodedDetector;
use App\Cipher\Detector\VigenereDetector;
use App\Cipher\Detector\XorDetector;
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
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента определения шифра.
 */
final class CipherIdentifierApiCipherToolTest extends TestCase
{
    private CipherIdentifierApiCipherTool $tool;

    private ApiCipherToolRegistry $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $scorer  = new LetterFrequencyScorer();
        $ioc     = new IndexOfCoincidence();
        $caesar  = new CaesarCipherService();
        $catalog = new AlphabetCatalog();
        $folder  = new CaseFolder();

        $service = new CipherIdentifierService(
            [
                new JwtDetector(),
                new MorseCodeDetector(),
                new BaconDetector(),
                new BinaryDetector(),
                new HexDetector(),
                new Base64Detector(),
                new A1z26Detector(),
                new PolybiusSquareDetector(),
                new UrlEncodedDetector(),
                new UnicodeEscapeDetector(),
                new Rot13Detector($scorer, $caesar),
                new CaesarDetector($scorer, $caesar),
                new AtbashDetector($scorer, new AtbashCipherService()),
                new AffineDetector(),
                new SimpleSubstitutionDetector(),
                new XorDetector(),
                new VigenereDetector(),
                new BeaufortDetector(),
                new AutokeyDetector(),
                new GronsfeldDetector(),
                new AlbertiDetector(),
                new BifidDetector(),
                new TrifidDetector(),
                new RailFenceDetector($scorer),
                new ColumnarTranspositionDetector($scorer),
                new PlayfairDetector(),
                new HillDetector(),
            ],
            $scorer,
            $ioc,
        );

        $this->registry = new ApiCipherToolRegistry(
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
            new AffineBruteForceApiCipherTool(new AffineCipherService(), $scorer, $catalog, new BigramFrequencyScorer(), new NullCache()),
            new SimpleSubstitutionApiCipherTool(new SimpleSubstitutionCipherService()),
            new XorApiCipherTool(new XorCipherService()),
            new VigenereCrackerApiCipherTool(new VigenereCipherService(), $scorer, $catalog, new BigramFrequencyScorer(), new NullCache()),
            new BifidApiCipherTool(new BifidCipherService($catalog, new AlphabetTool($catalog, $folder), $folder)),
            new TrifidApiCipherTool(new TrifidCipherService($catalog, new AlphabetTool($catalog, $folder), $folder)),
            new AlbertiApiCipherTool(new AlbertiCipherService()),
        );

        $this->tool = new CipherIdentifierApiCipherTool($service, $this->registry);
    }

    /**
     * Проверяет, что action() возвращает 'cipher-identifier'.
     */
    public function testActionReturnsCipherIdentifier(): void
    {
        self::assertSame('cipher-identifier', $this->tool->action());
    }

    /**
     * Проверяет, что пустой текст вызывает ValidationFailedException.
     */
    public function testEmptyTextThrowsValidationException(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->tool->execute(['text' => '']);
    }

    /**
     * Проверяет, что текст с пробелами вызывает ValidationFailedException.
     */
    public function testWhitespaceOnlyTextThrowsValidationException(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->tool->execute(['text' => '   ']);
    }

    /**
     * Проверяет, что слишком длинный текст вызывает ValidationFailedException.
     */
    public function testTooLongTextThrowsValidationException(): void
    {
        $this->expectException(ValidationFailedException::class);
        $this->tool->execute(['text' => str_repeat('A', CipherIdentifierApiCipherTool::MAX_TEXT_LENGTH + 1)]);
    }

    /**
     * Проверяет, что азбука Морзе возвращает кандидатов.
     */
    public function testMorseCodeReturnsCandidates(): void
    {
        $result = $this->tool->execute(['text' => '.... . .-.. .-.. --- / .-- --- .-. .-.. -...']);

        self::assertTrue($result['ok']);
        self::assertNotEmpty($result['candidates']);
        self::assertSame('CIPHER_NAME_MORSE_CODE', $result['candidates'][0]['cipher_key']);
        self::assertGreaterThan(0.80, $result['candidates'][0]['confidence']);
    }

    /**
     * Проверяет, что Base64 возвращает кандидата с правильным ключом.
     */
    public function testBase64ReturnsCandidates(): void
    {
        $result = $this->tool->execute(['text' => 'SGVsbG8gV29ybGQh']);

        self::assertTrue($result['ok']);
        self::assertNotEmpty($result['candidates']);
        self::assertSame('CIPHER_NAME_BASE64', $result['candidates'][0]['cipher_key']);
    }

    /**
     * Проверяет, что Caesar shift=3 на надёжном тексте автоматически запускает brute-force.
     *
     * Это основной регрессионный тест против бага «AUTO_THRESHOLD недостижим».
     */
    public function testCaesarAutoTriggersBruteForce(): void
    {
        $result = $this->tool->execute([
            'text' => 'KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ',
        ]);

        self::assertTrue($result['ok']);
        self::assertNotEmpty($result['candidates']);
        self::assertSame('CIPHER_NAME_CAESAR', $result['candidates'][0]['cipher_key']);
        self::assertGreaterThanOrEqual(CipherIdentifierService::AUTO_THRESHOLD, $result['candidates'][0]['confidence']);
        self::assertSame('caesar-brute-force', $result['auto_action']);
        self::assertIsArray($result['auto_result']);
        self::assertTrue($result['auto_result']['ok']);
        self::assertArrayHasKey('results', $result['auto_result']);
        self::assertSame(3, $result['auto_result']['best_shift']);
    }

    /**
     * Проверяет структуру каждого кандидата в ответе.
     */
    public function testCandidatesHaveExpectedStructure(): void
    {
        $result = $this->tool->execute(['text' => '.... . .-.. .-.. ---']);

        self::assertNotEmpty($result['candidates']);
        $candidate = $result['candidates'][0];

        self::assertArrayHasKey('tool_slug', $candidate);
        self::assertArrayHasKey('cipher_key', $candidate);
        self::assertArrayHasKey('confidence', $candidate);
        self::assertArrayHasKey('confidence_pct', $candidate);
        self::assertArrayHasKey('evidence_keys', $candidate);
        self::assertArrayHasKey('brute_force_action', $candidate);
        self::assertIsArray($candidate['evidence_keys']);
        self::assertGreaterThanOrEqual(0, $candidate['confidence_pct']);
        self::assertLessThanOrEqual(100, $candidate['confidence_pct']);
    }

    /**
     * Проверяет, что auto_action/auto_result равны null, когда нет лидера выше порога.
     */
    public function testAutoActionNullWhenNoStrongLeader(): void
    {
        $result = $this->tool->execute(['text' => '.... . .-.. .-.. ---']);

        self::assertArrayHasKey('auto_action', $result);
        self::assertArrayHasKey('auto_result', $result);
        // Morse code не имеет bruteForceAction.
        self::assertNull($result['auto_action']);
        self::assertNull($result['auto_result']);
    }

    /**
     * Проверяет, что исключение в брут-форсе не валит ответ identifier-а.
     */
    public function testBruteForceFailureDoesNotBreakResponse(): void
    {
        // Создаём failing-registry, который кидает исключение для любого brute-force action.
        $failingRegistry = new class () implements ApiCipherToolExecutorInterface {
            public function execute(string $action, array $payload): array
            {
                throw new \RuntimeException('Simulated brute-force failure');
            }
        };

        $scorer  = new LetterFrequencyScorer();
        $ioc     = new IndexOfCoincidence();
        $caesar  = new CaesarCipherService();

        $service = new CipherIdentifierService(
            [
                new CaesarDetector($scorer, $caesar),
            ],
            $scorer,
            $ioc,
        );

        $tool = new CipherIdentifierApiCipherTool($service, $failingRegistry);

        $result = $tool->execute([
            'text' => 'KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ',
        ]);

        self::assertTrue($result['ok']);
        self::assertNotEmpty($result['candidates']);
        self::assertSame('caesar-brute-force', $result['auto_action']);
        self::assertNull($result['auto_result']);
    }

    /**
     * Проверяет, что MAX_TEXT_LENGTH равно 3000.
     */
    public function testMaxTextLength(): void
    {
        self::assertSame(3000, CipherIdentifierApiCipherTool::MAX_TEXT_LENGTH);
    }
}
