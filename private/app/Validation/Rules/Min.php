<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

/**
 * Правило: значение должно быть не меньше заданного минимума.
 *
 * Для чисел проверяется числовое значение, для строк — длина в символах,
 * для массивов — количество элементов.
 */
final class Min implements RuleInterface
{
    /**
     * Создаёт правило с минимально допустимым значением.
     */
    public function __construct(private readonly float $min)
    {
    }

    /**
     * Проверяет, что значение не меньше минимума.
     */
    public function validate(string $field, mixed $value, array $data): bool
    {
        if (is_numeric($value)) {
            return $value >= $this->min;
        }

        if (is_array($value)) {
            return count($value) >= $this->min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $this->min;
        }

        return false;
    }

    /**
     * Возвращает сообщение об ошибке.
     */
    public function message(string $field): string
    {
        return "The {$field} must be at least {$this->min}.";
    }
}
