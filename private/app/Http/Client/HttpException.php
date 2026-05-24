<?php

declare(strict_types=1);

namespace App\Http\Client;

use RuntimeException;

/**
 * Исключение HTTP-клиента при ответе с кодом 4xx или 5xx.
 */
final class HttpException extends RuntimeException
{
    /**
     * @param HttpResponse $response HTTP-ответ, вызвавший исключение.
     */
    public function __construct(private readonly HttpResponse $response)
    {
        parent::__construct(
            "HTTP request returned status code {$response->status()}."
        );
    }

    /**
     * Возвращает HTTP-ответ, вызвавший исключение.
     */
    public function response(): HttpResponse
    {
        return $this->response;
    }
}
