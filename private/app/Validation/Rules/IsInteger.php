<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

/**
 * Правило: значение должно быть целым числом.
 */
final class IsInteger implements RuleInterface
{
    /**
     * Проверяет, что значение является целым числом.
     */
    public function validate(string $field, mixed $value, array $data): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Возвращает сообщение об ошибке.
     */
    public function message(string $field): string
    {
        return "The {$field} must be an integer.";
    }
}
