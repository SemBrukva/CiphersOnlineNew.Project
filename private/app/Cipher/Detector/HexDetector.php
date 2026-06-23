<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор HEX-кодировки.
 *
 * Чисто символьная проверка (`^[0-9a-fA-F\s]+$`) ловит и hex-кодированный
 * обычный текст, и hex-представление произвольных байт (включая шифр XOR).
 * Чтобы не конкурировать с XorDetector на одинаковом входе, выполняется
 * decode-check: высокий confidence — только когда декодированные байты
 * выглядят как печатный ASCII/UTF-8, иначе — низкий confidence.
 */
final readonly class HexDetector implements CipherDetectorInterface
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

        $decoded = @hex2bin($clean);
        if ($decoded === false) {
            return null;
        }

        $confidence = match (true) {
            $this->looksLikeText($decoded) => $len < 8 ? 0.78 : 0.92,
            default                        => 0.55,
        };

        return new CipherDetection(
            toolSlug: 'encoding/hex',
            cipherKey: 'CIPHER_NAME_HEX',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_HEX'],
        );
    }

    /**
     * Проверяет, что декодированные байты похожи на текст: мало control-байт
     * (≤ {@see CONTROL_RATIO_MAX}) и поток валиден как UTF-8. ASCII-control'ы
     * \t\n\r не считаются «плохими», NUL/SO/DEL и пр. — считаются.
     *
     * Без проверки control-байт чистая UTF-8-валидация пропускала бы данные
     * с управляющими байтами в случайных позициях (типичная картина для
     * бинарных данных или XOR-шифрованного текста).
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
