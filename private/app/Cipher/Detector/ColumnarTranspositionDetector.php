<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;

/**
 * Детектор столбцовой перестановки (Columnar Transposition).
 *
 * Признак: IoC ≈ естественному языку (частоты сохранены), текст не читается.
 */
final readonly class ColumnarTranspositionDetector implements CipherDetectorInterface
{
    /**
     * {@inheritDoc}
     */
    public function detect(IdentificationContext $ctx): ?CipherDetection
    {
        $alphabet = $ctx->effectiveAlphabet();
        if (!$ctx->hasReliableSample($alphabet)) {
            return null;
        }
        if ($ctx->letterRatio($alphabet) < 0.80) {
            return null;
        }

        $iocValue   = $ctx->iocFor($alphabet);
        $naturalIoc = IndexOfCoincidence::LANGUAGE_IOC[$alphabet] ?? 0.065;
        $randomIoc  = IndexOfCoincidence::RANDOM_IOC[$alphabet]   ?? 0.038;
        $iocRatio   = abs($iocValue - $naturalIoc) / ($naturalIoc - $randomIoc + 0.001);
        if ($iocRatio > 0.3) {
            return null;
        }

        $chiOrig = $ctx->chiSquaredOriginal($alphabet);
        if ($chiOrig < 0.1) {
            return null;
        }

        return new CipherDetection(
            toolSlug: 'classical-ciphers/columnar-transposition',
            cipherKey: 'CIPHER_NAME_COLUMNAR_TRANSPOSITION',
            confidence: 0.38,
            evidenceKeys: ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_PRESERVED', 'CID_EV_AMBIGUOUS_POLYALPHA'],
            detectedAlphabet: $alphabet,
        );
    }
}
