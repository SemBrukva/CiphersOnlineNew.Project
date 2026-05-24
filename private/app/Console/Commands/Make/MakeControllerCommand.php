<?php

declare(strict_types=1);

namespace App\Console\Commands\Make;

/**
 * Команда создания скелета контроллера.
 *
 * Поддерживает поддиректории через слеш:
 *   make:controller UserController           → Controller/UserController.php
 *   make:controller Api/UserController       → Controller/Api/UserController.php
 *   make:controller Admin/DashboardController → Controller/Admin/DashboardController.php
 */
final class MakeControllerCommand extends AbstractMakeCommand
{
    /**
     * {@inheritdoc}
     */
    protected function getType(): string
    {
        return 'controller';
    }

    /**
     * {@inheritdoc}
     */
    protected function getStub(string $name = ''): string
    {
        return 'controller.stub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetPath(string $name): string
    {
        return APP_PATH . '/Controller/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildReplacements(string $name): array
    {
        $parts     = explode('/', str_replace('\\', '/', $name));
        $className = array_pop($parts);
        $namespace = 'App\\Controller';

        if ($parts !== []) {
            $namespace .= '\\' . implode('\\', $parts);
        }

        return [
            '{{Namespace}}' => $namespace,
            '{{Class}}'     => $className,
        ];
    }
}
