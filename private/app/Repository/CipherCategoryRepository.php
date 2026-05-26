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
            'SELECT c.id, c.alias, c.category, c.sort_order, c.published, c.created_at, c.updated_at, '
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
            'SELECT c.id, c.alias, c.category, c.sort_order, c.published, c.created_at, c.updated_at, '
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

    /**
     * Возвращает список задач категории для админки.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listTasksByCategoryId(int $categoryId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_CATEGORIES_TASKS . ' WHERE category_id = ? ORDER BY sort_order ASC, id ASC',
            [$categoryId]
        );
    }

    /**
     * Возвращает переводы задач категорий для списка id задач.
     *
     * @param  int[] $taskIds Список ID задач.
     * @return array<int, array<string, mixed>>
     */
    public function listTaskTranslationsByTaskIds(array $taskIds): array
    {
        if ($taskIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($taskIds), '?'));

        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS
            . ' WHERE task_id IN (' . $placeholders . ') ORDER BY task_id ASC, language ASC, id ASC',
            $taskIds
        );
    }

    /**
     * Возвращает переведённые задачи категории вместе со связанным шифром.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findTasksByCategoryIdWithTranslationAndCipher(int $categoryId, string $language, string $defaultLanguage): array
    {
        return $this->db->fetchAll(
            'SELECT t.id, t.category_id, t.relation_cipher_id, t.sort_order, t.published, '
            . 'COALESCE(tt_cur.language, tt_def.language, ?) AS language, '
            . 'COALESCE(tt_cur.title, tt_def.title, \'\') AS title, '
            . 'COALESCE(tt_cur.description, tt_def.description, \'\') AS description, '
            . 'c.alias AS cipher_alias, '
            . 'COALESCE(ct_cur.name_short, ct_def.name_short, c.alias) AS cipher_name_short '
            . 'FROM ' . Tables::CIPHERS_CATEGORIES_TASKS . ' t '
            . 'INNER JOIN ' . Tables::CIPHERS . ' c ON c.id = t.relation_cipher_id AND c.published = 1 '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS . ' tt_cur '
            . 'ON tt_cur.task_id = t.id AND tt_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS . ' tt_def '
            . 'ON tt_def.task_id = t.id AND tt_def.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' ct_cur '
            . 'ON ct_cur.app_id = c.id AND ct_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' ct_def '
            . 'ON ct_def.app_id = c.id AND ct_def.language = ? '
            . 'WHERE t.category_id = ? AND t.published = 1 '
            . 'ORDER BY t.sort_order ASC, t.id ASC',
            [$defaultLanguage, $language, $defaultLanguage, $language, $defaultLanguage, $categoryId]
        );
    }

    /**
     * Возвращает список связок used together для админки.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listUsedTogetherByCategoryId(int $categoryId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER . ' WHERE category_id = ? ORDER BY sort_order ASC, id ASC',
            [$categoryId]
        );
    }

    /**
     * Возвращает переводы связок used together для списка id.
     *
     * @param  int[] $usedTogetherIds Список ID связок.
     * @return array<int, array<string, mixed>>
     */
    public function listUsedTogetherTranslationsByIds(array $usedTogetherIds): array
    {
        if ($usedTogetherIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($usedTogetherIds), '?'));

        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS
            . ' WHERE used_together_id IN (' . $placeholders . ') ORDER BY used_together_id ASC, language ASC, id ASC',
            $usedTogetherIds
        );
    }

    /**
     * Возвращает переведённые связки used together с двумя связанными шифрами.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findUsedTogetherByCategoryIdWithTranslationAndCiphers(int $categoryId, string $language, string $defaultLanguage): array
    {
        return $this->db->fetchAll(
            'SELECT ut.id, ut.category_id, ut.relation_cipher_first_id, ut.relation_cipher_second_id, ut.sort_order, ut.published, '
            . 'COALESCE(utt_cur.language, utt_def.language, ?) AS language, '
            . 'COALESCE(utt_cur.title, utt_def.title, \'\') AS title, '
            . 'cf.alias AS first_cipher_alias, '
            . 'COALESCE(cft_cur.name_short, cft_def.name_short, cf.alias) AS first_cipher_name_short, '
            . 'cs.alias AS second_cipher_alias, '
            . 'COALESCE(cst_cur.name_short, cst_def.name_short, cs.alias) AS second_cipher_name_short '
            . 'FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER . ' ut '
            . 'INNER JOIN ' . Tables::CIPHERS . ' cf ON cf.id = ut.relation_cipher_first_id AND cf.published = 1 '
            . 'INNER JOIN ' . Tables::CIPHERS . ' cs ON cs.id = ut.relation_cipher_second_id AND cs.published = 1 '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS . ' utt_cur '
            . 'ON utt_cur.used_together_id = ut.id AND utt_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS . ' utt_def '
            . 'ON utt_def.used_together_id = ut.id AND utt_def.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' cft_cur '
            . 'ON cft_cur.app_id = cf.id AND cft_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' cft_def '
            . 'ON cft_def.app_id = cf.id AND cft_def.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' cst_cur '
            . 'ON cst_cur.app_id = cs.id AND cst_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' cst_def '
            . 'ON cst_def.app_id = cs.id AND cst_def.language = ? '
            . 'WHERE ut.category_id = ? AND ut.published = 1 '
            . 'ORDER BY ut.sort_order ASC, ut.id ASC',
            [$defaultLanguage, $language, $defaultLanguage, $language, $defaultLanguage, $language, $defaultLanguage, $categoryId]
        );
    }

    /**
     * Возвращает список FAQ категории для админки.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listFaqByCategoryId(int $categoryId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_CATEGORIES_FAQ . ' WHERE category_id = ? ORDER BY sort_order ASC, id ASC',
            [$categoryId]
        );
    }

    /**
     * Возвращает переводы FAQ категорий для списка id FAQ.
     *
     * @param  int[] $faqIds Список ID FAQ.
     * @return array<int, array<string, mixed>>
     */
    public function listFaqTranslationsByFaqIds(array $faqIds): array
    {
        if ($faqIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($faqIds), '?'));

        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS
            . ' WHERE faq_id IN (' . $placeholders . ') ORDER BY faq_id ASC, language ASC, id ASC',
            $faqIds
        );
    }

    /**
     * Возвращает переведённые FAQ категории.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findFaqByCategoryIdWithTranslation(int $categoryId, string $language, string $defaultLanguage): array
    {
        return $this->db->fetchAll(
            'SELECT f.id, f.category_id, f.sort_order, f.published, '
            . 'COALESCE(ft_cur.language, ft_def.language, ?) AS language, '
            . 'COALESCE(ft_cur.question, ft_def.question, \'\') AS question, '
            . 'COALESCE(ft_cur.answer, ft_def.answer, \'\') AS answer '
            . 'FROM ' . Tables::CIPHERS_CATEGORIES_FAQ . ' f '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS . ' ft_cur '
            . 'ON ft_cur.faq_id = f.id AND ft_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS . ' ft_def '
            . 'ON ft_def.faq_id = f.id AND ft_def.language = ? '
            . 'WHERE f.category_id = ? AND f.published = 1 '
            . 'ORDER BY f.sort_order ASC, f.id ASC',
            [$defaultLanguage, $language, $defaultLanguage, $categoryId]
        );
    }
}
