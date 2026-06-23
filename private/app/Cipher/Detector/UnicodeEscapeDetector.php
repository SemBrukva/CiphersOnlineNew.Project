<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор Unicode-экранирования.
 *
 * Признак: паттерны \uXXXX или U+XXXX встречаются хотя бы раз.
 */
final readonly class UnicodeEscapeDetector implements CipherDetectorInterface
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

        $countEscape    = preg_match_all('/\\\\u[0-9a-fA-F]{4}/', $trimmed);
        $countCodepoint = preg_match_all('/U\+[0-9a-fA-F]{4,6}/', $trimmed);
        $total          = ($countEscape ?: 0) + ($countCodepoint ?: 0);

        if ($total < 1) {
            return null;
        }

        $confidence = $total >= 3 ? 0.90 : 0.78;

        return new CipherDetection(
            toolSlug: 'encoding/unicode-converter',
            cipherKey: 'CIPHER_NAME_UNICODE',
            confidence: $confidence,
            evidenceKeys: [],
        );
    }
}
