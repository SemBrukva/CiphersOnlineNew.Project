<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Вычисляет Индекс совпадений (IoC / IC) текста по заданному алфавиту.
 *
 * IoC показывает вероятность того, что два случайно выбранных символа одинаковы.
 * Для моноалфавитных шифров IoC близок к IoC исходного языка,
 * для полиалфавитных — стремится к случайному (≈ 1/|A|).
 */
final readonly class IndexOfCoincidence
{
    /**
     * Эталонные IoC для естественных языков (буквенные алфавиты).
     *
     * @var array<string, float>
     */
    public const array LANGUAGE_IOC = [
        'en' => 0.0667,
        'ru' => 0.0572,
        'de' => 0.0762,
        'fr' => 0.0778,
        'es' => 0.0770,
        'it' => 0.0738,
        'pt' => 0.0745,
        'tr' => 0.0665,
    ];

    /**
     * IoC случайного текста (≈ 1/|A|) для алфавитов разных языков.
     *
     * @var array<string, float>
     */
    public const array RANDOM_IOC = [
        'en' => 0.0385,
        'ru' => 0.0303,
        'de' => 0.0385,
        'fr' => 0.0385,
        'es' => 0.0385,
        'it' => 0.0385,
        'pt' => 0.0313,
        'tr' => 0.0345,
    ];

    /**
     * Вычисляет IoC текста по буквам заданного алфавита.
     *
     * @param  string $text     Входной текст (любой регистр).
     * @param  string $alphabet Код языка ('en', 'ru', ...).
     */
    public function compute(string $text, string $alphabet): float
    {
        $scorer = new LetterFrequencyScorer();
        $freq   = [];
        $n      = 0;
        $len    = mb_strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $c = mb_strtolower(mb_substr($text, $i, 1));
            if ($scorer->countLetters($c, $alphabet) > 0) {
                $freq[$c] = ($freq[$c] ?? 0) + 1;
                $n++;
            }
        }

        if ($n < 2) {
            return 0.0;
        }

        $numerator = 0.0;
        foreach ($freq as $count) {
            $numerator += $count * ($count - 1);
        }

        return $numerator / ($n * ($n - 1));
    }
}
