<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;
use App\Cipher\LetterFrequencyScorer;

/**
 * Детектор шифра Rail Fence (транспозиция).
 *
 * Признак: IoC и χ² близки к естественному языку (частоты сохранены).
 */
final readonly class RailFenceDetector implements CipherDetectorInterface
{
    /**
     * Создаёт экземпляр детектора.
     */
    public function __construct(
        private LetterFrequencyScorer $scorer,
    ) {
    }

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

        // Транспозиция: IoC близок к естественному (±20%).
        $iocRatio = abs($iocValue - $naturalIoc) / ($naturalIoc - $randomIoc + 0.001);
        if ($iocRatio > 0.3) {
            return null;
        }

        // chi² исходного должен быть высоким (текст не читается).
        $chiOrig = $this->scorer->chiSquared($ctx->text, $alphabet);
        if ($chiOrig < 0.1) {
            return null;
        }

        return new CipherDetection(
            toolSlug: 'classical-ciphers/rail-fence',
            cipherKey: 'CIPHER_NAME_RAIL_FENCE',
            confidence: 0.40,
            evidenceKeys: ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_PRESERVED', 'CID_EV_AMBIGUOUS_POLYALPHA'],
            detectedAlphabet: $alphabet,
        );
    }
}
