<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use App\Container\Container;
use App\Container\ContainerException;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Проверяет базовое поведение DI-контейнера.
 */
final class ContainerTest extends TestCase
{
    /**
     * Проверяет регистрацию и получение готового экземпляра.
     */
    public function testInstanceReturnsRegisteredObject(): void
    {
        $container = new Container();
        $service = new stdClass();

        $container->instance('service', $service);

        self::assertSame($service, $container->get('service'));
    }

    /**
     * Проверяет ленивое создание сервиса фабрикой.
     */
    public function testFactoryBuildsObject(): void
    {
        $container = new Container();

        $container->set('service', static fn (): object => new stdClass());

        self::assertInstanceOf(stdClass::class, $container->get('service'));
    }

    /**
     * Проверяет, что контейнер кэширует результат фабрики как singleton.
     */
    public function testFactoryResultIsCachedAsSingleton(): void
    {
        $container = new Container();
        $calls = 0;

        $container->set('service', static function () use (&$calls): object {
            $calls++;
            return new stdClass();
        });

        $first = $container->get('service');
        $second = $container->get('service');

        self::assertSame($first, $second);
        self::assertSame(1, $calls);
    }

    /**
     * Проверяет исключение при запросе незарегистрированного сервиса.
     */
    public function testGetThrowsWhenBindingMissing(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Container binding not found: missing');

        $container->get('missing');
    }

    /**
     * Проверяет исключение, если фабрика вернула не объект.
     */
    public function testGetThrowsWhenFactoryReturnsNotObject(): void
    {
        $container = new Container();

        $container->set('bad', static fn (): int => 1);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Container factory must return an object for: bad');

        $container->get('bad');
    }

    /**
     * Проверяет auto-wiring класса по типам конструктора.
     */
    public function testContainerAutowiresClassDependencies(): void
    {
        $container = new Container();
        $container->instance(stdClass::class, new stdClass());

        $service = $container->get(ContainerAutowireFixture::class);

        self::assertInstanceOf(ContainerAutowireFixture::class, $service);
        self::assertInstanceOf(stdClass::class, $service->dependency);
    }
}

/**
 * Тестовый класс для проверки auto-wiring контейнера.
 */
final class ContainerAutowireFixture
{
    /**
     * Создаёт фикстуру с объектной зависимостью.
     */
    public function __construct(public stdClass $dependency)
    {
    }
}
