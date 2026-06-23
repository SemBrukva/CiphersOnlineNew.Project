<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\BaconCipherService;
use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор шифра Бэкона.
 *
 * Сначала структурный фильтр: только символы A/B, длина без пробелов кратна 5.
 * Затем decode-check через {@see BaconCipherService}: если декодировка даёт
 * непустой результат с буквами алфавита — confidence высокий, иначе пониженный.
 */
final readonly class BaconDetector implements CipherDetectorInterface
{
    /**
     * Создаёт экземпляр детектора.
     */
    public function __construct(
        private BaconCipherService $bacon,
    ) {
    }

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

        $expectedGroups = intdiv($len, 5);
        $decoded        = $this->bacon->process($trimmed, 'en', 'decrypt');
        $letterCount    = preg_match_all('/\p{L}/u', $decoded);

        // Если хотя бы 80% групп декодировано в валидные буквы — считаем результат «читаемым».
        if ($letterCount >= (int) ceil($expectedGroups * 0.80)) {
            $confidence = 0.94;
        } elseif ($letterCount > 0) {
            $confidence = 0.75;
        } else {
            $confidence = 0.45;
        }

        return new CipherDetection(
            toolSlug: 'codes-and-alphabets/bacon',
            cipherKey: 'CIPHER_NAME_BACON',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_BACON', 'CID_EV_LENGTH_MULTIPLE_OF'],
        );
    }
}
