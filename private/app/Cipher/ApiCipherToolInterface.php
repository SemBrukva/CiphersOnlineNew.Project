<?php

declare(strict_types=1);

namespace App\Cipher;

/**
 * Контракт API-инструмента шифрования.
 */
interface ApiCipherToolInterface
{
    /**
     * Возвращает уникальный идентификатор инструмента (api action).
     */
    public function action(): string;

    /**
     * Выполняет обработку входных данных и возвращает API-результат.
     *
     * @param  array<string, mixed> $payload JSON-полезная нагрузка запроса.
     * @return array<string, mixed>          Данные для JSON-ответа.
     */
    public function execute(array $payload): array;
}
