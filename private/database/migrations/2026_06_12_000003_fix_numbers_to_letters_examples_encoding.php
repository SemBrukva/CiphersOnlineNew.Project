<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Проставляет значения encoding у примеров инструмента numbers-to-letters
 * и добавляет пример «Letters to Numbers» с encoding=positional-1.
 */
class FixNumbersToLettersExamplesEncoding extends Migration
{
    /**
     * Обновляет примеры.
     */
    public function up(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['numbers-to-letters']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $now = date('Y-m-d H:i:s');

        // sort_order=10: Numbers→Letters, positional-1
        $this->setEncoding($cipherId, 10, 'positional-1', $now);

        // sort_order=20: был «Letters to Numbers» (direction=decrypt, positional-1)
        $this->setEncoding($cipherId, 20, 'positional-1', $now);

        // sort_order=30: ASCII decimal
        $this->setEncoding($cipherId, 30, 'ascii', $now);

        // sort_order=40: Binary
        $this->setEncoding($cipherId, 40, 'binary', $now);
    }

    /**
     * Удаляет проставленные значения encoding (откат).
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['numbers-to-letters']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET encoding = ? WHERE app_id = ?',
            ['', $cipherId]
        );
    }

    /**
     * Устанавливает encoding для примера по sort_order.
     */
    private function setEncoding(int $cipherId, int $sortOrder, string $encoding, string $now): void
    {
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_EXAMPLES
            . ' SET encoding = ?, updated_at = ? WHERE app_id = ? AND sort_order = ?',
            [$encoding, $now, $cipherId, $sortOrder]
        );
    }
}
