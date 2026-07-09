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
use App\Cipher\Rot47CipherService;
use App\Cipher\SimpleSubstitutionApiCipherTool;
use App\Cipher\SimpleSubstitutionCipherService;
use App\Cipher\SolverApiCipherTool;
use App\Cipher\SolverService;
use App\Cipher\SubstitutionCrackerApiCipherTool;
use App\Cipher\TrifidApiCipherTool;
use App\Cipher\TrifidCipherService;
use App\Cipher\TrigramFrequencyScorer;
use App\Cipher\VernamApiCipherTool;
use App\Cipher\VernamCipherService;
use App\Cipher\VigenereApiCipherTool;
use App\Cipher\VigenereCipherService;
use App\Cipher\VigenereCrackerApiCipherTool;
use App\Cipher\XorApiCipherTool;
use App\Cipher\XorBruteForceApiCipherTool;
use App\Cipher\XorCipherService;
use App\Http\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * Тесты API-инструмента «умного» авто-солвера.
 */
final class SolverApiCipherToolTest extends TestCase
{
    private SolverApiCipherTool $tool;

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

        $bigramScorer   = new BigramFrequencyScorer();
        $affineCipher   = new AffineCipherService();
        $baconCipher    = new BaconCipherService();
        $a1z26Cipher    = new A1z26CipherService();
        $polybiusCipher = new PolybiusSquareCipherService();

        $service = new CipherIdentifierService(
            [
                new JwtDetector(),
                new MorseCodeDetector(),
                new BaconDetector($baconCipher),
                new BinaryDetector(),
                new HexDetector(),
                new Base64Detector(),
                new A1z26Detector($a1z26Cipher),
                new PolybiusSquareDetector($polybiusCipher, $scorer),
                new UrlEncodedDetector(),
                new UnicodeEscapeDetector(),
                new Rot13Detector($scorer, $caesar),
                new CaesarDetector($scorer, $caesar),
                new AtbashDetector($scorer, new AtbashCipherService()),
                new AffineDetector($scorer, $affineCipher),
                new SimpleSubstitutionDetector(),
                new XorDetector(),
                new VigenereDetector($catalog),
                new BeaufortDetector(),
                new AutokeyDetector(),
                new GronsfeldDetector(),
                new AlbertiDetector(),
                new BifidDetector(),
                new TrifidDetector(),
                new RailFenceDetector(),
                new ColumnarTranspositionDetector(),
                new PlayfairDetector(),
                new HillDetector(),
            ],
            $scorer,
            $ioc,
            $bigramScorer,
        );

        $registry = new ApiCipherToolRegistry(
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
            new CaesarBruteForceApiCipherTool($caesar, $scorer, new BigramFrequencyScorer(), new TrigramFrequencyScorer()),
            new AffineBruteForceApiCipherTool(new AffineCipherService(), $scorer, $catalog, new BigramFrequencyScorer(), new NullCache()),
            new SimpleSubstitutionApiCipherTool(new SimpleSubstitutionCipherService()),
            new SubstitutionCrackerApiCipherTool(new SimpleSubstitutionCipherService(), new LetterFrequencyScorer(), new AlphabetCatalog(), new BigramFrequencyScorer(), new TrigramFrequencyScorer(), new NullCache()),
            new XorApiCipherTool(new XorCipherService()),
            new XorBruteForceApiCipherTool(new LetterFrequencyScorer(), new BigramFrequencyScorer(), new TrigramFrequencyScorer()),
            new VigenereCrackerApiCipherTool(new VigenereCipherService(), $scorer, $catalog, new BigramFrequencyScorer(), new NullCache()),
            new BifidApiCipherTool(new BifidCipherService($catalog, new AlphabetTool($catalog, $folder), $folder)),
            new TrifidApiCipherTool(new TrifidCipherService($catalog, new AlphabetTool($catalog, $folder), $folder)),
            new AlbertiApiCipherTool(new AlbertiCipherService()),
            new \App\Cipher\EnigmaApiCipherTool(new \App\Cipher\EnigmaCipherService()),
            new \App\Cipher\AnagramSolverApiCipherTool(
                new \App\Cipher\AnagramSolver\AnagramEngine(
                    new \App\Cipher\Dictionary\DictionaryRepository(
                        sys_get_temp_dir() . '/anagram-solvertest-' . uniqid('', true),
                    ),
                    new \App\Cipher\Dictionary\WordSignature($catalog),
                    new \App\Cipher\AnagramSolver\ScrabbleScorer(),
                ),
                new \App\Cipher\Dictionary\DictionaryRepository(
                    sys_get_temp_dir() . '/anagram-solvertest-' . uniqid('', true),
                ),
                $scorer,
            ),
        );

        $solver     = new SolverService($service, $bigramScorer, $scorer, new TrigramFrequencyScorer(), new Rot47CipherService());
        $this->tool = new SolverApiCipherTool($solver, $registry);
    }

    /**
     * Проверяет, что action() возвращает 'cipher-solver'.
     */
    public function testActionReturnsCipherSolver(): void
    {
        self::assertSame('cipher-solver', $this->tool->action());
    }

    /**
     * Проверяет, что пустой текст вызывает ValidationFailedException.
     */
    public function testEmptyTextThrowsValidationException(): void
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
        $this->tool->execute(['text' => str_repeat('A', SolverApiCipherTool::MAX_TEXT_LENGTH + 1)]);
    }

    /**
     * Проверяет, что Caesar shift=3 решается «из коробки»: верхний ответ — верная
     * расшифровка, полученная через caesar-brute-force, с высокой читаемостью.
     */
    public function testCaesarIsSolvedAsBestAnswer(): void
    {
        $result = $this->tool->execute([
            'text' => 'KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ',
        ]);

        self::assertTrue($result['ok']);
        self::assertIsArray($result['best']);
        self::assertStringContainsStringIgnoringCase('HELLO WORLD', $result['best']['plaintext']);
        self::assertSame('caesar-brute-force', $result['best']['via_action']);
        self::assertSame('shift=3', $result['best']['key_label']);
        self::assertGreaterThan(60, $result['best']['readability_pct']);
    }

    /**
     * Проверяет структуру ответа: answers отсортированы по читаемости по убыванию,
     * лидер совпадает с best, присутствует блок type_candidates.
     */
    public function testAnswersAreRankedByReadability(): void
    {
        $result = $this->tool->execute([
            'text' => 'KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ',
        ]);

        self::assertNotEmpty($result['answers']);
        self::assertNotEmpty($result['type_candidates']);
        self::assertSame($result['answers'][0]['plaintext'], $result['best']['plaintext']);

        $pcts = array_column($result['answers'], 'readability_pct');
        $sorted = $pcts;
        rsort($sorted);
        self::assertSame($sorted, $pcts);
    }

    /**
     * Проверяет, что каждый answer содержит ожидаемые поля.
     */
    public function testAnswerHasExpectedStructure(): void
    {
        $result = $this->tool->execute([
            'text' => 'KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ',
        ]);

        $answer = $result['answers'][0];
        foreach (['tool_slug', 'cipher_key', 'plaintext', 'key_label', 'readability', 'readability_pct', 'via_action', 'detected_alphabet'] as $key) {
            self::assertArrayHasKey($key, $answer);
        }
        self::assertGreaterThanOrEqual(0, $answer['readability_pct']);
        self::assertLessThanOrEqual(100, $answer['readability_pct']);
    }

    /**
     * Короткий Caesar-текст решается через универсальный caesar-brute, хотя
     * детектор типа на такой длине неуверен. Регресс на n-gram-отбор сдвига.
     */
    public function testShortCaesarIsSolved(): void
    {
        $result = $this->tool->execute(['text' => 'WKH FDW']);

        self::assertIsArray($result['best']);
        self::assertSame('THE CAT', $result['best']['plaintext']);
    }

    /**
     * Короткий Atbash-текст решается универсальным крекером с корректным
     * алфавитом (регресс на баг авто-определения языка на коротком слове).
     */
    public function testShortAtbashIsSolved(): void
    {
        $result = $this->tool->execute(['text' => 'SVOOL']);

        self::assertIsArray($result['best']);
        self::assertSame('HELLO', $result['best']['plaintext']);
    }

    /**
     * ROT47-шифртекст (символьно-нагружен) решается отдельной ROT47-веткой,
     * минуя буквенный гейт универсальных крекеров.
     */
    public function testRot47IsSolved(): void
    {
        // ROT47('The secret meeting is at noon tomorrow').
        $result = $this->tool->execute(['text' => '%96 D64C6E >66E:?8 :D 2E ?@@? E@>@CC@H']);

        self::assertIsArray($result['best']);
        self::assertSame('The secret meeting is at noon tomorrow', $result['best']['plaintext']);
        self::assertSame('rot47', $result['best']['via_action']);
    }

    /**
     * Однобайтовый XOR (hex-вход) взламывается перебором 256 ключей с отбором
     * по доле букв+пробелов и n-gram-читаемости.
     */
    public function testSingleByteXorIsSolved(): void
    {
        $plaintext = 'the treasure is buried under the old oak tree';
        $key       = 0x3C;
        $hex       = '';
        for ($i = 0, $len = strlen($plaintext); $i < $len; $i++) {
            $hex .= sprintf('%02x', ord($plaintext[$i]) ^ $key);
        }

        $result = $this->tool->execute(['text' => $hex]);

        self::assertIsArray($result['best']);
        self::assertSame($plaintext, $result['best']['plaintext']);
        self::assertSame('xor-brute-force', $result['best']['via_action']);
        self::assertSame('key=0x3C', $result['best']['key_label']);
    }

    /**
     * Моноалфавитная замена (≥80 букв) взламывается substitution-cracker'ом:
     * частотный старт + hill climbing. Взлом частичный (тонкие n-gram-таблицы),
     * поэтому проверяем via_action и порог совпадения букв, а не точное равенство.
     */
    public function testSubstitutionCipherIsSolved(): void
    {
        $cipher     = new SimpleSubstitutionCipherService();
        $plaintext  = 'the art of cryptography is the science of secret writing with the goal '
            . 'of hiding the meaning of a message from anyone but the intended recipient '
            . 'throughout history people have used ciphers to protect sensitive information';
        $ciphertext = $cipher->process($plaintext, 'en', 'qwertyuiopasdfghjklzxcvbnm', 'encrypt');

        $result = $this->tool->execute(['text' => $ciphertext]);

        self::assertIsArray($result['best']);
        self::assertSame('substitution-cracker', $result['best']['via_action']);

        $expected = (string) preg_replace('/[^a-z]/', '', mb_strtolower($plaintext));
        $got      = (string) preg_replace('/[^a-z]/', '', mb_strtolower($result['best']['plaintext']));
        $match    = 0;
        $len      = min(strlen($expected), strlen($got));
        for ($i = 0; $i < $len; $i++) {
            if ($expected[$i] === $got[$i]) {
                $match++;
            }
        }

        self::assertGreaterThanOrEqual(0.7, $match / $len);
    }

    /**
     * Проверяет, что для нераспознаваемого мусора best равен null, а ответ не падает.
     */
    public function testUnsolvableTextReturnsNullBest(): void
    {
        $result = $this->tool->execute(['text' => '!@#$%^&*()']);

        self::assertTrue($result['ok']);
        self::assertNull($result['best']);
        self::assertSame([], $result['answers']);
    }

    /**
     * Проверяет, что MAX_TEXT_LENGTH равно 3000.
     */
    public function testMaxTextLength(): void
    {
        self::assertSame(3000, SolverApiCipherTool::MAX_TEXT_LENGTH);
    }
}
