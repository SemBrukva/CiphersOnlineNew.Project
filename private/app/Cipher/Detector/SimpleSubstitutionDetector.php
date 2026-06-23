<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;

/**
 * Детектор шифра простой замены.
 *
 * Признак: только буквы; IoC ≈ IoC языка; длина ≥ 80 букв.
 * Brute-force недоступен — только ссылка на инструмент.
 */
final readonly class SimpleSubstitutionDetector implements CipherDetectorInterface
{
    /**
     * Минимальная длина текста для детекции.
     */
    private const int MIN_LETTERS = 80;

    /**
     * {@inheritDoc}
     */
    public function detect(IdentificationContext $ctx): ?CipherDetection
    {
        $alphabet    = $ctx->effectiveAlphabet();
        $letterCount = $ctx->letterCount($alphabet);
        if ($letterCount < self::MIN_LETTERS) {
            return null;
        }
        if ($ctx->letterRatio($alphabet) < 0.80) {
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
            toolSlug: 'classical-ciphers/simple-substitution',
            cipherKey: 'CIPHER_NAME_SIMPLE_SUBSTITUTION',
            confidence: 0.50,
            evidenceKeys: ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_MONO'],
            detectedAlphabet: $alphabet,
        );
    }
}
