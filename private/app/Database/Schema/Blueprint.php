<?php

declare(strict_types=1);

namespace App\Database\Schema;

/**
 * Схема таблицы: накапливает определения столбцов, индексов и ограничений.
 *
 * Передаётся как аргумент callback-функции в Schema::create() и Schema::table().
 */
final class Blueprint
{
    /** @var ColumnDefinition[] Определения столбцов в порядке добавления. */
    private array $columns = [];

    /** @var array<array{name: string, columns: string[]}> Обычные индексы. */
    private array $indexes = [];

    /** @var array<array{name: string, columns: string[]}> Явно заданные уникальные индексы. */
    private array $uniques = [];

    /** @var string[] Столбцы составного первичного ключа. */
    private array $primaryColumns = [];

    /** @var string[] Столбцы для удаления (только Schema::table()). */
    private array $droppedColumns = [];

    /** @var ForeignKeyDefinition[] Определения внешних ключей. */
    private array $foreignKeys = [];

    // -------------------------------------------------------------------------
    // Методы добавления столбцов
    // -------------------------------------------------------------------------

    /**
     * Добавляет целочисленный первичный ключ с автоинкрементом (int).
     */
    public function id(string $name = 'id'): ColumnDefinition
    {
        $col                = $this->addColumn($name, 'integer');
        $col->autoIncrement = true;
        $col->primary       = true;
        $col->unsigned      = true;

        return $col;
    }

    /**
     * Добавляет bigint-первичный ключ с автоинкрементом.
     */
    public function bigId(string $name = 'id'): ColumnDefinition
    {
        $col                = $this->addColumn($name, 'bigInteger');
        $col->autoIncrement = true;
        $col->primary       = true;
        $col->unsigned      = true;

        return $col;
    }

    /**
     * Добавляет строковый столбец (varchar).
     *
     * @param int $length Максимальная длина строки.
     */
    public function string(string $name, int $length = 255): ColumnDefinition
    {
        $col         = $this->addColumn($name, 'string');
        $col->length = $length;

        return $col;
    }

    /**
     * Добавляет столбец типа text.
     */
    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'text');
    }

    /**
     * Добавляет столбец типа mediumtext.
     */
    public function mediumText(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'mediumText');
    }

    /**
     * Добавляет столбец типа longtext.
     */
    public function longText(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'longText');
    }

    /**
     * Добавляет целочисленный столбец (int).
     */
    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'integer');
    }

    /**
     * Добавляет столбец типа bigint.
     */
    public function bigInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'bigInteger');
    }

    /**
     * Добавляет столбец типа tinyint.
     */
    public function tinyInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'tinyInteger');
    }

    /**
     * Добавляет булев столбец (tinyint(1)).
     */
    public function boolean(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'boolean');
    }

    /**
     * Добавляет беззнаковый bigint.
     */
    public function unsignedBigInteger(string $name): ColumnDefinition
    {
        $col           = $this->addColumn($name, 'bigInteger');
        $col->unsigned = true;

        return $col;
    }

    /**
     * Добавляет беззнаковый int.
     */
    public function unsignedInteger(string $name): ColumnDefinition
    {
        $col           = $this->addColumn($name, 'integer');
        $col->unsigned = true;

        return $col;
    }

    /**
     * Добавляет беззнаковый tinyint.
     */
    public function unsignedTinyInteger(string $name): ColumnDefinition
    {
        $col           = $this->addColumn($name, 'tinyInteger');
        $col->unsigned = true;

        return $col;
    }

    /**
     * Добавляет столбец типа smallint.
     */
    public function smallInteger(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'smallInteger');
    }

    /**
     * Добавляет беззнаковый smallint.
     */
    public function unsignedSmallInteger(string $name): ColumnDefinition
    {
        $col           = $this->addColumn($name, 'smallInteger');
        $col->unsigned = true;

        return $col;
    }

    /**
     * Добавляет столбец типа float.
     *
     * @param int $total  Общее количество цифр.
     * @param int $places Знаков после запятой.
     */
    public function float(string $name, int $total = 8, int $places = 2): ColumnDefinition
    {
        $col            = $this->addColumn($name, 'float');
        $col->precision = $total;
        $col->scale     = $places;

        return $col;
    }

    /**
     * Добавляет столбец типа decimal.
     *
     * @param int $total  Общее количество цифр.
     * @param int $places Знаков после запятой.
     */
    public function decimal(string $name, int $total = 8, int $places = 2): ColumnDefinition
    {
        $col            = $this->addColumn($name, 'decimal');
        $col->precision = $total;
        $col->scale     = $places;

        return $col;
    }

    /**
     * Добавляет столбец unix-временной метки (integer).
     */
    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'timestamp');
    }

    /**
     * Добавляет столбец datetime.
     */
    public function datetime(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'datetime');
    }

    /**
     * Добавляет столбец date.
     */
    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn($name, 'date');
    }

    /**
     * Добавляет пару nullable-столбцов created_at и updated_at (datetime).
     */
    public function timestamps(): void
    {
        $this->datetime('created_at')->nullable();
        $this->datetime('updated_at')->nullable();
    }

    /**
     * Добавляет столбец deleted_at для мягкого удаления (datetime, nullable).
     */
    public function softDeletes(string $name = 'deleted_at'): ColumnDefinition
    {
        return $this->datetime($name)->nullable();
    }

    // -------------------------------------------------------------------------
    // Методы добавления ограничений и индексов
    // -------------------------------------------------------------------------

    /**
     * Добавляет уникальный индекс.
     *
     * @param string|string[] $columns Один столбец или список столбцов.
     * @param string|null     $name    Имя индекса (генерируется автоматически, если null).
     */
    public function unique(string|array $columns, ?string $name = null): void
    {
        $columns         = (array) $columns;
        $this->uniques[] = [
            'name'    => $name ?? $this->indexName('unique', $columns),
            'columns' => $columns,
        ];
    }

    /**
     * Добавляет обычный индекс.
     *
     * @param string|string[] $columns Один столбец или список столбцов.
     * @param string|null     $name    Имя индекса (генерируется автоматически, если null).
     */
    public function index(string|array $columns, ?string $name = null): void
    {
        $columns         = (array) $columns;
        $this->indexes[] = [
            'name'    => $name ?? $this->indexName('idx', $columns),
            'columns' => $columns,
        ];
    }

    /**
     * Задаёт составной первичный ключ.
     *
     * @param string|string[] $columns Столбцы первичного ключа.
     */
    public function primary(string|array $columns): void
    {
        $this->primaryColumns = array_merge($this->primaryColumns, (array) $columns);
    }

    /**
     * Помечает столбец для удаления (только Schema::table()).
     */
    public function dropColumn(string $column): void
    {
        $this->droppedColumns[] = $column;
    }

    /**
     * Добавляет определение внешнего ключа с fluent-настройкой.
     *
     * Пример: $table->foreign('user_id')->references('id')->on('users')->onDelete('SET NULL')
     */
    public function foreign(string $column): ForeignKeyDefinition
    {
        $fk                  = new ForeignKeyDefinition($column);
        $this->foreignKeys[] = $fk;

        return $fk;
    }

    // -------------------------------------------------------------------------
    // Геттеры для грамматик
    // -------------------------------------------------------------------------

    /**
     * Возвращает все определения столбцов.
     *
     * @return ColumnDefinition[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Возвращает все уникальные индексы: явно добавленные через unique()
     * плюс одиночные уникальные, заданные через ->unique() на столбце.
     *
     * @return array<array{name: string, columns: string[]}>
     */
    public function getUniques(): array
    {
        $fromColumns = [];

        foreach ($this->columns as $col) {
            if ($col->isUnique && !$col->primary) {
                $fromColumns[] = [
                    'name'    => $col->name . '_unique',
                    'columns' => [$col->name],
                ];
            }
        }

        return array_merge($fromColumns, $this->uniques);
    }

    /**
     * Возвращает обычные индексы.
     *
     * @return array<array{name: string, columns: string[]}>
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Возвращает столбцы составного первичного ключа.
     *
     * @return string[]
     */
    public function getPrimaryColumns(): array
    {
        return $this->primaryColumns;
    }

    /**
     * Возвращает столбцы для удаления.
     *
     * @return string[]
     */
    public function getDroppedColumns(): array
    {
        return $this->droppedColumns;
    }

    /**
     * Возвращает определения внешних ключей.
     *
     * @return ForeignKeyDefinition[]
     */
    public function getForeignKeys(): array
    {
        return $this->foreignKeys;
    }

    // -------------------------------------------------------------------------
    // Приватные хелперы
    // -------------------------------------------------------------------------

    /**
     * Создаёт и регистрирует определение столбца.
     */
    private function addColumn(string $name, string $type): ColumnDefinition
    {
        $col             = new ColumnDefinition($name, $type);
        $this->columns[] = $col;

        return $col;
    }

    /**
     * Генерирует имя индекса из имён столбцов.
     *
     * @param string[] $columns
     */
    private function indexName(string $suffix, array $columns): string
    {
        return implode('_', $columns) . '_' . $suffix;
    }
}
