<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор Base64-кодировки.
 *
 * Признак: только символы A-Za-z0-9+/=, длина очищенного текста кратна 4.
 */
final readonly class Base64Detector implements CipherDetectorInterface
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

        if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $clean)) {
            return null;
        }

        $len = strlen($clean);
        if ($len < 4) {
            return null;
        }

        // Проверяем, что паддинг корректный (не более 2 символов =).
        if (preg_match('/={3,}/', $clean)) {
            return null;
        }

        // Три части через точку → возможно JWT, а не base64.
        if (substr_count($clean, '.') === 2) {
            return null;
        }

        // Жёсткий decode-check: строка должна быть валидной Base64, и декодированный
        // результат должен быть корректным UTF-8. Без него детектор ловил любые
        // строки из A-Z/a-z, включая Caesar-зашифрованный текст.
        $decoded = base64_decode($clean, true);
        if ($decoded === false || !mb_check_encoding($decoded, 'UTF-8')) {
            return null;
        }

        // Штраф за короткий текст.
        $confidence = $len < 16 ? 0.75 : 0.90;

        return new CipherDetection(
            toolSlug: 'encoding/base64',
            cipherKey: 'CIPHER_NAME_BASE64',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_BASE64'],
        );
    }
}
