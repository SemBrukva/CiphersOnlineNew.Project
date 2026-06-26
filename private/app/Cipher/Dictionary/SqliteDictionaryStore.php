<?php

declare(strict_types=1);

namespace App\Cipher\Dictionary;

use Generator;
use PDO;

/**
 * Реализация {@see DictionaryStore} поверх SQLite-файла.
 *
 * Ожидаемая схема файла:
 *   CREATE TABLE words (signature TEXT NOT NULL, word TEXT NOT NULL, length INTEGER NOT NULL);
 *   CREATE INDEX idx_words_sig ON words(signature);
 *   CREATE INDEX idx_words_len ON words(length, signature);
 */
final class SqliteDictionaryStore implements DictionaryStore
{
    private ?\PDOStatement $wordsStmt = null;
    private ?\PDOStatement $sigsStmt  = null;

    /**
     * Создаёт хранилище поверх готового PDO-подключения к SQLite.
     */
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * {@inheritDoc}
     */
    public function wordsForSignature(string $signature): array
    {
        if ($signature === '') {
            return [];
        }
        $this->wordsStmt ??= $this->pdo->prepare(
            'SELECT word FROM words WHERE signature = ? ORDER BY word'
        );
        $this->wordsStmt->execute([$signature]);

        /** @var list<string> $rows */
        $rows = $this->wordsStmt->fetchAll(PDO::FETCH_COLUMN);
        $this->wordsStmt->closeCursor();

        return $rows;
    }

    /**
     * {@inheritDoc}
     *
     * @return Generator<int, string>
     */
    public function signaturesOfLength(int $length): Generator
    {
        if ($length <= 0) {
            return;
        }
        $this->sigsStmt ??= $this->pdo->prepare(
            'SELECT DISTINCT signature FROM words WHERE length = ? ORDER BY signature'
        );
        $this->sigsStmt->execute([$length]);

        try {
            while (($signature = $this->sigsStmt->fetchColumn()) !== false) {
                yield (string) $signature;
            }
        } finally {
            $this->sigsStmt->closeCursor();
        }
    }
}
