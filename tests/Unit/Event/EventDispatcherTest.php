<?php

declare(strict_types=1);

namespace Tests\Unit\Event;

use App\Container\Container;
use App\Event\ConfigListenerProvider;
use App\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет работу EventDispatcher и ConfigListenerProvider.
 */
final class EventDispatcherTest extends TestCase
{
    /**
     * Проверяет, что диспетчер вызывает инвокабельный listener-класс из контейнера.
     */
    public function testDispatchCallsClassListener(): void
    {
        $container = new Container();
        $listener = new EventDispatcherTestListener();
        $container->instance(EventDispatcherTestListener::class, $listener);

        $provider = new ConfigListenerProvider([
            EventDispatcherTestEvent::class => [EventDispatcherTestListener::class],
        ]);

        $dispatcher = new EventDispatcher($provider, $container);
        $event = new EventDispatcherTestEvent('john@example.com');
        $dispatcher->dispatch($event);

        self::assertSame(['john@example.com'], $listener->emails);
    }

    /**
     * Проверяет, что provider находит listeners по интерфейсу события.
     */
    public function testProviderMatchesListenersByImplementedInterface(): void
    {
        $provider = new ConfigListenerProvider([
            EventDispatcherTestMarker::class => [static fn (): null => null],
        ]);

        $listeners = iterator_to_array($provider->getListenersForEvent(new EventDispatcherTestEvent('a@b.c')));

        self::assertCount(1, $listeners);
    }
}

/**
 * Маркерный интерфейс тестового события.
 */
interface EventDispatcherTestMarker
{
}

/**
 * Тестовое событие.
 */
final readonly class EventDispatcherTestEvent implements EventDispatcherTestMarker
{
    public function __construct(public string $email)
    {
    }
}

/**
 * Тестовый listener, накапливающий email адреса.
 */
final class EventDispatcherTestListener
{
    /** @var string[] */
    public array $emails = [];

    public function __invoke(EventDispatcherTestEvent $event): void
    {
        $this->emails[] = $event->email;
    }
}
