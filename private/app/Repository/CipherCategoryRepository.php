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

    /**
     * Возвращает опубликованную категорию с переводом для указанного языка.
     *
     * @return array<string, mixed>|null
     */
    public function findPublishedCategoryPageByAliasAndLanguage(string $alias, string $language): ?array
    {
        $row = $this->db->fetch(
            'SELECT c.id, c.alias, c.sort_order, c.published, c.created_at, c.updated_at, '
            . 't.language, t.name, t.description, t.meta_title, t.meta_description '
            . 'FROM ' . $this->table . ' c '
            . 'INNER JOIN ' . Tables::CIPHER_CATEGORY_TRANSLATIONS . ' t ON t.category_id = c.id '
            . 'WHERE c.alias = ? AND c.published = 1 AND t.language = ? '
            . 'LIMIT 1',
            [$alias, $language]
        );

        return $row === false ? null : $row;
    }

    /**
     * Возвращает опубликованную категорию с переводом без жёсткой привязки к языку.
     *
     * @return array<string, mixed>|null
     */
    public function findPublishedCategoryPageByAlias(string $alias): ?array
    {
        $row = $this->db->fetch(
            'SELECT c.id, c.alias, c.sort_order, c.published, c.created_at, c.updated_at, '
            . 't.language, t.name, t.description, t.meta_title, t.meta_description '
            . 'FROM ' . $this->table . ' c '
            . 'INNER JOIN ' . Tables::CIPHER_CATEGORY_TRANSLATIONS . ' t ON t.category_id = c.id '
            . 'WHERE c.alias = ? AND c.published = 1 '
            . 'ORDER BY CASE WHEN t.language = ? THEN 0 ELSE 1 END, t.id ASC '
            . 'LIMIT 1',
            [$alias, (string) config('locale.locale', 'en')]
        );

        return $row === false ? null : $row;
    }

    /**
     * Возвращает опубликованные категории для выпадающего меню в навигации.
     *
     * @return array<int, array{alias:string, name:string}>
     */
    public function listPublishedForNavigation(string $language, string $defaultLanguage): array
    {
        return $this->db->fetchAll(
            'SELECT c.alias, COALESCE(t_current.name, t_default.name, c.alias) AS name '
            . 'FROM ' . $this->table . ' c '
            . 'LEFT JOIN ' . Tables::CIPHER_CATEGORY_TRANSLATIONS . ' t_current '
            . 'ON t_current.category_id = c.id AND t_current.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHER_CATEGORY_TRANSLATIONS . ' t_default '
            . 'ON t_default.category_id = c.id AND t_default.language = ? '
            . 'WHERE c.published = 1 '
            . 'ORDER BY c.sort_order ASC, c.id ASC',
            [$language, $defaultLanguage]
        );
    }

    /**
     * Возвращает список блоков категории для админки.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listBlocksByCategoryId(int $categoryId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_CATEGORIES_BLOCKS . ' WHERE category_id = ? ORDER BY sort_order ASC, id ASC',
            [$categoryId]
        );
    }

    /**
     * Возвращает переводы блоков категорий для списка id блоков.
     *
     * @param  int[] $blockIds Список ID блоков.
     * @return array<int, array<string, mixed>>
     */
    public function listBlockTranslationsByBlockIds(array $blockIds): array
    {
        if ($blockIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($blockIds), '?'));

        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS
            . ' WHERE block_id IN (' . $placeholders . ') ORDER BY block_id ASC, language ASC, id ASC',
            $blockIds
        );
    }

    /**
     * Возвращает переведённые блоки контента категории.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findBlocksByCategoryIdWithTranslation(int $categoryId, string $language, string $defaultLanguage): array
    {
        return $this->db->fetchAll(
            'SELECT b.id, b.category_id, b.sort_order, b.published, '
            . 'COALESCE(bt_cur.language, bt_def.language, ?) AS language, '
            . 'COALESCE(bt_cur.title, bt_def.title, \'\') AS title, '
            . 'COALESCE(bt_cur.text, bt_def.text, \'\') AS text '
            . 'FROM ' . Tables::CIPHERS_CATEGORIES_BLOCKS . ' b '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS . ' bt_cur '
            . 'ON bt_cur.block_id = b.id AND bt_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS . ' bt_def '
            . 'ON bt_def.block_id = b.id AND bt_def.language = ? '
            . 'WHERE b.category_id = ? AND b.published = 1 '
            . 'ORDER BY b.sort_order ASC, b.id ASC',
            [$defaultLanguage, $language, $defaultLanguage, $categoryId]
        );
    }
}
