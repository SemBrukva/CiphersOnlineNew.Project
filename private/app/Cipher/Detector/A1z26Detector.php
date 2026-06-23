<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор кодировки A1Z26 (числа 1–26 через разделители).
 *
 * Признак: строка из целых чисел через тире, пробелы или запятые;
 * все числа в диапазоне 1..33 (покрывает кириллицу).
 */
final readonly class A1z26Detector implements CipherDetectorInterface
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

        if (!preg_match('/^\d+(?:[-\s,]\d+)+$/', $trimmed)) {
            return null;
        }

        $parts = preg_split('/[-\s,]+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 2) {
            return null;
        }

        $maxAlphaSize = 33; // Кириллица.
        $polybiusOnly = true;
        foreach ($parts as $part) {
            $n = (int) $part;
            if ($n < 1 || $n > $maxAlphaSize) {
                return null;
            }
            // Polybius-числа всегда двузначные с цифрами 1–5/1–6.
            if (mb_strlen($part) !== 2 || !preg_match('/^[1-6][1-6]$/', $part)) {
                $polybiusOnly = false;
            }
        }

        // Когда каждое число — двузначное в диапазоне Polybius, отдаём приоритет
        // Polybius-детектору, понизив наш confidence.
        $confidence = 0.88;
        if ($polybiusOnly) {
            $confidence = 0.55;
        } elseif (count($parts) < 3) {
            $confidence = 0.72;
        }

        return new CipherDetection(
            toolSlug: 'codes-and-alphabets/a1z26',
            cipherKey: 'CIPHER_NAME_A1Z26',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_NUMBERS'],
        );
    }
}
