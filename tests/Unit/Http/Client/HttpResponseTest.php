<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Client;

use App\Http\Client\HttpException;
use App\Http\Client\HttpResponse;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет поведение объекта HTTP-ответа.
 */
final class HttpResponseTest extends TestCase
{
    /**
     * Проверяет, что status() возвращает переданный код.
     */
    public function testStatusReturnsGivenCode(): void
    {
        $response = new HttpResponse(200, [], '');

        self::assertSame(200, $response->status());
    }

    /**
     * Проверяет ok() для различных кодов в диапазоне 2xx.
     */
    public function testOkIsTrueFor2xxCodes(): void
    {
        self::assertTrue((new HttpResponse(200, [], ''))->ok());
        self::assertTrue((new HttpResponse(201, [], ''))->ok());
        self::assertTrue((new HttpResponse(204, [], ''))->ok());
        self::assertTrue((new HttpResponse(299, [], ''))->ok());
    }

    /**
     * Проверяет ok() для кодов вне диапазона 2xx.
     */
    public function testOkIsFalseOutside2xx(): void
    {
        self::assertFalse((new HttpResponse(301, [], ''))->ok());
        self::assertFalse((new HttpResponse(400, [], ''))->ok());
        self::assertFalse((new HttpResponse(500, [], ''))->ok());
    }

    /**
     * Проверяет, что successful() ведёт себя идентично ok().
     */
    public function testSuccessfulIsSameAsOk(): void
    {
        $ok  = new HttpResponse(200, [], '');
        $bad = new HttpResponse(400, [], '');

        self::assertTrue($ok->successful());
        self::assertFalse($bad->successful());
    }

    /**
     * Проверяет clientError() для кодов 4xx.
     */
    public function testClientErrorIsTrueFor4xxCodes(): void
    {
        self::assertTrue((new HttpResponse(400, [], ''))->clientError());
        self::assertTrue((new HttpResponse(401, [], ''))->clientError());
        self::assertTrue((new HttpResponse(404, [], ''))->clientError());
        self::assertTrue((new HttpResponse(422, [], ''))->clientError());
        self::assertTrue((new HttpResponse(499, [], ''))->clientError());
    }

    /**
     * Проверяет clientError() для кодов вне 4xx.
     */
    public function testClientErrorIsFalseOutside4xx(): void
    {
        self::assertFalse((new HttpResponse(200, [], ''))->clientError());
        self::assertFalse((new HttpResponse(301, [], ''))->clientError());
        self::assertFalse((new HttpResponse(500, [], ''))->clientError());
    }

    /**
     * Проверяет serverError() для кодов 5xx.
     */
    public function testServerErrorIsTrueFor5xxCodes(): void
    {
        self::assertTrue((new HttpResponse(500, [], ''))->serverError());
        self::assertTrue((new HttpResponse(502, [], ''))->serverError());
        self::assertTrue((new HttpResponse(503, [], ''))->serverError());
        self::assertTrue((new HttpResponse(599, [], ''))->serverError());
    }

    /**
     * Проверяет serverError() для кодов вне 5xx.
     */
    public function testServerErrorIsFalseOutside5xx(): void
    {
        self::assertFalse((new HttpResponse(200, [], ''))->serverError());
        self::assertFalse((new HttpResponse(404, [], ''))->serverError());
    }

    /**
     * Проверяет failed() для 4xx и 5xx.
     */
    public function testFailedIsTrueFor4xxAnd5xx(): void
    {
        self::assertTrue((new HttpResponse(400, [], ''))->failed());
        self::assertTrue((new HttpResponse(404, [], ''))->failed());
        self::assertTrue((new HttpResponse(500, [], ''))->failed());
        self::assertTrue((new HttpResponse(503, [], ''))->failed());
    }

    /**
     * Проверяет failed() для 2xx.
     */
    public function testFailedIsFalseFor2xx(): void
    {
        self::assertFalse((new HttpResponse(200, [], ''))->failed());
        self::assertFalse((new HttpResponse(201, [], ''))->failed());
    }

    /**
     * Проверяет body() возвращает переданную строку без изменений.
     */
    public function testBodyReturnsRawString(): void
    {
        $response = new HttpResponse(200, [], 'plain text body');

        self::assertSame('plain text body', $response->body());
    }

    /**
     * Проверяет json() без ключа — возвращает весь декодированный массив.
     */
    public function testJsonDecodesEntireBody(): void
    {
        $response = new HttpResponse(200, [], '{"foo":"bar","num":42}');

        $data = $response->json();
        self::assertSame('bar', $data['foo']);
        self::assertSame(42, $data['num']);
    }

    /**
     * Проверяет json($key) — возвращает конкретное значение.
     */
    public function testJsonReturnsValueByKey(): void
    {
        $response = new HttpResponse(200, [], '{"user":{"name":"Alice"}}');

        self::assertSame(['name' => 'Alice'], $response->json('user'));
    }

    /**
     * Проверяет json($key) возвращает $default при отсутствующем ключе.
     */
    public function testJsonReturnsDefaultForMissingKey(): void
    {
        $response = new HttpResponse(200, [], '{"foo":"bar"}');

        self::assertNull($response->json('missing'));
        self::assertSame('fallback', $response->json('missing', 'fallback'));
    }

    /**
     * Проверяет json() возвращает $default при невалидном JSON.
     */
    public function testJsonReturnsDefaultForInvalidJson(): void
    {
        $response = new HttpResponse(200, [], 'not a json');

        self::assertNull($response->json());
        self::assertSame([], $response->json(null, []));
    }

    /**
     * Проверяет header() выполняет поиск без учёта регистра.
     */
    public function testHeaderReturnsCaseInsensitively(): void
    {
        $response = new HttpResponse(200, ['Content-Type' => 'application/json'], '');

        self::assertSame('application/json', $response->header('Content-Type'));
        self::assertSame('application/json', $response->header('content-type'));
        self::assertSame('application/json', $response->header('CONTENT-TYPE'));
    }

    /**
     * Проверяет header() возвращает null при отсутствующем заголовке.
     */
    public function testHeaderReturnsNullForMissingHeader(): void
    {
        $response = new HttpResponse(200, [], '');

        self::assertNull($response->header('X-Missing'));
    }

    /**
     * Проверяет headers() возвращает все заголовки.
     */
    public function testHeadersReturnsAllHeaders(): void
    {
        $headers = ['Content-Type' => 'application/json', 'X-Request-Id' => 'abc123'];
        $response = new HttpResponse(200, $headers, '');

        self::assertSame($headers, $response->headers());
    }

    /**
     * Проверяет throw() возвращает $this для 2xx-ответа.
     */
    public function testThrowReturnsSelfFor2xxResponse(): void
    {
        $response = new HttpResponse(200, [], '');

        self::assertSame($response, $response->throw());
    }

    /**
     * Проверяет throw() бросает HttpException для 4xx.
     */
    public function testThrowThrowsHttpExceptionFor4xx(): void
    {
        $response = new HttpResponse(404, [], '{"error":"not found"}');

        $this->expectException(HttpException::class);
        $response->throw();
    }

    /**
     * Проверяет throw() бросает HttpException для 5xx.
     */
    public function testThrowThrowsHttpExceptionFor5xx(): void
    {
        $response = new HttpResponse(500, [], '');

        $this->expectException(HttpException::class);
        $response->throw();
    }

    /**
     * Проверяет, что HttpException содержит исходный HttpResponse.
     */
    public function testThrowExceptionContainsOriginalResponse(): void
    {
        $response = new HttpResponse(422, [], '{"message":"Unprocessable"}');

        try {
            $response->throw();
            self::fail('Ожидалось исключение HttpException.');
        } catch (HttpException $e) {
            self::assertSame($response, $e->response());
            self::assertSame(422, $e->response()->status());
        }
    }
}
