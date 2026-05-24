<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

/**
 * Правило: поле обязательно для заполнения и не должно быть пустым.
 */
final class Required implements RuleInterface
{
    /**
     * Проверяет наличие поля в данных и что его значение не пустое.
     */
    public function validate(string $field, mixed $value, array $data): bool
    {
        if (!array_key_exists($field, $data)) {
            return false;
        }

        return $value !== null && $value !== '' && $value !== [];
    }

    /**
     * Возвращает сообщение об ошибке.
     */
    public function message(string $field): string
    {
        return "The {$field} field is required.";
    }
}
