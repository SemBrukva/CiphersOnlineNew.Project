<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\A1z26CipherService;
use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;

/**
 * Детектор кодировки A1Z26 (числа 1–26 через разделители).
 *
 * Сначала структурный фильтр: только числа с делителями `-`, ` `, `,`. Затем
 * decode-check через {@see A1z26CipherService}: если все числа дают валидные
 * индексы в выбранном алфавите — confidence высокий.
 */
final readonly class A1z26Detector implements CipherDetectorInterface
{
    /** Максимально возможный индекс алфавита (кириллица — 33). */
    private const int MAX_ALPHABET_SIZE = 33;

    /**
     * Создаёт экземпляр детектора.
     */
    public function __construct(
        private A1z26CipherService $a1z26,
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

        if (!preg_match('/^\d+(?:[-\s,]\d+)+$/', $trimmed)) {
            return null;
        }

        $parts = preg_split('/[-\s,]+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) < 2) {
            return null;
        }

        $polybiusOnly = true;
        foreach ($parts as $part) {
            $n = (int) $part;
            if ($n < 1 || $n > self::MAX_ALPHABET_SIZE) {
                return null;
            }
            if (mb_strlen($part) !== 2 || !preg_match('/^[1-6][1-6]$/', $part)) {
                $polybiusOnly = false;
            }
        }

        // Декод-проверка: какой алфавит и разделитель дают самый «читаемый» вывод?
        $delimiter   = $this->guessDelimiter($trimmed);
        $alphabet    = $ctx->effectiveAlphabet();
        $decoded     = $this->a1z26->process($trimmed, $alphabet, 'decrypt', $delimiter);
        $letterCount = preg_match_all('/\p{L}/u', $decoded);
        $totalNums   = count($parts);

        // Когда каждое число — двузначное в диапазоне Polybius, отдаём приоритет
        // Polybius-детектору, понизив наш confidence.
        if ($polybiusOnly) {
            return new CipherDetection(
                toolSlug: 'codes-and-alphabets/a1z26',
                cipherKey: 'CIPHER_NAME_A1Z26',
                confidence: 0.55,
                evidenceKeys: ['CID_EV_CHARSET_NUMBERS'],
            );
        }

        if ($letterCount >= (int) ceil($totalNums * 0.80)) {
            $confidence = $totalNums < 3 ? 0.78 : 0.92;
        } elseif ($letterCount > 0) {
            $confidence = 0.65;
        } else {
            // Декод не вернул ни одной буквы — ни один индекс не валиден в алфавите.
            $confidence = 0.40;
        }

        return new CipherDetection(
            toolSlug: 'codes-and-alphabets/a1z26',
            cipherKey: 'CIPHER_NAME_A1Z26',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_NUMBERS'],
        );
    }

    /**
     * Определяет наиболее вероятный разделитель чисел: тот, что встречается чаще всех.
     */
    private function guessDelimiter(string $text): string
    {
        $counts = [
            'dash'  => substr_count($text, '-'),
            'comma' => substr_count($text, ','),
            'space' => preg_match_all('/\s+/', $text) ?: 0,
        ];

        return array_search(max($counts), $counts, true) ?: 'dash';
    }
}
