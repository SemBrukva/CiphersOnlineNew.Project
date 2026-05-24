<?php

declare(strict_types=1);

namespace App\Event;

/**
 * Контракт провайдера листенеров для заданного события.
 */
interface ListenerProviderInterface
{
    /**
     * Возвращает список листенеров для события.
     *
     * @return iterable<int, callable|class-string>
     */
    public function getListenersForEvent(object $event): iterable;
}
