<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher;

use App\Cipher\A1z26CipherService;
use App\Cipher\AffineCipherService;
use App\Cipher\AlphabetCatalog;
use App\Cipher\AtbashCipherService;
use App\Cipher\BaconCipherService;
use App\Cipher\BigramFrequencyScorer;
use App\Cipher\CaesarCipherService;
use App\Cipher\CipherIdentifierService;
use App\Cipher\Detector\A1z26Detector;
use App\Cipher\Detector\AffineDetector;
use App\Cipher\Detector\AtbashDetector;
use App\Cipher\Detector\BaconDetector;
use App\Cipher\Detector\Base64Detector;
use App\Cipher\Detector\BinaryDetector;
use App\Cipher\Detector\CaesarDetector;
use App\Cipher\Detector\ColumnarTranspositionDetector;
use App\Cipher\Detector\HexDetector;
use App\Cipher\Detector\HillDetector;
use App\Cipher\Detector\JwtDetector;
use App\Cipher\Detector\MorseCodeDetector;
use App\Cipher\Detector\PolybiusSquareDetector;
use App\Cipher\Detector\RailFenceDetector;
use App\Cipher\Detector\Rot13Detector;
use App\Cipher\Detector\SimpleSubstitutionDetector;
use App\Cipher\Detector\UnicodeEscapeDetector;
use App\Cipher\Detector\UrlEncodedDetector;
use App\Cipher\Detector\VigenereDetector;
use App\Cipher\Detector\XorDetector;
use App\Cipher\IndexOfCoincidence;
use App\Cipher\LetterFrequencyScorer;
use App\Cipher\PolybiusSquareCipherService;
use App\Cipher\VigenereCipherService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Регрессионный корпус для CipherIdentifier.
 *
 * Каждый кейс — известный шифр-текст с ожидаемым лидером и минимальной
 * уверенностью. Шифр-тексты по возможности генерируются на лету через
 * соответствующие CipherServices — это убирает риск опечатки в фикстуре и
 * фиксирует, что детектор узнаёт «настоящий» вывод сервиса, а не идеализированный.
 *
 * Тест служит регрессионной сеткой при будущей калибровке порогов confidence:
 * если правка ломает реальные кейсы — это видно сразу.
 */
final class CipherIdentifierCorpusTest extends TestCase
{
    private CipherIdentifierService $service;

    private CaesarCipherService $caesar;

    private AffineCipherService $affine;

    private AtbashCipherService $atbash;

    private VigenereCipherService $vigenere;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $scorer        = new LetterFrequencyScorer();
        $ioc           = new IndexOfCoincidence();
        $catalog       = new AlphabetCatalog();
        $bigramScorer  = new BigramFrequencyScorer();
        $this->caesar  = new CaesarCipherService();
        $this->affine  = new AffineCipherService();
        $this->atbash  = new AtbashCipherService();
        $this->vigenere = new VigenereCipherService();

        $this->service = new CipherIdentifierService(
            [
                new JwtDetector(),
                new MorseCodeDetector(),
                new BaconDetector(new BaconCipherService()),
                new BinaryDetector(),
                new HexDetector(),
                new Base64Detector(),
                new A1z26Detector(new A1z26CipherService()),
                new PolybiusSquareDetector(new PolybiusSquareCipherService(), $scorer),
                new UrlEncodedDetector(),
                new UnicodeEscapeDetector(),
                new Rot13Detector($scorer, $this->caesar),
                new CaesarDetector($scorer, $this->caesar),
                new AtbashDetector($scorer, $this->atbash),
                new AffineDetector($scorer, $this->affine),
                new SimpleSubstitutionDetector(),
                new XorDetector(),
                new VigenereDetector($catalog),
                new RailFenceDetector(),
                new ColumnarTranspositionDetector(),
                new HillDetector(),
            ],
            $scorer,
            $ioc,
            $bigramScorer,
        );
    }

    /**
     * Регрессия по корпусу шифр-текстов.
     *
     * @param string $text             Шифр-текст для подачи в Identifier.
     * @param string $expectedToolSlug Ожидаемый toolSlug лидера.
     * @param float  $minConfidence    Минимально допустимая confidence лидера.
     */
    #[DataProvider('encodingCases')]
    #[DataProvider('caesarCases')]
    #[DataProvider('atbashCases')]
    #[DataProvider('affineCases')]
    #[DataProvider('vigenereCases')]
    #[DataProvider('miscellaneousCases')]
    public function testLeaderMatchesExpectation(string $text, string $expectedToolSlug, float $minConfidence): void
    {
        $candidates = $this->service->identify($text, null);

        self::assertNotEmpty($candidates, 'Identifier должен вернуть хотя бы одного кандидата');

        $leader = $candidates[0];
        self::assertSame(
            $expectedToolSlug,
            $leader->toolSlug,
            sprintf('Лидер должен быть %s, получили %s (confidence=%.2f)', $expectedToolSlug, $leader->toolSlug, $leader->confidence)
        );
        self::assertGreaterThanOrEqual(
            $minConfidence,
            $leader->confidence,
            sprintf('Confidence лидера %s ожидался ≥ %.2f, получили %.2f', $leader->toolSlug, $minConfidence, $leader->confidence)
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: float}>
     */
    public static function encodingCases(): array
    {
        return [
            'base64 plain ascii' => [
                base64_encode('Hello, World! This is a regression sample.'),
                'encoding/base64',
                0.85,
            ],
            'base64 png signature' => [
                base64_encode("\x89PNG\r\n\x1a\nIHDR\x00\x00\x00\x10"),
                'encoding/base64',
                0.95,
            ],
            'hex printable text' => [
                bin2hex('Lorem ipsum dolor sit amet'),
                'encoding/hex',
                0.85,
            ],
            'binary 8bit ascii' => [
                implode(' ', array_map(static fn (int $b): string => str_pad(decbin($b), 8, '0', STR_PAD_LEFT), array_values(unpack('C*', 'Hello!') ?: []))),
                'encoding/binary-converter',
                0.85,
            ],
            'morse hello world' => [
                '.... . .-.. .-.. --- / .-- --- .-. .-.. -..',
                'codes-and-alphabets/morse-code',
                0.90,
            ],
            'jwt header.payload.signature' => [
                'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0In0.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
                'encoding/jwt-decoder',
                0.95,
            ],
        ];
    }

    /**
     * Длинный английский плейнтекст без сильных повторов: даёт IoC, близкий к
     * natural en (0.067), что важно для классических детекторов — на коротких
     * текстах с зацикленными повторами IoC взлетает и Caesar/Atbash отказываются.
     */
    private const string PLAINTEXT_EN_LONG =
        'A LONG WINTER EVENING WAS APPROACHING THE QUIET LIBRARY WHERE A YOUNG STUDENT '
        . 'WAS READING AN OLD MANUSCRIPT ABOUT ANCIENT CIPHERS USED BY DIPLOMATS DURING '
        . 'IMPORTANT NEGOTIATIONS BETWEEN POWERFUL NATIONS HE STUDIED HOW LETTERS WERE '
        . 'REPLACED ACCORDING TO COMPLEX RULES THAT KEPT THEIR MEANING HIDDEN FROM ENEMIES';

    private const string PLAINTEXT_RU_LONG =
        'ПРОХЛАДНЫМ ВЕЧЕРОМ МОЛОДОЙ УЧЕНЫЙ СИДЕЛ В БИБЛИОТЕКЕ И ИЗУЧАЛ СТАРИННУЮ '
        . 'РУКОПИСЬ ПОСВЯЩЕННУЮ КЛАССИЧЕСКИМ ШИФРАМ КОТОРЫЕ ИСПОЛЬЗОВАЛИ ДИПЛОМАТЫ '
        . 'ДЛЯ ОБМЕНА СЕКРЕТНЫМИ СООБЩЕНИЯМИ МЕЖДУ КОРОЛЕВСКИМИ ДВОРАМИ ВЕДУЩИХ ДЕРЖАВ';

    /**
     * @return array<string, array{0: string, 1: string, 2: float}>
     */
    public static function caesarCases(): array
    {
        $caesar = new CaesarCipherService();

        return [
            'caesar shift 3 long en' => [
                $caesar->process(self::PLAINTEXT_EN_LONG, 'en', 3, 'encrypt'),
                'classical-ciphers/caesar',
                0.80,
            ],
            'caesar shift 5 short en' => [
                $caesar->process('HELLO WORLD THIS IS A TEST MESSAGE FOR CIPHER DETECTION', 'en', 5, 'encrypt'),
                'classical-ciphers/caesar',
                0.70,
            ],
            'caesar shift 7 ru' => [
                $caesar->process(self::PLAINTEXT_RU_LONG, 'ru', 7, 'encrypt'),
                'classical-ciphers/caesar',
                0.80,
            ],
            'rot13 on en' => [
                $caesar->process(self::PLAINTEXT_EN_LONG, 'en', 13, 'encrypt'),
                'classical-ciphers/rot13',
                0.70,
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: float}>
     */
    public static function atbashCases(): array
    {
        $atbash = new AtbashCipherService();

        return [
            'atbash en long' => [
                $atbash->process(self::PLAINTEXT_EN_LONG, 'en'),
                'classical-ciphers/atbash',
                0.65,
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: float}>
     */
    public static function affineCases(): array
    {
        $affine = new AffineCipherService();

        return [
            'affine en a=5 b=8' => [
                $affine->process(self::PLAINTEXT_EN_LONG, 'en', 5, 8, 'encrypt'),
                'classical-ciphers/affine',
                0.75,
            ],
            'affine en a=7 b=11' => [
                $affine->process(self::PLAINTEXT_EN_LONG, 'en', 7, 11, 'encrypt'),
                'classical-ciphers/affine',
                0.70,
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: float}>
     */
    public static function vigenereCases(): array
    {
        $vigenere = new VigenereCipherService();

        return [
            'vigenere key=KEY (length 3)' => [
                $vigenere->process(self::PLAINTEXT_EN_LONG, 'KEY', 'en', 'encrypt'),
                'classical-ciphers/vigenere',
                0.60,
            ],
            'vigenere key=SECRET (length 6)' => [
                $vigenere->process(self::PLAINTEXT_EN_LONG, 'SECRET', 'en', 'encrypt'),
                'classical-ciphers/vigenere',
                0.55,
            ],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: float}>
     */
    public static function miscellaneousCases(): array
    {
        return [
            // Polybius 5×5 en: HELLO WORLD → 23 15 31 31 34 52 34 42 31 14
            'polybius hello world en' => [
                '23 15 31 31 34 52 34 42 31 14 23 15 31 31 34 52 34 42 31 14',
                'codes-and-alphabets/polybius-square',
                0.60,
            ],
            // A1Z26: HELLO → 8-5-12-12-15
            'a1z26 hello' => [
                '8-5-12-12-15',
                'codes-and-alphabets/a1z26',
                0.70,
            ],
            // Bacon HELLO → AABBB AABAA ABABA ABABA ABBAB
            'bacon hello' => [
                'AABBB AABAA ABABA ABABA ABBAB',
                'codes-and-alphabets/bacon',
                0.75,
            ],
        ];
    }
}
