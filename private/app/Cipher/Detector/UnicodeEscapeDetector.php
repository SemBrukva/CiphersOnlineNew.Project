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

        // Разворачиваем \uXXXX и U+XXXX в реальные символы: детерминированный
        // декод, пригодный как расшифровка для авто-солвера.
        $decoded = preg_replace_callback(
            '/\\\\u([0-9a-fA-F]{4})/',
            static fn (array $m): string => mb_chr((int) hexdec($m[1]), 'UTF-8'),
            $trimmed
        );
        $decoded = preg_replace_callback(
            '/U\+([0-9a-fA-F]{4,6})/',
            static fn (array $m): string => mb_chr((int) hexdec($m[1]), 'UTF-8'),
            (string) $decoded
        );
        $plaintext = (is_string($decoded) && $decoded !== $trimmed && mb_check_encoding($decoded, 'UTF-8')) ? $decoded : null;

        return new CipherDetection(
            toolSlug: 'encoding/unicode-converter',
            cipherKey: 'CIPHER_NAME_UNICODE',
            confidence: $confidence,
            evidenceKeys: [],
            decryptedText: $plaintext,
        );
    }
}
