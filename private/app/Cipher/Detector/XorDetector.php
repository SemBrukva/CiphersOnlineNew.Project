<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор XOR-шифра.
 *
 * Признак: hex-текст с чётной длиной и неравномерным распределением байт.
 */
final readonly class XorDetector implements CipherDetectorInterface
{
    /**
     * {@inheritDoc}
     */
    public function detect(IdentificationContext $ctx): ?CipherDetection
    {
        $clean = $ctx->cleanedText();
        if ($clean === '') {
            return null;
        }

        if (!preg_match('/^[0-9a-fA-F]+$/', $clean)) {
            return null;
        }

        $len = strlen($clean);
        if ($len < 4 || $len % 2 !== 0) {
            return null;
        }

        $byteFreq = [];
        for ($i = 0; $i < $len; $i += 2) {
            $byte            = hexdec(substr($clean, $i, 2));
            $byteFreq[$byte] = ($byteFreq[$byte] ?? 0) + 1;
        }

        $totalBytes   = $len / 2;
        $uniqueBytes  = count($byteFreq);
        $maxFreq      = max($byteFreq);
        $uniformRatio = $maxFreq / $totalBytes;

        if ($uniqueBytes < 4 || $uniformRatio < 0.05) {
            return null;
        }

        return new CipherDetection(
            toolSlug: 'classical-ciphers/xor-cipher',
            cipherKey: 'CIPHER_NAME_XOR',
            confidence: 0.50,
            evidenceKeys: ['CID_EV_CHARSET_HEX'],
        );
    }
}
