<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Client;

use App\Http\Client\HttpException;
use App\Http\Client\HttpResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Проверяет поведение исключения HttpException.
 */
final class HttpExceptionTest extends TestCase
{
    /**
     * Проверяет, что exception является RuntimeException.
     */
    public function testExtendsRuntimeException(): void
    {
        $exception = new HttpException(new HttpResponse(500, [], ''));

        self::assertInstanceOf(RuntimeException::class, $exception);
    }

    /**
     * Проверяет, что response() возвращает переданный HttpResponse.
     */
    public function testResponseReturnsGivenHttpResponse(): void
    {
        $response  = new HttpResponse(404, ['X-Id' => 'abc'], '{"error":"not found"}');
        $exception = new HttpException($response);

        self::assertSame($response, $exception->response());
    }

    /**
     * Проверяет, что сообщение исключения содержит статус-код.
     */
    public function testMessageContainsStatusCode(): void
    {
        $exception = new HttpException(new HttpResponse(422, [], ''));

        self::assertStringContainsString('422', $exception->getMessage());
    }

    /**
     * Проверяет доступ к данным ответа через exception->response().
     */
    public function testResponseDataIsAccessibleFromException(): void
    {
        $response  = new HttpResponse(503, ['Retry-After' => '60'], '{"retry":true}');
        $exception = new HttpException($response);

        self::assertSame(503, $exception->response()->status());
        self::assertTrue($exception->response()->serverError());
        self::assertSame('60', $exception->response()->header('Retry-After'));
        self::assertTrue((bool) $exception->response()->json('retry'));
    }
}
