<?php

declare(strict_types=1);

namespace App\Queue;

/**
 * Контракт фоновой задачи, исполняемой воркером очереди.
 *
 * Реализации сериализуются через serialize() при попадании в очередь
 * и десериализуются воркером перед вызовом handle().
 */
interface JobInterface
{
    /**
     * Выполняет полезную работу задачи.
     */
    public function handle(): void;
}
