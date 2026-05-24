<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

/**
 * Правило: значение должно быть строкой.
 */
final class IsString implements RuleInterface
{
    /**
     * Проверяет, что значение имеет тип string.
     */
    public function validate(string $field, mixed $value, array $data): bool
    {
        return is_string($value);
    }

    /**
     * Возвращает сообщение об ошибке.
     */
    public function message(string $field): string
    {
        return "The {$field} must be a string.";
    }
}
