<?php

declare(strict_types=1);

namespace App\Http\Exception;

/**
 * Исключение для отсутствующего API-ресурса или отключённого эндпоинта.
 */
final class NotFoundException extends HttpException
{
    /**
     * Создаёт исключение с кодом 404.
     */
    public function __construct(string $message = 'Not Found')
    {
        parent::__construct($message, 404, 'not_found');
    }
}
