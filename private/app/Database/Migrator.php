<?php

declare(strict_types=1);

namespace App\Database;

/**
 * Управляет выполнением и откатом миграций базы данных.
 *
 * Отслеживает применённые миграции в служебной таблице,
 * группирует их в пакеты для атомарного отката.
 */
final class Migrator
{
    /** @var string Имя служебной таблицы для хранения истории миграций. */
    private const string TABLE = 'migrations';

    /**
     * Создаёт экземпляр мигратора.
     *
     * @param Database $db   Подключение к базе данных.
     * @param string   $path Абсолютный путь к директории с файлами миграций.
     */
    public function __construct(
        private readonly Database $db,
        private readonly string   $path
    ) {
    }

    /**
     * Применяет все ожидающие миграции в хронологическом порядке.
     *
     * @return string[] Список имён применённых миграций.
     */
    public function run(): array
    {
        $this->ensureTable();

        $pending = $this->getPending();

        if (empty($pending)) {
            return [];
        }

        $batch = $this->getNextBatch();
        $ran   = [];

        foreach ($pending as $name) {
            $this->resolve($name)->up();

            $this->db->insert(
                'INSERT INTO ' . self::TABLE . ' (migration, batch) VALUES (?, ?)',
                [$name, $batch]
            );

            $ran[] = $name;
        }

        return $ran;
    }

    /**
     * Откатывает все миграции последнего пакета в обратном порядке.
     *
     * @return string[] Список имён откаченных миграций.
     */
    public function rollback(): array
    {
        $this->ensureTable();

        $batch = $this->getLastBatch();

        if ($batch === 0) {
            return [];
        }

        $rows = $this->db->fetchAll(
            'SELECT migration FROM ' . self::TABLE . ' WHERE batch = ? ORDER BY id DESC',
            [$batch]
        );

        $rolled = [];

        foreach ($rows as $row) {
            $this->resolve($row['migration'])->down();

            $this->db->execute(
                'DELETE FROM ' . self::TABLE . ' WHERE migration = ?',
                [$row['migration']]
            );

            $rolled[] = $row['migration'];
        }

        return $rolled;
    }

    /**
     * Возвращает статус всех миграций: применена или ожидает.
     *
     * @return array<array{migration: string, ran: bool, batch: int|null}>
     */
    public function status(): array
    {
        $this->ensureTable();

        $ran    = $this->db->fetchAll('SELECT migration, batch FROM ' . self::TABLE . ' ORDER BY id');
        $ranMap = array_column($ran, 'batch', 'migration');

        return array_map(
            static fn (string $name): array => [
                'migration' => $name,
                'ran'       => isset($ranMap[$name]),
                'batch'     => $ranMap[$name] ?? null,
            ],
            $this->getAll()
        );
    }

    /**
     * Создаёт служебную таблицу migrations, если она ещё не существует.
     */
    private function ensureTable(): void
    {
        $driver = config('database.default', 'sqlite');

        if ($driver === 'sqlite') {
            $this->db->execute('
                CREATE TABLE IF NOT EXISTS ' . self::TABLE . ' (
                    id        INTEGER PRIMARY KEY AUTOINCREMENT,
                    migration TEXT    NOT NULL,
                    batch     INTEGER NOT NULL
                )
            ');
        } else {
            $this->db->execute('
                CREATE TABLE IF NOT EXISTS `' . self::TABLE . '` (
                    `id`        int(11)      NOT NULL AUTO_INCREMENT,
                    `migration` varchar(255) NOT NULL,
                    `batch`     int(11)      NOT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ');
        }
    }

    /**
     * Возвращает список миграций, которые ещё не были применены.
     *
     * @return string[]
     */
    private function getPending(): array
    {
        $ran = array_column(
            $this->db->fetchAll('SELECT migration FROM ' . self::TABLE),
            'migration'
        );

        return array_values(array_diff($this->getAll(), $ran));
    }

    /**
     * Возвращает отсортированный список всех файлов миграций (без расширения .php).
     *
     * @return string[]
     */
    private function getAll(): array
    {
        $files = glob($this->path . '/*.php') ?: [];
        sort($files);

        return array_map(static fn (string $f): string => basename($f, '.php'), $files);
    }

    /**
     * Возвращает номер следующего пакета миграций.
     */
    private function getNextBatch(): int
    {
        $row = $this->db->fetch('SELECT MAX(batch) AS max_batch FROM ' . self::TABLE);

        return (int) ($row['max_batch'] ?? 0) + 1;
    }

    /**
     * Возвращает номер последнего применённого пакета.
     * Возвращает 0, если миграций ещё не было.
     */
    private function getLastBatch(): int
    {
        $row = $this->db->fetch('SELECT MAX(batch) AS max_batch FROM ' . self::TABLE);

        return (int) ($row['max_batch'] ?? 0);
    }

    /**
     * Загружает файл миграции и создаёт экземпляр её класса.
     *
     * Имя класса выводится из части описания в имени файла:
     * 2026_05_21_000001_create_users_table → CreateUsersTable
     */
    private function resolve(string $name): Migration
    {
        require_once $this->path . '/' . $name . '.php';

        $parts     = explode('_', $name, 5);
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $parts[4] ?? $name)));

        return new $className($this->db);
    }
}
