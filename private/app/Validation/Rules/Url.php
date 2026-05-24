<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

/**
 * Правило: значение должно быть корректным URL.
 */
final class Url implements RuleInterface
{
    /**
     * Проверяет, является ли значение валидным URL.
     */
    public function validate(string $field, mixed $value, array $data): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Возвращает сообщение об ошибке.
     */
    public function message(string $field): string
    {
        return "The {$field} must be a valid URL.";
    }
}
