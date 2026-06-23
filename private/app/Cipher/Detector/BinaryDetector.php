<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор двоичного (binary) представления.
 *
 * Признак: только символы 0, 1 и пробелы; блоки длиной 7, 8, 16 или 32 бита.
 */
final readonly class BinaryDetector implements CipherDetectorInterface
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

        if (!preg_match('/^[01\s]+$/', $trimmed)) {
            return null;
        }

        $clean = $ctx->cleanedText();
        if ($clean === '') {
            return null;
        }

        $len = strlen($clean);
        if ($len < 7) {
            return null;
        }

        $blocks = preg_split('/\s+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($blocks) > 1) {
            $validBlockSizes = [7, 8, 16, 32];
            $blockSizes      = array_unique(array_map('strlen', $blocks));
            $allValid        = count($blockSizes) === 1 && in_array((int) reset($blockSizes), $validBlockSizes, true);
            if (!$allValid && count($blockSizes) > 2) {
                return null;
            }
        } elseif ($len % 8 !== 0 && $len % 7 !== 0) {
            // Без разделителей — длина должна быть кратна 7 или 8.
            return null;
        }

        $confidence = $len < 16 ? 0.75 : 0.92;

        return new CipherDetection(
            toolSlug: 'encoding/binary-converter',
            cipherKey: 'CIPHER_NAME_BINARY',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_BINARY'],
        );
    }
}
