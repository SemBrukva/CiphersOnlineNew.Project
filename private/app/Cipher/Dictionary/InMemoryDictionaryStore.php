<?php

declare(strict_types=1);

namespace App\Cipher\Dictionary;

/**
 * Реализация {@see DictionaryStore} поверх in-memory массива.
 *
 * Используется в юнит-тестах: позволяет собирать словарь прямо в коде теста,
 * без обращения к файловой системе или базе данных.
 */
final readonly class InMemoryDictionaryStore implements DictionaryStore
{
    /** @var array<int, list<string>> Кеш сигнатур по длине. */
    private array $byLength;

    /**
     * Создаёт хранилище из карты сигнатура → слова.
     *
     * @param array<string, list<string>> $bySignature
     */
    public function __construct(private array $bySignature)
    {
        $byLength = [];
        foreach (array_keys($bySignature) as $signature) {
            $byLength[mb_strlen($signature)][] = $signature;
        }
        foreach ($byLength as &$signatures) {
            sort($signatures, SORT_STRING);
        }
        unset($signatures);
        ksort($byLength, SORT_NUMERIC);

        $this->byLength = $byLength;
    }

    /**
     * {@inheritDoc}
     */
    public function wordsForSignature(string $signature): array
    {
        return $this->bySignature[$signature] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function signaturesOfLength(int $length): iterable
    {
        return $this->byLength[$length] ?? [];
    }
}
