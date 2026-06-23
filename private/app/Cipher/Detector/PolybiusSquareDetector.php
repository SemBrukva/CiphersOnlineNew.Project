<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор квадрата Полибия.
 *
 * Признак: пары цифр 1..5 (или 1..6), разделённых пробелами.
 */
final readonly class PolybiusSquareDetector implements CipherDetectorInterface
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

        $tokens = preg_split('/\s+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) < 2) {
            return null;
        }

        $valid5 = 0;
        $valid6 = 0;
        $total  = count($tokens);

        foreach ($tokens as $token) {
            if (preg_match('/^[1-5]{2}$/', $token)) {
                $valid5++;
                $valid6++;
            } elseif (preg_match('/^[1-6]{2}$/', $token)) {
                $valid6++;
            }
        }

        $ratio5 = $valid5 / $total;
        $ratio6 = $valid6 / $total;

        if ($ratio6 < 0.85) {
            return null;
        }

        $confidence = $ratio5 >= 0.85 ? 0.88 : 0.82;
        if ($total < 3) {
            $confidence -= 0.15;
        }

        return new CipherDetection(
            toolSlug: 'codes-and-alphabets/polybius-square',
            cipherKey: 'CIPHER_NAME_POLYBIUS_SQUARE',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_NUMBERS'],
        );
    }
}
