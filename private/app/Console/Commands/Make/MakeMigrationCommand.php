<?php

declare(strict_types=1);

namespace App\Console\Commands\Make;

/**
 * Команда создания скелета миграции с автоматическим timestamp в имени файла.
 *
 * Пример:
 *   make:migration create_posts_table
 *   make:migration add_status_to_posts
 */
final class MakeMigrationCommand extends AbstractMakeCommand
{
    /**
     * {@inheritdoc}
     */
    protected function getType(): string
    {
        return 'migration';
    }

    /**
     * {@inheritdoc}
     *
     * Возвращает alter-stub для миграций вида add_* / drop_* / rename_* / modify_* / change_*,
     * иначе — create-stub.
     */
    protected function getStub(string $name = ''): string
    {
        if ($name !== '' && preg_match('/^(?:add|drop|rename|modify|change)_/', $name)) {
            return 'migration_alter.stub';
        }

        return 'migration.stub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetPath(string $name): string
    {
        $timestamp = date('Y_m_d_His');
        return DATABASE_PATH . '/migrations/' . $timestamp . '_' . $name . '.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildReplacements(string $name): array
    {
        return [
            '{{Class}}'       => $this->toClassName($name),
            '{{Description}}' => ucfirst(str_replace('_', ' ', $name)),
            '{{table}}'       => $this->extractTable($name),
        ];
    }

    /**
     * Переводит snake_case имя в PascalCase имя класса.
     */
    private function toClassName(string $name): string
    {
        return str_replace('_', '', ucwords($name, '_'));
    }

    /**
     * Извлекает предполагаемое имя таблицы из имени миграции.
     *
     * create_posts_table  → posts
     * add_status_to_posts → posts
     * drop_orders_table   → orders
     * Прочие              → имя без изменений
     */
    private function extractTable(string $name): string
    {
        if (preg_match('/^(?:create|drop)_(.+)_table$/', $name, $m)) {
            return $m[1];
        }

        if (preg_match('/_to_([^_].+)$/', $name, $m)) {
            return $m[1];
        }

        return $name;
    }
}
