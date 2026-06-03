<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Database\Database;
use PDO;
use Throwable;

/**
 * Консольная команда переноса данных из SQLite в текущую MySQL-базу.
 */
final readonly class DbImportSqliteCommand implements CommandInterface
{
    /** @var int Размер пачки строк при чтении из SQLite. */
    private const int DEFAULT_BATCH_SIZE = 500;

    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Database $db)
    {
    }

    /**
     * Переносит данные из SQLite-файла в активное MySQL-подключение.
     *
     * @param string[] $args Аргументы команды.
     */
    public function handle(array $args): int
    {
        if (in_array('--help', $args, true) || in_array('-h', $args, true)) {
            $this->printUsage();

            return 0;
        }

        $options = $this->parseArgs($args);
        $sourcePath = (string) ($options['source'] ?? $this->defaultSourcePath());

        if (!is_file($sourcePath) || !is_readable($sourcePath)) {
            echo 'SQLite-файл не найден или недоступен для чтения: ' . $sourcePath . PHP_EOL;

            return 1;
        }

        if ((string) config('database.default', 'sqlite') !== 'mysql') {
            echo 'Активное подключение должно быть MySQL. Установите DB_CONNECTION=mysql в env.' . PHP_EOL;

            return 1;
        }

        try {
            $source = $this->openSqlite($sourcePath);
            $tables = $this->resolveTables($source, $options);

            if ($tables === []) {
                echo 'Нет общих таблиц для импорта между SQLite и MySQL.' . PHP_EOL;

                return 0;
            }

            $stats = $this->tableStats($source, $tables);
            $this->printPlan($sourcePath, $stats, (bool) $options['clear']);

            if (!(bool) $options['force']) {
                echo PHP_EOL . 'Dry-run: данные не изменены. Для реального импорта добавьте --force.' . PHP_EOL;

                return 0;
            }

            $imported = $this->import($source, $tables, (bool) $options['clear'], (int) $options['batch']);

            echo PHP_EOL . "Импорт завершён: {$imported} строк." . PHP_EOL;

            return 0;
        } catch (Throwable $e) {
            echo 'Ошибка импорта: ' . $e->getMessage() . PHP_EOL;

            return 1;
        }
    }

    /**
     * Выводит краткую справку по команде.
     */
    private function printUsage(): void
    {
        echo <<<'TXT'
Usage:
  php bin/console db:import-sqlite [path] [--force] [--clear] [--tables=a,b] [--except=a,b] [--batch=500]

Options:
  path, --source=path  Путь к SQLite-файлу. По умолчанию берётся sqlite.database из конфига.
  --force             Выполнить импорт. Без флага команда показывает только dry-run.
  --clear             Очистить целевые MySQL-таблицы перед импортом.
  --tables=a,b        Импортировать только указанные таблицы.
  --except=a,b        Исключить указанные таблицы.
  --batch=500         Размер пачки строк при чтении из SQLite.

TXT;
    }

    /**
     * Разбирает аргументы командной строки.
     *
     * @param  string[]              $args
     * @return array<string, mixed>
     */
    private function parseArgs(array $args): array
    {
        $options = [
            'source' => null,
            'force' => false,
            'clear' => false,
            'tables' => [],
            'except' => [],
            'batch' => self::DEFAULT_BATCH_SIZE,
        ];

        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];

            if ($arg === '--force') {
                $options['force'] = true;
                continue;
            }

            if ($arg === '--clear') {
                $options['clear'] = true;
                continue;
            }

            if ($arg === '--source' && isset($args[$i + 1])) {
                $options['source'] = $args[++$i];
                continue;
            }

            if (str_starts_with($arg, '--source=')) {
                $options['source'] = substr($arg, 9);
                continue;
            }

            if (str_starts_with($arg, '--tables=')) {
                $options['tables'] = $this->splitList(substr($arg, 9));
                continue;
            }

            if (str_starts_with($arg, '--except=')) {
                $options['except'] = $this->splitList(substr($arg, 9));
                continue;
            }

            if (str_starts_with($arg, '--batch=')) {
                $options['batch'] = max(1, (int) substr($arg, 8));
                continue;
            }

            if (!str_starts_with($arg, '-') && $options['source'] === null) {
                $options['source'] = $arg;
            }
        }

        return $options;
    }

    /**
     * Возвращает путь к SQLite-файлу из конфигурации.
     */
    private function defaultSourcePath(): string
    {
        return (string) config('database.connections.sqlite.database', STORAGE_PATH . '/database/database.sqlite');
    }

    /**
     * Открывает SQLite-файл как источник данных.
     */
    private function openSqlite(string $path): PDO
    {
        return new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * Возвращает список таблиц, которые нужно перенести.
     *
     * @param  array<string, mixed> $options
     * @return string[]
     */
    private function resolveTables(PDO $source, array $options): array
    {
        $sourceTables = $this->sourceTables($source);
        $targetTables = $this->targetTables();

        $tables = array_values(array_intersect($sourceTables, $targetTables));

        if ($options['tables'] !== []) {
            $tables = array_values(array_intersect($tables, $options['tables']));
        }

        if ($options['except'] !== []) {
            $tables = array_values(array_diff($tables, $options['except']));
        }

        return $this->sortTablesForImport($tables);
    }

    /**
     * Возвращает пользовательские таблицы SQLite.
     *
     * @return string[]
     */
    private function sourceTables(PDO $source): array
    {
        $rows = $source
            ->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
            ->fetchAll();

        return array_map(static fn (array $row): string => (string) $row['name'], $rows);
    }

    /**
     * Возвращает таблицы текущей MySQL-базы.
     *
     * @return string[]
     */
    private function targetTables(): array
    {
        $rows = $this->db->pdo()->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);

        return array_map(static fn (array $row): string => (string) $row[0], $rows);
    }

    /**
     * Сортирует таблицы так, чтобы базовые таблицы шли раньше зависимых.
     *
     * @param  string[] $tables
     * @return string[]
     */
    private function sortTablesForImport(array $tables): array
    {
        $preferred = [
            'migrations',
            'users',
            'system_pages',
            'redirects',
            'contacts',
            'jobs',
            'failed_jobs',
            'ciphers_categories',
            'ciphers_categories_translations',
            'ciphers_categories_blocks',
            'ciphers_categories_blocks_translations',
            'ciphers_categories_tasks',
            'ciphers_categories_tasks_translations',
            'ciphers_categories_used_together',
            'ciphers_categories_used_together_translations',
            'ciphers_categories_faq',
            'ciphers_categories_faq_translations',
            'ciphers',
            'ciphers_translations',
            'ciphers_blocks',
            'ciphers_blocks_translations',
            'ciphers_examples',
            'ciphers_examples_translations',
            'ciphers_faq',
            'ciphers_faq_translations',
            'ciphers_tags',
            'ciphers_tags_translations',
        ];

        $ordered = array_values(array_intersect($preferred, $tables));
        $rest = array_values(array_diff($tables, $ordered));
        sort($rest);

        return array_merge($ordered, $rest);
    }

    /**
     * Возвращает количество строк в каждой таблице.
     *
     * @param  string[]                         $tables
     * @return array<int, array{table: string, rows: int}>
     */
    private function tableStats(PDO $source, array $tables): array
    {
        $stats = [];

        foreach ($tables as $table) {
            $rows = $source->query('SELECT COUNT(*) AS cnt FROM ' . $this->quoteSqlite($table))->fetch();
            $stats[] = [
                'table' => $table,
                'rows' => (int) ($rows['cnt'] ?? 0),
            ];
        }

        return $stats;
    }

    /**
     * Выводит план импорта.
     *
     * @param array<int, array{table: string, rows: int}> $stats
     */
    private function printPlan(string $sourcePath, array $stats, bool $clear): void
    {
        echo 'Источник SQLite: ' . $sourcePath . PHP_EOL;
        echo 'Цель MySQL: ' . (string) config('database.connections.mysql.database') . PHP_EOL;
        echo 'Режим очистки: ' . ($clear ? 'да' : 'нет') . PHP_EOL;
        echo PHP_EOL . 'Таблицы к импорту:' . PHP_EOL;

        foreach ($stats as $row) {
            echo sprintf('  %-48s %d', $row['table'], $row['rows']) . PHP_EOL;
        }
    }

    /**
     * Выполняет перенос данных.
     *
     * @param string[] $tables
     */
    private function import(PDO $source, array $tables, bool $clear, int $batchSize): int
    {
        $target = $this->db->pdo();
        $imported = 0;

        $target->exec('SET FOREIGN_KEY_CHECKS=0');
        $target->beginTransaction();

        try {
            if ($clear) {
                foreach (array_reverse($tables) as $table) {
                    $target->exec('DELETE FROM ' . $this->quoteMysql($table));
                }
            }

            foreach ($tables as $table) {
                $imported += $this->importTable($source, $target, $table, $batchSize);
            }

            $target->commit();

            foreach ($tables as $table) {
                try {
                    $this->resetAutoIncrement($target, $table);
                } catch (Throwable $e) {
                    echo "Предупреждение: не удалось обновить AUTO_INCREMENT для {$table}: "
                        . $e->getMessage() . PHP_EOL;
                }
            }

            $target->exec('SET FOREIGN_KEY_CHECKS=1');
        } catch (Throwable $e) {
            if ($target->inTransaction()) {
                $target->rollBack();
            }

            $target->exec('SET FOREIGN_KEY_CHECKS=1');

            throw $e;
        }

        return $imported;
    }

    /**
     * Переносит одну таблицу.
     */
    private function importTable(PDO $source, PDO $target, string $table, int $batchSize): int
    {
        $columns = $this->commonColumns($source, $target, $table);

        if ($columns === []) {
            echo "Пропущена {$table}: нет общих колонок." . PHP_EOL;

            return 0;
        }

        $insert = $this->prepareInsert($target, $table, $columns);
        $imported = 0;
        $offset = 0;

        do {
            $select = $source->prepare(
                'SELECT ' . implode(', ', array_map($this->quoteSqlite(...), $columns))
                . ' FROM ' . $this->quoteSqlite($table)
                . ' LIMIT ? OFFSET ?'
            );
            $select->bindValue(1, $batchSize, PDO::PARAM_INT);
            $select->bindValue(2, $offset, PDO::PARAM_INT);
            $select->execute();

            $rows = $select->fetchAll();

            foreach ($rows as $row) {
                $insert->execute(array_map(static fn (string $column): mixed => $row[$column] ?? null, $columns));
                $imported++;
            }

            $offset += $batchSize;
        } while (count($rows) === $batchSize);

        echo "Импортирована {$table}: {$imported} строк." . PHP_EOL;

        return $imported;
    }

    /**
     * Возвращает пересечение колонок источника и цели.
     *
     * @return string[]
     */
    private function commonColumns(PDO $source, PDO $target, string $table): array
    {
        $sourceRows = $source->query('PRAGMA table_info(' . $this->quoteSqlite($table) . ')')->fetchAll();
        $sourceColumns = array_map(static fn (array $row): string => (string) $row['name'], $sourceRows);

        $targetRows = $target->query('SHOW COLUMNS FROM ' . $this->quoteMysql($table))->fetchAll();
        $targetColumns = array_map(static fn (array $row): string => (string) $row['Field'], $targetRows);

        return array_values(array_intersect($sourceColumns, $targetColumns));
    }

    /**
     * Подготавливает INSERT-запрос для таблицы.
     *
     * @param string[] $columns
     */
    private function prepareInsert(PDO $target, string $table, array $columns): \PDOStatement
    {
        $columnSql = implode(', ', array_map($this->quoteMysql(...), $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        return $target->prepare(
            'INSERT INTO ' . $this->quoteMysql($table) . " ({$columnSql}) VALUES ({$placeholders})"
        );
    }

    /**
     * Синхронизирует AUTO_INCREMENT с максимальным id в таблице.
     */
    private function resetAutoIncrement(PDO $target, string $table): void
    {
        if (!$this->hasAutoIncrementId($target, $table)) {
            return;
        }

        $row = $target->query('SELECT MAX(`id`) AS max_id FROM ' . $this->quoteMysql($table))->fetch();
        $next = (int) ($row['max_id'] ?? 0) + 1;

        $target->exec('ALTER TABLE ' . $this->quoteMysql($table) . ' AUTO_INCREMENT = ' . $next);
    }

    /**
     * Проверяет наличие автоинкрементной колонки id в целевой таблице.
     */
    private function hasAutoIncrementId(PDO $target, string $table): bool
    {
        $statement = $target->prepare(
            'SELECT EXTRA FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $statement->execute([$table, 'id']);
        $row = $statement->fetch();

        return str_contains((string) ($row['EXTRA'] ?? ''), 'auto_increment');
    }

    /**
     * Разбивает строку со списком через запятую.
     *
     * @return string[]
     */
    private function splitList(string $value): array
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            static fn (string $item): bool => $item !== ''
        ));
    }

    /**
     * Экранирует идентификатор SQLite.
     */
    private function quoteSqlite(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * Экранирует идентификатор MySQL.
     */
    private function quoteMysql(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}
