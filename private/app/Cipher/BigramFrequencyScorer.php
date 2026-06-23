<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Оценивает «естественность» текста на основе таблиц частот биграмм.
 *
 * Биграммный скоринг значительно надёжнее моновариантного χ² на коротких
 * текстах (≤200 букв): моновариантные метрики не различают правильный ключ
 * от шума, поскольку короткие тексты заметно отклоняются от усреднённого
 * частотного профиля языка. Биграммы же кодируют локальную структуру
 * (типичные сочетания согласных и гласных), которая ломается при неверном
 * ключе.
 *
 * Каждая таблица содержит список топ-биграмм языка в порядке убывания
 * частоты. Используется ранговое лог-правдоподобие: биграмма ранга r из
 * N получает вес log((N + 1) / (r + 1)); биграммы вне списка получают
 * фиксированный штраф (имитирует log(p_min)).
 */
final readonly class BigramFrequencyScorer
{
    /** Штраф за биграмму, отсутствующую в таблице (имитирует log редкой частоты). */
    public const float UNKNOWN_BIGRAM_PENALTY = -2.0;

    /**
     * Минимальное число букв алфавита для надёжного биграммного взлома Виженера.
     *
     * Значения подобраны эмпирически на тестовом корпусе из публицистических
     * текстов и набора ключей разной длины (3-8 букв): порог — это длина,
     * начиная с которой все четыре ключа восстанавливаются точно. Чем больше
     * алфавит языка (особенно русский 33, португальский 36, французский 40),
     * тем больше букв требуется для надёжной статистики на колонках.
     *
     * @var array<string, int>
     */
    private const array MIN_LETTERS_BY_ALPHABET = [
        'en' => 100,
        'ru' => 200,
        'es' => 150,
        'pt' => 100,
        'fr' => 100,
        'de' => 125,
        'it' => 100,
        'tr' => 125,
    ];

    /** Резервный порог для алфавитов без эмпирических данных. */
    private const int FALLBACK_MIN_LETTERS = 150;

    /**
     * Списки наиболее частых биграмм по языкам в порядке убывания частоты.
     *
     * Данные основаны на корпусах открытых текстов (художественная литература,
     * публицистика). Списки урезаны до топ-≈120 пар, что покрывает ≈80%
     * биграмм типичного текста — этого достаточно для надёжного скоринга.
     *
     * @var array<string, string[]>
     */
    private const array BIGRAMS = [
        'en' => [
            'th', 'he', 'in', 'er', 'an', 're', 'on', 'at', 'en', 'nd',
            'ti', 'es', 'or', 'te', 'of', 'ed', 'is', 'it', 'al', 'ar',
            'st', 'to', 'nt', 'ng', 'se', 'ha', 'as', 'ou', 'io', 'le',
            've', 'co', 'me', 'de', 'hi', 'ri', 'ro', 'ic', 'ne', 'ea',
            'ra', 'ce', 'li', 'ch', 'll', 'be', 'ma', 'si', 'om', 'ur',
            'ca', 'el', 'ta', 'la', 'ns', 'di', 'fo', 'ho', 'pe', 'ec',
            'pr', 'no', 'ct', 'us', 'ac', 'ot', 'il', 'tr', 'ly', 'nc',
            'et', 'ut', 'ss', 'so', 'rs', 'un', 'lo', 'wa', 'ge', 'ie',
            'wh', 'ee', 'wi', 'em', 'ad', 'ol', 'rt', 'po', 'we', 'na',
            'ul', 'ni', 'ts', 'mo', 'ow', 'pa', 'im', 'mi', 'ai', 'sh',
            'ir', 'su', 'id', 'os', 'iv', 'ia', 'am', 'fi', 'ci', 'vi',
            'pl', 'ig', 'tu', 'ev', 'ld', 'ry', 'mp', 'fe', 'bl', 'ab',
            'gh', 'ty', 'op', 'wo', 'sa', 'ay', 'ex', 'ke', 'fr', 'oo',
            'av', 'ag', 'if', 'ap', 'gr', 'od',
        ],
        'ru' => [
            'ст', 'но', 'то', 'на', 'ен', 'ов', 'не', 'пр', 'ко', 'ра',
            'во', 'ал', 'от', 'ни', 'по', 'та', 'ро', 'ка', 'ва', 'ел',
            'ри', 'ес', 'ор', 'ан', 'де', 'ет', 'ит', 'ир', 'тр', 'ло',
            'ме', 'ос', 'ть', 'со', 'ом', 'ле', 'ли', 'ил', 'ат', 'за',
            'ин', 'го', 'ис', 'ер', 'ог', 'се', 'же', 'ой', 'ма', 'ия',
            'ьн', 'ев', 'об', 'пе', 'тв', 'ас', 'до', 'ча', 'ам', 'те',
            'ем', 'ож', 'св', 'чт', 'уч', 'ьс', 'ив', 'ие', 'ег', 'ох',
            'ии', 'ге', 'ез', 'ще', 'щи', 'еи', 'ту', 'их', 'ря', 'юд',
            'оз', 'лу', 'ум', 'из', 'ши', 'жи', 'нт', 'др', 'ху', 'ук',
            'кр', 'ср', 'тс', 'ши', 'ач', 'ия', 'як', 'ши', 'аж', 'еж',
            'ук', 'ря', 'жн', 'мн', 'тн', 'дн', 'жд', 'пл', 'кл', 'вл',
            'фи', 'фо', 'ць', 'ць', 'хо', 'оп', 'ыл', 'ыс', 'ые', 'ым',
            'ой', 'ый', 'ил', 'ор', 'ос', 'ну', 'ау', 'ую', 'ую', 'ож',
        ],
        'es' => [
            'de', 'en', 'es', 'ra', 'er', 'on', 'os', 'ar', 'la', 'as',
            'el', 'an', 'al', 'ad', 're', 'qu', 'or', 'ue', 'do', 'co',
            'st', 'ie', 'le', 'na', 'lo', 'nt', 'ci', 'po', 'me', 'ta',
            'ed', 'ro', 'ti', 'om', 'da', 'nd', 'pa', 'rt', 'ma', 'mi',
            'ón', 'pe', 'di', 'no', 'em', 'gu', 'so', 'nc', 'ic', 'tr',
            'mo', 'un', 'to', 'ri', 'lu', 'is', 'sa', 'se', 'pr', 'os',
            'ho', 'fo', 'ca', 'cu', 'or', 'pu', 'ec', 'ia', 'rc', 'rg',
            'su', 'ac', 'ho', 'fe', 'bi', 'eo', 'fi', 'gi', 'le', 'la',
            'pl', 'ñ a', 'mp', 'br', 'os', 'va', 'fo', 'sp', 'cl', 'cr',
            'gr', 'tu', 'iv', 'us', 'mu', 'rm', 'ad', 'ag', 'cs', 'au',
            'os', 'pi', 'om', 'bu', 'ba', 'mb', 'ms', 'rs', 'rl', 'js',
            'gn', 'ig', 'gu', 'gé', 'gé', 'ha', 'pé', 'pó', 'pé', 'tó',
        ],
        'pt' => [
            'de', 'es', 'ra', 'os', 'as', 'do', 'en', 'er', 'ar', 'ad',
            'an', 'co', 'em', 'te', 'da', 're', 'nt', 'ta', 'or', 'na',
            'st', 'me', 'ma', 'al', 'la', 'qu', 'ei', 'pa', 'ic', 'ti',
            'is', 'ou', 'el', 'ro', 'sa', 'mo', 'to', 'po', 'le', 'so',
            'no', 'pr', 'tr', 'um', 'on', 'av', 'ed', 'mi', 'ce', 'ne',
            'nd', 'oc', 'ca', 'us', 'pe', 'co', 'ca', 'ço', 'eu', 'ma',
            'mu', 'ne', 'nh', 'lh', 'al', 'rt', 'ri', 'di', 'mb', 'om',
            'cu', 'qu', 'fi', 'hu', 'bo', 'rn', 'fo', 'rd', 'rc', 'rg',
            'er', 'os', 'ut', 'rg', 'lo', 'ç', 'lv', 'sp', 'ag', 'gu',
            'gi', 'gr', 'gn', 'qu', 'bs', 'mp', 'br', 'cl', 'cr', 'fl',
            'fr', 'pl', 'tu', 'ts', 'va', 'vi', 'vo', 'vr', 'qu', 'mb',
            'fo', 'ho', 'pé', 'gu', 'pé', 'gé', 'ár', 'ér', 'él', 'óp',
        ],
        'tr' => [
            'la', 'er', 'an', 'in', 'ar', 'en', 'ir', 'ya', 'al', 'ka',
            'le', 'na', 'as', 'da', 'or', 'ek', 'ta', 'ba', 'lı', 'el',
            'es', 'ye', 'ay', 'ne', 'ma', 'ed', 'ah', 'an', 'me', 'iz',
            'is', 'on', 'ki', 'bi', 'di', 'ri', 'sa', 'mi', 'li', 'et',
            'le', 'va', 'on', 'so', 'us', 'lu', 'ko', 'ev', 'çı', 'ca',
            'ay', 'rd', 'şi', 'ço', 'ig', 'ce', 'sı', 'ar', 'um', 'iy',
            'ün', 'os', 'eh', 'st', 'nl', 'ml', 'rl', 'rs', 'lr', 'ya',
            'üy', 'üz', 'üs', 'üm', 'ün', 'ür', 'üt', 'ım', 'ız', 'ığ',
            'şt', 'şk', 'rş', 'rç', 'rs', 'tş', 'mş', 'iş', 'ış', 'uş',
            'aş', 'oş', 'üş', 'mş', 'lş', 'hş', 'ğd', 'ğı', 'ğu', 'rt',
            'ld', 'md', 'nd', 'ts', 'br', 'cr', 'gr', 'kr', 'tr', 'pr',
            'fr', 'gl', 'kl', 'pl', 'sl', 'tl', 'vl', 'mr', 'sb', 'sc',
        ],
        'fr' => [
            'es', 'le', 'en', 'de', 'on', 'nt', 'ai', 're', 'er', 'te',
            'an', 'el', 'ne', 'et', 'me', 'il', 'it', 'ar', 'la', 'ra',
            'ns', 'ie', 'ou', 'ie', 'ce', 'qu', 'us', 'pa', 'se', 'pl',
            'le', 'rs', 'po', 'pe', 'ec', 'ec', 'ur', 'is', 'eu', 'ie',
            'tr', 'ri', 'st', 'ti', 'pa', 'co', 'so', 'on', 'au', 'em',
            'av', 'in', 'su', 'or', 'es', 'do', 'ar', 'én', 'lu', 'ev',
            'fa', 'ag', 'ho', 'go', 'ca', 'ud', 'eu', 'sé', 'rt', 'ut',
            'mi', 'di', 'éc', 'ph', 'mp', 'ms', 'ns', 'sp', 'st', 'gn',
            'cl', 'br', 'cr', 'pr', 'tr', 'fr', 'vr', 'bl', 'pl', 'gl',
            'fl', 'gu', 'gi', 'gr', 'gé', 'ux', 'is', 'no', 'mo', 'al',
            'ds', 'rt', 'ux', 'os', 'mb', 'om', 'lm', 'fr', 'br', 'ch',
            'ai', 'oi', 'eu', 'au', 'ou', 'on', 'an', 'en', 'in', 'un',
        ],
        'de' => [
            'en', 'er', 'ch', 'de', 'nd', 'ei', 'ie', 'in', 'te', 'ng',
            'es', 'ne', 'un', 'an', 'be', 'st', 'ge', 'sc', 'ic', 'au',
            'da', 'di', 'we', 'wi', 'mi', 'el', 'ic', 'as', 'is', 'se',
            'al', 'me', 'em', 'it', 'le', 'ed', 'ek', 'eu', 'fü', 'rs',
            'üc', 'üh', 'ür', 'üb', 'ün', 'üt', 'sz', 'rk', 'rm', 'rl',
            'rn', 'rt', 'sa', 'so', 'st', 'ig', 'er', 'or', 'ur', 'ar',
            'el', 'au', 'eu', 'ä u', 'sc', 'sp', 'sm', 'st', 'tz', 'tt',
            'ss', 'mm', 'nn', 'ck', 'pf', 'pa', 'gr', 'br', 'kr', 'tr',
            'fr', 'pr', 'vr', 'bl', 'gl', 'kl', 'pl', 'sl', 'fl', 'cl',
            'ag', 'eg', 'ig', 'og', 'ug', 'üg', 'fa', 'fe', 'fi', 'fo',
            'fu', 'fü', 'ka', 'ke', 'ki', 'ko', 'ku', 'kü', 'ha', 'he',
            'hi', 'ho', 'hu', 'hü', 'ja', 'je', 'ju', 'jü', 'ya', 'ye',
        ],
        'it' => [
            'di', 'er', 'es', 'el', 'en', 'on', 'la', 'an', 'co', 'or',
            'ar', 're', 'al', 'st', 'no', 'ra', 'in', 'nt', 'at', 'ne',
            'os', 'ol', 'lo', 'ri', 'te', 'ti', 'mo', 'on', 'me', 'ma',
            'tr', 'gl', 'gn', 'oz', 'os', 'ed', 'pe', 'ce', 'ci', 'ch',
            'da', 'do', 'na', 'na', 'pa', 'po', 'ag', 'al', 'an', 'pi',
            'sa', 'so', 'se', 'si', 'su', 'sc', 'sp', 'sl', 'tt', 'rr',
            'pp', 'cc', 'mm', 'nn', 'gg', 'zz', 'bb', 'cl', 'cr', 'br',
            'fr', 'pr', 'gr', 'tr', 'dr', 'bl', 'fl', 'gl', 'pl', 'sl',
            'va', 've', 'vi', 'vo', 'vu', 'ba', 'be', 'bi', 'bo', 'bu',
            'ca', 'ce', 'ci', 'co', 'cu', 'da', 'de', 'di', 'do', 'du',
            'fa', 'fe', 'fi', 'fo', 'fu', 'ga', 'ge', 'gi', 'go', 'gu',
            'ha', 'he', 'hi', 'ho', 'hu', 'ja', 'la', 'le', 'li', 'lo',
        ],
    ];

    /**
     * Топ-частые триграммы / короткие слова естественного языка. Если хотя бы
     * одна из них найдена в расшифровке — это очень сильный сигнал, что
     * расшифровка действительно правильная (биграммная статистика на коротких
     * текстах не различает «правильно» и «почти правильно», а конкретное слово
     * различает однозначно).
     *
     * @var array<string, string[]>
     */
    private const array COMMON_NGRAMS = [
        'en' => ['the', 'and', 'ing', 'ion', 'tion', 'ent', 'for', 'tha', 'her', 'with'],
        'ru' => ['что', 'как', 'при', 'это', 'для', 'все', 'его', 'или', 'был', 'ого'],
        'de' => ['der', 'die', 'und', 'ich', 'ein', 'den', 'gen', 'nde', 'sch', 'das'],
        'es' => ['que', 'los', 'las', 'del', 'con', 'ent', 'por', 'ent', 'res', 'ado'],
        'fr' => ['les', 'des', 'que', 'ent', 'ait', 'ion', 'lle', 'pou', 'tio', 'our'],
        'it' => ['che', 'ent', 'one', 'are', 'ion', 'all', 'lla', 'ato', 'per', 'sta'],
        'pt' => ['que', 'ent', 'com', 'par', 'ado', 'ara', 'aci', 'eit', 'inh', 'mai'],
        'tr' => ['bir', 'ile', 'lar', 'ler', 'gel', 'ola', 'eri', 'ene', 'esi', 'and'],
    ];

    /**
     * Считает, сколько из топ-частых триграмм языка встречается в тексте.
     *
     * Дешёвая проверка: если расшифровка содержит характерные триграммы языка
     * (например, "the", "and", "ing" для en) — confidence можно поднять заметно
     * сильнее, чем по одному биграммному скору.
     */
    public function commonNgramMatches(string $text, string $alphabet): int
    {
        $ngrams = self::COMMON_NGRAMS[$alphabet] ?? null;
        if ($ngrams === null) {
            return 0;
        }
        $lower = mb_strtolower($text);
        $count = 0;
        foreach ($ngrams as $ngram) {
            if (mb_strpos($lower, $ngram) !== false) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Возвращает биграммный скор текста для указанного алфавита.
     *
     * Выше — лучше. Скор нормализован на количество биграмм, то есть
     * сравним между текстами разной длины. При недостатке букв или
     * отсутствии таблицы для алфавита возвращает 0.
     */
    public function score(string $text, string $alphabet): float
    {
        $bigrams = self::BIGRAMS[$alphabet] ?? null;
        if ($bigrams === null) {
            return 0.0;
        }

        $ranks = $this->buildRankMap($bigrams);
        $count = count($ranks);
        if ($count === 0) {
            return 0.0;
        }

        $chars = $this->extractLetters($text, $alphabet);
        if (count($chars) < 2) {
            return 0.0;
        }

        $total = 0.0;
        $pairs = 0;
        for ($i = 0, $n = count($chars) - 1; $i < $n; $i++) {
            $bigram = $chars[$i] . $chars[$i + 1];
            if (isset($ranks[$bigram])) {
                $total += log(($count + 1) / ($ranks[$bigram] + 1));
            } else {
                $total += self::UNKNOWN_BIGRAM_PENALTY;
            }
            $pairs++;
        }

        return $total / $pairs;
    }

    /**
     * Проверяет, поддерживается ли биграммный скоринг для алфавита.
     */
    public function supports(string $alphabet): bool
    {
        return isset(self::BIGRAMS[$alphabet]);
    }

    /**
     * Возвращает карту весов биграмм по int-ключам для оптимизированного скоринга.
     *
     * Ключ: prevIdx * alphabetSize + curIdx, где prevIdx/curIdx — индексы букв
     * в алфавите. Значение: предвычисленный вес log((N + 1) / (rank + 1)),
     * где N — число уникальных биграмм языка. Числа идентичны тем, что выдаёт
     * {@see score()} в цикле.
     *
     * Карта кешируется per-alphabet на время жизни процесса.
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

        $bigrams = self::BIGRAMS[$alphabet] ?? null;
        if ($bigrams === null) {
            return $cache[$alphabet] = [];
        }

        $stringMap = $this->buildRankMap($bigrams);
        $count     = count($stringMap);
        $map       = [];
        foreach ($stringMap as $bigram => $rank) {
            $a = mb_substr($bigram, 0, 1);
            $b = mb_substr($bigram, 1, 1);
            if (!isset($indexMap[$a], $indexMap[$b])) {
                continue;
            }
            $key = $indexMap[$a] * $alphabetSize + $indexMap[$b];
            if (!isset($map[$key])) {
                $map[$key] = log(($count + 1) / ($rank + 1));
            }
        }

        return $cache[$alphabet] = $map;
    }

    /**
     * Возвращает минимальное число букв алфавита для надёжного взлома Виженера.
     * Тексты короче этого порога с большой вероятностью не дают точный ключ.
     */
    public function minReliableLetterCount(string $alphabet): int
    {
        return self::MIN_LETTERS_BY_ALPHABET[$alphabet] ?? self::FALLBACK_MIN_LETTERS;
    }

    /**
     * Строит карту биграмма → ранг для указанного списка.
     *
     * @param  string[] $bigrams
     * @return array<string, int>
     */
    private function buildRankMap(array $bigrams): array
    {
        static $cache = [];
        $key = spl_object_id($this) . ':' . md5(implode('|', $bigrams));
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $map = [];
        foreach ($bigrams as $rank => $bigram) {
            $normalized = mb_strtolower($bigram);
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
        $index = array_flip($catalogLetters);
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
