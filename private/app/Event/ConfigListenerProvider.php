<?php

declare(strict_types=1);

namespace App\Event;

/**
 * Провайдер листенеров, читающий карту событий из конфигурации.
 */
final readonly class ConfigListenerProvider implements ListenerProviderInterface
{
    /**
     * @param array<string, array<int, callable|class-string>> $map Карта Event::class => список листенеров.
     */
    public function __construct(private array $map)
    {
    }

    /**
     * Возвращает листенеры для события по классу, интерфейсам и родителям.
     *
     * @return iterable<int, callable|class-string>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $classes = array_values(array_unique(array_merge(
            [get_class($event)],
            class_parents($event) ?: [],
            class_implements($event) ?: []
        )));

        $listeners = [];

        foreach ($classes as $class) {
            foreach ($this->map[$class] ?? [] as $listener) {
                $listeners[] = $listener;
            }
        }

        return $listeners;
    }
}
