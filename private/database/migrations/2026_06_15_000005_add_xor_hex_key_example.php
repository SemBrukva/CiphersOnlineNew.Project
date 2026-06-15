<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет пример с hex-ключом к XOR-шифру.
 */
class AddXorHexKeyExample extends Migration
{
    /**
     * Добавляет пример и его переводы.
     */
    public function up(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['xor-cipher']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId  = (int) $cipher['id'];
        $now       = date('Y-m-d H:i:s');
        $exampleId = $this->upsertExample($cipherId, 40, 'encrypt', $now);

        $this->upsertExampleTranslation(
            $exampleId, 'en',
            'Encrypt with hex key',
            'HELLO',
            '0A070E0E0D',
            '42',
            'Key format: Hex. Single-byte key 0x42: H(0x48)^0x42=0x0A, E(0x45)^0x42=0x07, L(0x4C)^0x42=0x0E, O(0x4F)^0x42=0x0D.',
            $now
        );

        $this->upsertExampleTranslation(
            $exampleId, 'ru',
            'Шифрование с hex-ключом',
            'HELLO',
            '0A070E0E0D',
            '42',
            'Формат ключа: Hex. Однобайтовый ключ 0x42: H(0x48)^0x42=0x0A, E(0x45)^0x42=0x07, L(0x4C)^0x42=0x0E, O(0x4F)^0x42=0x0D.',
            $now
        );
    }

    /**
     * Удаляет пример с hex-ключом.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['xor-cipher']
        );

        if ($cipher === false) {
            return;
        }

        $example = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [(int) $cipher['id'], 40]
        );

        if ($example === false) {
            return;
        }

        $exampleId = (int) $example['id'];
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ?', [$exampleId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE id = ?', [$exampleId]);
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
                'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET direction = ?, delimiter = ?, published = 1, updated_at = ? WHERE id = ?',
                [$direction, '', $now, (int) $row['id']]
            );
            return (int) $row['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, direction, delimiter, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?)',
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
        string $key,
        string $description,
        string $now
    ): void {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($existing !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS
                . ' SET `title` = ?, `input` = ?, `output` = ?, `key` = ?, `shift` = 0, `description` = ?, updated_at = ? WHERE id = ?',
                [$title, $input, $output, $key, $description, $now, (int) $existing['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS
            . ' (`example_id`, `language`, `title`, `input`, `output`, `key`, `shift`, `description`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)',
            [$exampleId, $language, $title, $input, $output, $key, $description, $now, $now]
        );
    }
}
