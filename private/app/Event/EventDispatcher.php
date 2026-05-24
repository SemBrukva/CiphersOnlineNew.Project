<?php

declare(strict_types=1);

namespace App\Event;

use App\Container\Container;
use RuntimeException;

/**
 * Диспетчер событий, вызывающий листенеры из ListenerProvider.
 */
final readonly class EventDispatcher implements EventDispatcherInterface
{
    /**
     * Создаёт экземпляр диспетчера событий.
     */
    public function __construct(
        private ListenerProviderInterface $provider,
        private Container $container
    ) {
    }

    /**
     * Последовательно вызывает листенеры для события.
     */
    public function dispatch(object $event): object
    {
        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            $callable = $this->resolveListener($listener);
            $callable($event);
        }

        return $event;
    }

    /**
     * Преобразует listener-запись в исполняемый callable.
     *
     * @param callable|class-string $listener
     */
    private function resolveListener(callable|string $listener): callable
    {
        if (is_callable($listener)) {
            return $listener;
        }

        $instance = $this->container->get($listener);

        if (!is_callable($instance)) {
            throw new RuntimeException('Listener class must be invokable: ' . $listener);
        }

        return $instance;
    }
}
