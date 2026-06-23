<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\AtbashCipherService;
use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;
use App\Cipher\LetterFrequencyScorer;

/**
 * Детектор шифра Атбаш.
 *
 * Признак: только буквы; IoC ≈ IoC языка; обратный алфавит даёт χ²,
 * существенно меньший, чем у оригинала и явно близкий к нулю.
 */
final readonly class AtbashDetector implements CipherDetectorInterface
{
    /** Порог χ² расшифровки, ниже которого результат «читается». */
    private const float CHI_READABLE = 0.05;

    /** Требуемое соотношение χ²(decrypted) / χ²(original) для срабатывания. */
    private const float REQUIRED_RATIO = 0.50;

    /**
     * Создаёт экземпляр детектора.
     */
    public function __construct(
        private LetterFrequencyScorer $scorer,
        private AtbashCipherService $atbash,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function detect(IdentificationContext $ctx): ?CipherDetection
    {
        $alphabet    = $ctx->effectiveAlphabet();
        $letterCount = $ctx->letterCount($alphabet);
        if ($letterCount < 5) {
            return null;
        }
        if ($ctx->letterRatio($alphabet) < 0.80) {
            return null;
        }

        $iocValue   = $ctx->iocFor($alphabet);
        $naturalIoc = IndexOfCoincidence::LANGUAGE_IOC[$alphabet] ?? 0.065;
        $randomIoc  = IndexOfCoincidence::RANDOM_IOC[$alphabet]   ?? 0.038;
        $iocRatio   = abs($iocValue - $naturalIoc) / ($naturalIoc - $randomIoc + 0.001);
        if ($iocRatio > 0.6) {
            return null;
        }

        $decrypted = $this->atbash->process($ctx->text, $alphabet);
        $chiDecr   = $this->scorer->chiSquared($decrypted, $alphabet);
        $chiOrig   = $this->scorer->chiSquared($ctx->text, $alphabet);

        // Расшифровка должна быть строго лучше оригинала, иначе это случайный шум.
        if ($chiOrig <= 0.0 || $chiDecr >= $chiOrig * self::REQUIRED_RATIO) {
            return null;
        }

        // Базовый сигнал: 0.55. Если расшифровка действительно «читается» —
        // поднимаем до 0.78, что выше AUTO_THRESHOLD = 0.70.
        $confidence = $chiDecr < self::CHI_READABLE ? 0.78 : 0.55;

        $hints = [];
        if (!$ctx->hasReliableSample($alphabet)) {
            $scale       = $letterCount / LetterFrequencyScorer::MIN_LETTERS_FOR_RELIABLE_SCORING;
            $confidence *= $scale;
            $hints['low_sample'] = true;
        }

        $evidence = ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_MONO'];
        if ($chiDecr < self::CHI_READABLE) {
            $evidence[] = 'CID_EV_CHISQ_BEST_SHIFT';
        }

        return new CipherDetection(
            toolSlug: 'classical-ciphers/atbash',
            cipherKey: 'CIPHER_NAME_ATBASH',
            confidence: $confidence,
            evidenceKeys: $evidence,
            detectedAlphabet: $alphabet,
            hints: $hints,
        );
    }
}
