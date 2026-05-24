<?php

declare(strict_types=1);

namespace App\Console\Commands\Make;

/**
 * Команда создания скелета задачи очереди.
 *
 * Пример:
 *   make:job ProcessPaymentJob → Queue/Jobs/ProcessPaymentJob.php
 */
final class MakeJobCommand extends AbstractMakeCommand
{
    /**
     * {@inheritdoc}
     */
    protected function getType(): string
    {
        return 'job';
    }

    /**
     * {@inheritdoc}
     */
    protected function getStub(string $name = ''): string
    {
        return 'job.stub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTargetPath(string $name): string
    {
        return APP_PATH . '/Queue/Jobs/' . $name . '.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildReplacements(string $name): array
    {
        return [
            '{{Namespace}}' => 'App\\Queue\\Jobs',
            '{{Class}}'     => $name,
        ];
    }
}
