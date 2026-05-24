<?php

declare(strict_types=1);

namespace App\Database;

use App\Http\RequestContext;
use PDO;
use PDOStatement;
use Throwable;

/**
 * Обёртка над PDO с ленивым подключением и удобными методами выборки.
 *
 * Соединение устанавливается при первом запросе и кэшируется на всё время жизни объекта.
 * Поддерживает драйверы SQLite и MySQL/MariaDB.
 */
final class Database
{
    /** @var PDO|null Экземпляр PDO; null до первого обращения к базе. */
    private ?PDO $pdo = null;
    /** @var array<int, array<string, mixed>> Лог выполненных SQL-запросов за текущий запрос. */
    private array $queryLog = [];

    /**
     * Создаёт экземпляр базы данных.
     *
     * @param array<string, mixed> $config Конфигурация подключения из config/database.php.
     */
    public function __construct(
        private readonly array $config,
        private readonly RequestContext $context
    ) {
    }

    /**
     * Возвращает PDO-соединение, создавая его при первом вызове.
     *
     * Сделан публичным, чтобы низкоуровневые компоненты (например, воркер очереди)
     * могли использовать прямой контроль над транзакциями и блокировками строк.
     */
    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->connect();
        }

        return $this->pdo;
    }

    /**
     * Устанавливает PDO-соединение в зависимости от драйвера из конфигурации.
     */
    private function connect(): PDO
    {
        $c = $this->config;

        if ($c['driver'] === 'sqlite') {
            return new PDO("sqlite:{$c['database']}", options: $c['options'] ?? []);
        }

        $dsn = "{$c['driver']}:host={$c['host']};port={$c['port']};dbname={$c['database']};charset={$c['charset']}";

        return new PDO($dsn, $c['username'], $c['password'], $c['options'] ?? []);
    }

    /**
     * Подготавливает и выполняет SQL-запрос с привязкой параметров.
     *
     * @param array<int|string, mixed> $bindings Параметры для привязки к запросу.
     */
    private function run(string $sql, array $bindings): PDOStatement
    {
        $startedAt = microtime(true);
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($bindings);
        $elapsedMs = (microtime(true) - $startedAt) * 1000;

        $this->queryLog[] = [
            'sql'            => $sql,
            'bindings'       => $bindings,
            'execution_time' => round($elapsedMs, 3),
            'offset_ms'      => $this->context->offsetMs($startedAt),
        ];

        return $stmt;
    }

    /**
     * Возвращает первую строку результата запроса или false, если строк нет.
     *
     * @param  array<int|string, mixed> $bindings
     * @return array<string, mixed>|false
     */
    public function fetch(string $sql, array $bindings = []): array|false
    {
        return $this->run($sql, $bindings)->fetch();
    }

    /**
     * Возвращает все строки результата запроса.
     *
     * @param  array<int|string, mixed>   $bindings
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $bindings = []): array
    {
        return $this->run($sql, $bindings)->fetchAll();
    }

    /**
     * Выполняет запрос без выборки и возвращает количество затронутых строк.
     *
     * @param array<int|string, mixed> $bindings
     */
    public function execute(string $sql, array $bindings = []): int
    {
        return $this->run($sql, $bindings)->rowCount();
    }

    /**
     * Выполняет INSERT-запрос и возвращает ID последней вставленной строки.
     *
     * @param  array<int|string, mixed> $bindings
     */
    public function insert(string $sql, array $bindings = []): string
    {
        $this->run($sql, $bindings);

        return $this->pdo()->lastInsertId();
    }

    /**
     * Выполняет $callback внутри транзакции.
     * При исключении откатывает транзакцию и пробрасывает его дальше.
     *
     * @throws Throwable
     */
    public function transaction(callable $callback): mixed
    {
        $this->pdo()->beginTransaction();

        try {
            $result = $callback($this);
            $this->pdo()->commit();

            return $result;
        } catch (Throwable $e) {
            $this->pdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Возвращает лог SQL-запросов, выполненных в рамках текущего запроса.
     *
     * @return array<int, array{sql: string, bindings: array<int|string, mixed>, execution_time: float}>
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Возвращает суммарное время выполнения всех SQL-запросов в миллисекундах.
     */
    public function getTotalQueryTimeMs(): float
    {
        $sum = 0.0;

        foreach ($this->queryLog as $query) {
            $sum += (float) $query['execution_time'];
        }

        return round($sum, 3);
    }
}
