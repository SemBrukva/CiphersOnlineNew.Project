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
 * Дополнительно: после декода проверяются magic-bytes известных форматов файлов
 * (PNG, ZIP, PDF, PEM и т.д.). Совпадение сигнатуры поднимает confidence почти
 * до максимума и кладёт тип файла в hints — пользователь сразу видит, что в base64.
 */
final readonly class Base64Detector implements CipherDetectorInterface
{
    /**
     * Magic-bytes известных форматов файлов. Ключ — байтовая сигнатура (точно с её начала),
     * значение — пользовательское имя формата.
     *
     * @var array<string, string>
     */
    private const array FILE_SIGNATURES = [
        "\x50\x4b\x03\x04"             => 'ZIP/Office',
        "\x89PNG\r\n\x1a\n"            => 'PNG',
        "GIF87a"                       => 'GIF',
        "GIF89a"                       => 'GIF',
        "\xff\xd8\xff"                 => 'JPEG',
        "%PDF-"                        => 'PDF',
        "-----BEGIN"                   => 'PEM/PGP',
        "\x1f\x8b"                     => 'GZIP',
        "BZh"                          => 'BZIP2',
        "7z\xbc\xaf\x27\x1c"           => '7Z',
        "Rar!\x1a\x07"                 => 'RAR',
        "ssh-rsa "                     => 'SSH public key',
        "ssh-ed25519 "                 => 'SSH public key',
        "<?xml"                        => 'XML',
        "<svg"                         => 'SVG',
    ];

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

        if (preg_match('/={3,}/', $clean)) {
            return null;
        }

        // Три части через точку → возможно JWT, а не base64.
        if (substr_count($clean, '.') === 2) {
            return null;
        }

        $decoded = base64_decode($clean, true);
        if ($decoded === false) {
            return null;
        }

        $signature = $this->detectFileSignature($decoded);
        if ($signature !== null) {
            return new CipherDetection(
                toolSlug: 'encoding/base64',
                cipherKey: 'CIPHER_NAME_BASE64',
                confidence: 0.97,
                evidenceKeys: ['CID_EV_CHARSET_BASE64', 'CID_EV_FILE_SIGNATURE'],
                hints: ['file_format' => $signature],
            );
        }

        // Без сигнатуры падаем обратно к UTF-8-проверке: только текстовый base64.
        if (!mb_check_encoding($decoded, 'UTF-8')) {
            return null;
        }

        $confidence = $len < 16 ? 0.75 : 0.90;

        return new CipherDetection(
            toolSlug: 'encoding/base64',
            cipherKey: 'CIPHER_NAME_BASE64',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_BASE64'],
        );
    }

    /**
     * Сравнивает префикс декодированных байт с известными file-magic.
     */
    private function detectFileSignature(string $bytes): ?string
    {
        foreach (self::FILE_SIGNATURES as $magic => $type) {
            if (str_starts_with($bytes, $magic)) {
                return $type;
            }
        }

        return null;
    }
}
