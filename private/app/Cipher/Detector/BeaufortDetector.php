<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;

/**
 * Детектор шифра Бофора.
 *
 * Статистически неотличим от Vigenere — оба помечаются одновременно.
 */
final readonly class BeaufortDetector implements CipherDetectorInterface
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

        $lowerBound = $randomIoc + ($naturalIoc - $randomIoc) * 0.1;
        $upperBound = $naturalIoc - ($naturalIoc - $randomIoc) * 0.2;
        if ($iocValue < $lowerBound || $iocValue >= $upperBound) {
            return null;
        }

        return new CipherDetection(
            toolSlug: 'classical-ciphers/beaufort',
            cipherKey: 'CIPHER_NAME_BEAUFORT',
            confidence: 0.43,
            evidenceKeys: ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_POLY', 'CID_EV_AMBIGUOUS_POLYALPHA'],
            detectedAlphabet: $alphabet,
        );
    }
}
