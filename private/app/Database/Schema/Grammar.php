<?php

declare(strict_types=1);

namespace App\Database\Schema;

/**
 * Абстрактная грамматика DDL-выражений.
 *
 * Подклассы реализуют специфику конкретной СУБД.
 */
abstract class Grammar
{
    /**
     * Компилирует SQL-выражения для создания таблицы.
     *
     * @return string[] Список SQL-выражений для выполнения.
     */
    abstract public function compileCreate(string $table, Blueprint $blueprint): array;

    /**
     * Компилирует SQL-выражения для изменения таблицы (ALTER TABLE).
     *
     * @return string[] Список SQL-выражений для выполнения.
     */
    abstract public function compileAlter(string $table, Blueprint $blueprint): array;

    /**
     * Компилирует DROP TABLE.
     */
    abstract public function compileDrop(string $table): string;

    /**
     * Компилирует DROP TABLE IF EXISTS.
     */
    abstract public function compileDropIfExists(string $table): string;

    /**
     * Возвращает SQL для проверки существования таблицы.
     * Принимает один placeholder-параметр — имя таблицы.
     */
    abstract public function compileHasTable(): string;

    /**
     * Возвращает SQL для проверки существования столбца.
     * Принимает два placeholder-параметра: имя таблицы и имя столбца.
     */
    abstract public function compileHasColumn(): string;

    /**
     * Заключает идентификатор в кавычки СУБД.
     */
    abstract protected function wrap(string $value): string;

    /**
     * Компилирует значение по умолчанию в SQL-литерал.
     */
    protected function compileDefaultValue(mixed $value): string
    {
        if ($value instanceof RawExpression) {
            return $value->value;
        }

        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'" . addslashes((string) $value) . "'";
    }
}
