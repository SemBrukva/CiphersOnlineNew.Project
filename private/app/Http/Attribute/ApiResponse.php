<?php

declare(strict_types=1);

namespace App\Http\Attribute;

use Attribute;

/**
 * Документирует один вариант ответа API-операции.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class ApiResponse
{
    /**
     * @param int                  $status      HTTP-статус ответа.
     * @param string               $description Описание ответа.
     * @param array<string, mixed> $schema      Inline-схема тела ответа (OpenAPI Schema Object).
     */
    public function __construct(
        public int $status = 200,
        public string $description = '',
        public array $schema = [],
    ) {
    }
}
