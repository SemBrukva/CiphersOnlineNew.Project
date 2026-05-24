<?php

declare(strict_types=1);

namespace App\Console;

/**
 * Контракт консольной команды.
 */
interface CommandInterface
{
    /**
     * Выполняет команду.
     *
     * @param  string[] $args Аргументы командной строки (без имени команды).
     * @return int            Код завершения: 0 — успех, ненулевой — ошибка.
     */
    public function handle(array $args): int;
}
