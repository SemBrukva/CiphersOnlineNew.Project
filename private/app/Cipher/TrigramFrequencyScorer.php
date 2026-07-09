<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Оценивает «естественность» текста по таблицам частот триграмм.
 *
 * Триграммы различают правильный ключ на коротких текстах заметно надёжнее
 * биграмм: тройки букв кодируют более длинную структуру языка (типичные
 * сочетания «the», «ing», «ени», «ост»), которая почти не встречается в шуме.
 * Используется то же ранговое лог-правдоподобие, что и в {@see BigramFrequencyScorer}:
 * триграмма ранга r из N получает вес log((N + 1) / (r + 1)); триграммы вне
 * таблицы — фиксированный штраф.
 *
 * Таблицы заданы для языков с наибольшим трафиком (en, ru). Для остальных
 * алфавитов скоринг возвращает 0 / not-supported — вызывающий код откатывается
 * к биграммам.
 */
final readonly class TrigramFrequencyScorer
{
    /** Штраф за триграмму, отсутствующую в таблице. */
    public const float UNKNOWN_TRIGRAM_PENALTY = -3.0;

    /**
     * Списки наиболее частых триграмм по языкам в порядке убывания частоты.
     * Урезаны до топ-≈90 троек, что покрывает значимую долю триграмм текста.
     *
     * @var array<string, string[]>
     */
    private const array TRIGRAMS = [
        'en' => [
            'the', 'and', 'ing', 'ent', 'ion', 'her', 'for', 'tha', 'nth', 'int',
            'ere', 'tio', 'ter', 'est', 'ers', 'ati', 'hat', 'ate', 'all', 'eth',
            'hes', 'ver', 'his', 'oft', 'ith', 'sth', 'oth', 'res', 'ont', 'thi',
            'ght', 'ind', 'ist', 'oun', 'wit', 'ame', 'nce', 'ave', 'men', 'are',
            'ell', 'was', 'som', 'igh', 'oul', 'con', 'per', 'nde', 'has', 'not',
            'you', 'ard', 'com', 'sti', 'out', 'wor', 'thr', 'one', 'our', 'lin',
            'sto', 'end', 'ort', 'age', 'ost', 'ual', 'ese', 'ain', 'ory', 'und',
            'ill', 'chi', 'sio', 'hin', 'rea', 'nte', 'ome', 'sec', 'ove', 'tra',
        ],
        'ru' => [
            'ост', 'ени', 'ния', 'ово', 'ста', 'ать', 'про', 'сти', 'ест', 'ани',
            'тор', 'тел', 'ний', 'тся', 'тво', 'ных', 'ные', 'его', 'ому', 'что',
            'как', 'при', 'ние', 'ото', 'ает', 'нно', 'ств', 'ель', 'сто', 'ват',
            'ова', 'кот', 'аль', 'общ', 'дел', 'раз', 'пре', 'под', 'нас', 'нов',
            'сво', 'льн', 'нии', 'иче', 'чес', 'еск', 'ско', 'ког', 'вер', 'дер',
            'жен', 'зна', 'йст', 'мен', 'осо', 'пол', 'реш', 'тре', 'уще', 'ерь',
            'льк', 'ора', 'оро', 'оло', 'кий', 'аст', 'ате', 'бор', 'бра', 'ван',
            'вид', 'вле', 'воз', 'гла', 'дан', 'дол', 'дос', 'има', 'кон', 'кос',
            'лен', 'мож', 'над', 'нее', 'нес', 'рос', 'обр', 'пер', 'пла', 'рог',
        ],
    ];

    /**
     * Возвращает триграммный скор текста для указанного алфавита.
     * Выше — лучше; нормализован на число триграмм. При отсутствии таблицы
     * или недостатке букв возвращает 0.
     */
    public function score(string $text, string $alphabet): float
    {
        $trigrams = self::TRIGRAMS[$alphabet] ?? null;
        if ($trigrams === null) {
            return 0.0;
        }

        $ranks = $this->buildRankMap($trigrams);
        $count = count($ranks);
        if ($count === 0) {
            return 0.0;
        }

        $chars = $this->extractLetters($text, $alphabet);
        $n     = count($chars);
        if ($n < 3) {
            return 0.0;
        }

        $total   = 0.0;
        $triples = 0;
        for ($i = 0, $limit = $n - 2; $i < $limit; $i++) {
            $trigram = $chars[$i] . $chars[$i + 1] . $chars[$i + 2];
            if (isset($ranks[$trigram])) {
                $total += log(($count + 1) / ($ranks[$trigram] + 1));
            } else {
                $total += self::UNKNOWN_TRIGRAM_PENALTY;
            }
            $triples++;
        }

        return $total / $triples;
    }

    /**
     * Проверяет, поддерживается ли триграммный скоринг для алфавита.
     */
    public function supports(string $alphabet): bool
    {
        return isset(self::TRIGRAMS[$alphabet]);
    }

    /**
     * Возвращает карту весов триграмм по int-ключам для оптимизированного скоринга.
     *
     * Ключ: (aIdx * size + bIdx) * size + cIdx, где a/b/c — индексы букв алфавита.
     * Значение: предвычисленный вес log((N + 1) / (rank + 1)). Карта кешируется
     * per-alphabet на время жизни процесса. Используется brute-force/hill-climb
     * инструментами для O(text) скоринга без mb_substr.
     *
     * @param  array<string, int> $indexMap Карта буква→индекс в алфавите.
     * @return array<int, float>
     */
    public function buildIndexedWeightMap(string $alphabet, array $indexMap, int $alphabetSize): array
    {
        static $cache = [];
        if (isset($cache[$alphabet])) {
            return $cache[$alphabet];
        }

        $trigrams = self::TRIGRAMS[$alphabet] ?? null;
        if ($trigrams === null) {
            return $cache[$alphabet] = [];
        }

        $stringMap = $this->buildRankMap($trigrams);
        $count     = count($stringMap);
        $map       = [];
        foreach ($stringMap as $trigram => $rank) {
            $a = mb_substr($trigram, 0, 1);
            $b = mb_substr($trigram, 1, 1);
            $c = mb_substr($trigram, 2, 1);
            if (!isset($indexMap[$a], $indexMap[$b], $indexMap[$c])) {
                continue;
            }
            $key = ($indexMap[$a] * $alphabetSize + $indexMap[$b]) * $alphabetSize + $indexMap[$c];
            if (!isset($map[$key])) {
                $map[$key] = log(($count + 1) / ($rank + 1));
            }
        }

        return $cache[$alphabet] = $map;
    }

    /**
     * Строит карту триграмма → ранг для указанного списка.
     *
     * @param  string[] $trigrams
     * @return array<string, int>
     */
    private function buildRankMap(array $trigrams): array
    {
        static $cache = [];
        $key = md5(implode('|', $trigrams));
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $map = [];
        foreach ($trigrams as $rank => $trigram) {
            $normalized = mb_strtolower($trigram);
            if (!isset($map[$normalized])) {
                $map[$normalized] = $rank;
            }
        }

        return $cache[$key] = $map;
    }

    /**
     * Извлекает строчные буквы алфавита из текста.
     *
     * @return string[]
     */
    private function extractLetters(string $text, string $alphabet): array
    {
        $catalogLetters = (new AlphabetCatalog())->alphabet($alphabet);
        $index  = array_flip($catalogLetters);
        $result = [];
        $length = mb_strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $char = mb_strtolower(mb_substr($text, $i, 1));
            if (isset($index[$char])) {
                $result[] = $char;
            }
        }

        return $result;
    }
}
