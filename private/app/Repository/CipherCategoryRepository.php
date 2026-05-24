<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;
use App\Database\Tables;

/**
 * Репозиторий для управления категориями шифров.
 */
final class CipherCategoryRepository extends AbstractRepository
{
    /**
     * Создаёт экземпляр репозитория категорий шифров.
     */
    public function __construct(Database $db)
    {
        parent::__construct($db, Tables::CIPHER_CATEGORIES);
    }

    /**
     * Возвращает список категорий для админки.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForAdmin(): array
    {
        return $this->db->fetchAll(
            'SELECT c.*, ru.name AS name_ru '
            . 'FROM ' . Tables::CIPHER_CATEGORIES . ' c '
            . 'LEFT JOIN ' . Tables::CIPHER_CATEGORY_TRANSLATIONS . ' ru '
            . "ON ru.category_id = c.id AND ru.language = 'ru' "
            . 'ORDER BY c.sort_order ASC, c.id ASC'
        );
    }

    /**
     * Возвращает карту наличия переводов по категориям и языкам.
     *
     * @return array<int, array<string, int>>
     */
    public function listLanguageMapByCategory(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT id, category_id, language FROM ' . Tables::CIPHER_CATEGORY_TRANSLATIONS
        );

        $map = [];

        foreach ($rows as $row) {
            $categoryId = (int) ($row['category_id'] ?? 0);
            $language = mb_strtolower((string) ($row['language'] ?? ''));

            if ($categoryId < 1 || $language === '') {
                continue;
            }

            $map[$categoryId][$language] = (int) ($row['id'] ?? 0);
        }

        return $map;
    }

    /**
     * Проверяет уникальность alias среди категорий.
     */
    public function existsByAlias(string $alias, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM ' . $this->table . ' WHERE alias = ?';
        $bindings = [$alias];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $bindings[] = $exceptId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $bindings) !== false;
    }

    /**
     * Возвращает категории для select-списка в формах переводов.
     *
     * @return array<int, array{id:int, alias:string}>
     */
    public function listForSelect(): array
    {
        return $this->db->fetchAll(
            'SELECT id, alias FROM ' . $this->table . ' ORDER BY alias ASC, id ASC'
        );
    }
}
