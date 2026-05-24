<?php

declare(strict_types=1);

namespace App\Database\Schema;

/**
 * Определение одного столбца таблицы с fluent-интерфейсом для настройки модификаторов.
 */
final class ColumnDefinition
{
    /** Разрешить NULL-значения. */
    public bool $nullable = false;

    /** Установлено ли значение по умолчанию. */
    public bool $hasDefault = false;

    /** Значение по умолчанию (скаляр или RawExpression). */
    public mixed $defaultValue = null;

    /** Беззнаковый тип (только для числовых столбцов). */
    public bool $unsigned = false;

    /** Автоинкремент. */
    public bool $autoIncrement = false;

    /** Является первичным ключом. */
    public bool $primary = false;

    /** Добавить уникальный индекс для этого столбца. */
    public bool $isUnique = false;

    /** Длина для строкового типа. */
    public ?int $length = null;

    /** Точность для decimal/float (общее количество цифр). */
    public ?int $precision = null;

    /** Масштаб для decimal (знаков после запятой). */
    public ?int $scale = null;

    /** Разместить столбец после этого (только MySQL). */
    public ?string $after = null;

    /**
     * @param string $name Имя столбца.
     * @param string $type Внутренний тип столбца.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
    ) {
    }

    /**
     * Разрешает NULL-значения для столбца.
     */
    public function nullable(bool $value = true): static
    {
        $this->nullable = $value;

        return $this;
    }

    /**
     * Устанавливает значение по умолчанию.
     *
     * @param mixed $value Скаляр или RawExpression для SQL-выражений.
     */
    public function default(mixed $value): static
    {
        $this->hasDefault   = true;
        $this->defaultValue = $value;

        return $this;
    }

    /**
     * Делает числовой столбец беззнаковым.
     */
    public function unsigned(): static
    {
        $this->unsigned = true;

        return $this;
    }

    /**
     * Добавляет уникальный индекс для этого столбца.
     */
    public function unique(): static
    {
        $this->isUnique = true;

        return $this;
    }

    /**
     * Размещает столбец после указанного (поддерживается только MySQL).
     *
     * @param string $column Имя столбца-ориентира.
     */
    public function after(string $column): static
    {
        $this->after = $column;

        return $this;
    }
}
