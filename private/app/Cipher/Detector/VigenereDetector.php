<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;

/**
 * Детектор шифра Виженера.
 *
 * Признак: только буквы; IoC между моноалфавитным и случайным.
 * Чем IoC ближе к центру полиалфавитной зоны, тем выше confidence.
 */
final readonly class VigenereDetector implements CipherDetectorInterface
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

        $iocValue   = $ctx->iocFor($alphabet);
        $naturalIoc = IndexOfCoincidence::LANGUAGE_IOC[$alphabet] ?? 0.065;
        $randomIoc  = IndexOfCoincidence::RANDOM_IOC[$alphabet]   ?? 0.038;

        // IoC должен быть в полиалфавитной зоне между случайным и естественным.
        $lowerBound = $randomIoc + ($naturalIoc - $randomIoc) * 0.1;
        $upperBound = $naturalIoc - ($naturalIoc - $randomIoc) * 0.2;
        if ($iocValue < $lowerBound || $iocValue >= $upperBound) {
            return null;
        }

        // Центр полиалфавитной зоны соответствует «классическому» Vigenère
        // с длиной ключа 5-7. Чем ближе IoC к центру, тем выше уверенность.
        $center      = ($lowerBound + $upperBound) / 2.0;
        $halfRange   = ($upperBound - $lowerBound) / 2.0;
        $centerDist  = abs($iocValue - $center) / max($halfRange, 1e-6);
        $confidence  = 0.55 + 0.25 * (1.0 - min(1.0, $centerDist));

        return new CipherDetection(
            toolSlug: 'classical-ciphers/vigenere',
            cipherKey: 'CIPHER_NAME_VIGENERE',
            confidence: min(0.80, $confidence),
            evidenceKeys: ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_POLY', 'CID_EV_AMBIGUOUS_POLYALPHA'],
            bruteForceAction: 'vigenere-cracker',
            detectedAlphabet: $alphabet,
        );
    }
}
