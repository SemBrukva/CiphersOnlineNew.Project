<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;

/**
 * Детектор шифра Плейфера.
 *
 * Признак: только буквы, длина чётная, в тексте отсутствует буква J (для EN).
 */
final readonly class PlayfairDetector implements CipherDetectorInterface
{
    /**
     * {@inheritDoc}
     */
    public function detect(IdentificationContext $ctx): ?CipherDetection
    {
        $alphabet = $ctx->effectiveAlphabet();

        // Playfair работает только с латинскими и близкими алфавитами.
        if (!in_array($alphabet, ['en', 'fr', 'de', 'es', 'it', 'pt', 'tr'], true)) {
            return null;
        }

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

        // Для классического английского Playfair буква J заменяется на I.
        if ($alphabet === 'en' && mb_strpos(mb_strtolower($ctx->text), 'j') !== false) {
            return null;
        }

        $iocValue   = $ctx->iocFor($alphabet);
        $naturalIoc = IndexOfCoincidence::LANGUAGE_IOC[$alphabet];
        $randomIoc  = IndexOfCoincidence::RANDOM_IOC[$alphabet];
        $iocRatio   = abs($iocValue - $naturalIoc) / ($naturalIoc - $randomIoc + 0.001);
        if ($iocRatio > 0.5) {
            return null;
        }

        return new CipherDetection(
            toolSlug: 'classical-ciphers/playfair',
            cipherKey: 'CIPHER_NAME_PLAYFAIR',
            confidence: 0.48,
            evidenceKeys: ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_MONO'],
            detectedAlphabet: $alphabet,
        );
    }
}
