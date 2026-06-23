<?php

declare(strict_types=1);

namespace Tests\Unit\Cipher\Detector;

use App\Cipher\AffineCipherService;
use App\Cipher\Detector\AffineDetector;
use App\Cipher\LetterFrequencyScorer;

/**
 * Тесты детектора аффинного шифра.
 */
final class AffineDetectorTest extends DetectorTestCase
{
    private AffineDetector $detector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new AffineDetector(
            new LetterFrequencyScorer(),
            new AffineCipherService(),
        );
    }

    /**
     * Проверяет, что аффинно-зашифрованный текст не вызывает исключений.
     *
     * Мягкий детектор: результат может быть null для неоднозначных текстов.
     */
    public function testDetectsValidInput(): void
    {
        $result = $this->detector->detect($this->ctx('IHHWVC SWFRCP CVSPYFZ CISR LCZZCP OWZR ZOA VEQCPWS GCYU IVX PCJCIL LIVMEIMC FIZZCPVU EVXCP IVILYUWU'));
        self::assertTrue($result === null || $result->confidence > 0.0);
        if ($result !== null) {
            self::assertSame('affine-brute-force', $result->bruteForceAction);
        }
    }

    /**
     * Проверяет, что неподходящий ввод возвращает null.
     */
    public function testRejectsInvalidInput(): void
    {
        $result = $this->detector->detect($this->ctx('.... . .-.. .-.. ---'));
        self::assertNull($result);
    }

    /**
     * Проверяет, что пустая строка не вызывает ошибок и возвращает null.
     */
    public function testEmptyStringReturnsNull(): void
    {
        $result = $this->detector->detect($this->ctx(''));
        self::assertNull($result);
    }

    /**
     * Проверяет, что брутфорс находит правильную пару (a=5, b=8) на длинном тексте
     * и поднимает confidence до уровня winner-а.
     *
     * Исходный текст: "HELLO WORLD THIS IS A TEST MESSAGE FOR CIPHER DETECTION"
     * c Affine (a=5, b=8).
     */
    public function testBruteForceFindsCorrectKeyOnRealCiphertext(): void
    {
        $result = $this->detector->detect(
            $this->ctx('RCLLA OAPLX ZRWU WU I ZCUZ QCUUIMC HAP SWFRCP XCZCSZWAV')
        );

        self::assertNotNull($result);
        self::assertGreaterThanOrEqual(0.80, $result->confidence);
        self::assertContains('CID_EV_CHISQ_BEST_SHIFT', $result->evidenceKeys);
        self::assertSame(5, $result->hints['multiplier']);
        self::assertSame(8, $result->hints['shift']);
        self::assertSame('HELLO WORLD THIS IS A TEST MESSAGE FOR CIPHER DETECTION', $result->decryptedText);
    }

    /**
     * Проверяет, что Caesar-текст помечается как degenerate_caesar и НЕ получает
     * winner-confidence: иначе он конкурировал бы за лидерство с CaesarDetector
     * и убивал auto-trigger gap.
     */
    public function testDegenerateCaesarDoesNotClaimWinnerConfidence(): void
    {
        $result = $this->detector->detect(
            $this->ctx('KHOOR ZRUOG WKLV LV D WHVW PHVVDJH IRU FLSKHU GHWHFWLRQ')
        );

        self::assertNotNull($result);
        self::assertSame(1, $result->hints['multiplier']);
        self::assertTrue($result->hints['degenerate_caesar'] ?? false);
        self::assertLessThan(0.70, $result->confidence);
    }
}
