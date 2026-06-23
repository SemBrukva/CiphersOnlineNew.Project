<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;
use App\Cipher\LetterFrequencyScorer;

/**
 * Детектор аффинного шифра.
 *
 * Признак: только буквы; IoC ≈ IoC языка; неравномерное распределение букв.
 * Отличить аффинный от Caesar без brute-force нельзя — поэтому отдаём
 * базовый confidence, идентичный Caesar без явного χ²-победителя.
 */
final readonly class AffineDetector implements CipherDetectorInterface
{
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

        $confidence = 0.52;

        $hints = [];
        if (!$ctx->hasReliableSample($alphabet)) {
            $scale       = $letterCount / LetterFrequencyScorer::MIN_LETTERS_FOR_RELIABLE_SCORING;
            $confidence *= $scale;
            $hints['low_sample'] = true;
        }

        return new CipherDetection(
            toolSlug: 'classical-ciphers/affine',
            cipherKey: 'CIPHER_NAME_AFFINE',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_MONO'],
            bruteForceAction: 'affine-brute-force',
            detectedAlphabet: $alphabet,
            hints: $hints,
        );
    }
}
