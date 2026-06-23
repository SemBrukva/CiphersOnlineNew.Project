<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор HEX-кодировки.
 *
 * Признак: только 0-9a-fA-F и пробелы, длина чистых hex-символов чётная.
 */
final readonly class HexDetector implements CipherDetectorInterface
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

        if (!preg_match('/^[0-9a-fA-F\s]+$/', $trimmed)) {
            return null;
        }

        $clean = $ctx->cleanedText();
        if ($clean === '') {
            return null;
        }

        $len = strlen($clean);
        if ($len < 2 || $len % 2 !== 0) {
            return null;
        }

        $confidence = $len < 8 ? 0.75 : 0.90;

        return new CipherDetection(
            toolSlug: 'encoding/hex',
            cipherKey: 'CIPHER_NAME_HEX',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_HEX'],
        );
    }
}
