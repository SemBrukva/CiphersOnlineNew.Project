<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент перебора всех сдвигов шифра Цезаря (brute force).
 */
final readonly class CaesarBruteForceApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Создаёт экземпляр API-инструмента перебора Цезаря.
     */
    public function __construct(
        private CaesarCipherService $cipher,
        private LetterFrequencyScorer $scorer,
        private BigramFrequencyScorer $bigramScorer,
        private TrigramFrequencyScorer $trigramScorer,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'caesar-brute-force';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text     = (string) ($payload['text'] ?? '');
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $alphabet = mb_strtolower(trim((string) ($settings['alphabet'] ?? 'auto')));

        $isAuto    = $alphabet === 'auto';
        $supported = $this->cipher->supportedAlphabetCodes();

        $errors = [];
        if ($text === '') {
            $errors['text'][] = trans('CAESAR_ERR_TEXT_REQUIRED');
        }
        if (!$isAuto && !in_array($alphabet, $supported, true)) {
            $errors['settings.alphabet'][] = trans('CAESAR_ERR_ALPHABET_UNSUPPORTED');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('CAESAR_ERR_INVALID'), ['errors' => $errors]);
        }

        $detectedAlphabet = null;

        if ($isAuto) {
            $detectedAlphabet = $this->scorer->detectAlphabet($text);
            $alphabet         = $detectedAlphabet;
        } elseif ($this->scorer->countLetters($text, $alphabet) === 0) {
            $detectedAlphabet = $this->scorer->detectAlphabet($text);
            $alphabet         = $detectedAlphabet;
        }

        $maxShift = $this->cipher->maxShiftForAlphabet($alphabet);

        $texts = [];
        for ($shift = 0; $shift <= $maxShift; $shift++) {
            $texts[$shift] = $this->cipher->process($text, $alphabet, $shift, 'decrypt');
        }

        // Отбор лучшего сдвига: биграммное + триграммное лог-правдоподобие
        // различает верный сдвиг на коротких текстах надёжнее моно-χ². Для
        // алфавитов без n-gram-таблиц откатываемся к χ² (прежнее поведение).
        if ($this->bigramScorer->supports($alphabet)) {
            $useTrigram = $this->trigramScorer->supports($alphabet);
            $scores     = [];
            foreach ($texts as $shift => $t) {
                $score = $this->bigramScorer->score($t, $alphabet);
                if ($useTrigram) {
                    $score += $this->trigramScorer->score($t, $alphabet);
                }
                $scores[$shift] = $score;
            }
            $bestShift = (int) array_keys($scores, max($scores), true)[0];
            $fitness   = $this->scoresToFitness($scores);
        } else {
            $chiValues = array_map(fn (string $t): float => $this->scorer->chiSquared($t, $alphabet), $texts);
            $fitness   = $this->scorer->toFitness($chiValues);
            $bestShift = (int) array_key_first(array_filter($fitness, static fn (int $f): bool => $f === max($fitness)));
        }

        $letterCount = $this->scorer->countLetters($texts[0], $alphabet);
        $reliable    = $letterCount >= LetterFrequencyScorer::MIN_LETTERS_FOR_RELIABLE_SCORING;

        $results = [];
        foreach ($texts as $shift => $decrypted) {
            $results[] = [
                'shift'   => $shift,
                'text'    => $decrypted,
                'fitness' => $fitness[$shift],
            ];
        }

        return [
            'ok'                => true,
            'results'           => $results,
            'alphabet'          => $alphabet,
            'detected_alphabet' => $detectedAlphabet,
            'best_shift'        => $bestShift,
            'reliable'          => $reliable,
        ];
    }

    /**
     * Нормализует n-gram-скоры (выше — лучше) к оценкам пригодности 0..100.
     * Лучший сдвиг → 100, худший → 0.
     *
     * @param  array<int, float> $scores
     * @return array<int, int>
     */
    private function scoresToFitness(array $scores): array
    {
        if ($scores === []) {
            return [];
        }

        $max = max($scores);
        $min = min($scores);
        if ($max - $min < 1e-9) {
            return array_map(static fn (): int => 100, $scores);
        }

        return array_map(
            static fn (float $s): int => (int) round(100 * ($s - $min) / ($max - $min)),
            $scores
        );
    }
}
