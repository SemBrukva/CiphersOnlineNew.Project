<?php

declare(strict_types=1);

namespace App\Cipher\Dictionary;

/**
 * Контракт хранилища словаря для поиска анаграмм.
 *
 * Реализации не обязаны держать словарь целиком в памяти — методы
 * могут читать данные потоково (например, через PDO-курсор).
 */
interface DictionaryStore
{
    /**
     * Возвращает все слова с заданной сигнатурой.
     *
     * @return list<string>
     */
    public function wordsForSignature(string $signature): array;

    /**
     * Перебирает сигнатуры слов указанной длины в лексикографическом порядке.
     *
     * @return iterable<int, string>
     */
    public function signaturesOfLength(int $length): iterable;
}
