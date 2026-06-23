<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор двоичного (binary) представления.
 *
 * Сначала структурный фильтр (charset 0/1, длина блоков). Затем decode-check:
 * декодируем последовательность бит как байты (8 или 7 бит) и проверяем, что
 * результат — печатный ASCII/UTF-8. Без проверки декодирования детектор
 * срабатывал на любой длинной строке из 0/1, включая шум.
 */
final readonly class BinaryDetector implements CipherDetectorInterface
{
    /** Максимально допустимая доля control-байт в результате, который считается «текстом». */
    private const float CONTROL_RATIO_MAX = 0.05;

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
        $bitsPerByte = 0;

        if (count($blocks) > 1) {
            $validBlockSizes = [7, 8, 16, 32];
            $blockSizes      = array_unique(array_map('strlen', $blocks));
            $allValid        = count($blockSizes) === 1 && in_array((int) reset($blockSizes), $validBlockSizes, true);
            if (!$allValid && count($blockSizes) > 2) {
                return null;
            }
            if ($allValid) {
                $bitsPerByte = (int) reset($blockSizes);
            }
        } else {
            if ($len % 8 === 0) {
                $bitsPerByte = 8;
            } elseif ($len % 7 === 0) {
                $bitsPerByte = 7;
            } else {
                return null;
            }
        }

        $decoded = $this->decodeBits($clean, $bitsPerByte);
        if ($decoded === null) {
            // Нечитаемая структура: ширина блока не нашлась.
            return new CipherDetection(
                toolSlug: 'encoding/binary-converter',
                cipherKey: 'CIPHER_NAME_BINARY',
                confidence: 0.55,
                evidenceKeys: ['CID_EV_CHARSET_BINARY'],
            );
        }

        $confidence = match (true) {
            $this->looksLikeText($decoded) => $len < 16 ? 0.78 : 0.93,
            default                        => 0.55,
        };

        return new CipherDetection(
            toolSlug: 'encoding/binary-converter',
            cipherKey: 'CIPHER_NAME_BINARY',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_BINARY'],
        );
    }

    /**
     * Декодирует последовательность из 0/1 в строку байт фиксированной ширины.
     * При нулевой ширине или неполном последнем блоке возвращает null.
     */
    private function decodeBits(string $bits, int $bitsPerByte): ?string
    {
        if ($bitsPerByte <= 0 || $bitsPerByte > 16) {
            return null;
        }
        if (strlen($bits) % $bitsPerByte !== 0) {
            return null;
        }

        $result = '';
        for ($i = 0; $i < strlen($bits); $i += $bitsPerByte) {
            $byte = (int) bindec(substr($bits, $i, $bitsPerByte));
            if ($byte < 0 || $byte > 0xFF) {
                return null;
            }
            $result .= chr($byte);
        }

        return $result;
    }

    /**
     * Проверяет, что декодированные байты похожи на текст: мало control-байт
     * (≤ {@see CONTROL_RATIO_MAX}) и поток валиден как UTF-8. \t\n\r допускаются,
     * остальные control'ы — нет.
     */
    private function looksLikeText(string $bytes): bool
    {
        $len = strlen($bytes);
        if ($len === 0) {
            return false;
        }

        $control = 0;
        for ($i = 0; $i < $len; $i++) {
            $b = ord($bytes[$i]);
            if ($b === 0x09 || $b === 0x0A || $b === 0x0D) {
                continue;
            }
            if ($b < 0x20 || $b === 0x7F) {
                $control++;
            }
        }

        return ($control / $len) <= self::CONTROL_RATIO_MAX && mb_check_encoding($bytes, 'UTF-8');
    }
}
