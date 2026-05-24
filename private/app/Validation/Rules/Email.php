<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

/**
 * Правило: значение должно быть корректным адресом электронной почты.
 */
final class Email implements RuleInterface
{
    /**
     * Проверяет, является ли значение валидным e-mail адресом.
     */
    public function validate(string $field, mixed $value, array $data): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Возвращает сообщение об ошибке.
     */
    public function message(string $field): string
    {
        return "The {$field} must be a valid email address.";
    }
}
