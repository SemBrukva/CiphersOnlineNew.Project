<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Database\Database;
use App\Database\Tables;

/**
 * Консольная команда повторного добавления упавших задач в очередь.
 *
 * Использование:
 *   php bin/console queue:retry all          # ретрай всех упавших задач
 *   php bin/console queue:retry <id> [<id>]  # ретрай по идентификаторам
 */
final readonly class QueueRetryCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Database $db)
    {
    }

    /**
     * Перемещает указанные failed_jobs обратно в jobs.
     *
     * @param string[] $args
     */
    public function handle(array $args): int
    {
        if ($args === []) {
            echo 'Usage: php bin/console queue:retry all|<id> [<id> ...]' . PHP_EOL;

            return 1;
        }

        $rows = $args[0] === 'all'
            ? $this->db->fetchAll('SELECT * FROM ' . Tables::FAILED_JOBS . ' ORDER BY id')
            : $this->db->fetchAll(
                'SELECT * FROM ' . Tables::FAILED_JOBS . ' WHERE id IN (' . $this->placeholders($args) . ')',
                array_map('intval', $args)
            );

        if ($rows === []) {
            echo 'No failed jobs to retry.' . PHP_EOL;

            return 0;
        }

        $now = time();
        $count = 0;

        foreach ($rows as $row) {
            $this->db->insert(
                'INSERT INTO ' . Tables::JOBS
                . ' (queue, payload, attempts, available_at, reserved_at, created_at)'
                . ' VALUES (?, ?, 0, ?, NULL, ?)',
                [(string) $row['queue'], (string) $row['payload'], $now, $now]
            );

            $this->db->execute(
                'DELETE FROM ' . Tables::FAILED_JOBS . ' WHERE id = ?',
                [(int) $row['id']]
            );

            echo "Re-queued failed job #{$row['id']} (queue={$row['queue']})" . PHP_EOL;
            $count++;
        }

        echo "Re-queued {$count} job(s)." . PHP_EOL;

        return 0;
    }

    /**
     * Возвращает строку плейсхолдеров `?` по числу элементов в массиве.
     *
     * @param string[] $values
     */
    private function placeholders(array $values): string
    {
        return implode(',', array_fill(0, count($values), '?'));
    }
}
