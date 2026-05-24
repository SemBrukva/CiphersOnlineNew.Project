<?php

declare(strict_types=1);

namespace App\Http\Exception;

/**
 * Исключение для запрещённых API-операций.
 */
final class ForbiddenException extends HttpException
{
    /**
     * Создаёт исключение с кодом 403.
     */
    public function __construct(string $message = 'Forbidden')
    {
        parent::__construct($message, 403, 'forbidden');
    }
}
