<?php

declare(strict_types=1);

namespace App\Console\Commands\Make;

/**
 * Команда создания скелета репозитория.
 *
 * Пример:
 *   make:repository PostRepository → Repository/PostRepository.php
 */
final class MakeRepositoryCommand extends AbstractMakeCommand
{
    /**
     * {@inheritdoc}
     */
    protected function getType(): string
    {
        return 'repository';
    }

    /**
     * {@inheritdoc}
     */
    protected function getStub(string $name = ''): string
    {
        return 'repository.stub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetPath(string $name): string
    {
        return APP_PATH . '/Repository/' . $name . '.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildReplacements(string $name): array
    {
        return [
            '{{Namespace}}' => 'App\\Repository',
            '{{Class}}'     => $name,
            '{{table}}'     => $this->inferTable($name),
        ];
    }

    /**
     * Выводит предполагаемое имя таблицы из имени репозитория.
     *
     * PostRepository → posts
     * UserRepository → users
     * OrderRepository → orders
     */
    private function inferTable(string $name): string
    {
        $base = preg_replace('/Repository$/i', '', $name) ?? $name;

        return strtolower($base) . 's';
    }
}
