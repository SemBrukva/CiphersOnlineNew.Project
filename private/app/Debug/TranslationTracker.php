<?php

declare(strict_types=1);

namespace App\Debug;

use ArrayAccess;

/**
 * Обёртка над массивом переводов с трекингом обращений по ключам.
 *
 * Реализует ArrayAccess, поэтому Smarty-шаблоны могут использовать её
 * как обычный массив: {$t.KEY_NAME}. В момент каждого обращения фиксирует
 * использованный ключ или помечает его как отсутствующий.
 *
 * Является мутабельным синглтоном — переводы загружаются отложенно
 * через load() после инициализации локали.
 */
/** @implements ArrayAccess<string, string> */
final class TranslationTracker implements ArrayAccess
{
    /** @var array<string, string> Загруженные переводы. */
    private array $translations = [];

    /** @var array<string, string> Ключи, к которым обращались, с их значениями. */
    private array $used = [];

    /** @var string[] Ключи, которые запрашивались, но не найдены в переводах. */
    private array $missing = [];

    /**
     * Загружает массив переводов для текущей локали.
     * Вызывается из ShareViewDataMiddleware после инициализации локали.
     *
     * @param array<string, string> $translations
     */
    public function load(array $translations): void
    {
        $this->translations = $translations;
        $this->used         = [];
        $this->missing      = [];
    }

    /**
     * Возвращает значение перевода по ключу и фиксирует обращение.
     * Если ключ не найден — возвращает сам ключ (как Translator::get()).
     */
    public function offsetGet(mixed $offset): string
    {
        $key = (string) $offset;

        if (array_key_exists($key, $this->translations)) {
            $this->used[$key] = $this->translations[$key];
            return $this->translations[$key];
        }

        if (!in_array($key, $this->missing, true)) {
            $this->missing[] = $key;
        }

        return $key;
    }

    /**
     * Проверяет существование ключа в загруженных переводах.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->translations[(string) $offset]);
    }

    /** @throws \LogicException Массив переводов неизменяем. */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('TranslationTracker is read-only.');
    }

    /** @throws \LogicException Массив переводов неизменяем. */
    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('TranslationTracker is read-only.');
    }

    /**
     * Возвращает использованные ключи с их переведёнными значениями.
     *
     * @return array<string, string>
     */
    public function getUsed(): array
    {
        return $this->used;
    }

    /**
     * Возвращает список ключей, которые запрашивались, но не найдены.
     *
     * @return string[]
     */
    public function getMissing(): array
    {
        return $this->missing;
    }
}
