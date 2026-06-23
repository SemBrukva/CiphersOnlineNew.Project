<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * DTO результата детекции шифра/кодировки.
 */
final readonly class CipherDetection
{
    /**
     * Создаёт экземпляр результата детекции.
     *
     * @param string              $toolSlug          Canonical slug инструмента ('classical-ciphers/caesar').
     * @param string              $cipherKey         Ключ перевода названия шифра ('CIPHER_NAME_CAESAR').
     * @param float               $confidence        0.0–1.0.
     * @param string[]            $evidenceKeys      Ключи перевода-объяснения ('CID_EV_CHARSET_LETTERS').
     * @param string|null         $bruteForceAction  action для ApiCipherToolRegistry ('caesar-brute-force').
     * @param string|null         $detectedAlphabet  'en' | 'ru' | ... или null.
     * @param array<string,scalar> $hints             Доп. подсказки для UI (например, key_required).
     */
    public function __construct(
        public string $toolSlug,
        public string $cipherKey,
        public float $confidence,
        public array $evidenceKeys = [],
        public ?string $bruteForceAction = null,
        public ?string $detectedAlphabet = null,
        public array $hints = [],
    ) {
    }
}
