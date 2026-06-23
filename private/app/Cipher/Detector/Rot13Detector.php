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
 * Детектор ROT13 (частный случай Caesar shift=13 для английского).
 *
 * Проверяет, что shift=13 даёт явно лучший χ², чем другие сдвиги.
 * При срабатывании предлагает caesar-brute-force как auto-trigger:
 * сам инструмент Rot13 — клиентский и не имеет API-action.
 */
final readonly class Rot13Detector implements CipherDetectorInterface
{
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
        // ROT13 — соглашение строго для латинского алфавита.
        $alphabet = $ctx->effectiveAlphabet();
        if ($alphabet !== 'en') {
            return null;
        }

        $letterCount = $ctx->letterCount('en');
        if ($letterCount < 5) {
            return null;
        }
        if ($ctx->letterRatio('en') < 0.80) {
            return null;
        }

        $iocValue   = $ctx->iocFor('en');
        $naturalIoc = IndexOfCoincidence::LANGUAGE_IOC['en'];
        $randomIoc  = IndexOfCoincidence::RANDOM_IOC['en'];
        $iocRatio   = abs($iocValue - $naturalIoc) / ($naturalIoc - $randomIoc + 0.001);
        if ($iocRatio > 0.6) {
            return null;
        }

        $rot13Decrypted = $this->caesar->process($ctx->text, 'en', 13, 'decrypt');
        $chi13          = $this->scorer->chiSquared($rot13Decrypted, 'en');

        $chiValues = [];
        for ($shift = 0; $shift <= 25; $shift++) {
            if ($shift === 13) {
                continue;
            }
            $decrypted   = $this->caesar->process($ctx->text, 'en', $shift, 'decrypt');
            $chiValues[] = $this->scorer->chiSquared($decrypted, 'en');
        }

        $minOther = min($chiValues);
        if ($chi13 >= $minOther) {
            return null;
        }

        $gap = $minOther - $chi13;
        if ($gap < 0.01) {
            return null;
        }

        $confidence = 0.65 + min(0.15, $gap * 2.0);

        $hints = [];
        if (!$ctx->hasReliableSample('en')) {
            $scale       = $letterCount / LetterFrequencyScorer::MIN_LETTERS_FOR_RELIABLE_SCORING;
            $confidence *= $scale;
            $hints['low_sample'] = true;
        }

        return new CipherDetection(
            toolSlug: 'classical-ciphers/rot13',
            cipherKey: 'CIPHER_NAME_ROT13',
            confidence: min(0.80, $confidence),
            evidenceKeys: ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_MONO', 'CID_EV_CHISQ_BEST_SHIFT'],
            bruteForceAction: 'caesar-brute-force',
            detectedAlphabet: 'en',
            hints: $hints,
        );
    }
}
