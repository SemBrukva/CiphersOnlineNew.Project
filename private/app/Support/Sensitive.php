<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Утилита для маскирования чувствительных значений в логах и отладочной информации.
 *
 * Содержит общий список имён ключей (паролей, токенов, CSRF и т.д.), которые
 * нельзя выводить в логи или показывать в debug-панели.
 */
final class Sensitive
{
    /** @var string[] Подстроки, по которым ключи считаются чувствительными. */
    public const array KEYS = [
        'password',
        'passwd',
        'pass',
        'password_confirmation',
        'current_password',
        'secret',
        'token',
        'access_token',
        'refresh_token',
        '_csrf_token',
        'auth',
        'authorization',
        'cookie',
        'api_key',
        'card',
        'cvv',
        'pin',
        'private',
    ];

    /**
     * Проверяет, является ли имя ключа чувствительным (нестрогое сравнение по подстроке).
     *
     * @param  string   $key  Имя проверяемого ключа.
     * @param  string[] $keys Список чувствительных подстрок (по умолчанию — общий список).
     */
    public static function isSensitive(string $key, array $keys = self::KEYS): bool
    {
        $lower = strtolower($key);

        foreach ($keys as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Рекурсивно маскирует значения чувствительных ключей в массиве.
     *
     * @param  array<int|string, mixed> $data        Исходный массив.
     * @param  string                   $replacement Чем заменять значение.
     * @param  string[]                 $keys        Список подстрок (по умолчанию — общий список).
     * @return array<int|string, mixed>
     */
    public static function mask(array $data, string $replacement = '***', array $keys = self::KEYS): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && self::isSensitive($key, $keys)) {
                $data[$key] = $replacement;
                continue;
            }

            if (is_array($value)) {
                $data[$key] = self::mask($value, $replacement, $keys);
            }
        }

        return $data;
    }
}
