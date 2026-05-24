<?php

declare(strict_types=1);

namespace App\Console\Commands\Make;

/**
 * Команда создания скелета middleware.
 *
 * Пример:
 *   make:middleware RateLimitMiddleware → Http/Middleware/RateLimitMiddleware.php
 */
final class MakeMiddlewareCommand extends AbstractMakeCommand
{
    /**
     * {@inheritdoc}
     */
    protected function getType(): string
    {
        return 'middleware';
    }

    /**
     * {@inheritdoc}
     */
    protected function getStub(string $name = ''): string
    {
        return 'middleware.stub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetPath(string $name): string
    {
        return APP_PATH . '/Http/Middleware/' . $name . '.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildReplacements(string $name): array
    {
        return [
            '{{Namespace}}' => 'App\\Http\\Middleware',
            '{{Class}}'     => $name,
        ];
    }
}
