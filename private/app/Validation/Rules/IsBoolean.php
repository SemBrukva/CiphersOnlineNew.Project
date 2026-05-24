<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

/**
 * Правило: значение должно быть булевым (true/false, 1/0, «yes»/«no» и т.д.).
 */
final class IsBoolean implements RuleInterface
{
    /**
     * Проверяет, что значение интерпретируется как булево.
     */
    public function validate(string $field, mixed $value, array $data): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) !== null;
    }

    /**
     * Возвращает сообщение об ошибке.
     */
    public function message(string $field): string
    {
        return "The {$field} must be a boolean.";
    }
}
