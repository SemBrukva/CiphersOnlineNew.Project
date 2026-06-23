<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CaesarCipherService;
use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;
use App\Cipher\LetterFrequencyScorer;

/**
 * Детектор шифра Цезаря.
 *
 * Базовый сигнал — буквы + IoC ≈ языку. Уверенный сигнал — один из 26 сдвигов
 * даёт χ², существенно ниже второго лучшего.
 */
final readonly class CaesarDetector implements CipherDetectorInterface
{
    /**
     * Минимальный относительный отрыв лучшего χ² от второго, чтобы говорить о явном победителе.
     *
     * Сравнивается как (chi₂ − chi₁) / chi₁. Калибровано под пропорциональный
     * χ² из {@see LetterFrequencyScorer::chiSquared()} (значения в районе 0..0.5).
     */
    private const float CHI_WINNER_RATIO = 0.30;

    /**
     * Создаёт экземпляр детектора.
     */
    public function __construct(
        private LetterFrequencyScorer $scorer,
        private CaesarCipherService $caesar,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function detect(IdentificationContext $ctx): ?CipherDetection
    {
        $alphabet    = $ctx->effectiveAlphabet();
        $letterCount = $ctx->letterCount($alphabet);
        if ($letterCount < 5) {
            return null;
        }
        if ($ctx->letterRatio($alphabet) < 0.80) {
            return null;
        }

        $iocValue   = $ctx->iocFor($alphabet);
        $naturalIoc = IndexOfCoincidence::LANGUAGE_IOC[$alphabet] ?? 0.065;
        $randomIoc  = IndexOfCoincidence::RANDOM_IOC[$alphabet]   ?? 0.038;
        $iocRatio   = abs($iocValue - $naturalIoc) / ($naturalIoc - $randomIoc + 0.001);
        if ($iocRatio > 0.6) {
            return null;
        }

        // Пробуем все сдвиги, считаем χ² для каждой расшифровки.
        $maxShift  = $this->caesar->maxShiftForAlphabet($alphabet);
        $chiValues = [];
        for ($shift = 0; $shift <= $maxShift; $shift++) {
            $decrypted         = $this->caesar->process($ctx->text, $alphabet, $shift, 'decrypt');
            $chiValues[$shift] = $this->scorer->chiSquared($decrypted, $alphabet);
        }

        asort($chiValues);
        $sortedShifts = array_keys($chiValues);
        $sortedVals   = array_values($chiValues);
        $bestShift    = (int) $sortedShifts[0];
        $bestChi      = $sortedVals[0];
        $secondChi    = $sortedVals[1] ?? $bestChi;

        $hasWinner = $bestChi > 0.0 && (($secondChi - $bestChi) / $bestChi) > self::CHI_WINNER_RATIO;

        // Без явного χ²-победителя Caesar статистически неотличим от Affine —
        // отдаём ту же базу 0.52, чтобы не вытеснять Affine.
        // С явным победителем — 0.85, что выше AUTO_THRESHOLD = 0.70.
        $confidence = $hasWinner ? 0.85 : 0.52;

        $hints = ['best_shift' => $bestShift];
        if (!$ctx->hasReliableSample($alphabet)) {
            $scale       = $letterCount / LetterFrequencyScorer::MIN_LETTERS_FOR_RELIABLE_SCORING;
            $confidence *= $scale;
            $hints['low_sample'] = true;
        }

        $evidence = ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_MONO'];
        if ($hasWinner) {
            $evidence[] = 'CID_EV_CHISQ_BEST_SHIFT';
        }

        return new CipherDetection(
            toolSlug: 'classical-ciphers/caesar',
            cipherKey: 'CIPHER_NAME_CAESAR',
            confidence: $confidence,
            evidenceKeys: $evidence,
            bruteForceAction: 'caesar-brute-force',
            detectedAlphabet: $alphabet,
            hints: $hints,
        );
    }
}
