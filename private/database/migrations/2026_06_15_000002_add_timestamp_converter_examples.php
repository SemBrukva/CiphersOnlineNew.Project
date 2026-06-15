<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет 4 примера к инструменту Timestamp Converter.
 */
class AddTimestampConverterExamples extends Migration
{
    /**
     * Добавляет примеры.
     */
    public function up(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['timestamp-converter']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $now      = date('Y-m-d H:i:s');

        // Пример 1: Unix Epoch — encode (Timestamp → Date)
        $e1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation(
            $e1,
            'en',
            'Unix Epoch',
            '0',
            '1970-01-01T00:00:00.000Z',
            'Every Unix timestamp counts seconds from this moment — midnight UTC on 1 January 1970.',
            $now
        );
        $this->upsertExampleTranslation(
            $e1,
            'ru',
            'Unix-эпоха',
            '0',
            '1970-01-01T00:00:00.000Z',
            'Каждая Unix-метка отсчитывает секунды от этого момента — полуночи UTC 1 января 1970 года.',
            $now
        );

        // Пример 2: JavaScript milliseconds — encode (Timestamp → Date)
        $e2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation(
            $e2,
            'en',
            'JavaScript milliseconds',
            '1700000000000',
            '2023-11-14T22:13:20.000Z',
            'JavaScript\'s Date.now() returns milliseconds. The tool auto-detects 13-digit timestamps as milliseconds.',
            $now
        );
        $this->upsertExampleTranslation(
            $e2,
            'ru',
            'Миллисекунды JavaScript',
            '1700000000000',
            '2023-11-14T22:13:20.000Z',
            'JavaScript Date.now() возвращает миллисекунды. Инструмент автоматически определяет 13-значные метки как миллисекунды.',
            $now
        );

        // Пример 3: ISO date → Unix timestamp — decode (Date → Timestamp)
        $e3 = $this->upsertExample($cipherId, 30, 'decrypt', $now);
        $this->upsertExampleTranslation(
            $e3,
            'en',
            'Date to timestamp',
            '2024-01-01T00:00:00Z',
            '1704067200',
            'Paste an ISO 8601 date to get the Unix timestamp in seconds — useful when building API calls or database queries.',
            $now
        );
        $this->upsertExampleTranslation(
            $e3,
            'ru',
            'Дата в метку',
            '2024-01-01T00:00:00Z',
            '1704067200',
            'Вставьте дату в формате ISO 8601, чтобы получить Unix-метку в секундах — пригодится при составлении API-запросов или запросов к базе данных.',
            $now
        );

        // Пример 4: Year 2038 problem — encode (Timestamp → Date)
        $e4 = $this->upsertExample($cipherId, 40, 'encrypt', $now);
        $this->upsertExampleTranslation(
            $e4,
            'en',
            'Year 2038 limit',
            '2147483647',
            '2038-01-19T03:14:07.000Z',
            'The maximum value of a 32-bit signed integer. After this moment, systems that store timestamps as 32-bit integers will overflow (Y2K38 problem).',
            $now
        );
        $this->upsertExampleTranslation(
            $e4,
            'ru',
            'Предел 2038 года',
            '2147483647',
            '2038-01-19T03:14:07.000Z',
            'Максимальное значение 32-битного знакового целого числа. После этого момента системы, хранящие метки как 32-битные числа, переполнятся (проблема Y2K38).',
            $now
        );
    }

    /**
     * Удаляет добавленные примеры.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['timestamp-converter']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];

        foreach ([10, 20, 30, 40] as $sortOrder) {
            $row = $this->db->fetch(
                'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
                [$cipherId, $sortOrder]
            );
            if ($row !== false) {
                $exId = (int) $row['id'];
                $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ?', [$exId]);
                $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE id = ?', [$exId]);
            }
        }
    }

    /**
     * Создаёт или обновляет запись примера.
     */
    private function upsertExample(int $cipherId, int $sortOrder, string $direction, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES
                . ' SET direction = ?, delimiter = ?, published = 1, updated_at = ? WHERE id = ?',
                [$direction, '', $now, (int) $row['id']]
            );
            return (int) $row['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES
            . ' (app_id, sort_order, published, direction, delimiter, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $direction, '', $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(
        int $exampleId,
        string $language,
        string $title,
        string $input,
        string $output,
        string $description,
        string $now
    ): void {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS
            . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        $data = [
            'title'       => $title,
            'input'       => $input,
            'output'      => $output,
            'key'         => '',
            'shift'       => 0,
            'description' => $description,
        ];

        if ($existing !== false) {
            $assignments = array_map(
                static fn (string $field): string => '`' . $field . '` = ?',
                array_keys($data)
            );
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS
                . ' SET ' . implode(', ', $assignments) . ', updated_at = ? WHERE id = ?',
                [...array_values($data), $now, (int) $existing['id']]
            );
            return;
        }

        $columns      = array_map(
            static fn (string $field): string => '`' . $field . '`',
            ['example_id', 'language', ...array_keys($data), 'created_at', 'updated_at']
        );
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS
            . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            [$exampleId, $language, ...array_values($data), $now, $now]
        );
    }
}
