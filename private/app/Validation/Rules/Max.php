<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

/**
 * Правило: значение не должно превышать заданный максимум.
 *
 * Для чисел проверяется числовое значение, для строк — длина в символах,
 * для массивов — количество элементов.
 */
final class Max implements RuleInterface
{
    /**
     * Создаёт правило с максимально допустимым значением.
     */
    public function __construct(private readonly float $max)
    {
    }

    /**
     * Проверяет, что значение не превышает максимум.
     */
    public function validate(string $field, mixed $value, array $data): bool
    {
        if (is_numeric($value)) {
            return $value <= $this->max;
        }

        if (is_array($value)) {
            return count($value) <= $this->max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $this->max;
        }

        return false;
    }

    /**
     * Возвращает сообщение об ошибке.
     */
    public function message(string $field): string
    {
        return "The {$field} must not exceed {$this->max}.";
    }
}
