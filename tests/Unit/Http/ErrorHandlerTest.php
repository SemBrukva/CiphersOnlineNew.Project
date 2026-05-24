<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use App\Http\ErrorHandler;
use App\Http\Exception\ForbiddenException;
use App\Http\RequestContext;
use App\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Проверяет формирование API-ошибок в ErrorHandler.
 */
final class ErrorHandlerTest extends TestCase
{
    /**
     * Проверяет ответ 422 для ValidationException с деталями по полям.
     */
    public function testHandleApiReturns422ForValidationException(): void
    {
        $handler = new ErrorHandler(null, new RequestContext('req-1', microtime(true), true));
        $response = $handler->handleApi(new ValidationException([
            'email' => ['Invalid email.'],
        ]));

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('validation_failed', $payload['error']['code']);
        self::assertSame(['email' => ['Invalid email.']], $payload['error']['details']['errors']);
        self::assertSame('req-1', $payload['request_id']);
    }

    /**
     * Проверяет ответ 403 для HttpException-потомка.
     */
    public function testHandleApiReturnsStatusFromHttpException(): void
    {
        $handler = new ErrorHandler(null, new RequestContext('req-2', microtime(true), true));
        $response = $handler->handleApi(new ForbiddenException());

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('forbidden', $payload['error']['code']);
        self::assertSame('Forbidden', $payload['error']['message']);
        self::assertSame('req-2', $payload['request_id']);
    }

    /**
     * Проверяет fallback-ответ 500 для неожиданных исключений.
     */
    public function testHandleApiReturns500ForUnexpectedException(): void
    {
        $handler = new ErrorHandler(null, new RequestContext('req-3', microtime(true), true));
        $response = $handler->handleApi(new RuntimeException('Boom'));

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('internal_error', $payload['error']['code']);
        self::assertSame('Internal Server Error', $payload['error']['message']);
        self::assertSame('req-3', $payload['request_id']);
    }
}
