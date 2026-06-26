<?php

declare(strict_types=1);

namespace App\Cipher;

use App\Cipher\AnagramSolver\AnagramEngine;
use App\Cipher\AnagramSolver\AnagramOptions;
use App\Cipher\Dictionary\DictionaryRepository;
use App\Http\Exception\ValidationFailedException;
use RuntimeException;

/**
 * API-инструмент поиска анаграмм для произвольной строки/шаблона.
 *
 * Поддерживает четыре режима:
 *   anagram      — строгая анаграмма (использует все буквы);
 *   word-finder  — Scrabble-стиль (подмножество букв);
 *   pattern      — шаблон с `?`-wildcard;
 *   multi-word   — фраза из 2–3 слов.
 */
final readonly class AnagramSolverApiCipherTool implements ApiCipherToolInterface
{
    /** Максимальная длина исходного текста. */
    public const int MAX_TEXT_LENGTH = 64;

    /** @var list<string> Поддерживаемые языки словарей. */
    private const array SUPPORTED_LANGUAGES = ['en', 'ru', 'es', 'pt', 'tr', 'fr', 'de', 'it'];

    /** @var list<string> Поддерживаемые режимы поиска. */
    private const array SUPPORTED_MODES = [
        AnagramEngine::MODE_ANAGRAM,
        AnagramEngine::MODE_WORD_FINDER,
        AnagramEngine::MODE_PATTERN,
        AnagramEngine::MODE_MULTI_WORD,
    ];

    /**
     * Создаёт инструмент.
     */
    public function __construct(
        private AnagramEngine $engine,
        private DictionaryRepository $dictionaries,
        private LetterFrequencyScorer $scorer,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function action(): string
    {
        return 'anagram-solver';
    }

    /**
     * Выполняет поиск и возвращает результат для JSON-ответа.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function execute(array $payload): array
    {
        $text     = trim((string) ($payload['text'] ?? ''));
        $settings = is_array($payload['settings'] ?? null) ? $payload['settings'] : [];
        $mode     = mb_strtolower(trim((string) ($settings['anagram_mode'] ?? AnagramEngine::MODE_ANAGRAM)));
        $language = mb_strtolower(trim((string) ($settings['alphabet'] ?? 'en')));

        $errors = [];
        if ($text === '') {
            $errors['text'][] = trans('ANAGRAM_ERR_TEXT_REQUIRED');
        } elseif (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            $errors['text'][] = trans('ANAGRAM_ERR_TEXT_TOO_LONG');
        }
        if (!in_array($mode, self::SUPPORTED_MODES, true)) {
            $errors['settings.anagram_mode'][] = trans('ANAGRAM_ERR_MODE_UNSUPPORTED');
        }
        if (!in_array($language, self::SUPPORTED_LANGUAGES, true)) {
            $errors['settings.alphabet'][] = trans('ANAGRAM_ERR_LANG_UNSUPPORTED');
        }

        if ($errors !== []) {
            throw new ValidationFailedException(trans('ANAGRAM_ERR_INVALID'), ['errors' => $errors]);
        }

        if (!$this->dictionaries->hasIndex($language)) {
            throw new ValidationFailedException(trans('ANAGRAM_ERR_DICT_UNAVAILABLE'), [
                'errors' => ['settings.alphabet' => [trans('ANAGRAM_ERR_DICT_UNAVAILABLE')]],
            ]);
        }

        $options = $this->buildOptions($settings, $mode);

        try {
            $result = match ($mode) {
                AnagramEngine::MODE_ANAGRAM     => $this->engine->findAnagrams($text, $language, $options),
                AnagramEngine::MODE_WORD_FINDER => $this->engine->findSubAnagrams($text, $language, $options),
                AnagramEngine::MODE_PATTERN     => $this->engine->findByPattern($text, $language, $options),
                AnagramEngine::MODE_MULTI_WORD  => $this->engine->findMultiWord($text, $language, $options),
                default                          => throw new RuntimeException('Unknown anagram mode'),
            };
        } catch (RuntimeException $exception) {
            throw new ValidationFailedException($exception->getMessage(), [
                'errors' => ['settings.alphabet' => [$exception->getMessage()]],
            ]);
        }

        $payload = $result->toArray();
        if ($this->hasAlphabetMismatch($text, $language, $mode)) {
            $payload['warning'] = trans('ANAGRAM_WARN_ALPHABET_MISMATCH');
        }

        return $payload;
    }

    /**
     * Возвращает true, если в тексте меньше половины букв принадлежит выбранному
     * алфавиту. Pattern-режим игнорируется (там много `?` и мало букв).
     */
    private function hasAlphabetMismatch(string $text, string $language, string $mode): bool
    {
        if ($mode === AnagramEngine::MODE_PATTERN) {
            return false;
        }

        $totalLetters = preg_match_all('/\p{L}/u', $text);
        if ($totalLetters === false || $totalLetters < 3) {
            return false;
        }

        $covered = $this->scorer->countLetters($text, $language);

        return $covered / $totalLetters < 0.5;
    }

    /**
     * Собирает опции поиска из массива настроек запроса.
     *
     * @param array<string, mixed> $settings
     */
    private function buildOptions(array $settings, string $mode): AnagramOptions
    {
        $minLength  = (int) ($settings['min_length']  ?? ($mode === AnagramEngine::MODE_MULTI_WORD ? 3 : 2));
        $maxLength  = (int) ($settings['max_length']  ?? 0);
        $startsWith = mb_strtolower(trim((string) ($settings['starts_with'] ?? '')));
        $endsWith   = mb_strtolower(trim((string) ($settings['ends_with']   ?? '')));
        $contains   = mb_strtolower(trim((string) ($settings['contains']    ?? '')));
        $maxResults = (int) ($settings['max_results'] ?? 200);
        $maxWords   = (int) ($settings['max_words']   ?? 2);
        $sort       = (string) ($settings['sort']     ?? AnagramOptions::SORT_LENGTH);

        return new AnagramOptions(
            minLength: $minLength,
            maxLength: $maxLength,
            startsWith: $startsWith,
            endsWith: $endsWith,
            contains: $contains,
            maxResults: $maxResults,
            maxWords: $maxWords,
            sort: $sort,
        );
    }
}
