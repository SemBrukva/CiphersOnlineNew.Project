<?php

declare(strict_types=1);

namespace App\Http\Attribute;

use Attribute;

/**
 * Документирует параметр API-операции (path, query, header, cookie).
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class ApiParam
{
    /**
     * @param string $name        Имя параметра.
     * @param string $in          Расположение: path | query | header | cookie.
     * @param string $description Описание параметра.
     * @param bool   $required    Признак обязательности.
     * @param string $type        OpenAPI-тип: string | integer | number | boolean.
     * @param mixed  $example     Пример значения.
     */
    public function __construct(
        public string $name,
        public string $in = 'path',
        public string $description = '',
        public bool $required = true,
        public string $type = 'string',
        public mixed $example = null,
    ) {
    }
}
