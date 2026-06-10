<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Очищает хаб классических шифров от ссылок на перенесённые инструменты.
 */
class CleanupClassicalCategoryAfterCodesMove extends Migration
{
    /**
     * Удаляет задачи и FAQ классической категории, относящиеся к перенесённым кодам.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['classical-ciphers']
        );

        if ($category === false) {
            return;
        }

        $categoryId = (int) $category['id'];
        $cipherIds = $this->movedCipherIds();

        if ($cipherIds !== []) {
            $placeholders = implode(', ', array_fill(0, count($cipherIds), '?'));
            $this->db->execute(
                'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_TASKS
                . ' WHERE category_id = ? AND relation_cipher_id IN (' . $placeholders . ')',
                [$categoryId, ...$cipherIds]
            );
        }

        $this->deleteFaqContaining($categoryId, [
            'A1Z26',
            'Bacon',
            'Bacone',
            'Bacon-Chiffre',
            'cifrado Bacon',
            'chiffre de Bacon',
            'cifra de Bacon',
            'Bacon şifresi',
            'Бэкона',
            'Polybius',
            'Polybe',
            'Полиб',
        ]);
    }

    /**
     * Откат не восстанавливает удалённый справочный контент хаба.
     */
    public function down(): void
    {
    }

    /**
     * Возвращает ID перенесённых инструментов.
     *
     * @return int[]
     */
    private function movedCipherIds(): array
    {
        $placeholders = implode(', ', array_fill(0, count($this->movedAliases()), '?'));
        $rows = $this->db->fetchAll(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias IN (' . $placeholders . ')',
            $this->movedAliases()
        );

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    /**
     * Удаляет FAQ, текст которого содержит один из маркеров перенесённых инструментов.
     *
     * @param string[] $needles Маркеры для поиска.
     */
    private function deleteFaqContaining(int $categoryId, array $needles): void
    {
        foreach ($needles as $needle) {
            $this->db->execute(
                'DELETE FROM ' . Tables::CIPHERS_CATEGORIES_FAQ
                . ' WHERE category_id = ? AND id IN ('
                . 'SELECT faq_id FROM ' . Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS
                . ' WHERE question LIKE ? OR answer LIKE ?'
                . ')',
                [$categoryId, '%' . $needle . '%', '%' . $needle . '%']
            );
        }
    }

    /**
     * Возвращает alias перенесённых инструментов.
     *
     * @return string[]
     */
    private function movedAliases(): array
    {
        return ['a1z26', 'polybius-square', 'bacon', 'morse-code'];
    }
}
