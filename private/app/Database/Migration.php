<?php

declare(strict_types=1);

namespace App\Database;

/**
 * Абстрактный базовый класс для всех миграций базы данных.
 *
 * Каждая миграция реализует методы up() для применения
 * и down() для отката изменений схемы.
 */
abstract class Migration
{
    /**
     * Создаёт экземпляр миграции с доступом к базе данных.
     */
    public function __construct(protected readonly Database $db)
    {
    }

    /**
     * Применяет миграцию: создаёт таблицы, добавляет столбцы и т.д.
     */
    abstract public function up(): void;

    /**
     * Откатывает миграцию: удаляет таблицы, столбцы и т.д.
     */
    abstract public function down(): void;
}
