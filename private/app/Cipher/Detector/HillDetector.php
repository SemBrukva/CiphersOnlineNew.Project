<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;

/**
 * Детектор шифра Хилла.
 *
 * Признак: только буквы; длина кратна 2 (для матрицы 2×2).
 */
final readonly class HillDetector implements CipherDetectorInterface
{
    /**
     * {@inheritDoc}
     */
    public function detect(IdentificationContext $ctx): ?CipherDetection
    {
        $alphabet    = $ctx->effectiveAlphabet();
        $letterCount = $ctx->letterCount($alphabet);
        if (!$ctx->hasReliableSample($alphabet)) {
            return null;
        }
        if ($ctx->letterRatio($alphabet) < 0.80) {
            return null;
        }
        if ($letterCount % 2 !== 0) {
            return null;
        }

        $iocValue   = $ctx->iocFor($alphabet);
        $naturalIoc = IndexOfCoincidence::LANGUAGE_IOC[$alphabet] ?? 0.065;
        $randomIoc  = IndexOfCoincidence::RANDOM_IOC[$alphabet]   ?? 0.038;
        $iocRatio   = abs($iocValue - $naturalIoc) / ($naturalIoc - $randomIoc + 0.001);
        if ($iocRatio > 0.5) {
            return null;
        }

        return new CipherDetection(
            toolSlug: 'classical-ciphers/hill',
            cipherKey: 'CIPHER_NAME_HILL',
            confidence: 0.42,
            evidenceKeys: ['CID_EV_CHARSET_LETTERS', 'CID_EV_LENGTH_MULTIPLE_OF'],
            detectedAlphabet: $alphabet,
        );
    }
}
