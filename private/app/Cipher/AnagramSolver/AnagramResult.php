<?php

declare(strict_types=1);

namespace App\Cipher\AnagramSolver;

/**
 * Результат запроса к {@see AnagramEngine}.
 *
 * Для одиночных режимов (anagram / word-finder / pattern) используется `results`,
 * для multi-word — `phrases`.
 */
final readonly class AnagramResult
{
    /**
     * Создаёт результат.
     *
     * @param string                                                        $mode        Имя режима (`anagram`/`word-finder`/`pattern`/`multi-word`).
     * @param list<array{word: string, length: int, score: int}>            $results     Список найденных слов (для одиночных режимов).
     * @param list<array{words: list<string>, length: int, score: int}>     $phrases     Список фраз (для multi-word).
     * @param int                                                           $totalFound  Полное количество совпадений до лимита.
     * @param bool                                                          $truncated   Был ли результат усечён.
     * @param string                                                        $language    Код языка словаря.
     */
    public function __construct(
        public string $mode,
        public array $results,
        public array $phrases,
        public int $totalFound,
        public bool $truncated,
        public string $language,
    ) {
    }

    /**
     * Преобразует результат в массив для JSON-ответа API.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'ok'         => true,
            'mode'       => $this->mode,
            'language'   => $this->language,
            'totalFound' => $this->totalFound,
            'truncated'  => $this->truncated,
        ];

        if ($this->mode === AnagramEngine::MODE_MULTI_WORD) {
            $payload['phrases'] = $this->phrases;
        } else {
            $payload['results'] = $this->results;
        }

        return $payload;
    }
}
