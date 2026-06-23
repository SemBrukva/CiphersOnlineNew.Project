<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\AffineCipherService;
use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;
use App\Cipher\LetterFrequencyScorer;

/**
 * Детектор аффинного шифра.
 *
 * Признак: только буквы; IoC ≈ IoC языка. После grosse-filter перебирает все
 * валидные пары (a, b) и выбирает расшифровку с минимальным χ². Если победитель
 * явно отрывается от второго — поднимает confidence до уровня Caesar-winner,
 * иначе оставляет базовый. Расшифровка кладётся в CipherDetection::decryptedText
 * для последующего bigram-rescore в {@see \App\Cipher\CipherIdentifierService}.
 */
final readonly class AffineDetector implements CipherDetectorInterface
{
    /**
     * Относительный отрыв лучшего χ² от второго, при котором аффинный кандидат
     * признаётся «явным победителем». Сравнивается как (chi₂ − chi₁) / chi₁.
     */
    private const float CHI_WINNER_RATIO = 0.30;

    /**
     * Максимальная длина текста для перебора 312–660 ключей: ограничение
     * сложности O(text × keysCount). Для надёжного χ² 500 букв с запасом хватает.
     */
    private const int BRUTE_FORCE_TEXT_CAP = 500;

    /**
     * Создаёт экземпляр детектора.
     */
    public function __construct(
        private LetterFrequencyScorer $scorer,
        private AffineCipherService $affine,
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

        $sampleText   = $this->sampleForBruteForce($ctx->text);
        $alphabetSize = $this->affine->alphabetSize($alphabet);
        $bruteResult  = $this->bruteForceAffine($sampleText, $alphabet, $alphabetSize);

        // Вырожденные случаи Affine:
        //   - multiplier=1 → Caesar (любой shift);
        //   - multiplier=N-1, shift=N-1 → Atbash.
        // Не конкурируем с CaesarDetector/AtbashDetector: отдаём базовый
        // confidence без winner-бонуса, чтобы соответствующий специализированный
        // детектор остался лидером и не делил с нами top места (иначе auto-trigger
        // gap обнуляется).
        $isDegenerateCaesar = $bruteResult['multiplier'] === 1;
        $isDegenerateAtbash = $bruteResult['multiplier'] === $alphabetSize - 1
            && $bruteResult['shift'] === $alphabetSize - 1;
        $isDegenerate = $isDegenerateCaesar || $isDegenerateAtbash;
        $hasWinner    = $bruteResult['has_winner'] && !$isDegenerate;
        $confidence   = $hasWinner ? 0.85 : 0.52;

        $hints = [
            'multiplier' => $bruteResult['multiplier'],
            'shift'      => $bruteResult['shift'],
        ];
        if ($isDegenerateCaesar) {
            $hints['degenerate_caesar'] = true;
        }
        if ($isDegenerateAtbash) {
            $hints['degenerate_atbash'] = true;
        }
        if (!$ctx->hasReliableSample($alphabet)) {
            $scale       = $letterCount / LetterFrequencyScorer::MIN_LETTERS_FOR_RELIABLE_SCORING;
            $confidence *= $scale;
            $hints['low_sample'] = true;
        }

        $evidence = ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_MONO'];
        if ($hasWinner) {
            $evidence[] = 'CID_EV_CHISQ_BEST_SHIFT';
        }

        // Полную расшифровку получаем уже на исходном тексте, чтобы bigram-rescore
        // в CipherIdentifierService работал на максимальной выборке.
        $fullDecrypted = $this->affine->process(
            $ctx->text,
            $alphabet,
            $bruteResult['multiplier'],
            $bruteResult['shift'],
            'decrypt'
        );

        return new CipherDetection(
            toolSlug: 'classical-ciphers/affine',
            cipherKey: 'CIPHER_NAME_AFFINE',
            confidence: $confidence,
            evidenceKeys: $evidence,
            bruteForceAction: 'affine-brute-force',
            detectedAlphabet: $alphabet,
            hints: $hints,
            decryptedText: $fullDecrypted,
        );
    }

    /**
     * Перебирает все пары (a, b), где gcd(a, N) = 1, и возвращает пару с
     * минимальным χ²-отклонением расшифровки от языкового профиля.
     *
     * @return array{multiplier: int, shift: int, has_winner: bool}
     */
    private function bruteForceAffine(string $text, string $alphabet, int $alphabetSize): array
    {
        $chiValues = [];

        for ($a = 1; $a < $alphabetSize; $a++) {
            if (!$this->affine->isValidMultiplier($a, $alphabet)) {
                continue;
            }
            for ($b = 0; $b < $alphabetSize; $b++) {
                $decrypted    = $this->affine->process($text, $alphabet, $a, $b, 'decrypt');
                $chiValues[]  = ['a' => $a, 'b' => $b, 'chi' => $this->scorer->chiSquared($decrypted, $alphabet)];
            }
        }

        usort($chiValues, static fn (array $x, array $y): int => $x['chi'] <=> $y['chi']);

        $best   = $chiValues[0];
        $second = $chiValues[1] ?? $best;

        $hasWinner = $best['chi'] > 0.0
            && (($second['chi'] - $best['chi']) / $best['chi']) > self::CHI_WINNER_RATIO;

        return [
            'multiplier' => $best['a'],
            'shift'      => $best['b'],
            'has_winner' => $hasWinner,
        ];
    }

    /**
     * Возвращает префикс исходного текста, ограниченный по длине: для брутфорса
     * χ² 500 символов с запасом достаточно, а ресурс на полный текст тратить нет смысла.
     */
    private function sampleForBruteForce(string $text): string
    {
        if (mb_strlen($text) <= self::BRUTE_FORCE_TEXT_CAP) {
            return $text;
        }

        return mb_substr($text, 0, self::BRUTE_FORCE_TEXT_CAP);
    }
}
