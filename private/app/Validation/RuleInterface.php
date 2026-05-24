<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Контракт для правил валидации.
 */
interface RuleInterface
{
    /**
     * Проверяет значение поля и возвращает true, если оно прошло правило.
     *
     * @param array<string, mixed> $data Все данные формы (для правил с зависимостями между полями).
     */
    public function validate(string $field, mixed $value, array $data): bool;

    /**
     * Возвращает сообщение об ошибке для указанного поля.
     */
    public function message(string $field): string;
}
