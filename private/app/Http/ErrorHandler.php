<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Exception\HttpException;
use App\Log\LoggerInterface;
use App\Validation\ValidationException;
use Throwable;

/**
 * HTTP-обработчик непойманных исключений из pipeline.
 *
 * Логирует исключение через Logger и возвращает Response с кодом 500.
 * В debug-режиме включает в ответ сообщение и трассировку стека.
 */
final class ErrorHandler
{
    /**
     * Создаёт экземпляр обработчика.
     *
     * @param LoggerInterface|null $logger Сервис логирования; null допустим только в тестах.
     */
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        private readonly ?RequestContext $context = null
    ) {
    }

    /**
     * Обрабатывает исключение: логирует и формирует HTTP-ответ.
     */
    public function handle(Throwable $exception): Response
    {
        $this->log($exception);

        $isDebug = config('app.debug', false);

        if ($isDebug) {
            $content = sprintf(
                '<h1>%s</h1><pre>%s</pre>',
                htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8')
            );
        } else {
            $content = 'Internal Server Error';
        }

        return new Response($content, 500);
    }

    /**
     * Обрабатывает исключение для API: логирует и возвращает JSON-ответ.
     */
    public function handleApi(Throwable $exception): Response
    {
        $this->log($exception);

        $isDebug   = config('app.debug', false);
        $requestId = $this->context !== null ? $this->context->requestId : '';
        $statusCode = 500;
        $error = [
            'code' => 'internal_error',
            'message' => 'Internal Server Error',
        ];

        if ($exception instanceof ValidationException) {
            $statusCode = $exception->httpStatusCode();
            $error = [
                'code' => 'validation_failed',
                'message' => $exception->getMessage(),
                'details' => [
                    'errors' => $exception->errors(),
                ],
            ];
        } elseif ($exception instanceof HttpException) {
            $statusCode = $exception->statusCode();
            $error = [
                'code' => $exception->errorCode(),
                'message' => $exception->getMessage(),
            ];

            if ($exception->details() !== []) {
                $error['details'] = $exception->details();
            }
        }

        if ($isDebug) {
            $error['debug'] = [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return Response::json([
            'error' => $error,
            'request_id' => $requestId,
        ], $statusCode);
    }

    /**
     * Записывает исключение в лог.
     * Оборачивает в try/catch — сбой логирования не должен маскировать исходную ошибку.
     */
    private function log(Throwable $exception): void
    {
        if ($this->logger === null) {
            return;
        }

        try {
            $this->logger->critical(
                'HTTP exception: {class}: {message}',
                [
                    'class' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                    'request_id' => $this->context !== null ? $this->context->requestId : '',
                    'request_method' => (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
                    'request_uri' => (string) ($_SERVER['REQUEST_URI'] ?? '/'),
                    'ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli'),
                ]
            );
        } catch (Throwable) {
            // не перебиваем исходное исключение
        }
    }
}
