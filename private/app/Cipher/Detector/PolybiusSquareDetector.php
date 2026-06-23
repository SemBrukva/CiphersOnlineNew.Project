<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\LetterFrequencyScorer;
use App\Cipher\PolybiusSquareCipherService;

/**
 * Детектор квадрата Полибия.
 *
 * Структурный фильтр: пары цифр 1..5 (или 1..6), разделённые пробелами.
 * Decode-check через {@see PolybiusSquareCipherService}: проверяется доля
 * декодированных букв И χ² результата против профиля языка. Случайные «33 12
 * 21 11 22» структурно валидны, но дают почти равномерное распределение букв —
 * χ² высок → confidence низкий → ложное срабатывание отсекается.
 */
final readonly class PolybiusSquareDetector implements CipherDetectorInterface
{
    /** Порог χ², ниже которого расшифровка читается как естественный язык. */
    private const float CHI_READABLE = 0.40;

    /** Порог χ², выше которого расшифровка считается шумом. */
    private const float CHI_NOISE = 1.00;

    /**
     * Минимум букв в декоде, при котором χ² уже надёжен и можно применять
     * бонус/штраф. На коротких декодах (HELLO WORLD) χ² высок просто потому,
     * что выборка маленькая — это не сигнал шума.
     */
    private const int CHI_RELIABLE_MIN_LETTERS = 30;

    /**
     * Создаёт экземпляр детектора.
     */
    public function __construct(
        private PolybiusSquareCipherService $polybius,
        private LetterFrequencyScorer $scorer,
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

        $tokens = preg_split('/\s+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) < 2) {
            return null;
        }

        $valid5 = 0;
        $valid6 = 0;
        $total  = count($tokens);

        foreach ($tokens as $token) {
            if (preg_match('/^[1-5]{2}$/', $token)) {
                $valid5++;
                $valid6++;
            } elseif (preg_match('/^[1-6]{2}$/', $token)) {
                $valid6++;
            }
        }

        $ratio5 = $valid5 / $total;
        $ratio6 = $valid6 / $total;

        if ($ratio6 < 0.85) {
            return null;
        }

        $alphabet    = $ctx->effectiveAlphabet();
        $decoded     = $this->polybius->process($trimmed, $alphabet, 'decrypt', 'space');
        $letterCount = preg_match_all('/\p{L}/u', $decoded);
        $letterRatio = $letterCount / max(1, $total);

        // Базовая шкала: чем «чище» 5×5 паттерн, тем выше confidence.
        $base = $ratio5 >= 0.85 ? 0.88 : 0.82;
        if ($total < 3) {
            $base -= 0.15;
        }

        // χ² результата: «читается» → высокий confidence, «шум» → понижение.
        // Бонус/штраф применяем только на достаточно длинных декодах, иначе
        // короткий HELLO WORLD из 10 букв даст высокий χ² по статистике выборки,
        // а не из-за реальной непохожести на язык.
        $chiBonus = 0.0;
        $hints    = [];
        if ($letterCount >= max(3, (int) ceil($total * 0.80))) {
            if ($letterCount >= self::CHI_RELIABLE_MIN_LETTERS) {
                $chi = $this->scorer->chiSquared($decoded, $alphabet);
                $hints['decoded_chi_squared'] = round($chi, 4);
                if ($chi <= self::CHI_READABLE) {
                    $chiBonus = 0.05;
                } elseif ($chi >= self::CHI_NOISE) {
                    $chiBonus = -0.30;
                }
            }
        } elseif ($letterRatio < 0.5) {
            $chiBonus = -0.30;
        }

        $confidence = max(0.30, min(0.93, $base + $chiBonus));

        return new CipherDetection(
            toolSlug: 'codes-and-alphabets/polybius-square',
            cipherKey: 'CIPHER_NAME_POLYBIUS_SQUARE',
            confidence: $confidence,
            evidenceKeys: ['CID_EV_CHARSET_NUMBERS'],
            hints: $hints,
        );
    }
}
