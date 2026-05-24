<?php

declare(strict_types=1);

namespace App\Http\Exception;

/**
 * Исключение для неавторизованных API-запросов.
 */
final class UnauthorizedException extends HttpException
{
    /**
     * Создаёт исключение с кодом 401.
     */
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 401, 'unauthorized');
    }
}
