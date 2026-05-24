<?php

declare(strict_types=1);

namespace App\Queue;

use App\Database\Database;
use App\Database\Tables;
use Throwable;

/**
 * Менеджер очереди задач на базе данных.
 *
 * Поддерживает SQLite (через BEGIN IMMEDIATE) и MySQL (через SELECT ... FOR UPDATE SKIP LOCKED).
 * Payload — это serialize() самой задачи; десериализация выполняется воркером перед handle().
 */
final class QueueManager
{
    /** @var string Имя очереди по умолчанию. */
    public const string DEFAULT_QUEUE = 'default';

    /**
     * Создаёт менеджер очереди.
     *
     * @param array<string, mixed> $config Конфигурация очереди (см. config/queue.php).
     */
    public function __construct(
        private readonly Database $db,
        private readonly array $config = []
    ) {
    }

    /**
     * Помещает задачу в очередь.
     *
     * @param  JobInterface $job   Задача для исполнения.
     * @param  int          $delay Задержка перед доступностью задачи, секунды.
     * @param  string|null  $queue Имя очереди (по умолчанию — DEFAULT_QUEUE).
     * @return int                 ID созданной записи в таблице jobs.
     */
    public function push(JobInterface $job, int $delay = 0, ?string $queue = null): int
    {
        $now = time();
        $queueName = $queue ?? self::DEFAULT_QUEUE;

        $id = $this->db->insert(
            'INSERT INTO ' . Tables::JOBS
            . ' (queue, payload, attempts, available_at, reserved_at, created_at)'
            . ' VALUES (?, ?, 0, ?, NULL, ?)',
            [$queueName, serialize($job), $now + max(0, $delay), $now]
        );

        return (int) $id;
    }

    /**
     * Извлекает и резервирует следующую доступную задачу из очереди.
     *
     * Возвращает запись с полями id, queue, payload, attempts либо null,
     * если в очереди нет готовых к исполнению задач.
     *
     * @return array{id: int, queue: string, payload: string, attempts: int}|null
     */
    public function pop(?string $queue = null): ?array
    {
        $queueName = $queue ?? self::DEFAULT_QUEUE;
        $now = time();
        $expiredBefore = $now - $this->retryAfter();
        $driver = (string) config('database.default', 'sqlite');

        return $driver === 'mysql'
            ? $this->popMysql($queueName, $now, $expiredBefore)
            : $this->popSqlite($queueName, $now, $expiredBefore);
    }

    /**
     * Удаляет задачу из очереди после успешного выполнения.
     */
    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM ' . Tables::JOBS . ' WHERE id = ?', [$id]);
    }

    /**
     * Возвращает задачу в очередь для повторной попытки.
     */
    public function release(int $id, int $delay = 0): void
    {
        $this->db->execute(
            'UPDATE ' . Tables::JOBS . ' SET reserved_at = NULL, available_at = ? WHERE id = ?',
            [time() + max(0, $delay), $id]
        );
    }

    /**
     * Перемещает упавшую задачу в failed_jobs и удаляет её из основной очереди.
     */
    public function markFailed(int $id, string $queue, string $payload, Throwable $exception): void
    {
        $this->db->insert(
            'INSERT INTO ' . Tables::FAILED_JOBS . ' (queue, payload, exception, failed_at) VALUES (?, ?, ?, ?)',
            [$queue, $payload, (string) $exception, date('Y-m-d H:i:s')]
        );

        $this->delete($id);
    }

    /**
     * Максимальное количество попыток выполнения задачи.
     */
    public function maxAttempts(): int
    {
        return (int) ($this->config['max_attempts'] ?? 3);
    }

    /**
     * Задержка перед повторным попаданием задачи в очередь, секунды.
     */
    public function retryAfter(): int
    {
        return (int) ($this->config['retry_after'] ?? 90);
    }

    /**
     * Резервирует задачу в MySQL через SELECT ... FOR UPDATE SKIP LOCKED.
     *
     * @return array{id: int, queue: string, payload: string, attempts: int}|null
     */
    private function popMysql(string $queue, int $now, int $expiredBefore): ?array
    {
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'SELECT id, queue, payload, attempts FROM ' . Tables::JOBS
                . ' WHERE queue = ? AND ((reserved_at IS NULL AND available_at <= ?) OR reserved_at <= ?)'
                . ' ORDER BY id ASC LIMIT 1 FOR UPDATE SKIP LOCKED'
            );
            $stmt->execute([$queue, $now, $expiredBefore]);
            $row = $stmt->fetch();

            if ($row === false) {
                $pdo->commit();

                return null;
            }

            $update = $pdo->prepare(
                'UPDATE ' . Tables::JOBS . ' SET reserved_at = ?, attempts = attempts + 1 WHERE id = ?'
            );
            $update->execute([$now, (int) $row['id']]);

            $pdo->commit();

            return [
                'id'       => (int) $row['id'],
                'queue'    => (string) $row['queue'],
                'payload'  => (string) $row['payload'],
                'attempts' => (int) $row['attempts'] + 1,
            ];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Резервирует задачу в SQLite через эксклюзивную транзакцию BEGIN IMMEDIATE.
     *
     * @return array{id: int, queue: string, payload: string, attempts: int}|null
     */
    private function popSqlite(string $queue, int $now, int $expiredBefore): ?array
    {
        $pdo = $this->db->pdo();
        $pdo->exec('BEGIN IMMEDIATE');

        try {
            $stmt = $pdo->prepare(
                'SELECT id, queue, payload, attempts FROM ' . Tables::JOBS
                . ' WHERE queue = ? AND ((reserved_at IS NULL AND available_at <= ?) OR reserved_at <= ?)'
                . ' ORDER BY id ASC LIMIT 1'
            );
            $stmt->execute([$queue, $now, $expiredBefore]);
            $row = $stmt->fetch();

            if ($row === false) {
                $pdo->exec('COMMIT');

                return null;
            }

            $update = $pdo->prepare(
                'UPDATE ' . Tables::JOBS . ' SET reserved_at = ?, attempts = attempts + 1 WHERE id = ?'
            );
            $update->execute([$now, (int) $row['id']]);

            $pdo->exec('COMMIT');

            return [
                'id'       => (int) $row['id'],
                'queue'    => (string) $row['queue'],
                'payload'  => (string) $row['payload'],
                'attempts' => (int) $row['attempts'] + 1,
            ];
        } catch (Throwable $e) {
            $pdo->exec('ROLLBACK');
            throw $e;
        }
    }
}
