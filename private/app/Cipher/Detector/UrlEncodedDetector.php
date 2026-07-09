<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор URL-кодировки (percent-encoding).
 *
 * Признак: встречается хотя бы одна последовательность %XX.
 */
final readonly class UrlEncodedDetector implements CipherDetectorInterface
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

        $count = preg_match_all('/%[0-9a-fA-F]{2}/', $trimmed);
        if ($count < 1) {
            return null;
        }

        $confidence = $count >= 3 ? 0.92 : 0.82;

        // Percent-decoding детерминировано: отдаём расшифровку, если результат —
        // валидный UTF-8 и реально отличается от исходного.
        $decoded   = rawurldecode($trimmed);
        $plaintext = ($decoded !== $trimmed && mb_check_encoding($decoded, 'UTF-8')) ? $decoded : null;

        return new CipherDetection(
            toolSlug: 'encoding/url-encode',
            cipherKey: 'CIPHER_NAME_URL_ENCODE',
            confidence: $confidence,
            evidenceKeys: [],
            decryptedText: $plaintext,
        );
    }
}
