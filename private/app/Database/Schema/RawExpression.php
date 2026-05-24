<?php

declare(strict_types=1);

namespace App\Database\Schema;

/**
 * Обёртка для встраивания сырых SQL-выражений в значение DEFAULT.
 *
 * Используется через Schema::raw('CURRENT_TIMESTAMP'), чтобы
 * значение не оборачивалось в кавычки при генерации DDL.
 */
final class RawExpression
{
    /**
     * @param string $value Сырое SQL-выражение.
     */
    public function __construct(public readonly string $value)
    {
    }
}
