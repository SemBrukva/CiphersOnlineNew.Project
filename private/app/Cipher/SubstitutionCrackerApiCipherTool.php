<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Cache\CacheInterface;
use App\Http\Exception\ValidationFailedException;

/**
 * API-инструмент автоматического взлома шифра простой замены (моноалфавитного).
 *
 * Алгоритм: стартовый ключ подбирается частотным анализом (самая частая буква
 * шифртекста → самая частая буква языка), затем уточняется hill climbing'ом —
 * пробуются все обмены пар букв ключа, принимаются улучшающие триграммный/
 * биграммный fitness расшифровки. Несколько рестартов с возмущениями выводят
 * из локальных максимумов. Триграммы обязательны для en/ru (различают ключ на
 * ~80+ буквах); для прочих алфавитов используется биграммный fitness.
 */
final readonly class SubstitutionCrackerApiCipherTool implements ApiCipherToolInterface
{
    /**
     * Жёсткий потолок длины входа (в символах Unicode). Защита от DoS:
     * hill climbing — O(restarts × passes × 26² × text).
     */
    public const int MAX_TEXT_LENGTH = 5000;

    /** Минимум букв алфавита для попытки взлома (ниже — статистика нестабильна). */
    private const int MIN_LETTERS = 40;

    /** Число рестартов hill climbing со случайных ключей. */
    private const int RESTARTS = 25;

    /** Максимум проходов внутри одного hill climbing (защита от зацикливания). */
    private const int MAX_PASSES = 200;

    /** TTL кеша результата. */
    private const int CACHE_TTL = 3600;

    /**
     * Создаёт экземпляр инструмента взлома простой замены.
     */
    public function __construct(
        private SimpleSubstitutionCipherService $cipher,
        private LetterFrequencyScorer $scorer,
        private AlphabetCatalog $catalog,
        private BigramFrequencyScorer $bigramScorer,
        private TrigramFrequencyScorer $trigramScorer,
        private CacheInterface $cache,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'substitution-cracker';
    }

    /**
     * {@inheritDoc}
     */
    public function execute(array $payload): array
    {
        $text     = (string) ($payload['text'] ?? '');
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $alphabet = mb_strtolower(trim((string) ($settings['alphabet'] ?? 'auto')));

        $errors = [];
        if ($text === '') {
            $errors['text'][] = trans('SUBSTITUTION_CRACK_ERR_TEXT_REQUIRED');
        } elseif (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            $errors['text'][] = trans('SUBSTITUTION_CRACK_ERR_TEXT_TOO_LONG', ['limit' => (string) self::MAX_TEXT_LENGTH]);
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('SUBSTITUTION_CRACK_ERR_INVALID'), ['errors' => $errors]);
        }

        $cacheKey = 'substitution-cracker:v1:' . hash('sha256', $text . '|' . $alphabet . '|' . locale());

        return $this->cache->remember($cacheKey, self::CACHE_TTL, fn (): array => $this->compute($text, $alphabet));
    }

    /**
     * Выполняет тяжёлый расчёт взлома. Вынесен из {@see execute()} ради кеша.
     *
     * @return array<string, mixed>
     */
    private function compute(string $text, string $alphabet): array
    {
        $detectedAlphabet = null;
        if ($alphabet === 'auto') {
            $detectedAlphabet = $this->scorer->detectAlphabet($text);
            $alphabet         = $detectedAlphabet;
        }

        $alphabetData = $this->catalog->alphabet($alphabet);
        $size         = count($alphabetData);
        $indexMap     = array_flip($alphabetData);

        $cipherIndices = $this->extractLetterIndices($text, $indexMap);
        $letterCount   = count($cipherIndices);

        $useTrigram = $this->trigramScorer->supports($alphabet);
        $useBigram  = $this->bigramScorer->supports($alphabet);

        if ($letterCount < self::MIN_LETTERS || !$useBigram) {
            return [
                'ok'                => true,
                'key'               => '',
                'decrypted'         => $text,
                'alphabet'          => $alphabet,
                'detected_alphabet' => $detectedAlphabet,
                'reliable'          => false,
            ];
        }

        $bigramMap  = $this->bigramScorer->buildIndexedWeightMap($alphabet, $indexMap, $size);
        $trigramMap = $useTrigram ? $this->trigramScorer->buildIndexedWeightMap($alphabet, $indexMap, $size) : [];

        // Стартовый ключ по частотам + hill climbing, затем рестарты со случайных
        // ключей. Полный shuffle на рестарте выводит из локальных максимумов
        // радикально лучше локальных возмущений. Сид детерминирован — результат
        // воспроизводим для одного входа.
        mt_srand(crc32($text));
        $startKey  = $this->frequencyKey($cipherIndices, $alphabet, $alphabetData, $size);
        $bestKey   = $this->hillClimb($cipherIndices, $startKey, $size, $bigramMap, $trigramMap, $useTrigram);
        $bestScore = $this->scoreKey($cipherIndices, $bestKey, $size, $bigramMap, $trigramMap, $useTrigram);

        for ($r = 0; $r < self::RESTARTS; $r++) {
            $randomKey = range(0, $size - 1);
            shuffle($randomKey);
            $candidate = $this->hillClimb($cipherIndices, $randomKey, $size, $bigramMap, $trigramMap, $useTrigram);
            $score     = $this->scoreKey($cipherIndices, $candidate, $size, $bigramMap, $trigramMap, $useTrigram);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestKey   = $candidate;
            }
        }

        $keyString = $this->keyString($bestKey, $alphabetData, $size);
        $decrypted = $this->cipher->process($text, $alphabet, $keyString, 'decrypt');
        $reliable  = $letterCount >= self::MIN_LETTERS * 2;

        return [
            'ok'                => true,
            'key'               => $keyString,
            'decrypted'         => $decrypted,
            'alphabet'          => $alphabet,
            'detected_alphabet' => $detectedAlphabet,
            'reliable'          => $reliable,
        ];
    }

    /**
     * Извлекает индексы букв алфавита из текста в порядке следования.
     *
     * @param  array<string, int> $indexMap
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
     * Строит стартовый ключ дешифровки по частотам: k-я по частоте буква
     * шифртекста отображается в k-ю по частоте букву языка.
     *
     * @param  int[]    $cipherIndices
     * @param  string[] $alphabetData
     * @return int[]                    key[cipherIdx] = plainIdx.
     */
    private function frequencyKey(array $cipherIndices, string $alphabet, array $alphabetData, int $size): array
    {
        $counts = array_fill(0, $size, 0);
        foreach ($cipherIndices as $idx) {
            $counts[$idx]++;
        }

        // Индексы шифр-букв по убыванию частоты (стабильно по индексу при равенстве).
        $cipherOrder = range(0, $size - 1);
        usort($cipherOrder, static fn (int $a, int $b): int => ($counts[$b] <=> $counts[$a]) ?: ($a <=> $b));

        // Индексы букв языка по убыванию ожидаемой частоты.
        $expected   = $this->scorer->expectedFrequencyVector($alphabet, $alphabetData);
        $plainOrder = range(0, $size - 1);
        usort($plainOrder, static fn (int $a, int $b): int => ($expected[$b] <=> $expected[$a]) ?: ($a <=> $b));

        $key = array_fill(0, $size, 0);
        for ($i = 0; $i < $size; $i++) {
            $key[$cipherOrder[$i]] = $plainOrder[$i];
        }

        return $key;
    }

    /**
     * Hill climbing: пока есть улучшение, пробует все обмены пар в ключе и
     * принимает те, что повышают fitness расшифровки.
     *
     * @param  int[]             $cipherIndices
     * @param  int[]             $startKey
     * @param  array<int, float> $bigramMap
     * @param  array<int, float> $trigramMap
     * @return int[]
     */
    private function hillClimb(array $cipherIndices, array $startKey, int $size, array $bigramMap, array $trigramMap, bool $useTrigram): array
    {
        $key   = $startKey;
        $score = $this->scoreKey($cipherIndices, $key, $size, $bigramMap, $trigramMap, $useTrigram);

        for ($pass = 0; $pass < self::MAX_PASSES; $pass++) {
            $improved = false;
            for ($a = 0; $a < $size - 1; $a++) {
                for ($b = $a + 1; $b < $size; $b++) {
                    [$key[$a], $key[$b]] = [$key[$b], $key[$a]];
                    $newScore = $this->scoreKey($cipherIndices, $key, $size, $bigramMap, $trigramMap, $useTrigram);
                    if ($newScore > $score) {
                        $score    = $newScore;
                        $improved = true;
                    } else {
                        [$key[$a], $key[$b]] = [$key[$b], $key[$a]];
                    }
                }
            }
            if (!$improved) {
                break;
            }
        }

        return $key;
    }

    /**
     * Оценивает ключ: расшифровывает индексы «на лету» и суммирует биграммное
     * (+ триграммное) лог-правдоподобие за один проход.
     *
     * @param int[]             $cipherIndices
     * @param int[]             $key           key[cipherIdx] = plainIdx.
     * @param array<int, float> $bigramMap
     * @param array<int, float> $trigramMap
     */
    private function scoreKey(array $cipherIndices, array $key, int $size, array $bigramMap, array $trigramMap, bool $useTrigram): float
    {
        $n = count($cipherIndices);
        if ($n < 3) {
            return 0.0;
        }

        $biPenalty = BigramFrequencyScorer::UNKNOWN_BIGRAM_PENALTY;
        $triPenalty = TrigramFrequencyScorer::UNKNOWN_TRIGRAM_PENALTY;

        $p0 = $key[$cipherIndices[0]];
        $p1 = $key[$cipherIndices[1]];

        $bigramTotal  = $bigramMap[$p0 * $size + $p1] ?? $biPenalty;
        $trigramTotal = 0.0;

        for ($i = 2; $i < $n; $i++) {
            $p2 = $key[$cipherIndices[$i]];
            $bigramTotal += $bigramMap[$p1 * $size + $p2] ?? $biPenalty;
            if ($useTrigram) {
                $trigramTotal += $trigramMap[($p0 * $size + $p1) * $size + $p2] ?? $triPenalty;
            }
            $p0 = $p1;
            $p1 = $p2;
        }

        $score = $bigramTotal / ($n - 1);
        if ($useTrigram) {
            // Равный вес биграмм и триграмм: при равном весе (эмпирически) hill
            // climbing сходится к верному ключу точнее, чем при перевесе триграмм —
            // таблица триграмм тонкая и её штрафной вклад не стоит переоценивать.
            $score += $trigramTotal / ($n - 2);
        }

        return $score;
    }

    /**
     * Преобразует ключ дешифровки в строку-ключ шифрования для
     * {@see SimpleSubstitutionCipherService::process()} (key[plainIdx] = cipherLetter).
     *
     * @param  int[]    $key          key[cipherIdx] = plainIdx.
     * @param  string[] $alphabetData
     */
    private function keyString(array $key, array $alphabetData, int $size): string
    {
        $enc = array_fill(0, $size, 0);
        for ($c = 0; $c < $size; $c++) {
            $enc[$key[$c]] = $c;
        }

        $out = '';
        for ($p = 0; $p < $size; $p++) {
            $out .= $alphabetData[$enc[$p]];
        }

        return $out;
    }
}
