<?php

declare(strict_types=1);

namespace App\Validation\Rules;

use App\Validation\RuleInterface;

/**
 * Правило: значение должно входить в заданный список допустимых значений.
 */
final class In implements RuleInterface
{
    /** @var string[] Список допустимых значений. */
    private array $allowed;

    /**
     * Создаёт правило с перечнем допустимых значений.
     *
     * @param string[] $allowed Допустимые значения.
     */
    public function __construct(array $allowed)
    {
        $this->allowed = $allowed;
    }

    /**
     * Проверяет, что значение присутствует в списке допустимых.
     */
    public function validate(string $field, mixed $value, array $data): bool
    {
        return in_array((string) $value, $this->allowed, strict: true);
    }

    /**
     * Возвращает сообщение об ошибке.
     */
    public function message(string $field): string
    {
        return "The {$field} must be one of: " . implode(', ', $this->allowed) . '.';
    }
}
