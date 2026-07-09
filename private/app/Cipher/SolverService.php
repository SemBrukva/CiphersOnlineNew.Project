<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Оркестратор «умного» авто-солвера: единый вход → ранжированные расшифровки.
 *
 * Строится поверх готовых кирпичей: {@see CipherIdentifierService} определяет
 * вероятные типы шифра, зарегистрированные brute-force/cracker-инструменты
 * (через {@see ApiCipherToolExecutorInterface}) выдают plaintext-кандидатов, а
 * {@see BigramFrequencyScorer} ранжирует их по читаемости. На выходе — плоский
 * список расшифровок, отсортированный по убыванию читаемости, где верхний
 * элемент и есть «вероятный ответ».
 */
final readonly class SolverService
{
    /**
     * Бюджет тяжёлых запусков brute-force/cracker на один вызов.
     *
     * Защита от DoS: каждый детектор-кандидат может запустить свой перебор, а
     * cracker'ы Виженера/аффинного нелинейны по длине текста. Ограничиваем
     * число фактических исполнений, кандидаты берутся в порядке confidence.
     */
    private const int MAX_ACTIONS = 6;

    /**
     * Максимум расшифровок в ответе.
     */
    private const int MAX_ANSWERS = 8;

    /**
     * Минимум букв алфавита для n-gram-скоринга читаемости.
     * Занижен намеренно: триграммы дают полезный сигнал уже с ~4 букв, а именно
     * короткие тексты важно уметь ранжировать.
     */
    private const int MIN_LETTERS_FOR_BIGRAM = 4;

    /**
     * Минимум букв для запуска универсальных дешёвых крекеров.
     */
    private const int MIN_LETTERS_FOR_UNIVERSAL = 3;

    /**
     * Максимальная доля букв во входе, при которой пробуется ROT47.
     * ROT47-шифртекст обычно символьно-нагружен (буквы шифруются в символы),
     * поэтому высокая доля букв означает, что это, скорее, обычная подстановка.
     */
    private const float ROT47_MAX_LETTER_RATIO = 0.5;

    /**
     * Минимальная читаемость расшифровки ROT47 для попадания в выдачу.
     * ROT47 обратим для любой строки, поэтому спекулятивную попытку показываем
     * только при осмысленном результате.
     */
    private const float ROT47_MIN_READABILITY = 0.35;

    /**
     * Нижняя граница confidence детекции, ниже которой кандидат не рассматривается.
     */
    private const float CANDIDATE_FLOOR = 0.25;

    /**
     * Универсальные дешёвые детерминированные крекеры, прогоняемые всегда на
     * буквенном вводе: action → [toolSlug, cipherKey]. Caesar-brute покрывает все
     * сдвиги (включая ROT13/ROT-N), Atbash — фиксированную обратную подстановку.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const array UNIVERSAL_ACTIONS = [
        'caesar-brute-force' => ['classical-ciphers/caesar', 'CIPHER_NAME_CAESAR'],
        'atbash'             => ['classical-ciphers/atbash', 'CIPHER_NAME_ATBASH'],
    ];

    /**
     * Создаёт экземпляр оркестратора.
     *
     * Исполнитель ({@see ApiCipherToolExecutorInterface}) передаётся не в
     * конструктор, а в {@see solve()}: сам реестр строит этот сервис, поэтому
     * конструкторная зависимость от реестра замкнула бы контейнер в цикл.
     */
    public function __construct(
        private CipherIdentifierService $identifier,
        private BigramFrequencyScorer $bigramScorer,
        private LetterFrequencyScorer $letterScorer,
        private TrigramFrequencyScorer $trigramScorer,
        private Rot47CipherService $rot47,
    ) {
    }

    /**
     * Решает шифр: определяет тип, прогоняет авто-взлом и ранжирует расшифровки.
     *
     * @param  string                         $text     Исходный текст пользователя.
     * @param  string|null                    $alphabet Явно заданный алфавит или null (auto).
     * @param  ApiCipherToolExecutorInterface $executor Реестр для запуска brute-force/cracker-инструментов.
     * @return array{answers: SolverResult[], best: SolverResult|null, detections: CipherDetection[]}
     */
    public function solve(string $text, ?string $alphabet, ApiCipherToolExecutorInterface $executor): array
    {
        $detections = $this->identifier->identify($text, $alphabet);

        $answers     = [];
        $executions  = 0;
        $ranActions  = [];

        foreach ($detections as $detection) {
            if ($detection->confidence < self::CANDIDATE_FLOOR) {
                continue;
            }

            $plaintexts = [];

            if ($detection->bruteForceAction !== null && $executions < self::MAX_ACTIONS) {
                $executions++;
                $ranActions[$detection->bruteForceAction] = true;
                $plaintexts = $this->runAction($detection->bruteForceAction, $text, $detection->detectedAlphabet ?? $alphabet, $executor);
            } elseif ($detection->decryptedText !== null && $detection->decryptedText !== '') {
                $plaintexts = [['text' => $detection->decryptedText, 'key' => null]];
            }

            $this->appendAnswers($answers, $plaintexts, $detection->toolSlug, $detection->cipherKey, $detection->bruteForceAction, $detection->detectedAlphabet);
        }

        // Универсальные дешёвые детерминированные крекеры прогоняются ВСЕГДА на
        // буквенном вводе, независимо от уверенности детекции: на коротких текстах
        // (5–15 букв) детекторы типа молчат, но Caesar/ROT/Atbash перебираются
        // мгновенно, а ранжирование по читаемости выберет верную расшифровку.
        $inputAlphabet = $alphabet ?? $this->letterScorer->detectAlphabet($text);
        if (
            $this->bigramScorer->supports($inputAlphabet)
            && $this->letterScorer->countLetters($text, $inputAlphabet) >= self::MIN_LETTERS_FOR_UNIVERSAL
        ) {
            foreach (self::UNIVERSAL_ACTIONS as $action => [$toolSlug, $cipherKey]) {
                if (isset($ranActions[$action]) || $executions >= self::MAX_ACTIONS) {
                    continue;
                }
                $executions++;
                $ranActions[$action] = true;
                // Передаём уже определённый $inputAlphabet, а не 'auto': собственное
                // авто-определение инструментов на коротких словах ненадёжно
                // (напр. atbash принимает 'SVOOL' за турецкий и портит реверс).
                $plaintexts = $this->runAction($action, $text, $inputAlphabet, $executor);
                $this->appendAnswers($answers, $plaintexts, $toolSlug, $cipherKey, $action, $inputAlphabet);
            }
        }

        // ROT47 (печатный ASCII, self-inverse) не проходит буквенный гейт: его
        // шифртекст символьно-нагружен. Пробуем на символьном вводе, но добавляем
        // кандидата только если расшифровка достаточно читаема — ROT47 обратим для
        // ЛЮБОЙ строки, поэтому без порога любой символьный мусор дал бы «ответ».
        if ($this->letterRatio($text) < self::ROT47_MAX_LETTER_RATIO) {
            $decoded = $this->rot47->process($text);
            if ($decoded !== $text) {
                $scoreAlphabet = $this->pickAlphabet(null, $decoded);
                $readability   = $this->scoreReadability($decoded, $scoreAlphabet);
                if ($readability >= self::ROT47_MIN_READABILITY) {
                    $answers[] = new SolverResult(
                        toolSlug: 'codes-and-alphabets/rot47',
                        cipherKey: 'CIPHER_NAME_ROT47',
                        plaintext: $decoded,
                        keyLabel: null,
                        readability: $readability,
                        readabilityPct: (int) round($readability * 100),
                        viaAction: 'rot47',
                        detectedAlphabet: $scoreAlphabet,
                    );
                }
            }
        }

        $answers = $this->dedupeByPlaintext($answers);

        usort($answers, static fn (SolverResult $a, SolverResult $b): int => $b->readability <=> $a->readability);

        $answers = array_slice($answers, 0, self::MAX_ANSWERS);

        return [
            'answers'    => $answers,
            'best'       => $answers[0] ?? null,
            'detections' => $detections,
        ];
    }

    /**
     * Строит SolverResult'ы из списка plaintext-кандидатов и добавляет их в аккумулятор.
     *
     * @param SolverResult[]                                     $answers    Аккумулятор (по ссылке).
     * @param array<int, array{text: string, key: string|null}> $plaintexts Расшифровки-кандидаты.
     */
    private function appendAnswers(array &$answers, array $plaintexts, string $toolSlug, string $cipherKey, ?string $viaAction, ?string $detectedAlphabet): void
    {
        foreach ($plaintexts as $candidate) {
            $plaintext = $candidate['text'];
            if ($plaintext === '') {
                continue;
            }

            $scoreAlphabet = $this->pickAlphabet($detectedAlphabet, $plaintext);
            $readability   = $this->scoreReadability($plaintext, $scoreAlphabet);

            $answers[] = new SolverResult(
                toolSlug: $toolSlug,
                cipherKey: $cipherKey,
                plaintext: $plaintext,
                keyLabel: $candidate['key'],
                readability: $readability,
                readabilityPct: (int) round($readability * 100),
                viaAction: $viaAction,
                detectedAlphabet: $scoreAlphabet,
            );
        }
    }

    /**
     * Запускает brute-force/cracker детекции и извлекает plaintext-кандидатов.
     * Любая ошибка исполнения гасится: солвер продолжает с остальными кандидатами.
     *
     * @return array<int, array{text: string, key: string|null}>
     */
    private function runAction(string $action, string $text, ?string $alphabet, ApiCipherToolExecutorInterface $executor): array
    {
        try {
            $result = $executor->execute($action, [
                'text'     => $text,
                'settings' => ['alphabet' => $alphabet ?? 'auto'],
            ]);
        } catch (\Throwable) {
            return [];
        }

        return $this->extractPlaintexts($action, $result);
    }

    /**
     * Нормализует ответ любого brute-force/cracker-инструмента к списку
     * plaintext-кандидатов с меткой ключа. Логика ранее жила на фронте
     * (`cipher-identifier.js`) и теперь централизована в PHP для переиспользования.
     *
     * @param  array<string, mixed> $result
     * @return array<int, array{text: string, key: string|null}>
     */
    private function extractPlaintexts(string $action, array $result): array
    {
        // Единый ключ большинства cracker'ов (vigenere-cracker, affine-brute-force).
        if (isset($result['decrypted']) && is_string($result['decrypted']) && $result['decrypted'] !== '') {
            return [['text' => $result['decrypted'], 'key' => $this->keyLabel($action, $result)]];
        }

        // caesar-brute-force: лучшая строка выбирается по best_shift.
        if ($action === 'caesar-brute-force') {
            $results = is_array($result['results'] ?? null) ? $result['results'] : [];
            $best    = (int) ($result['best_shift'] ?? -1);
            if ($best >= 0 && isset($results[$best]['text']) && is_string($results[$best]['text'])) {
                return [['text' => $results[$best]['text'], 'key' => 'shift=' . $best]];
            }
        }

        // Обобщённый список результатов (results[0] — лучший).
        if (isset($result['results'][0]['text']) && is_string($result['results'][0]['text'])) {
            return [['text' => $result['results'][0]['text'], 'key' => $this->keyLabel($action, $result)]];
        }

        // Простые дешифраторы (atbash, rot13): единственная строка.
        if (isset($result['result']) && is_string($result['result']) && $result['result'] !== '') {
            return [['text' => $result['result'], 'key' => null]];
        }

        return [];
    }

    /**
     * Формирует человекочитаемую метку ключа по action и ответу инструмента.
     *
     * @param array<string, mixed> $result Ответ инструмента.
     */
    private function keyLabel(string $action, array $result): ?string
    {
        if ($action === 'vigenere-cracker') {
            $key = (string) ($result['key'] ?? '');
            return $key !== '' ? 'key=' . $key : null;
        }
        if ($action === 'caesar-brute-force') {
            $shift = $result['best_shift'] ?? null;
            return is_numeric($shift) ? 'shift=' . (int) $shift : null;
        }
        if ($action === 'xor-brute-force') {
            $key = (string) ($result['key'] ?? '');
            return $key !== '' ? 'key=' . $key : null;
        }

        $key = $result['key'] ?? null;
        return (is_string($key) && $key !== '') ? $key : null;
    }

    /**
     * Выбирает алфавит для оценки читаемости: приоритет — алфавит из детекции,
     * иначе автоопределение по самому plaintext'у.
     */
    private function pickAlphabet(?string $detectedAlphabet, string $plaintext): string
    {
        if ($detectedAlphabet !== null && $this->bigramScorer->supports($detectedAlphabet)) {
            return $detectedAlphabet;
        }

        return $this->letterScorer->detectAlphabet($plaintext);
    }

    /**
     * Оценивает читаемость plaintext'а в диапазоне 0.0–1.0.
     *
     * Основной сигнал — биграммное лог-правдоподобие языка, усиленное бонусом за
     * найденные характерные триграммы/слова. Если для алфавита нет биграммной
     * таблицы либо букв слишком мало, используется грубая эвристика по доле
     * буквенных символов с потолком ниже уровня языково-оценённых расшифровок.
     */
    private function scoreReadability(string $plaintext, string $alphabet): float
    {
        if (
            $this->bigramScorer->supports($alphabet)
            && $this->letterScorer->countLetters($plaintext, $alphabet) >= self::MIN_LETTERS_FOR_BIGRAM
        ) {
            $bigram = $this->bigramScorer->score($plaintext, $alphabet);
            $ngram  = $this->bigramScorer->commonNgramMatches($plaintext, $alphabet);

            // Биграммный скор ≈[-2.5, 2.5] → [0, 1]; попадания слов дают до +0.25.
            $bigramTerm = 0.5 + $bigram / 5.0;

            // Триграммы (где есть таблица) резче отделяют осмысленный текст от шума
            // на коротких строках. Скор ≈[-3, 2] → [0, 1]; блендим поровну с биграммами.
            if ($this->trigramScorer->supports($alphabet)) {
                $trigram     = $this->trigramScorer->score($plaintext, $alphabet);
                $trigramTerm = 0.5 + $trigram / 6.0;
                $readability = 0.5 * $bigramTerm + 0.5 * $trigramTerm;
            } else {
                $readability = $bigramTerm;
            }

            $readability += min($ngram, 5) * 0.05;

            return $this->clamp01($readability);
        }

        return $this->clamp01($this->letterRatio($plaintext) * 0.45);
    }

    /**
     * Доля буквенных символов Unicode в строке (грубый признак «текстовости»).
     */
    private function letterRatio(string $plaintext): float
    {
        $chars = preg_split('//u', $plaintext, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $total = count($chars);
        if ($total === 0) {
            return 0.0;
        }

        $letters = 0;
        foreach ($chars as $char) {
            if (preg_match('/\p{L}/u', $char) === 1) {
                $letters++;
            }
        }

        return $letters / $total;
    }

    /**
     * Убирает дубли расшифровок по нормализованному тексту, оставляя лучший
     * по читаемости. Разные шифры нередко дают одинаковый plaintext (например,
     * ROT13 и Caesar со сдвигом 13) — показываем его один раз.
     *
     * @param  SolverResult[] $answers
     * @return SolverResult[]
     */
    private function dedupeByPlaintext(array $answers): array
    {
        $best = [];
        foreach ($answers as $answer) {
            $key = mb_strtolower(trim($answer->plaintext));
            if (!isset($best[$key]) || $answer->readability > $best[$key]->readability) {
                $best[$key] = $answer;
            }
        }

        return array_values($best);
    }

    /**
     * Ограничивает значение диапазоном [0.0, 1.0].
     */
    private function clamp01(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }

    /**
     * Возвращает UI-настройки инструмента для ToolRegistry: выбор алфавита.
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
            trans('SOLVER_TRUST_AUTO'),
            trans('SOLVER_TRUST_RANKED'),
            trans('CIPHER_TOOL_TRUST_LOCAL'),
        ];
    }
}
