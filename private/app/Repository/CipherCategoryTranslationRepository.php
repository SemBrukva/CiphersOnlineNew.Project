<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;
use App\Database\Tables;

/**
 * Репозиторий для управления переводами категорий шифров.
 */
final class CipherCategoryTranslationRepository extends AbstractRepository
{
    /**
     * Создаёт экземпляр репозитория переводов категорий.
     */
    public function __construct(Database $db)
    {
        parent::__construct($db, Tables::CIPHER_CATEGORY_TRANSLATIONS);
    }

    /**
     * Возвращает список переводов для админки с alias категории.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForAdmin(): array
    {
        return $this->db->fetchAll(
            'SELECT t.*, c.alias AS category_alias '
            . 'FROM ' . Tables::CIPHER_CATEGORY_TRANSLATIONS . ' t '
            . 'INNER JOIN ' . Tables::CIPHER_CATEGORIES . ' c ON c.id = t.category_id '
            . 'ORDER BY c.alias ASC, t.language ASC, t.id ASC'
        );
    }

    /**
     * Возвращает перевод по id вместе с alias категории.
     *
     * @return array<string, mixed>|null
     */
    public function findWithCategoryAlias(int $id): ?array
    {
        $row = $this->db->fetch(
            'SELECT t.*, c.alias AS category_alias '
            . 'FROM ' . Tables::CIPHER_CATEGORY_TRANSLATIONS . ' t '
            . 'INNER JOIN ' . Tables::CIPHER_CATEGORIES . ' c ON c.id = t.category_id '
            . 'WHERE t.id = ? LIMIT 1',
            [$id]
        );

        return $row === false ? null : $row;
    }

    /**
     * Проверяет уникальность пары category_id + language.
     */
    public function existsByCategoryAndLanguage(int $categoryId, string $language, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM ' . $this->table . ' WHERE category_id = ? AND language = ?';
        $bindings = [$categoryId, $language];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $bindings[] = $exceptId;
        }

        return $this->db->fetch($sql . ' LIMIT 1', $bindings) !== false;
    }

    /**
     * Возвращает список переводов конкретной категории.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listByCategoryId(int $categoryId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . $this->table . ' WHERE category_id = ? ORDER BY language ASC, id ASC',
            [$categoryId]
        );
    }

    /**
     * Возвращает перевод категории по языку.
     *
     * @return array<string, mixed>|null
     */
    public function findByCategoryAndLanguage(int $categoryId, string $language): ?array
    {
        $row = $this->db->fetch(
            'SELECT * FROM ' . $this->table . ' WHERE category_id = ? AND language = ? LIMIT 1',
            [$categoryId, $language]
        );

        return $row === false ? null : $row;
    }

    /**
     * Удаляет перевод категории по языку.
     */
    public function deleteByCategoryAndLanguage(int $categoryId, string $language): int
    {
        return $this->db->execute(
            'DELETE FROM ' . $this->table . ' WHERE category_id = ? AND language = ?',
            [$categoryId, $language]
        );
    }
}
