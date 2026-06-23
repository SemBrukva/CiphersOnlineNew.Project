<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор шифра Бэкона.
 *
 * Признак: только символы A и B (или a и b), длина очищенного текста кратна 5.
 */
final readonly class BaconDetector implements CipherDetectorInterface
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

        if (!preg_match('/^[ABab\s]+$/', $trimmed)) {
            return null;
        }

        $clean = $ctx->cleanedText();
        $len   = strlen($clean);
        if ($len < 5 || $len % 5 !== 0) {
            return null;
        }

        return new CipherDetection(
            toolSlug: 'codes-and-alphabets/bacon',
            cipherKey: 'CIPHER_NAME_BACON',
            confidence: 0.93,
            evidenceKeys: ['CID_EV_CHARSET_BACON', 'CID_EV_LENGTH_MULTIPLE_OF'],
        );
    }
}
