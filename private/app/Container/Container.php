<?php

declare(strict_types=1);

namespace App\Container;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

/**
 * Простой сервис-контейнер с ленивой инициализацией.
 *
 * Сервисы регистрируются через callable-фабрики и кэшируются как синглтоны
 * при первом обращении. Готовые объекты можно зарегистрировать напрямую через instance().
 */
final class Container
{
    /** @var array<string, callable> Зарегистрированные фабрики сервисов. */
    private array $bindings = [];

    /** @var array<string, object> Закэшированные экземпляры сервисов. */
    private array $instances = [];

    /**
     * Регистрирует фабрику для создания сервиса по идентификатору.
     */
    public function set(string $id, callable $factory): void
    {
        $this->bindings[$id] = $factory;
    }

    /**
     * Регистрирует готовый объект как синглтон по идентификатору.
     */
    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * Проверяет, зарегистрирован ли сервис с данным идентификатором.
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    /**
     * Возвращает сервис по идентификатору, создавая и кэшируя его при первом обращении.
     *
     * @throws ContainerException Если сервис не зарегистрирован и не может быть собран автоматически.
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            $instance = ($this->bindings[$id])($this);

            if (!is_object($instance)) {
                throw new ContainerException('Container factory must return an object for: ' . $id);
            }

            $this->instances[$id] = $instance;

            return $instance;
        }

        $instance = $this->build($id);
        $this->instances[$id] = $instance;

        return $instance;
    }

    /**
     * Автоматически создаёт объект по FQCN через Reflection.
     *
     * @throws ContainerException
     */
    private function build(string $id): object
    {
        try {
            $reflection = new ReflectionClass($id);
        } catch (ReflectionException $e) {
            throw new ContainerException('Container binding not found: ' . $id, 0, $e);
        }

        if (!$reflection->isInstantiable()) {
            throw new ContainerException('Target class is not instantiable: ' . $id);
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new ContainerException(sprintf(
                'Unable to resolve parameter $%s for %s::__construct()',
                $parameter->getName(),
                $id
            ));
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
