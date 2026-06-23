<?php

declare(strict_types=1);

namespace App\Cipher\Detector;

use App\Cipher\AlphabetCatalog;
use App\Cipher\CipherDetection;
use App\Cipher\CipherDetectorInterface;
use App\Cipher\IdentificationContext;
use App\Cipher\IndexOfCoincidence;

/**
 * Детектор шифра Виженера.
 *
 * Базовый сигнал — только буквы и IoC между моноалфавитным и случайным.
 * Дополнительно: голосование Касиски по триграммам и средний IoC по колонкам
 * для каждой кандидатной длины ключа. Если пик IoC по колонкам близок к
 * естественному IoC языка — это сильный сигнал, что текст полиалфавитный с
 * восстановимой длиной ключа; confidence поднимается. Если поверх этого пик
 * подтверждён Касиски-голосованием — ещё немного.
 */
final readonly class VigenereDetector implements CipherDetectorInterface
{
    /** Максимальная длина ключа для перебора в детекторе. */
    private const int MAX_KEY_LENGTH = 12;

    /** Минимум букв на колонку для устойчивого среднего IoC. */
    private const int MIN_LETTERS_PER_COLUMN = 10;

    /** Доля от natural IoC, при которой пик по колонкам считается значимым. */
    private const float COLUMN_IOC_PEAK_RATIO = 0.85;

    /** Бонус confidence при обнаружении пика IoC по колонкам. */
    private const float COLUMN_PEAK_BONUS = 0.10;

    /** Бонус confidence, когда пик подтверждён Касиски. */
    private const float KASISKI_BONUS = 0.05;

    /** Потолок confidence без подтверждения Касиски. */
    private const float CEILING_WITH_PEAK = 0.78;

    /** Потолок confidence при пике + Касиски. */
    private const float CEILING_WITH_KASISKI = 0.85;

    /** Минимум поддержек делителем расстояний для срабатывания Касиски. */
    private const int KASISKI_MIN_VOTES = 2;

    /**
     * Создаёт экземпляр детектора.
     */
    public function __construct(
        private AlphabetCatalog $catalog,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function detect(IdentificationContext $ctx): ?CipherDetection
    {
        $alphabet    = $ctx->effectiveAlphabet();
        $letterCount = $ctx->letterCount($alphabet);
        if (!$ctx->hasReliableSample($alphabet)) {
            return null;
        }
        if ($ctx->letterRatio($alphabet) < 0.80) {
            return null;
        }

        $iocValue   = $ctx->iocFor($alphabet);
        $naturalIoc = IndexOfCoincidence::LANGUAGE_IOC[$alphabet] ?? 0.065;
        $randomIoc  = IndexOfCoincidence::RANDOM_IOC[$alphabet]   ?? 0.038;

        // IoC должен быть в полиалфавитной зоне между случайным и естественным.
        $lowerBound = $randomIoc + ($naturalIoc - $randomIoc) * 0.1;
        $upperBound = $naturalIoc - ($naturalIoc - $randomIoc) * 0.2;
        if ($iocValue < $lowerBound || $iocValue >= $upperBound) {
            return null;
        }

        // Центр полиалфавитной зоны соответствует «классическому» Vigenère
        // с длиной ключа 5-7. Чем ближе IoC к центру, тем выше уверенность.
        $center     = ($lowerBound + $upperBound) / 2.0;
        $halfRange  = ($upperBound - $lowerBound) / 2.0;
        $centerDist = abs($iocValue - $center) / max($halfRange, 1e-6);
        $confidence = 0.55 + 0.25 * (1.0 - min(1.0, $centerDist));

        $evidence = ['CID_EV_CHARSET_LETTERS', 'CID_EV_IOC_POLY', 'CID_EV_AMBIGUOUS_POLYALPHA'];
        $hints    = [];

        $letterIndices = $this->extractLetterIndices($ctx->text, $alphabet);
        $maxKeyLen     = (int) min(self::MAX_KEY_LENGTH, intdiv(count($letterIndices), self::MIN_LETTERS_PER_COLUMN));

        if ($maxKeyLen >= 2) {
            $bestKeyLen = 0;
            $bestIc     = 0.0;
            for ($len = 2; $len <= $maxKeyLen; $len++) {
                $ic = $this->avgIcByColumns($letterIndices, $len);
                if ($ic > $bestIc) {
                    $bestIc     = $ic;
                    $bestKeyLen = $len;
                }
            }

            if ($bestKeyLen > 0 && $bestIc >= $naturalIoc * self::COLUMN_IOC_PEAK_RATIO) {
                $confidence                  = min(self::CEILING_WITH_PEAK, $confidence + self::COLUMN_PEAK_BONUS);
                $hints['key_length_estimate'] = $bestKeyLen;
                $hints['ioc_at_key_length']   = round($bestIc, 4);
                $evidence[]                   = 'CID_EV_IOC_COLUMNS_PEAK';

                $votes = $this->kasiskiVotes($letterIndices);
                if (($votes[$bestKeyLen] ?? 0) >= self::KASISKI_MIN_VOTES) {
                    $confidence            = min(self::CEILING_WITH_KASISKI, $confidence + self::KASISKI_BONUS);
                    $hints['kasiski_votes'] = $votes[$bestKeyLen];
                    $evidence[]             = 'CID_EV_KASISKI_AGREE';
                }
            }
        }

        return new CipherDetection(
            toolSlug: 'classical-ciphers/vigenere',
            cipherKey: 'CIPHER_NAME_VIGENERE',
            confidence: min(self::CEILING_WITH_KASISKI, $confidence),
            evidenceKeys: $evidence,
            bruteForceAction: 'vigenere-cracker',
            detectedAlphabet: $alphabet,
            hints: $hints,
        );
    }

    /**
     * Извлекает индексы букв алфавита из текста за один проход.
     *
     * @return int[]
     */
    private function extractLetterIndices(string $text, string $alphabet): array
    {
        $alphabetData = $this->catalog->alphabet($alphabet);
        $indexMap     = array_flip($alphabetData);
        $result       = [];
        $length       = mb_strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_strtolower(mb_substr($text, $i, 1));
            if (isset($indexMap[$char])) {
                $result[] = $indexMap[$char];
            }
        }

        return $result;
    }

    /**
     * Вычисляет средний индекс совпадений (IoC) по всем колонкам при делении
     * текста на $keyLen полос.
     *
     * @param int[] $letterIndices
     */
    private function avgIcByColumns(array $letterIndices, int $keyLen): float
    {
        $n         = count($letterIndices);
        $totalIc   = 0.0;
        $columns   = 0;

        for ($pos = 0; $pos < $keyLen; $pos++) {
            $counts = [];
            $colLen = 0;
            for ($i = $pos; $i < $n; $i += $keyLen) {
                $counts[$letterIndices[$i]] = ($counts[$letterIndices[$i]] ?? 0) + 1;
                $colLen++;
            }
            if ($colLen < 2) {
                continue;
            }

            $sum = 0.0;
            foreach ($counts as $c) {
                $sum += $c * ($c - 1);
            }
            $totalIc += $sum / ($colLen * ($colLen - 1));
            $columns++;
        }

        return $columns > 0 ? $totalIc / $columns : 0.0;
    }

    /**
     * Голосование Касиски: для всех повторяющихся триграмм собирает расстояния
     * между вхождениями и подсчитывает, сколько расстояний делится на каждое
     * число 2..MAX_KEY_LENGTH. Возвращает карту keyLen => votes.
     *
     * @param  int[] $letterIndices
     * @return array<int, int>
     */
    private function kasiskiVotes(array $letterIndices): array
    {
        $tris      = [];
        $distances = [];
        $n         = count($letterIndices) - 2;
        for ($i = 0; $i < $n; $i++) {
            $key = ($letterIndices[$i] << 16) | ($letterIndices[$i + 1] << 8) | $letterIndices[$i + 2];
            if (isset($tris[$key])) {
                $distances[] = $i - $tris[$key];
            } else {
                $tris[$key] = $i;
            }
        }

        if (count($distances) < self::KASISKI_MIN_VOTES) {
            return [];
        }

        $votes = [];
        foreach ($distances as $d) {
            for ($k = 2; $k <= self::MAX_KEY_LENGTH; $k++) {
                if ($d % $k === 0) {
                    $votes[$k] = ($votes[$k] ?? 0) + 1;
                }
            }
        }

        return $votes;
    }
}
