<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор JWT (JSON Web Token).
 *
 * Признак: три части через точки, каждая — валидный base64url.
 */
final readonly class JwtDetector implements CipherDetectorInterface
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

        $parts = explode('.', $trimmed);
        if (count($parts) !== 3) {
            return null;
        }

        foreach ($parts as $part) {
            if ($part === '' || !preg_match('/^[A-Za-z0-9\-_]+={0,2}$/', $part)) {
                return null;
            }
        }

        return new CipherDetection(
            toolSlug: 'encoding/jwt-decoder',
            cipherKey: 'CIPHER_NAME_JWT',
            confidence: 0.97,
            evidenceKeys: ['CID_EV_CHARSET_BASE64'],
        );
    }
}
