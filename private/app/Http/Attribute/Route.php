<?php

declare(strict_types=1);

namespace App\Http\Attribute;

use Attribute;

/**
 * Атрибут маршрута для регистрации экшена контроллера без правки config/routes.php.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final readonly class Route
{
    /**
     * @param string[] $middleware Список middleware-классов для маршрута.
     */
    public function __construct(
        public string $method,
        public string $path,
        public ?string $name = null,
        public array $middleware = [],
        public ?string $group = null
    ) {
    }
}
