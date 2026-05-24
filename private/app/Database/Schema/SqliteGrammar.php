<?php

declare(strict_types=1);

namespace App\Database\Schema;

/**
 * DDL-грамматика для SQLite.
 */
final class SqliteGrammar extends Grammar
{
    /**
     * Компилирует CREATE TABLE для SQLite.
     *
     * Индексы (кроме PRIMARY KEY и UNIQUE внутри CREATE TABLE)
     * возвращаются отдельными CREATE INDEX-выражениями.
     *
     * @return string[]
     */
    public function compileCreate(string $table, Blueprint $blueprint): array
    {
        $defs = [];

        foreach ($blueprint->getColumns() as $col) {
            $defs[] = $this->compileColumn($col);
        }

        foreach ($blueprint->getUniques() as $unique) {
            $cols   = implode(', ', $unique['columns']);
            $defs[] = "UNIQUE({$cols})";
        }

        $primaryCols = $blueprint->getPrimaryColumns();
        if (!empty($primaryCols)) {
            $cols   = implode(', ', $primaryCols);
            $defs[] = "PRIMARY KEY ({$cols})";
        }

        foreach ($blueprint->getForeignKeys() as $fk) {
            $actions = $this->compileFkActions($fk);
            $defs[]  = "FOREIGN KEY ({$fk->column}) REFERENCES {$fk->getOn()}({$fk->getReferences()}){$actions}";
        }

        $body = implode(",\n    ", $defs);
        $sql  = "CREATE TABLE IF NOT EXISTS {$table} (\n    {$body}\n)";

        $statements = [$sql];

        foreach ($blueprint->getIndexes() as $index) {
            $cols         = implode(', ', $index['columns']);
            $name         = $index['name'];
            $statements[] = "CREATE INDEX IF NOT EXISTS {$name} ON {$table} ({$cols})";
        }

        return $statements;
    }

    /**
     * Компилирует ALTER TABLE для SQLite.
     *
     * SQLite поддерживает ADD COLUMN (3.1+) и DROP COLUMN (3.35+).
     * Модификатор AFTER игнорируется.
     *
     * @return string[]
     */
    public function compileAlter(string $table, Blueprint $blueprint): array
    {
        $statements = [];

        foreach ($blueprint->getColumns() as $col) {
            $def          = $this->compileColumn($col);
            $statements[] = "ALTER TABLE {$table} ADD COLUMN {$def}";
        }

        foreach ($blueprint->getDroppedColumns() as $column) {
            $statements[] = "ALTER TABLE {$table} DROP COLUMN {$column}";
        }

        foreach ($blueprint->getIndexes() as $index) {
            $cols         = implode(', ', $index['columns']);
            $name         = $index['name'];
            $statements[] = "CREATE INDEX IF NOT EXISTS {$name} ON {$table} ({$cols})";
        }

        foreach ($blueprint->getUniques() as $unique) {
            $cols         = implode(', ', $unique['columns']);
            $name         = $unique['name'];
            $statements[] = "CREATE UNIQUE INDEX IF NOT EXISTS {$name} ON {$table} ({$cols})";
        }

        return $statements;
    }

    /**
     * Компилирует DROP TABLE.
     */
    public function compileDrop(string $table): string
    {
        return "DROP TABLE {$table}";
    }

    /**
     * Компилирует DROP TABLE IF EXISTS.
     */
    public function compileDropIfExists(string $table): string
    {
        return "DROP TABLE IF EXISTS {$table}";
    }

    /**
     * SQL для проверки существования таблицы.
     * Параметр: имя таблицы.
     */
    public function compileHasTable(): string
    {
        return "SELECT COUNT(*) AS cnt FROM sqlite_master WHERE type = 'table' AND name = ?";
    }

    /**
     * SQL для проверки существования столбца.
     * Параметры: имя таблицы, имя столбца.
     */
    public function compileHasColumn(): string
    {
        return 'SELECT COUNT(*) AS cnt FROM pragma_table_info(?) WHERE name = ?';
    }

    /**
     * SQLite не требует кавычек вокруг идентификаторов в этих контекстах.
     */
    protected function wrap(string $value): string
    {
        return $value;
    }

    /**
     * В SQLite произвольные выражения в DEFAULT должны быть обёрнуты в скобки.
     */
    protected function compileDefaultValue(mixed $value): string
    {
        if ($value instanceof RawExpression) {
            return '(' . $value->value . ')';
        }

        return parent::compileDefaultValue($value);
    }

    /**
     * Компилирует определение одного столбца для SQLite.
     */
    private function compileColumn(ColumnDefinition $col): string
    {
        $type  = $this->columnType($col);
        $parts = ["{$col->name} {$type}"];

        if ($col->primary && $col->autoIncrement) {
            $parts[] = 'PRIMARY KEY AUTOINCREMENT';
        } else {
            if (!$col->nullable) {
                $parts[] = 'NOT NULL';
            }

            if ($col->hasDefault) {
                $parts[] = 'DEFAULT ' . $this->compileDefaultValue($col->defaultValue);
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Возвращает SQLite-тип по внутреннему типу столбца.
     */
    private function columnType(ColumnDefinition $col): string
    {
        return match ($col->type) {
            'integer', 'bigInteger', 'smallInteger', 'tinyInteger', 'boolean',
            'timestamp'  => 'INTEGER',
            'string', 'text', 'mediumText', 'longText',
            'datetime', 'date' => 'TEXT',
            'float'      => 'REAL',
            'decimal'    => 'NUMERIC',
            default      => 'TEXT',
        };
    }

    /**
     * Компилирует суффиксы ON DELETE / ON UPDATE для внешнего ключа.
     */
    private function compileFkActions(ForeignKeyDefinition $fk): string
    {
        $actions = '';

        if ($fk->getOnDelete() !== null) {
            $actions .= " ON DELETE {$fk->getOnDelete()}";
        }

        if ($fk->getOnUpdate() !== null) {
            $actions .= " ON UPDATE {$fk->getOnUpdate()}";
        }

        return $actions;
    }
}
