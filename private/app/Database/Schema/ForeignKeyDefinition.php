<?php

declare(strict_types=1);

namespace App\Database\Schema;

/**
 * Определение внешнего ключа с fluent-интерфейсом.
 *
 * Создаётся через Blueprint::foreign() и накапливает параметры
 * до момента компиляции в грамматике.
 */
final class ForeignKeyDefinition
{
    /** Столбец, на который ссылается внешний ключ. */
    private string $references = 'id';

    /** Родительская таблица. */
    private string $on = '';

    /** Действие при удалении родительской строки. */
    private ?string $onDelete = null;

    /** Действие при обновлении родительской строки. */
    private ?string $onUpdate = null;

    /**
     * @param string $column Столбец, содержащий внешний ключ.
     */
    public function __construct(public readonly string $column)
    {
    }

    /**
     * Задаёт столбец родительской таблицы.
     */
    public function references(string $column): static
    {
        $this->references = $column;

        return $this;
    }

    /**
     * Задаёт родительскую таблицу.
     */
    public function on(string $table): static
    {
        $this->on = $table;

        return $this;
    }

    /**
     * Задаёт действие при удалении родительской строки (CASCADE, SET NULL, RESTRICT и т.д.).
     */
    public function onDelete(string $action): static
    {
        $this->onDelete = strtoupper($action);

        return $this;
    }

    /**
     * Задаёт действие при обновлении родительской строки.
     */
    public function onUpdate(string $action): static
    {
        $this->onUpdate = strtoupper($action);

        return $this;
    }

    /**
     * Возвращает имя столбца родительской таблицы.
     */
    public function getReferences(): string
    {
        return $this->references;
    }

    /**
     * Возвращает имя родительской таблицы.
     */
    public function getOn(): string
    {
        return $this->on;
    }

    /**
     * Возвращает действие ON DELETE или null.
     */
    public function getOnDelete(): ?string
    {
        return $this->onDelete;
    }

    /**
     * Возвращает действие ON UPDATE или null.
     */
    public function getOnUpdate(): ?string
    {
        return $this->onUpdate;
    }
}
