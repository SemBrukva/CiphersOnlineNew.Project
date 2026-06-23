<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Контракт для исполнения API-инструмента шифрования по строковому action.
 */
interface ApiCipherToolExecutorInterface
{
    /**
     * Выполняет инструмент по action и возвращает результат.
     *
     * @param  string               $action  Идентификатор инструмента.
     * @param  array<string, mixed> $payload Входные данные запроса.
     * @return array<string, mixed>
     */
    public function execute(string $action, array $payload): array;
}
