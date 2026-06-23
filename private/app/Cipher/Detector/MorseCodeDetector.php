<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор кода Морзе.
 *
 * Признак: только точки, тире, пробелы и слэши (разделители слов).
 * Последовательности dot/dash длиной 1–5.
 */
final readonly class MorseCodeDetector implements CipherDetectorInterface
{
    /**
     * {@inheritDoc}
     */
    public function detect(IdentificationContext $ctx): ?CipherDetection
    {
        $trimmed = trim($ctx->text);
        if ($trimmed === '') {
            return null;
        }

        if (!preg_match('/^[.\-\/\s]+$/', $trimmed)) {
            return null;
        }

        // Должна быть хотя бы одна точка или тире.
        if (!preg_match('/[.\-]/', $trimmed)) {
            return null;
        }

        $tokens = preg_split('/\s+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) < 1) {
            return null;
        }

        $validTokens = 0;
        foreach ($tokens as $token) {
            if ($token === '/' || preg_match('/^[.\-]{1,6}$/', $token)) {
                $validTokens++;
            }
        }

        if (($validTokens / count($tokens)) < 0.85) {
            return null;
        }

        return new CipherDetection(
            toolSlug: 'codes-and-alphabets/morse-code',
            cipherKey: 'CIPHER_NAME_MORSE_CODE',
            confidence: 0.95,
            evidenceKeys: ['CID_EV_CHARSET_MORSE'],
        );
    }
}
