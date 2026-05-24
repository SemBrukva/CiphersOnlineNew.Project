<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Интерфейс проверки наличия маршрута без его диспетчеризации.
 */
interface RouteMatcherInterface
{
    /**
     * Возвращает определение маршрута для HTTP-метода и пути или null, если маршрут не найден.
     *
     * @return array<string, mixed>|null
     */
    public function match(string $method, string $path): ?array;
}
