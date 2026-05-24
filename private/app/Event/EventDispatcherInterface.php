<?php

declare(strict_types=1);

namespace App\Event;

/**
 * Контракт диспетчера событий приложения.
 */
interface EventDispatcherInterface
{
    /**
     * Диспатчит событие всем подходящим листенерам.
     */
    public function dispatch(object $event): object;
}
