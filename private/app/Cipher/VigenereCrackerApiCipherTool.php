<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Cache\CacheInterface;
use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент автоматического взлома шифра Виженера.
 *
 * Гибридный алгоритм: метод индекса совпадений (IC) даёт начальные оценки длины
 * ключа; для каждой длины стартовый ключ подбирается частотным анализом χ² по
 * колонкам, после чего уточняется hill climbing'ом с биграммным log-likelihood
 * скорингом на всём расшифрованном тексте. Биграммный скоринг надёжнее χ² на
 * коротких текстах: правильный ключ отчётливо выделяется даже на 100 буквах.
 */
final readonly class VigenereCrackerApiCipherTool implements ApiCipherToolInterface
{
    /** Максимально проверяемая длина ключа. */
    private const int MAX_KEY_LENGTH = 20;

    /** Максимальное количество кандидатов в ответе. */
    private const int MAX_CANDIDATES = 5;

    /** Максимум итераций hill climbing на одну длину ключа. */
    private const int HILL_CLIMB_MAX_ITERATIONS = 20;

    /** Число рестартов hill climbing с возмущениями для выхода из локальных максимумов. */
    private const int HILL_CLIMB_RESTARTS = 2;

    /** Штраф к скору за каждую букву ключа: предпочитает короткие ключи при близких скорах. */
    private const float KEY_LENGTH_PENALTY = 0.015;

    /** TTL результата в кеше: повторные одинаковые запросы отдаются мгновенно в течение часа. */
    private const int CACHE_TTL = 3600;

    /**
     * Жёсткий потолок длины входного текста (в символах Unicode).
     *
     * Защищает от DoS: алгоритм имеет сложность O(text × keyLen × restarts × iterations),
     * и без потолка пользователь мог бы привязать FPM-воркер на минуты одним запросом.
     * Публичная, чтобы UI мог читать значение для maxlength и счётчика символов.
     */
    public const int MAX_TEXT_LENGTH = 3000;

    /**
     * Создаёт экземпляр инструмента взлома Виженера.
     */
    public function __construct(
        private VigenereCipherService   $vigenere,
        private LetterFrequencyScorer   $scorer,
        private AlphabetCatalog         $catalog,
        private BigramFrequencyScorer   $bigramScorer,
        private CacheInterface          $cache,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'vigenere-cracker';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text     = (string) ($payload['text'] ?? '');
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $alphabet = mb_strtolower(trim((string) ($settings['alphabet'] ?? 'auto')));
        $fixedKeyLength = $this->parseFixedKeyLength($settings['key_length'] ?? 'auto');

        $isAuto    = $alphabet === 'auto';
        $supported = $this->vigenere->supportedAlphabetCodes();

        $errors = [];
        if ($text === '') {
            $errors['text'][] = trans('VIGENERE_CRACK_ERR_TEXT_REQUIRED');
        } elseif (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            $errors['text'][] = trans('VIGENERE_CRACK_ERR_TEXT_TOO_LONG', [
                'limit' => (string) self::MAX_TEXT_LENGTH,
            ]);
        }
        if (!$isAuto && !in_array($alphabet, $supported, true)) {
            $errors['settings.alphabet'][] = trans('VIGENERE_CRACK_ERR_ALPHABET_UNSUPPORTED');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('VIGENERE_CRACK_ERR_INVALID'), ['errors' => $errors]);
        }

        $cacheKey = $this->buildCacheKey($text, $alphabet, $fixedKeyLength);

        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn (): array => $this->compute($text, $alphabet, $isAuto, $fixedKeyLength)
        );
    }

    /**
     * Формирует ключ кеша для результата взлома.
     *
     * Кешируем по сырому значению alphabet ('auto' и 'ru' дают разные результаты:
     * у auto в ответе ненулевой detected_alphabet). Локаль включаем, т.к. поле
     * warning переведено в текущей локали запроса.
     */
    private function buildCacheKey(string $text, string $alphabet, ?int $fixedKeyLength): string
    {
        $parts = [
            $text,
            $alphabet,
            $fixedKeyLength === null ? 'auto' : (string) $fixedKeyLength,
            locale(),
        ];

        return 'vigenere-cracker:v1:' . hash('sha256', implode('|', $parts));
    }

    /**
     * Выполняет тяжёлый расчёт взлома Виженера. Вынесен из {@see execute()},
     * чтобы оборачиваться кешем; никаких сайд-эффектов и зависимостей от
     * глобального состояния, кроме локали (учтена в ключе кеша).
     *
     * @return array<string, mixed>
     */
    private function compute(string $text, string $alphabet, bool $isAuto, ?int $fixedKeyLength): array
    {
        $detectedAlphabet = null;

        if ($isAuto) {
            $detectedAlphabet = $this->scorer->detectAlphabet($text);
            $alphabet         = $detectedAlphabet;
        }

        $alphabetData  = $this->catalog->alphabet($alphabet);
        $alphabetSize  = count($alphabetData);
        $indexMap      = array_flip($alphabetData);
        $letterIndices = $this->extractLetterIndices($text, $indexMap);
        $letterCount   = count($letterIndices);
        $reliable      = $letterCount >= $this->bigramScorer->minReliableLetterCount($alphabet);

        if ($letterCount < 2) {
            $warning = null;
            if (!$isAuto) {
                $actualAlphabet = $this->scorer->detectAlphabet($text);
                if ($actualAlphabet !== $alphabet) {
                    $warning = trans('VIGENERE_CRACK_WARN_LANG_MISMATCH');
                }
            }

            return [
                'ok'                => true,
                'key'               => '',
                'decrypted'         => $text,
                'fitness'           => 0,
                'key_length'        => 1,
                'candidates'        => [],
                'alphabet'          => $alphabet,
                'detected_alphabet' => $detectedAlphabet,
                'reliable'          => false,
                'warning'           => $warning,
            ];
        }

        // Минимум 10 букв на колонку — меньше ненадёжно для χ²-анализа.
        $autoMaxLen   = min(self::MAX_KEY_LENGTH, max(1, (int) floor($letterCount / 10)));
        $minKeyLen    = $fixedKeyLength ?? 1;
        $maxKeyLen    = $fixedKeyLength ?? $autoMaxLen;
        $useBigrams   = $this->bigramScorer->supports($alphabet);
        $rankMap      = $useBigrams
            ? $this->bigramScorer->buildIndexedWeightMap($alphabet, $indexMap, $alphabetSize)
            : [];

        // Массив строчных букв из исходного текста — нужен только findBestKey,
        // который использует χ²-скоринг через LetterFrequencyScorer (строковый API).
        $letters = [];
        foreach ($letterIndices as $idx) {
            $letters[] = $alphabetData[$idx];
        }

        $candidates = [];
        for ($len = $minKeyLen; $len <= $maxKeyLen; $len++) {
            $ic              = $this->computeAverageIcIndices($letterIndices, $len);
            $startKey        = $this->findBestKey($letters, $len, $alphabet);
            $startKeyIndices = $this->keyStringToIndices($startKey, $indexMap);

            if ($useBigrams) {
                $finalKeyIndices = $this->refineKeyByBigramsIndexed(
                    $letterIndices,
                    $startKeyIndices,
                    $alphabetSize,
                    $rankMap
                );

                // При фиксированной длине ключа не сворачиваем к делителям —
                // пользователь явно указал длину и не хочет её менять.
                if ($len > 1 && $fixedKeyLength === null) {
                    $finalKeyIndices = $this->collapseToBestDivisorIndexed(
                        $letterIndices,
                        $finalKeyIndices,
                        $alphabetSize,
                        $rankMap
                    );
                }
            } else {
                $finalKeyIndices = $startKeyIndices;
            }

            $finalKey  = $this->keyIndicesToString($finalKeyIndices, $alphabetData);
            $decrypted = $this->vigenere->process($text, $finalKey, $alphabet, 'decrypt');
            $score     = $useBigrams
                ? $this->scoreIndexed($letterIndices, $finalKeyIndices, $alphabetSize, $rankMap)
                : -$this->scorer->chiSquared($decrypted, $alphabet) * sqrt($len);

            $candidates[] = [
                'length' => count($finalKeyIndices),
                'ic'     => round($ic, 4),
                'key'    => mb_strtoupper($finalKey),
                'text'   => $decrypted,
                'score'  => $score,
            ];
        }

        $candidates = $this->dedupeCandidates($candidates);
        $candidates = $this->rankCandidates($candidates);
        $candidates = array_slice($candidates, 0, self::MAX_CANDIDATES);

        $fitness = $this->computeFitness(array_column($candidates, 'score'));
        foreach ($candidates as $i => &$c) {
            $c['fitness'] = $fitness[$i];
            unset($c['score']);
        }
        unset($c);

        $best = $candidates[0];

        $warning = null;
        if ($reliable && $best['length'] === 1 && $best['key'] === mb_strtoupper($alphabetData[0])) {
            $warning = trans('VIGENERE_CRACK_WARN_NOT_ENCRYPTED');
        }

        return [
            'ok'                => true,
            'key'               => $best['key'],
            'decrypted'         => $best['text'],
            'fitness'           => $best['fitness'],
            'key_length'        => $best['length'],
            'candidates'        => $candidates,
            'alphabet'          => $alphabet,
            'detected_alphabet' => $detectedAlphabet,
            'reliable'          => $reliable,
            'warning'           => $warning,
        ];
    }

    /**
     * Разбирает настройку фиксированной длины ключа: 'auto' или пусто → null,
     * целое число от 1 до MAX_KEY_LENGTH → это число, иначе null.
     */
    private function parseFixedKeyLength(mixed $raw): ?int
    {
        if (!is_scalar($raw)) {
            return null;
        }
        $value = trim((string) $raw);
        if ($value === '' || $value === 'auto') {
            return null;
        }
        if (!ctype_digit($value)) {
            return null;
        }
        $length = (int) $value;
        if ($length < 1 || $length > self::MAX_KEY_LENGTH) {
            return null;
        }

        return $length;
    }

    /**
     * Извлекает индексы букв алфавита из текста за один проход.
     *
     * @param  array<string, int> $indexMap Карта буква→индекс в алфавите.
     * @return int[]
     */
    private function extractLetterIndices(string $text, array $indexMap): array
    {
        $result = [];
        $length = mb_strlen($text);

        for ($i = 0; $i < $length; $i++) {
            $char = mb_strtolower(mb_substr($text, $i, 1));
            if (isset($indexMap[$char])) {
                $result[] = $indexMap[$char];
            }
        }

        return $result;
    }

    /**
     * Преобразует ключ-строку в массив индексов букв алфавита.
     *
     * @param  array<string, int> $indexMap
     * @return int[]
     */
    private function keyStringToIndices(string $key, array $indexMap): array
    {
        $result = [];
        $length = mb_strlen($key);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_strtolower(mb_substr($key, $i, 1));
            if (isset($indexMap[$char])) {
                $result[] = $indexMap[$char];
            }
        }

        return $result;
    }

    /**
     * Преобразует индексы ключа обратно в строку букв алфавита.
     *
     * @param  int[]    $keyIndices
     * @param  string[] $alphabetData
     */
    private function keyIndicesToString(array $keyIndices, array $alphabetData): string
    {
        $result = '';
        foreach ($keyIndices as $idx) {
            $result .= $alphabetData[$idx];
        }

        return $result;
    }

    /**
     * Вычисляет средний индекс совпадений (IC) по всем колонкам для данной длины ключа.
     *
     * @param int[] $letterIndices
     */
    private function computeAverageIcIndices(array $letterIndices, int $keyLen): float
    {
        $n       = count($letterIndices);
        $totalIc = 0.0;

        for ($pos = 0; $pos < $keyLen; $pos++) {
            $column = [];
            for ($i = $pos; $i < $n; $i += $keyLen) {
                $column[] = $letterIndices[$i];
            }

            $colLen = count($column);
            if ($colLen < 2) {
                continue;
            }

            $counts = array_count_values($column);
            $ic     = 0.0;
            foreach ($counts as $count) {
                $ic += $count * ($count - 1);
            }
            $totalIc += $ic / ($colLen * ($colLen - 1));
        }

        return $totalIc / $keyLen;
    }

    /**
     * Подбирает стартовый ключ заданной длины частотным анализом χ² по колонкам.
     *
     * @param string[] $letters
     */
    private function findBestKey(array $letters, int $keyLen, string $alphabet): string
    {
        $alphabetData = $this->catalog->alphabet($alphabet);
        $alphabetSize = count($alphabetData);
        $indexMap     = array_flip($alphabetData);
        $n            = count($letters);
        $key          = '';

        for ($pos = 0; $pos < $keyLen; $pos++) {
            $column = [];
            for ($i = $pos; $i < $n; $i += $keyLen) {
                $column[] = $letters[$i];
            }

            $bestShift = 0;
            $bestChi   = PHP_FLOAT_MAX;

            for ($shift = 0; $shift < $alphabetSize; $shift++) {
                $shifted = array_map(
                    static fn (string $c): string => $alphabetData[((int) $indexMap[$c] - $shift + $alphabetSize) % $alphabetSize],
                    $column
                );
                $chi = $this->scorer->chiSquared(implode('', $shifted), $alphabet);
                if ($chi < $bestChi) {
                    $bestChi   = $chi;
                    $bestShift = $shift;
                }
            }

            $key .= $alphabetData[$bestShift];
        }

        return $key;
    }

    /**
     * Уточняет ключ hill climbing'ом с рестартами от возмущённых вариантов.
     *
     * Делает первый прогон от стартового ключа, затем несколько рестартов от
     * случайно изменённой текущей версии — это помогает выйти из локальных
     * максимумов, где простой жадный поиск останавливается слишком рано.
     *
     * @param  int[]              $letterIndices
     * @param  int[]              $startKeyIndices
     * @param  array<int, float>  $rankMap
     * @return int[]
     */
    private function refineKeyByBigramsIndexed(array $letterIndices, array $startKeyIndices, int $alphabetSize, array $rankMap): array
    {
        if ($startKeyIndices === []) {
            return $startKeyIndices;
        }

        $bestKey   = $this->hillClimbIndexed($letterIndices, $startKeyIndices, $alphabetSize, $rankMap);
        $bestScore = $this->scoreIndexed($letterIndices, $bestKey, $alphabetSize, $rankMap);

        for ($r = 0; $r < self::HILL_CLIMB_RESTARTS; $r++) {
            $perturbed = $this->perturbKeyIndexed($bestKey, $alphabetSize, $r + 1);
            $candidate = $this->hillClimbIndexed($letterIndices, $perturbed, $alphabetSize, $rankMap);
            $score     = $this->scoreIndexed($letterIndices, $candidate, $alphabetSize, $rankMap);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestKey   = $candidate;
            }
        }

        return $bestKey;
    }

    /**
     * Одиночный hill climbing на int-индексах: для каждой позиции ключа
     * пробует все буквы алфавита и принимает замену, повышающую биграммный
     * скор. Итерируется до фиксации.
     *
     * @param  int[]             $letterIndices
     * @param  int[]             $startKeyIndices
     * @param  array<int, float> $rankMap
     * @return int[]
     */
    private function hillClimbIndexed(array $letterIndices, array $startKeyIndices, int $alphabetSize, array $rankMap): array
    {
        $keyLen       = count($startKeyIndices);
        $currentKey   = $startKeyIndices;
        $currentScore = $this->scoreIndexed($letterIndices, $currentKey, $alphabetSize, $rankMap);

        for ($iter = 0; $iter < self::HILL_CLIMB_MAX_ITERATIONS; $iter++) {
            $improved = false;
            for ($pos = 0; $pos < $keyLen; $pos++) {
                $origIdx   = $currentKey[$pos];
                $bestIdx   = $origIdx;
                $bestScore = $currentScore;

                for ($cand = 0; $cand < $alphabetSize; $cand++) {
                    // Воспроизводим оригинальный обход: пропускаем текущий
                    // лучший индекс (он обновляется по ходу цикла).
                    if ($cand === $bestIdx) {
                        continue;
                    }
                    $currentKey[$pos] = $cand;
                    $score = $this->scoreIndexed($letterIndices, $currentKey, $alphabetSize, $rankMap);

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestIdx   = $cand;
                    }
                }

                $currentKey[$pos] = $bestIdx;
                if ($bestIdx !== $origIdx) {
                    $currentScore = $bestScore;
                    $improved     = true;
                }
            }

            if (!$improved) {
                break;
            }
        }

        return $currentKey;
    }

    /**
     * Возмущает ключ детерминированной заменой нескольких индексов.
     * Использует хеш ключа как сид: возмущения воспроизводимы между запусками
     * и при этом отличаются между рестартами.
     *
     * @param  int[] $keyIndices
     * @return int[]
     */
    private function perturbKeyIndexed(array $keyIndices, int $alphabetSize, int $seed): array
    {
        $keyLen  = count($keyIndices);
        $changes = min($keyLen, max(1, intdiv($keyLen, 3)));
        $hash    = crc32(implode(',', $keyIndices) . '#' . $seed);

        for ($i = 0; $i < $changes; $i++) {
            $pos               = ($hash >> ($i * 4)) % $keyLen;
            $shift             = ($hash >> ($i * 4 + 16)) % ($alphabetSize - 1) + 1;
            $keyIndices[$pos]  = ($keyIndices[$pos] + $shift) % $alphabetSize;
        }

        return $keyIndices;
    }

    /**
     * Пробует сжать ключ длины L до делителя M < L: использует первые M
     * индексов как стартовый ключ, прогоняет hill climbing и сравнивает
     * биграммный скор. Возвращает короткий ключ, если он не хуже исходного —
     * это устраняет почти-периодические кандидаты вроде «секретсекреи» в
     * пользу настоящего периода «секрет».
     *
     * @param  int[]             $letterIndices
     * @param  int[]             $keyIndices
     * @param  array<int, float> $rankMap
     * @return int[]
     */
    private function collapseToBestDivisorIndexed(array $letterIndices, array $keyIndices, int $alphabetSize, array $rankMap): array
    {
        $keyLen = count($keyIndices);
        if ($keyLen < 2) {
            return $keyIndices;
        }

        $bestKey   = $keyIndices;
        $bestScore = $this->scoreIndexed($letterIndices, $bestKey, $alphabetSize, $rankMap);

        for ($period = 1; $period < $keyLen; $period++) {
            if ($keyLen % $period !== 0) {
                continue;
            }
            $startBlock = array_slice($keyIndices, 0, $period);
            $refined    = $this->refineKeyByBigramsIndexed($letterIndices, $startBlock, $alphabetSize, $rankMap);
            $score      = $this->scoreIndexed($letterIndices, $refined, $alphabetSize, $rankMap);

            if ($score >= $bestScore - 1e-9) {
                $bestScore = $score;
                $bestKey   = $refined;
                break;
            }
        }

        return $bestKey;
    }

    /**
     * Биграммный скоринг на int-индексах: совмещает дешифровку и подсчёт
     * скора в одном цикле, избегая mb_substr и пересоздания строк.
     *
     * Численно идентичен {@see BigramFrequencyScorer::score()}.
     *
     * @param int[]             $letterIndices
     * @param int[]             $keyIndices
     * @param array<int, float> $rankMap Карта prevIdx*size+curIdx → вес.
     */
    private function scoreIndexed(array $letterIndices, array $keyIndices, int $alphabetSize, array $rankMap): float
    {
        $keyLen = count($keyIndices);
        $n      = count($letterIndices);
        if ($n < 2 || $keyLen === 0 || $rankMap === []) {
            return 0.0;
        }

        $penalty = BigramFrequencyScorer::UNKNOWN_BIGRAM_PENALTY;
        $prev    = ($letterIndices[0] - $keyIndices[0] + $alphabetSize) % $alphabetSize;
        $total   = 0.0;
        $pairs   = $n - 1;

        for ($i = 1; $i < $n; $i++) {
            $cur = ($letterIndices[$i] - $keyIndices[$i % $keyLen] + $alphabetSize) % $alphabetSize;
            $key = $prev * $alphabetSize + $cur;
            $total += $rankMap[$key] ?? $penalty;
            $prev   = $cur;
        }

        return $total / $pairs;
    }

    /**
     * Убирает дублирующиеся кандидаты по паре длина+ключ, оставляя лучший скор.
     *
     * @param  array<int, array<string, mixed>> $candidates
     * @return array<int, array<string, mixed>>
     */
    private function dedupeCandidates(array $candidates): array
    {
        $deduped = [];
        foreach ($candidates as $c) {
            $key = $c['length'] . ':' . $c['key'];
            if (!isset($deduped[$key]) || $deduped[$key]['score'] < $c['score']) {
                $deduped[$key] = $c;
            }
        }

        return array_values($deduped);
    }

    /**
     * Ранжирует кандидатов по скорректированному скору score - penalty × length.
     * Штраф за длину устраняет «удачные» длинные ключи, у которых сырой
     * биграммный скор случайно завышен подгонкой шума: при близких скорах
     * выигрывает более короткий (истинный) ключ.
     *
     * @param  array<int, array<string, mixed>> $candidates
     * @return array<int, array<string, mixed>>
     */
    private function rankCandidates(array $candidates): array
    {
        usort(
            $candidates,
            static function (array $a, array $b): int {
                $adjA = $a['score'] - self::KEY_LENGTH_PENALTY * $a['length'];
                $adjB = $b['score'] - self::KEY_LENGTH_PENALTY * $b['length'];

                if (abs($adjA - $adjB) < 1e-9) {
                    return $a['length'] <=> $b['length'];
                }

                return $adjB <=> $adjA;
            }
        );

        return $candidates;
    }

    /**
     * Преобразует биграммные скоры в оценки пригодности 0..100.
     * Лучший (максимальный) скор → 100, остальные пропорционально меньше.
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
}
