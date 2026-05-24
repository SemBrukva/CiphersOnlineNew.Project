<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

/**
 * Правило: значение должно быть числом (целым или дробным).
 */
final class Numeric implements RuleInterface
{
    /**
     * Проверяет, что значение является числовым.
     */
    public function validate(string $field, mixed $value, array $data): bool
    {
        return is_numeric($value);
    }

    /**
     * Возвращает сообщение об ошибке.
     */
    public function message(string $field): string
    {
        return "The {$field} must be a number.";
    }
}
