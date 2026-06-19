<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Cache\CacheInterface;
use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент перебора всех допустимых ключей аффинного шифра (brute force).
 *
 * Аффинный шифр: E(x) = (a·x + b) mod m, где gcd(a, m) = 1.
 * Перебираются все допустимые пары (a, b) для выбранного алфавита размера m.
 *
 * Алгоритм оптимизирован: текст разбирается на utf-8 символы один раз, χ² для
 * каждой пары считается перестановкой гистограммы шифртекста (O(m)), без
 * посимвольного обхода. Топ-K кандидатов дополнительно ранжируется биграммным
 * лог-правдоподобием по int-индексам — это резко улучшает выбор истинного
 * ключа на коротких текстах, где моноварианты χ² путают шумовые совпадения.
 */
final readonly class AffineBruteForceApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Жёсткий потолок длины входного текста (в символах Unicode).
     *
     * Защищает от DoS: даже после оптимизации алгоритм выполняет полную
     * дешифровку каждой из 312-660 пар ключей для выдачи в UI, что
     * пропорционально длине текста.
     */
    public const int MAX_TEXT_LENGTH = 5000;

    /** Сколько кандидатов отдаётся клиенту. UX: подробно показываем только победителей. */
    private const int MAX_CANDIDATES = 10;

    /** TTL результата в кеше: повторные одинаковые запросы отдаются мгновенно в течение часа. */
    private const int CACHE_TTL = 3600;

    /**
     * Создаёт экземпляр API-инструмента перебора ключей аффинного шифра.
     */
    public function __construct(
        private AffineCipherService $cipher,
        private LetterFrequencyScorer $scorer,
        private AlphabetCatalog $catalog,
        private BigramFrequencyScorer $bigramScorer,
        private CacheInterface $cache,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'affine-brute-force';
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
            $errors['text'][] = trans('AFFINE_ERR_TEXT_REQUIRED');
        } elseif (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            $errors['text'][] = trans('AFFINE_BRUTE_ERR_TEXT_TOO_LONG', [
                'limit' => (string) self::MAX_TEXT_LENGTH,
            ]);
        }
        if (!$isAuto && !in_array($alphabet, $supported, true)) {
            $errors['settings.alphabet'][] = trans('CAESAR_ERR_ALPHABET_UNSUPPORTED');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('AFFINE_ERR_INVALID'), ['errors' => $errors]);
        }

        $cacheKey = $this->buildCacheKey($text, $alphabet);

        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn (): array => $this->compute($text, $alphabet, $isAuto)
        );
    }

    /**
     * Формирует ключ кеша. Локаль учитывается, потому что сам ответ напрямую не
     * содержит переведённых строк, но при ошибках валидации шапка ответа
     * чувствительна к языку.
     */
    private function buildCacheKey(string $text, string $alphabet): string
    {
        $parts = [$text, $alphabet, locale()];

        return 'affine-brute-force:v1:' . hash('sha256', implode('|', $parts));
    }

    /**
     * Тяжёлый расчёт. Вынесен из {@see execute()} ради оборачивания кешем.
     *
     * @return array<string, mixed>
     */
    private function compute(string $text, string $alphabet, bool $isAuto): array
    {
        $detectedAlphabet = null;

        if ($isAuto) {
            $detectedAlphabet = $this->scorer->detectAlphabet($text);
            $alphabet         = $detectedAlphabet;
        } elseif ($this->scorer->countLetters($text, $alphabet) === 0) {
            $detectedAlphabet = $this->scorer->detectAlphabet($text);
            $alphabet         = $detectedAlphabet;
        }

        $alphabetData      = $this->catalog->alphabet($alphabet);
        $alphabetSize      = count($alphabetData);
        $upperAlphabetData = array_map('mb_strtoupper', $alphabetData);
        $indexMap          = array_flip($alphabetData);
        $expectedByIndex   = $this->scorer->expectedFrequencyVector($alphabet, $alphabetData);

        // Один проход по тексту: разбираем на utf-8 символы, фиксируем позиции
        // букв алфавита и собираем гистограмму. mb_substr в цикле — O(text²);
        // preg_split даёт массив за O(text).
        $chars         = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $positions     = [];
        $letterIndices = [];
        $isUpper       = [];
        $counts        = array_fill(0, $alphabetSize, 0);
        $totalCounted  = 0;

        foreach ($chars as $charIdx => $char) {
            $lower = mb_strtolower($char);
            if (!isset($indexMap[$lower])) {
                continue;
            }
            $idx             = $indexMap[$lower];
            $positions[]     = $charIdx;
            $letterIndices[] = $idx;
            $isUpper[]       = $char !== $lower;
            if ($expectedByIndex[$idx] > 0.0) {
                $counts[$idx]++;
                $totalCounted++;
            }
        }

        $letterCount = count($letterIndices);
        $reliable    = $letterCount >= LetterFrequencyScorer::MIN_LETTERS_FOR_RELIABLE_SCORING;

        $useBigrams = $this->bigramScorer->supports($alphabet) && $letterCount >= 2;
        $rankMap    = $useBigrams
            ? $this->bigramScorer->buildIndexedWeightMap($alphabet, $indexMap, $alphabetSize)
            : [];

        // Для каждой допустимой пары (a, b) считаем оба сигнала:
        // χ² по гистограмме — O(m) на пару, для tiebreak и для fitness, когда
        // биграммы недоступны. Биграммный лог-правдоподобный скор — O(text) на
        // пару, основной сигнал отбора. Прежняя версия ограничивала биграммы
        // top-K по χ², но на коротких русских текстах χ² настолько шумен, что
        // правильный ключ оказывается в 50–60-м месте и отрезается. Пространство
        // ключей маленькое (250–660 пар), поэтому считаем биграммы для всех.
        $candidates = [];
        for ($a = 1; $a < $alphabetSize; $a++) {
            if (!$this->cipher->isValidMultiplier($a, $alphabet)) {
                continue;
            }
            for ($b = 0; $b < $alphabetSize; $b++) {
                $chi   = $this->chiFromHistogram($counts, $expectedByIndex, $totalCounted, $a, $b, $alphabetSize);
                $entry = ['a' => $a, 'b' => $b, 'chi' => $chi];
                if ($useBigrams) {
                    $entry['bigram'] = $this->bigramScoreForKey($letterIndices, $a, $b, $alphabetSize, $rankMap);
                }
                $candidates[] = $entry;
            }
        }

        if ($useBigrams) {
            usort(
                $candidates,
                static function (array $x, array $y): int {
                    if ($x['bigram'] === $y['bigram']) {
                        return $x['chi'] <=> $y['chi'];
                    }

                    return $y['bigram'] <=> $x['bigram'];
                }
            );
        } else {
            usort($candidates, static fn (array $x, array $y): int => $x['chi'] <=> $y['chi']);
        }

        // Отдаём только лучших — UX как у vigenere-cracker: подробно показываем
        // победителей, а не всё пространство ключей. Полная дешифровка делается
        // только для них (fast-path без mb_substr).
        $finalists = array_slice($candidates, 0, self::MAX_CANDIDATES);

        $bestA = $finalists[0]['a'];
        $bestB = $finalists[0]['b'];

        // Fitness по тому же сигналу, по которому ранжировали. Это даёт
        // монотонный fitness-бар в UI: row #1 = 100%, последующие меньше.
        $scoreValues = $useBigrams
            ? array_column($finalists, 'bigram')
            : array_map(static fn (float $c): float => -$c, array_column($finalists, 'chi'));
        $fitnesses   = $this->computeFitness($scoreValues);

        $results = [];
        foreach ($finalists as $i => $cand) {
            $inverseA  = $this->modInverse($cand['a'], $alphabetSize);
            $decrypted = $this->decryptFast(
                $chars,
                $positions,
                $letterIndices,
                $isUpper,
                $alphabetData,
                $upperAlphabetData,
                $alphabetSize,
                $inverseA,
                $cand['b']
            );
            $results[] = [
                'multiplier' => $cand['a'],
                'shift'      => $cand['b'],
                'text'       => $decrypted,
                'fitness'    => $fitnesses[$i],
            ];
        }

        $best = $results[0];

        return [
            'ok'                => true,
            'key'               => sprintf('a=%d, b=%d', $bestA, $bestB),
            'decrypted'         => $best['text'],
            'fitness'           => $best['fitness'],
            'results'           => $results,
            'alphabet'          => $alphabet,
            'detected_alphabet' => $detectedAlphabet,
            'best_multiplier'   => $bestA,
            'best_shift'        => $bestB,
            'reliable'          => $reliable,
        ];
    }

    /**
     * χ² для пары (a, b) по гистограмме шифртекста.
     *
     * Воспользуемся тождеством: после расшифровки буква x встречается столько
     * же раз, сколько в шифртексте встречается её зашифрованная форма
     * E(x) = (a·x + b) mod m. Поэтому достаточно один раз снять гистограмму
     * шифртекста, а χ² для каждой пары — это O(m) перестановка без обхода
     * текста и без modInverse.
     *
     * @param int[]   $counts          Гистограмма шифртекста по индексам алфавита.
     * @param float[] $expectedByIndex Ожидаемые доли (0..1) по индексам алфавита.
     */
    private function chiFromHistogram(array $counts, array $expectedByIndex, int $total, int $a, int $b, int $alphabetSize): float
    {
        if ($total === 0) {
            return 1.0e9;
        }

        $chi = 0.0;
        for ($x = 0; $x < $alphabetSize; $x++) {
            $expected = $expectedByIndex[$x];
            if ($expected <= 0.0) {
                continue;
            }
            $y        = ($a * $x + $b) % $alphabetSize;
            $observed = $counts[$y] / $total;
            $diff     = $observed - $expected;
            $chi     += ($diff * $diff) / $expected;
        }

        return $chi;
    }

    /**
     * Биграммный скор расшифровки на int-индексах. Дешевле полной дешифровки:
     * строит таблицу-перестановку D(y) = a⁻¹·(y − b) и идёт одним проходом
     * по индексам шифртекста.
     *
     * @param  int[]             $letterIndices Индексы букв шифртекста.
     * @param  array<int, float> $rankMap       Карта prev·size+cur → вес.
     */
    private function bigramScoreForKey(array $letterIndices, int $a, int $b, int $alphabetSize, array $rankMap): float
    {
        $n = count($letterIndices);
        if ($n < 2 || $rankMap === []) {
            return 0.0;
        }

        $inverseA = $this->modInverse($a, $alphabetSize);
        $decTable = [];
        for ($i = 0; $i < $alphabetSize; $i++) {
            $decTable[$i] = ($inverseA * ($i - $b + $alphabetSize)) % $alphabetSize;
        }

        $penalty = BigramFrequencyScorer::UNKNOWN_BIGRAM_PENALTY;
        $prev    = $decTable[$letterIndices[0]];
        $total   = 0.0;
        for ($i = 1; $i < $n; $i++) {
            $cur    = $decTable[$letterIndices[$i]];
            $key    = $prev * $alphabetSize + $cur;
            $total += $rankMap[$key] ?? $penalty;
            $prev   = $cur;
        }

        return $total / ($n - 1);
    }

    /**
     * Быстрая дешифровка по предвычисленным позициям букв.
     *
     * Стандартный {@see AffineCipherService::process()} на каждой итерации
     * делает mb_substr ($i, 1), что в PHP даёт суммарное O(text²). Здесь же
     * mb_strtolower вызван один раз на разборе, а сам проход — O(text) с
     * константой 1 индексация массива.
     *
     * @param string[] $chars             Исходный текст, разобранный на utf-8 символы.
     * @param int[]    $positions         Индексы букв в массиве $chars.
     * @param int[]    $letterIndices     Индексы букв в алфавите (cipher).
     * @param bool[]   $isUpper           Флаги верхнего регистра, выровнены с $positions.
     * @param string[] $alphabetData      Алфавит в нижнем регистре.
     * @param string[] $upperAlphabetData Алфавит в верхнем регистре.
     */
    private function decryptFast(
        array $chars,
        array $positions,
        array $letterIndices,
        array $isUpper,
        array $alphabetData,
        array $upperAlphabetData,
        int $alphabetSize,
        int $inverseA,
        int $b
    ): string {
        foreach ($positions as $k => $pos) {
            $newIdx       = ($inverseA * ($letterIndices[$k] - $b + $alphabetSize)) % $alphabetSize;
            $chars[$pos]  = $isUpper[$k] ? $upperAlphabetData[$newIdx] : $alphabetData[$newIdx];
        }

        return implode('', $chars);
    }

    /**
     * Линейная нормировка скора к 0..100. Самый сильный → 100, самый слабый → 0.
     *
     * @param  float[] $scores
     * @return int[]
     */
    private function computeFitness(array $scores): array
    {
        if ($scores === []) {
            return [];
        }
        $max = max($scores);
        $min = min($scores);
        if ($max - $min < 1e-9) {
            return array_fill(0, count($scores), 100);
        }

        return array_map(
            static fn (float $s): int => (int) round(100 * ($s - $min) / ($max - $min)),
            $scores
        );
    }

    /**
     * Модульная обратная величина через расширенный алгоритм Евклида.
     *
     * Замена линейного поиска ({@see AffineCipherService::modInverse()}): O(log m)
     * вместо O(m). Вызывается дважды на каждый отдаваемый кандидат — мелочь
     * сама по себе, но избавляет от 600+ линейных проходов на запрос.
     */
    private function modInverse(int $a, int $m): int
    {
        $a  = (($a % $m) + $m) % $m;
        $g0 = $m;
        $g1 = $a;
        $s0 = 0;
        $s1 = 1;
        while ($g1 !== 0) {
            $q  = intdiv($g0, $g1);
            [$g0, $g1] = [$g1, $g0 - $q * $g1];
            [$s0, $s1] = [$s1, $s0 - $q * $s1];
        }

        return $g0 === 1 ? (($s0 % $m) + $m) % $m : 0;
    }
}
