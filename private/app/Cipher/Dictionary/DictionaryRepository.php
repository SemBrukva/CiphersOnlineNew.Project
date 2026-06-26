<?php

declare(strict_types=1);

namespace App\Cipher\Dictionary;

use PDO;
use RuntimeException;

/**
 * Открывает словарь языка как SQLite-хранилище.
 *
 * Файлы словарей хранятся как `{baseDirectory}/{lang}.sqlite` и содержат
 * таблицу `words(signature, word, length)` с индексами по signature и length.
 * Подключение PDO кешируется на время жизни процесса.
 */
class DictionaryRepository
{
    /** @var array<string, DictionaryStore> Кеш открытых подключений на запрос. */
    private array $instances = [];

    /**
     * Создаёт репозиторий.
     */
    public function __construct(private readonly string $baseDirectory)
    {
    }

    /**
     * Возвращает абсолютный путь к файлу словаря для языка.
     */
    public function pathFor(string $language): string
    {
        return rtrim($this->baseDirectory, '/') . '/' . $language . '.sqlite';
    }

    /**
     * Проверяет, есть ли построенный SQLite-словарь для языка.
     */
    public function hasIndex(string $language): bool
    {
        return is_file($this->pathFor($language));
    }

    /**
     * Открывает SQLite-словарь и возвращает {@see DictionaryStore}.
     *
     * @throws RuntimeException если файл словаря не существует.
     */
    public function load(string $language): DictionaryStore
    {
        if (isset($this->instances[$language])) {
            return $this->instances[$language];
        }

        $path = $this->pathFor($language);
        if (!is_file($path)) {
            throw new RuntimeException(sprintf(
                'Dictionary index for language "%s" is not built. Run: php bin/console dictionary:build %s',
                $language,
                $language,
            ));
        }

        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $this->instances[$language] = new SqliteDictionaryStore($pdo);
    }
}
