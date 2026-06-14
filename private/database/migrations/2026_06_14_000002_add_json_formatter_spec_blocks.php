<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет блоки «JSON Specification» к инструменту JSON Formatter / Validator.
 */
class AddJsonFormatterSpecBlocks extends Migration
{
    /**
     * Добавляет блоки о типах данных и правилах синтаксиса JSON.
     */
    public function up(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['json-formatter']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $now = date('Y-m-d H:i:s');

        $block3 = $this->upsertBlock($cipherId, 30, $now);
        $this->upsertBlockTranslation($block3, 'en', 'JSON data types', '<p>JSON supports exactly six value types:</p><ul><li><strong>String</strong> — a sequence of Unicode characters in double quotes. Special characters must be escaped with a backslash: <code>\"</code>, <code>\\</code>, <code>\/</code>, <code>\n</code>, <code>\r</code>, <code>\t</code>, <code>\uXXXX</code>. Example: <code>"Hello, world!"</code></li><li><strong>Number</strong> — an integer or floating-point value. Leading zeros, <code>Infinity</code>, and <code>NaN</code> are not allowed. Example: <code>42</code>, <code>-3.14</code>, <code>1.5e10</code></li><li><strong>Boolean</strong> — exactly <code>true</code> or <code>false</code> (lowercase only).</li><li><strong>Null</strong> — exactly <code>null</code> (lowercase only), representing the absence of a value.</li><li><strong>Object</strong> — an unordered collection of key-value pairs wrapped in <code>{}</code>. Keys must be strings. Example: <code>{"name": "Alice", "age": 30}</code></li><li><strong>Array</strong> — an ordered list of values wrapped in <code>[]</code>. Values may be of any JSON type and may be mixed. Example: <code>[1, "two", true, null]</code></li></ul>', $now);
        $this->upsertBlockTranslation($block3, 'ru', 'Типы данных JSON', '<p>JSON поддерживает ровно шесть типов значений:</p><ul><li><strong>String (строка)</strong> — последовательность Unicode-символов в двойных кавычках. Специальные символы экранируются обратным слешем: <code>\"</code>, <code>\\</code>, <code>\/</code>, <code>\n</code>, <code>\r</code>, <code>\t</code>, <code>\uXXXX</code>. Пример: <code>"Привет, мир!"</code></li><li><strong>Number (число)</strong> — целое или число с плавающей точкой. Ведущие нули, <code>Infinity</code> и <code>NaN</code> запрещены. Примеры: <code>42</code>, <code>-3.14</code>, <code>1.5e10</code></li><li><strong>Boolean (логический)</strong> — строго <code>true</code> или <code>false</code> (только в нижнем регистре).</li><li><strong>Null</strong> — строго <code>null</code> (только в нижнем регистре), означает отсутствие значения.</li><li><strong>Object (объект)</strong> — неупорядоченный набор пар ключ-значение в <code>{}</code>. Ключи должны быть строками. Пример: <code>{"name": "Alice", "age": 30}</code></li><li><strong>Array (массив)</strong> — упорядоченный список значений в <code>[]</code>. Элементы могут быть любого JSON-типа и могут смешиваться. Пример: <code>[1, "два", true, null]</code></li></ul>', $now);

        $block4 = $this->upsertBlock($cipherId, 40, $now);
        $this->upsertBlockTranslation($block4, 'en', 'JSON syntax rules', '<p>A few rules that frequently cause JSON validation errors:</p><ul><li><strong>No trailing commas.</strong> <code>{"a": 1,}</code> and <code>[1, 2,]</code> are invalid. The last element in an object or array must not be followed by a comma.</li><li><strong>No comments.</strong> JSON does not support <code>// line</code> or <code>/* block */</code> comments. Strip them before parsing.</li><li><strong>Double quotes only.</strong> String keys and values must use <code>"double quotes"</code>. Single quotes (<code>\'</code>) and backticks are not allowed.</li><li><strong>No undefined or functions.</strong> Only the six types listed above are valid. JavaScript values like <code>undefined</code>, <code>NaN</code>, <code>Infinity</code>, and functions cannot be represented in JSON.</li><li><strong>Object keys must be unique.</strong> Duplicate keys within the same object are technically allowed by the spec but produce undefined behaviour; most parsers use the last value silently.</li><li><strong>Top-level value can be any type.</strong> A valid JSON document may be a string, number, boolean, null, object, or array — not just an object.</li></ul>', $now);
        $this->upsertBlockTranslation($block4, 'ru', 'Правила синтаксиса JSON', '<p>Несколько правил, которые чаще всего вызывают ошибки валидации JSON:</p><ul><li><strong>Никаких запятых в конце.</strong> <code>{"a": 1,}</code> и <code>[1, 2,]</code> — невалидный JSON. После последнего элемента объекта или массива запятая не ставится.</li><li><strong>Никаких комментариев.</strong> JSON не поддерживает <code>// строчные</code> и <code>/* блочные */</code> комментарии. Удалите их перед парсингом.</li><li><strong>Только двойные кавычки.</strong> Ключи и строковые значения должны быть в <code>"двойных кавычках"</code>. Одиночные кавычки (<code>\'</code>) и обратные апострофы не допускаются.</li><li><strong>Нет undefined и функций.</strong> Допустимы только шесть перечисленных выше типов. JavaScript-значения <code>undefined</code>, <code>NaN</code>, <code>Infinity</code> и функции не могут быть представлены в JSON.</li><li><strong>Ключи объекта должны быть уникальными.</strong> Дублирующиеся ключи в одном объекте технически допустимы спецификацией, но поведение не определено; большинство парсеров молча используют последнее значение.</li><li><strong>Корневым значением может быть любой тип.</strong> Валидный JSON-документ — это строка, число, булево, null, объект или массив, не только объект.</li></ul>', $now);
    }

    /**
     * Удаляет добавленные блоки.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['json-formatter']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];

        foreach ([30, 40] as $sortOrder) {
            $block = $this->db->fetch(
                'SELECT id FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
                [$cipherId, $sortOrder]
            );
            if ($block !== false) {
                $blockId = (int) $block['id'];
                $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ?', [$blockId]);
                $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE id = ?', [$blockId]);
            }
        }
    }

    /**
     * Создаёт или обновляет блок.
     */
    private function upsertBlock(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_BLOCKS . ' SET published = 1, updated_at = ? WHERE id = ?',
                [$now, (int) $row['id']]
            );
            return (int) $row['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод блока.
     */
    private function upsertBlockTranslation(int $blockId, string $language, string $title, string $text, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ? LIMIT 1',
            [$blockId, $language]
        );

        if ($existing !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS
                . ' SET `title` = ?, `text` = ?, updated_at = ? WHERE id = ?',
                [$title, $text, $now, (int) $existing['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS
            . ' (`block_id`, `language`, `title`, `text`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?)',
            [$blockId, $language, $title, $text, $now, $now]
        );
    }
}
