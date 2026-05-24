<?php

declare(strict_types=1);

namespace App\Database\Schema;

use App\Database\Database;

/**
 * Fluent-строитель схемы базы данных.
 *
 * Предоставляет статический API для создания, изменения и удаления таблиц
 * без написания сырого SQL. Автоматически выбирает грамматику (SQLite / MySQL)
 * по значению config('database.default').
 *
 * Примеры использования в миграциях:
 *
 *   Schema::create('users', function (Blueprint $table) {
 *       $table->id();
 *       $table->string('email')->unique();
 *       $table->string('password');
 *       $table->timestamps();
 *   });
 *
 *   Schema::table('users', function (Blueprint $table) {
 *       $table->string('avatar', 100)->nullable()->after('name');
 *   });
 *
 *   Schema::dropIfExists('users');
 *
 *   $table->datetime('created_at')->default(Schema::raw('CURRENT_TIMESTAMP'));
 */
final class Schema
{
    /**
     * Создаёт таблицу.
     *
     * @param callable(Blueprint): void $callback Функция для описания схемы таблицы.
     */
    public static function create(string $table, callable $callback): void
    {
        $blueprint = new Blueprint();
        $callback($blueprint);

        foreach (self::grammar()->compileCreate($table, $blueprint) as $sql) {
            self::db()->execute($sql);
        }
    }

    /**
     * Изменяет существующую таблицу (ADD / DROP COLUMN, ADD INDEX и т.д.).
     *
     * @param callable(Blueprint): void $callback Функция с описанием изменений.
     */
    public static function table(string $table, callable $callback): void
    {
        $blueprint = new Blueprint();
        $callback($blueprint);

        foreach (self::grammar()->compileAlter($table, $blueprint) as $sql) {
            self::db()->execute($sql);
        }
    }

    /**
     * Удаляет таблицу.
     */
    public static function drop(string $table): void
    {
        self::db()->execute(self::grammar()->compileDrop($table));
    }

    /**
     * Удаляет таблицу, если она существует.
     */
    public static function dropIfExists(string $table): void
    {
        self::db()->execute(self::grammar()->compileDropIfExists($table));
    }

    /**
     * Проверяет, существует ли таблица.
     */
    public static function hasTable(string $table): bool
    {
        $row = self::db()->fetch(self::grammar()->compileHasTable(), [$table]);

        return (int) ($row['cnt'] ?? 0) > 0;
    }

    /**
     * Проверяет, существует ли столбец в таблице.
     */
    public static function hasColumn(string $table, string $column): bool
    {
        $row = self::db()->fetch(self::grammar()->compileHasColumn(), [$table, $column]);

        return (int) ($row['cnt'] ?? 0) > 0;
    }

    /**
     * Создаёт объект сырого SQL-выражения для использования в ->default().
     *
     * @param string $sql Сырое SQL-выражение (например 'CURRENT_TIMESTAMP').
     */
    public static function raw(string $sql): RawExpression
    {
        return new RawExpression($sql);
    }

    // -------------------------------------------------------------------------

    /**
     * Возвращает грамматику для текущего драйвера базы данных.
     */
    private static function grammar(): Grammar
    {
        $driver = config('database.default', 'sqlite');

        return $driver === 'sqlite' ? new SqliteGrammar() : new MySqlGrammar();
    }

    /**
     * Возвращает соединение с базой данных из контейнера.
     */
    private static function db(): Database
    {
        return app(Database::class);
    }
}
