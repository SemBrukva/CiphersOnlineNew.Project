<?php

declare(strict_types=1);

namespace App\Http\Attribute;

use Attribute;

/**
 * Документирует метаданные операции API: заголовок, описание, теги.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class ApiOperation
{
    /**
     * @param string   $summary     Краткий заголовок операции.
     * @param string   $description Подробное описание.
     * @param string[] $tags        Теги для группировки в документации.
     * @param bool     $deprecated  Признак устаревшей операции.
     */
    public function __construct(
        public string $summary = '',
        public string $description = '',
        public array $tags = [],
        public bool $deprecated = false,
    ) {
    }
}
