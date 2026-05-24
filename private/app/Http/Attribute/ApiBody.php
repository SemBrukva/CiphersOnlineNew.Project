<?php

declare(strict_types=1);

namespace App\Http\Attribute;

use Attribute;

/**
 * Документирует тело запроса API-операции.
 *
 * Если указан FQCN FormRequest-класса, схема извлекается автоматически
 * из его метода rules().
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class ApiBody
{
    /**
     * @param string $class       FQCN класса FormRequest для автоматического вывода схемы.
     * @param string $description Описание тела запроса.
     * @param bool   $required    Признак обязательности тела.
     */
    public function __construct(
        public string $class = '',
        public string $description = '',
        public bool $required = true,
    ) {
    }
}
