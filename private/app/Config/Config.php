<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Хранилище конфигурации приложения.
 *
 * Загружает PHP-файлы из указанной директории, используя имя файла
 * как ключ верхнего уровня. Доступ к значениям — через точечную нотацию.
 */
final class Config
{
    /**
     * Создаёт экземпляр конфигурации.
     *
     * @param array<string, mixed> $items Начальный набор конфигурационных данных.
     */
    public function __construct(
        private array $items = []
    ) {
    }

    /**
     * Загружает все PHP-файлы из директории; имя файла становится ключом верхнего уровня.
     */
    public function load(string $path): void
    {
        foreach (glob($path . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            $this->items[$key] = require $file;
        }
    }

    /**
     * Загружает конфигурацию из кеш-файла.
     *
     * @return bool Возвращает true, если кеш успешно загружен.
     */
    public function loadFromCache(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        $data = require $path;

        if (!is_array($data)) {
            return false;
        }

        $this->items = $data;

        return true;
    }

    /**
     * Возвращает значение конфигурации по ключу с поддержкой точечной нотации.
     * Возвращает $default, если ключ не найден.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Возвращает все загруженные конфигурационные данные.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }
}
