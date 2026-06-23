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
use App\Cipher\CipherDetection;
use App\Cipher\CipherIdentifierService;
use App\Cipher\Detector\A1z26Detector;
use App\Cipher\Detector\AffineDetector;
use App\Cipher\Detector\AtbashDetector;
use App\Cipher\Detector\BaconDetector;
use App\Cipher\Detector\Base64Detector;
use App\Cipher\Detector\BinaryDetector;
use App\Cipher\Detector\CaesarDetector;
use App\Cipher\Detector\HexDetector;
use App\Cipher\Detector\JwtDetector;
use App\Cipher\Detector\MorseCodeDetector;
use App\Cipher\Detector\PolybiusSquareDetector;
use App\Cipher\Detector\Rot13Detector;
use App\Cipher\Detector\UnicodeEscapeDetector;
use App\Cipher\Detector\UrlEncodedDetector;
use App\Cipher\Detector\VigenereDetector;
use App\Cipher\IndexOfCoincidence;
use App\Cipher\LetterFrequencyScorer;
use App\Cipher\PolybiusSquareCipherService;
use PHPUnit\Framework\TestCase;

/**
 * Тесты сервиса идентификации шифра.
 */
final class CipherIdentifierServiceTest extends TestCase
{
    private CipherIdentifierService $service;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $scorer       = new LetterFrequencyScorer();
        $ioc          = new IndexOfCoincidence();
        $caesar       = new CaesarCipherService();
        $bigramScorer = new BigramFrequencyScorer();
        $catalog      = new AlphabetCatalog();

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
                new Rot13Detector($scorer, $caesar),
                new CaesarDetector($scorer, $caesar),
                new AtbashDetector($scorer, new AtbashCipherService()),
                new AffineDetector($scorer, new AffineCipherService()),
                new VigenereDetector($catalog),
            ],
            $scorer,
            $ioc,
            $bigramScorer,
        );
    }

    /**
     * Проверяет, что азбука Морзе определяется с высокой уверенностью.
     */
    public function testMorseCodeDetectedWithHighConfidence(): void
    {
        $candidates = $this->service->identify('.... . .-.. .-.. --- / .-- --- .-. .-.. -..', null);

        self::assertNotEmpty($candidates);
        $top = $candidates[0];
        self::assertSame('CIPHER_NAME_MORSE_CODE', $top->cipherKey);
        self::assertGreaterThan(0.80, $top->confidence);
    }

    /**
     * Проверяет, что Base64 определяется с высокой уверенностью.
     */
    public function testBase64DetectedWithHighConfidence(): void
    {
        $candidates = $this->service->identify('SGVsbG8gV29ybGQh', null);

        self::assertNotEmpty($candidates);
        $top = $candidates[0];
        self::assertSame('CIPHER_NAME_BASE64', $top->cipherKey);
        self::assertGreaterThan(0.75, $top->confidence);
    }

    /**
     * Проверяет, что на надёжном Caesar-тексте Caesar выходит в лидеры с confidence ≥ AUTO_THRESHOLD.
     */
    public function testCaesarLeaderReachesAutoThreshold(): void
    {
        $candidates = $this->service->identify(
            'KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ',
            null
        );

        self::assertNotEmpty($candidates);
        $leader = $candidates[0];
        self::assertSame('CIPHER_NAME_CAESAR', $leader->cipherKey);
        self::assertGreaterThanOrEqual(CipherIdentifierService::AUTO_THRESHOLD, $leader->confidence);
    }

    /**
     * Проверяет, что список отсортирован по убыванию confidence.
     */
    public function testResultsSortedByConfidenceDesc(): void
    {
        $candidates = $this->service->identify('KHOOR ZRUOG WKLV LV D WHVW', null);

        $previous = PHP_FLOAT_MAX;
        foreach ($candidates as $detection) {
            self::assertLessThanOrEqual($previous, $detection->confidence);
            $previous = $detection->confidence;
        }
    }

    /**
     * Проверяет, что дубликаты по toolSlug удалены.
     */
    public function testNoDuplicateToolSlugs(): void
    {
        $candidates = $this->service->identify('KHOOR ZRUOG WKLV LV D WHVW', null);
        $slugs      = array_map(static fn (CipherDetection $d): string => $d->toolSlug, $candidates);
        self::assertSame(array_unique($slugs), $slugs);
    }

    /**
     * Проверяет, что очень короткий текст не вызывает ошибок.
     */
    public function testShortTextDoesNotCrash(): void
    {
        self::assertIsArray($this->service->identify('Hi', null));
    }

    /**
     * Проверяет, что пустой текст возвращает пустой массив.
     */
    public function testEmptyTextReturnsEmptyArray(): void
    {
        self::assertSame([], $this->service->identify('', null));
        self::assertSame([], $this->service->identify('   ', null));
    }

    /**
     * Проверяет константы AUTO_THRESHOLD и AUTO_GAP.
     */
    public function testAutoThresholdAndGapConstants(): void
    {
        self::assertSame(0.70, CipherIdentifierService::AUTO_THRESHOLD);
        self::assertSame(0.10, CipherIdentifierService::AUTO_GAP);
    }

    /**
     * Регрессионный тест на bigram second-pass: Caesar-расшифровка существенно
     * улучшает биграммный скор, и это отражается в evidence и hints лидера.
     */
    public function testCaesarLeaderHasBigramReadableEvidenceAfterRescore(): void
    {
        $candidates = $this->service->identify(
            'KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ',
            null
        );

        self::assertNotEmpty($candidates);
        $leader = $candidates[0];
        self::assertSame('CIPHER_NAME_CAESAR', $leader->cipherKey);
        self::assertContains('CID_EV_BIGRAM_READABLE', $leader->evidenceKeys);
        self::assertArrayHasKey('bigram_delta', $leader->hints);
        self::assertGreaterThan(0.0, $leader->hints['bigram_delta']);
    }

    /**
     * Проверяет, что при сильном Caesar-лидере моноалфавитные «соседи» (Affine,
     * Atbash, SimpleSubstitution) подавляются и не висят в списке с 50%+.
     */
    public function testMonoNeighboursAreSuppressedWhenCaesarLeads(): void
    {
        $candidates = $this->service->identify(
            'KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ',
            null
        );

        $leader = $candidates[0];
        self::assertSame('CIPHER_NAME_CAESAR', $leader->cipherKey);
        self::assertGreaterThanOrEqual(0.80, $leader->confidence);

        foreach ($candidates as $detection) {
            if ($detection->toolSlug === $leader->toolSlug) {
                continue;
            }
            if (!in_array('CID_EV_IOC_MONO', $detection->evidenceKeys, true)) {
                continue;
            }
            self::assertArrayHasKey('suppressed_by', $detection->hints, sprintf(
                'Кандидат %s в моноалфавитной группе должен быть подавлен лидером Caesar',
                $detection->toolSlug
            ));
            self::assertLessThan(0.55, $detection->confidence);
        }
    }

    /**
     * Проверяет, что n-gram-бонус приклеивает evidence CID_EV_COMMON_WORDS лидеру
     * с расшифровкой, содержащей частые английские слова.
     */
    public function testNgramBonusAppliedWhenDecryptionContainsCommonWords(): void
    {
        $candidates = $this->service->identify(
            'KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ',
            null
        );

        $leader = $candidates[0];
        self::assertContains('CID_EV_COMMON_WORDS', $leader->evidenceKeys);
        self::assertArrayHasKey('ngram_matches', $leader->hints);
        self::assertGreaterThanOrEqual(2, $leader->hints['ngram_matches']);
    }
}
