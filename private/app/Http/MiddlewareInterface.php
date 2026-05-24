<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Контракт для всех middleware в цепочке обработки HTTP-запроса.
 */
interface MiddlewareInterface
{
    /**
     * Обрабатывает входящий запрос и передаёт управление следующему обработчику.
     */
    public function process(Request $request, callable $next): Response;
}
