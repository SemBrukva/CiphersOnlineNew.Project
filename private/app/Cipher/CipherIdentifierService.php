<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Сервис идентификации шифра/кодировки по произвольному тексту.
 *
 * Запускает все зарегистрированные детекторы и возвращает список кандидатов,
 * отсортированных по confidence по убыванию. Дополнительно вторым проходом
 * пересчитывает confidence для детекторов, вернувших decryptedText — сравнивает
 * биграммный скор расшифровки с биграммным скором исходного текста.
 */
final readonly class CipherIdentifierService
{
    /**
     * Порог confidence для автозапуска brute-force.
     */
    public const float AUTO_THRESHOLD = 0.70;

    /**
     * Минимальный отрыв лидера от второго кандидата для автозапуска brute-force.
     */
    public const float AUTO_GAP = 0.10;

    /**
     * Минимум букв алфавита для запуска bigram-rescore: ниже статистика биграмм нестабильна.
     */
    private const int BIGRAM_RESCORE_MIN_LETTERS = 30;

    /**
     * Сильный положительный сигнал: bigram-score расшифровки выше, чем у оригинала, на 1.0+ натуральных лога.
     */
    private const float BIGRAM_DELTA_STRONG = 1.00;

    /**
     * Бонус confidence при сильном положительном сигнале.
     */
    private const float BIGRAM_BONUS_STRONG = 0.12;

    /**
     * Умеренный положительный сигнал.
     */
    private const float BIGRAM_DELTA_WEAK = 0.30;

    /**
     * Бонус confidence при умеренном положительном сигнале.
     */
    private const float BIGRAM_BONUS_WEAK = 0.05;

    /**
     * Отрицательный сигнал: расшифровка читается заметно хуже исходного — детектор подсунул мусор.
     */
    private const float BIGRAM_DELTA_NEGATIVE = -0.30;

    /**
     * Штраф confidence при отрицательном сигнале.
     */
    private const float BIGRAM_PENALTY = -0.15;

    /**
     * Потолок confidence после bigram-бонуса (жёсткие кодировки 0.97+ оставляем).
     */
    private const float BIGRAM_CONFIDENCE_CEILING = 0.95;

    /**
     * Минимум совпадений топ-триграмм языка в расшифровке, чтобы дать n-gram-бонус.
     */
    private const int NGRAM_MATCH_THRESHOLD = 2;

    /**
     * Дополнительный бонус, когда в расшифровке найдены характерные триграммы языка.
     */
    private const float NGRAM_BONUS = 0.08;

    /**
     * Порог уверенности лидера группы, при котором подавляются «соседи».
     */
    private const float SUPPRESSION_LEADER_THRESHOLD = 0.80;

    /**
     * Понижение confidence для подавляемого «соседа».
     */
    private const float SUPPRESSION_PENALTY = -0.25;

    /**
     * Нижняя граница confidence для подавляемых соседей.
     */
    private const float SUPPRESSION_FLOOR = 0.30;

    /**
     * Группы шифров с одинаковым базовым сигналом: при сильном лидере подавляем
     * остальных в группе, чтобы UI не показывал «4 кандидата с 50%+».
     *
     * @var array<string, string>
     */
    private const array GROUP_EVIDENCE_MARKERS = [
        'mono'      => 'CID_EV_IOC_MONO',
        'poly'      => 'CID_EV_IOC_POLY',
        'preserved' => 'CID_EV_IOC_PRESERVED',
    ];

    /**
     * Создаёт экземпляр сервиса с зарегистрированными детекторами.
     *
     * @param CipherDetectorInterface[] $detectors Список детекторов.
     */
    public function __construct(
        private array $detectors,
        private LetterFrequencyScorer $scorer,
        private IndexOfCoincidence $ioc,
        private BigramFrequencyScorer $bigramScorer,
    ) {
    }

    /**
     * Запускает все детекторы и возвращает кандидатов, отсортированных по confidence desc.
     *
     * @param  string      $text     Исходный текст пользователя.
     * @param  string|null $alphabet Явно заданный алфавит или null (auto).
     * @return CipherDetection[]
     */
    public function identify(string $text, ?string $alphabet): array
    {
        if (trim($text) === '') {
            return [];
        }

        $ctx     = new IdentificationContext($text, $alphabet, $this->scorer, $this->ioc);
        $results = [];

        foreach ($this->detectors as $detector) {
            $detection = $detector->detect($ctx);
            if ($detection !== null) {
                $results[] = $detection;
            }
        }

        $results = $this->applyBigramRescore($results, $ctx);
        $results = $this->suppressNeighbours($results);

        usort($results, static fn (CipherDetection $a, CipherDetection $b): int => $b->confidence <=> $a->confidence);

        // Убираем дубликаты по toolSlug, оставляя лучший confidence.
        $seen   = [];
        $unique = [];
        foreach ($results as $detection) {
            if (!isset($seen[$detection->toolSlug])) {
                $seen[$detection->toolSlug] = true;
                $unique[]                   = $detection;
            }
        }

        return $unique;
    }

    /**
     * Пересчитывает confidence детекций, у которых заполнен `decryptedText`,
     * сравнивая биграммный скор расшифровки с биграммным скором исходного текста.
     *
     * Чем сильнее расшифровка «читается» относительно зашифрованного, тем выше
     * бонус. Если детектор подсунул расшифровку хуже исходного — confidence
     * штрафуется, чтобы такой кандидат не оказывался в лидерах.
     *
     * @param  CipherDetection[]      $detections
     * @return CipherDetection[]
     */
    private function applyBigramRescore(array $detections, IdentificationContext $ctx): array
    {
        if ($detections === []) {
            return $detections;
        }

        $alphabet = $ctx->effectiveAlphabet();
        if (!$this->bigramScorer->supports($alphabet)) {
            return $detections;
        }
        if ($ctx->letterCount($alphabet) < self::BIGRAM_RESCORE_MIN_LETTERS) {
            return $detections;
        }

        $baseline = $this->bigramScorer->score($ctx->text, $alphabet);

        foreach ($detections as $i => $detection) {
            if ($detection->decryptedText === null) {
                continue;
            }

            $candidateScore = $this->bigramScorer->score($detection->decryptedText, $alphabet);
            $delta          = $candidateScore - $baseline;
            $ngramMatches   = $this->bigramScorer->commonNgramMatches($detection->decryptedText, $alphabet);
            $hasNgrams      = $ngramMatches >= self::NGRAM_MATCH_THRESHOLD;

            $bonus = 0.0;
            if ($delta >= self::BIGRAM_DELTA_STRONG) {
                $bonus = self::BIGRAM_BONUS_STRONG;
            } elseif ($delta >= self::BIGRAM_DELTA_WEAK) {
                $bonus = self::BIGRAM_BONUS_WEAK;
            } elseif ($delta <= self::BIGRAM_DELTA_NEGATIVE) {
                $bonus = self::BIGRAM_PENALTY;
            }

            // N-gram-бонус накладывается поверх биграммного: для коротких текстов,
            // где biggram-delta невелика, конкретные слова дают решающее различение.
            if ($hasNgrams) {
                $bonus += self::NGRAM_BONUS;
            }

            if ($bonus === 0.0) {
                continue;
            }

            $newConfidence = max(0.05, min(self::BIGRAM_CONFIDENCE_CEILING, $detection->confidence + $bonus));

            $evidenceKeys = $detection->evidenceKeys;
            if ($bonus > 0 && !in_array('CID_EV_BIGRAM_READABLE', $evidenceKeys, true)) {
                $evidenceKeys[] = 'CID_EV_BIGRAM_READABLE';
            }
            if ($hasNgrams && !in_array('CID_EV_COMMON_WORDS', $evidenceKeys, true)) {
                $evidenceKeys[] = 'CID_EV_COMMON_WORDS';
            }

            $hints                  = $detection->hints;
            $hints['bigram_delta']  = round($delta, 4);
            if ($hasNgrams) {
                $hints['ngram_matches'] = $ngramMatches;
            }

            $detections[$i] = $detection->withRescore($newConfidence, $evidenceKeys, $hints);
        }

        return $detections;
    }

    /**
     * Подавляет «соседей» лидера в той же группе (моно / поли / транспозиция).
     *
     * Если у лидера группы confidence ≥ {@see SUPPRESSION_LEADER_THRESHOLD}, остальные
     * детекторы с тем же базовым сигналом признаются логически слабее (один и тот же
     * IoC-признак без дополнительных свидетельств), и их confidence прижимается к
     * полу {@see SUPPRESSION_FLOOR}. Это убирает шум из выдачи: пользователю не нужны
     * 4 моноалфавитных кандидата с 50%+, когда у Caesar 0.95.
     *
     * @param  CipherDetection[] $detections
     * @return CipherDetection[]
     */
    private function suppressNeighbours(array $detections): array
    {
        foreach (self::GROUP_EVIDENCE_MARKERS as $marker) {
            $bestSlug       = null;
            $bestConfidence = 0.0;
            foreach ($detections as $d) {
                if (!in_array($marker, $d->evidenceKeys, true)) {
                    continue;
                }
                if ($d->confidence > $bestConfidence) {
                    $bestConfidence = $d->confidence;
                    $bestSlug       = $d->toolSlug;
                }
            }

            if ($bestSlug === null || $bestConfidence < self::SUPPRESSION_LEADER_THRESHOLD) {
                continue;
            }

            foreach ($detections as $i => $d) {
                if ($d->toolSlug === $bestSlug) {
                    continue;
                }
                if (!in_array($marker, $d->evidenceKeys, true)) {
                    continue;
                }

                $newConfidence = max(self::SUPPRESSION_FLOOR, $d->confidence + self::SUPPRESSION_PENALTY);
                if ($newConfidence >= $d->confidence) {
                    continue;
                }

                $hints                  = $d->hints;
                $hints['suppressed_by'] = $bestSlug;

                $detections[$i] = $d->withRescore($newConfidence, $d->evidenceKeys, $hints);
            }
        }

        return $detections;
    }

    /**
     * Возвращает UI-настройки инструмента для ToolRegistry.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSettings(): array
    {
        return [
            [
                'type'    => 'select',
                'id'      => 'ciphers-alphabet',
                'key'     => 'alphabet',
                'label'   => trans('CIPHER_TOOL_SETTING_ALPHABET'),
                'options' => [
                    ['value' => 'auto', 'label' => trans('CIPHER_TOOL_SETTING_AUTO'), 'selected' => true],
                    ['value' => 'en',   'label' => trans('LANG_EN')],
                    ['value' => 'ru',   'label' => trans('LANG_RU')],
                    ['value' => 'de',   'label' => trans('LANG_DE')],
                    ['value' => 'es',   'label' => trans('LANG_ES')],
                    ['value' => 'fr',   'label' => trans('LANG_FR')],
                    ['value' => 'it',   'label' => trans('LANG_IT')],
                    ['value' => 'pt',   'label' => trans('LANG_PT')],
                    ['value' => 'tr',   'label' => trans('LANG_TR')],
                ],
                'default' => 'auto',
            ],
        ];
    }

    /**
     * Возвращает trust-items для ToolRegistry.
     *
     * @return string[]
     */
    public function getTrustItems(string $calculationMode): array
    {
        return [
            trans('CIPHER_IDENTIFIER_TRUST_TYPE'),
            trans('CIPHER_IDENTIFIER_TRUST_MULTI_ALPHA'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
