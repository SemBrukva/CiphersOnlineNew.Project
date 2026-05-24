<?php

declare(strict_types=1);

namespace App\Database\Schema;

/**
 * DDL-грамматика для MySQL.
 */
final class MySqlGrammar extends Grammar
{
    /**
     * Компилирует CREATE TABLE для MySQL.
     *
     * Все ограничения (PRIMARY KEY, UNIQUE KEY, KEY) включаются
     * в тело CREATE TABLE.
     *
     * @return string[]
     */
    public function compileCreate(string $table, Blueprint $blueprint): array
    {
        $defs        = [];
        $primaryCols = [];

        foreach ($blueprint->getColumns() as $col) {
            $defs[] = $this->compileColumn($col);

            if ($col->primary) {
                $primaryCols[] = $this->wrap($col->name);
            }
        }

        $explicitPrimary = $blueprint->getPrimaryColumns();
        if (!empty($explicitPrimary)) {
            $primaryCols = array_map($this->wrap(...), $explicitPrimary);
        }

        if (!empty($primaryCols)) {
            $defs[] = 'PRIMARY KEY (' . implode(', ', $primaryCols) . ')';
        }

        foreach ($blueprint->getUniques() as $unique) {
            $cols   = implode(', ', array_map($this->wrap(...), $unique['columns']));
            $defs[] = "UNIQUE KEY `{$unique['name']}` ({$cols})";
        }

        foreach ($blueprint->getIndexes() as $index) {
            $cols   = implode(', ', array_map($this->wrap(...), $index['columns']));
            $defs[] = "KEY `{$index['name']}` ({$cols})";
        }

        foreach ($blueprint->getForeignKeys() as $fk) {
            $col      = $this->wrap($fk->column);
            $refCol   = $this->wrap($fk->getReferences());
            $refTable = $this->wrap($fk->getOn());
            $fkName   = 'fk_' . $fk->column;
            $actions  = $this->compileFkActions($fk);
            $defs[]   = "CONSTRAINT `{$fkName}` FOREIGN KEY ({$col}) REFERENCES {$refTable} ({$refCol}){$actions}";
        }

        $body = implode(",\n    ", $defs);
        $t    = $this->wrap($table);

        return ["CREATE TABLE IF NOT EXISTS {$t} (\n    {$body}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"];
    }

    /**
     * Компилирует ALTER TABLE для MySQL.
     *
     * Поддерживает ADD COLUMN (с опциональным AFTER), DROP COLUMN,
     * ADD KEY, ADD UNIQUE KEY.
     *
     * @return string[]
     */
    public function compileAlter(string $table, Blueprint $blueprint): array
    {
        $statements = [];
        $t          = $this->wrap($table);

        foreach ($blueprint->getColumns() as $col) {
            $def   = $this->compileColumn($col);
            $after = $col->after !== null ? ' AFTER ' . $this->wrap($col->after) : '';
            $statements[] = "ALTER TABLE {$t} ADD COLUMN {$def}{$after}";
        }

        foreach ($blueprint->getDroppedColumns() as $column) {
            $statements[] = "ALTER TABLE {$t} DROP COLUMN {$this->wrap($column)}";
        }

        foreach ($blueprint->getIndexes() as $index) {
            $cols         = implode(', ', array_map($this->wrap(...), $index['columns']));
            $statements[] = "ALTER TABLE {$t} ADD KEY `{$index['name']}` ({$cols})";
        }

        foreach ($blueprint->getUniques() as $unique) {
            $cols         = implode(', ', array_map($this->wrap(...), $unique['columns']));
            $statements[] = "ALTER TABLE {$t} ADD UNIQUE KEY `{$unique['name']}` ({$cols})";
        }

        return $statements;
    }

    /**
     * Компилирует DROP TABLE.
     */
    public function compileDrop(string $table): string
    {
        return "DROP TABLE {$this->wrap($table)}";
    }

    /**
     * Компилирует DROP TABLE IF EXISTS.
     */
    public function compileDropIfExists(string $table): string
    {
        return "DROP TABLE IF EXISTS {$this->wrap($table)}";
    }

    /**
     * SQL для проверки существования таблицы.
     * Параметр: имя таблицы.
     */
    public function compileHasTable(): string
    {
        return 'SELECT COUNT(*) AS cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?';
    }

    /**
     * SQL для проверки существования столбца.
     * Параметры: имя таблицы, имя столбца.
     */
    public function compileHasColumn(): string
    {
        return 'SELECT COUNT(*) AS cnt FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?';
    }

    /**
     * Заключает идентификатор в обратные кавычки MySQL.
     */
    protected function wrap(string $value): string
    {
        return '`' . str_replace('`', '``', $value) . '`';
    }

    /**
     * Компилирует определение одного столбца для MySQL.
     */
    private function compileColumn(ColumnDefinition $col): string
    {
        $name  = $this->wrap($col->name);
        $type  = $this->columnType($col);
        $parts = ["{$name} {$type}"];

        if ($col->primary && $col->autoIncrement) {
            $parts[] = 'NOT NULL AUTO_INCREMENT';
        } elseif ($col->nullable) {
            $parts[] = 'NULL';
            $parts[] = 'DEFAULT ' . ($col->hasDefault
                ? $this->compileDefaultValue($col->defaultValue)
                : 'NULL');
        } else {
            $parts[] = 'NOT NULL';

            if ($col->hasDefault) {
                $parts[] = 'DEFAULT ' . $this->compileDefaultValue($col->defaultValue);
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Возвращает MySQL-тип по внутреннему типу столбца.
     */
    private function columnType(ColumnDefinition $col): string
    {
        $u = $col->unsigned ? ' UNSIGNED' : '';

        return match ($col->type) {
            'integer'      => "int(11){$u}",
            'bigInteger'   => "bigint(20){$u}",
            'smallInteger' => "smallint(6){$u}",
            'tinyInteger'  => "tinyint(4){$u}",
            'boolean'      => 'tinyint(1)',
            'string'       => 'varchar(' . ($col->length ?? 255) . ')',
            'text'         => 'text',
            'mediumText'   => 'mediumtext',
            'longText'     => 'longtext',
            'float'        => 'float',
            'decimal'      => 'decimal(' . ($col->precision ?? 8) . ',' . ($col->scale ?? 2) . ')',
            'timestamp'    => "int(11){$u}",
            'datetime'     => 'datetime',
            'date'         => 'date',
            default        => 'varchar(255)',
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
