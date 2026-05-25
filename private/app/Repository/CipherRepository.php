<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;
use App\Database\Tables;

/**
 * Репозиторий для управления шифрами и связанными сущностями в админке.
 */
final class CipherRepository extends AbstractRepository
{
    /**
     * Создаёт экземпляр репозитория шифров.
     */
    public function __construct(Database $db)
    {
        parent::__construct($db, Tables::CIPHERS);
    }

    /**
     * Возвращает список шифров для админской страницы.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForAdmin(): array
    {
        return $this->db->fetchAll(
            'SELECT c.id, c.alias, c.sort_order, c.published, c.category_id, '
            . 'COALESCE(tr.name, c.alias) AS title '
            . 'FROM ' . Tables::CIPHERS . ' c '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . " tr ON tr.app_id = c.id AND tr.language = 'ru' "
            . 'ORDER BY c.sort_order ASC, c.id ASC'
        );
    }

    /**
     * Возвращает шифры категории для select-списков в админке.
     *
     * @return array<int, array{id:int, alias:string}>
     */
    public function listForSelectByCategoryId(int $categoryId): array
    {
        return $this->db->fetchAll(
            'SELECT id, alias FROM ' . Tables::CIPHERS
            . ' WHERE category_id = ? ORDER BY sort_order ASC, id ASC',
            [$categoryId]
        );
    }

    /**
     * Возвращает карту наличия переводов по шифрам и языкам.
     *
     * @return array<int, array<string, int>>
     */
    public function listLanguageMapByCipher(): array
    {
        $rows = $this->db->fetchAll(
            'SELECT id, app_id, language FROM ' . Tables::CIPHERS_TRANSLATIONS
        );

        $map = [];

        foreach ($rows as $row) {
            $cipherId = (int) ($row['app_id'] ?? 0);
            $language = mb_strtolower((string) ($row['language'] ?? ''));

            if ($cipherId < 1 || $language === '') {
                continue;
            }

            $map[$cipherId][$language] = (int) ($row['id'] ?? 0);
        }

        return $map;
    }

    /**
     * Возвращает список блоков шифра.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listBlocksByCipherId(int $cipherId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ? ORDER BY sort_order ASC, id ASC',
            [$cipherId]
        );
    }

    /**
     * Возвращает список FAQ шифра.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listFaqByCipherId(int $cipherId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ? ORDER BY sort_order ASC, id ASC',
            [$cipherId]
        );
    }

    /**
     * Возвращает список примеров шифра.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listExamplesByCipherId(int $cipherId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? ORDER BY sort_order ASC, id ASC',
            [$cipherId]
        );
    }

    /**
     * Возвращает список тегов шифра.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listTagsByCipherId(int $cipherId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ? ORDER BY sort_order ASC, id ASC',
            [$cipherId]
        );
    }

    /**
     * Возвращает переводы шифра.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listCipherTranslationsByCipherId(int $cipherId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ? ORDER BY language ASC, id ASC',
            [$cipherId]
        );
    }

    /**
     * Возвращает переводы блоков для списка id блоков.
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
            'SELECT * FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS
            . ' WHERE block_id IN (' . $placeholders . ') ORDER BY block_id ASC, language ASC, id ASC',
            $blockIds
        );
    }

    /**
     * Возвращает переводы FAQ для списка id FAQ.
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
            'SELECT * FROM ' . Tables::CIPHERS_FAQ_TRANSLATIONS
            . ' WHERE faq_id IN (' . $placeholders . ') ORDER BY faq_id ASC, language ASC, id ASC',
            $faqIds
        );
    }

    /**
     * Возвращает переводы примеров для списка id примеров.
     *
     * @param  int[] $exampleIds Список ID примеров.
     * @return array<int, array<string, mixed>>
     */
    public function listExampleTranslationsByExampleIds(array $exampleIds): array
    {
        if ($exampleIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($exampleIds), '?'));

        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS
            . ' WHERE example_id IN (' . $placeholders . ') ORDER BY example_id ASC, language ASC, id ASC',
            $exampleIds
        );
    }

    /**
     * Возвращает переводы тегов для списка id тегов.
     *
     * @param  int[] $tagIds Список ID тегов.
     * @return array<int, array<string, mixed>>
     */
    public function listTagTranslationsByTagIds(array $tagIds): array
    {
        if ($tagIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($tagIds), '?'));

        return $this->db->fetchAll(
            'SELECT * FROM ' . Tables::CIPHERS_TAGS_TRANSLATIONS
            . ' WHERE tag_id IN (' . $placeholders . ') ORDER BY tag_id ASC, language ASC, id ASC',
            $tagIds
        );
    }

    /**
     * Возвращает опубликованные шифры категории с переводами для указанного языка (с fallback на defaultLanguage).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findPublishedByCategoryWithTranslation(int $categoryId, string $language, string $defaultLanguage): array
    {
        return $this->db->fetchAll(
            'SELECT c.id, c.alias, c.category_id, c.sort_order, '
            . 'COALESCE(t_cur.name, t_def.name, c.alias) AS name, '
            . 'COALESCE(t_cur.name_short, t_def.name_short, c.alias) AS name_short, '
            . 'COALESCE(t_cur.description, t_def.description, \'\') AS description, '
            . 'COALESCE(t_cur.description_stort, t_def.description_stort, \'\') AS description_short '
            . 'FROM ' . $this->table . ' c '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' t_cur ON t_cur.app_id = c.id AND t_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' t_def ON t_def.app_id = c.id AND t_def.language = ? '
            . 'WHERE c.category_id = ? AND c.published = 1 '
            . 'ORDER BY c.sort_order ASC, c.id ASC',
            [$language, $defaultLanguage, $categoryId]
        );
    }

    /**
     * Возвращает опубликованный инструмент по alias категории и alias инструмента с переводом.
     *
     * @return array<string, mixed>|null
     */
    public function findPublishedCipherPageByAliases(
        string $categoryAlias,
        string $cipherAlias,
        string $language,
        string $defaultLanguage
    ): ?array {
        $row = $this->db->fetch(
            'SELECT c.id, c.alias, c.category_id, c.sort_order, '
            . 'cat.alias AS category_alias, '
            . 'COALESCE(t_cur.language, t_def.language, ?) AS language, '
            . 'COALESCE(t_cur.name, t_def.name, c.alias) AS name, '
            . 'COALESCE(t_cur.name_short, t_def.name_short, c.alias) AS name_short, '
            . 'COALESCE(t_cur.description, t_def.description, \'\') AS description, '
            . 'COALESCE(t_cur.meta_title, t_def.meta_title, \'\') AS meta_title, '
            . 'COALESCE(t_cur.meta_description, t_def.meta_description, \'\') AS meta_description '
            . 'FROM ' . Tables::CIPHERS . ' c '
            . 'INNER JOIN ' . Tables::CIPHER_CATEGORIES . ' cat ON cat.id = c.category_id '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' t_cur ON t_cur.app_id = c.id AND t_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' t_def ON t_def.app_id = c.id AND t_def.language = ? '
            . 'WHERE c.alias = ? AND c.published = 1 '
            . 'AND cat.alias = ? AND cat.published = 1 '
            . 'LIMIT 1',
            [$defaultLanguage, $language, $defaultLanguage, $cipherAlias, $categoryAlias]
        );

        return $row === false ? null : $row;
    }

    /**
     * Возвращает переведённые блоки контента инструмента.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findBlocksByCipherIdWithTranslation(int $cipherId, string $language, string $defaultLanguage): array
    {
        return $this->db->fetchAll(
            'SELECT b.id, b.app_id, b.sort_order, b.published, '
            . 'COALESCE(bt_cur.language, bt_def.language, ?) AS language, '
            . 'COALESCE(bt_cur.title, bt_def.title, \'\') AS title, '
            . 'COALESCE(bt_cur.text, bt_def.text, \'\') AS text '
            . 'FROM ' . Tables::CIPHERS_BLOCKS . ' b '
            . 'LEFT JOIN ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' bt_cur '
            . 'ON bt_cur.block_id = b.id AND bt_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' bt_def '
            . 'ON bt_def.block_id = b.id AND bt_def.language = ? '
            . 'WHERE b.app_id = ? AND b.published = 1 '
            . 'ORDER BY b.sort_order ASC, b.id ASC',
            [$defaultLanguage, $language, $defaultLanguage, $cipherId]
        );
    }

    /**
     * Возвращает переведённые FAQ инструмента.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findFaqByCipherIdWithTranslation(int $cipherId, string $language, string $defaultLanguage): array
    {
        return $this->db->fetchAll(
            'SELECT f.id, f.app_id, f.sort_order, f.published, '
            . 'COALESCE(ft_cur.language, ft_def.language, ?) AS language, '
            . 'COALESCE(ft_cur.question, ft_def.question, \'\') AS question, '
            . 'COALESCE(ft_cur.answer, ft_def.answer, \'\') AS answer '
            . 'FROM ' . Tables::CIPHERS_FAQ . ' f '
            . 'LEFT JOIN ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' ft_cur '
            . 'ON ft_cur.faq_id = f.id AND ft_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' ft_def '
            . 'ON ft_def.faq_id = f.id AND ft_def.language = ? '
            . 'WHERE f.app_id = ? AND f.published = 1 '
            . 'ORDER BY f.sort_order ASC, f.id ASC',
            [$defaultLanguage, $language, $defaultLanguage, $cipherId]
        );
    }

    /**
     * Возвращает переведённые примеры инструмента.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findExamplesByCipherIdWithTranslation(int $cipherId, string $language, string $defaultLanguage): array
    {
        return $this->db->fetchAll(
            'SELECT e.id, e.app_id, e.sort_order, e.published, '
            . 'COALESCE(et_cur.language, et_def.language, ?) AS language, '
            . 'COALESCE(et_cur.title, et_def.title, \'\') AS label, '
            . 'COALESCE(et_cur.input, et_def.input, \'\') AS input, '
            . 'COALESCE(et_cur.output, et_def.output, \'\') AS output, '
            . 'COALESCE(et_cur.description, et_def.description, \'\') AS `desc` '
            . 'FROM ' . Tables::CIPHERS_EXAMPLES . ' e '
            . 'LEFT JOIN ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' et_cur '
            . 'ON et_cur.example_id = e.id AND et_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' et_def '
            . 'ON et_def.example_id = e.id AND et_def.language = ? '
            . 'WHERE e.app_id = ? AND e.published = 1 '
            . 'ORDER BY e.sort_order ASC, e.id ASC',
            [$defaultLanguage, $language, $defaultLanguage, $cipherId]
        );
    }

    /**
     * Возвращает теги с переводами для списка шифров, сгруппированные по cipher_id.
     *
     * @param  int[]  $cipherIds      Список ID шифров.
     * @param  string $language       Целевой язык.
     * @param  string $defaultLanguage Язык по умолчанию для fallback.
     * @return array<int, string[]>   Карта cipher_id → массив строк-тегов.
     */
    public function findTagsGroupedByCipherIds(array $cipherIds, string $language, string $defaultLanguage): array
    {
        if ($cipherIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($cipherIds), '?'));

        $rows = $this->db->fetchAll(
            'SELECT ct.app_id AS cipher_id, '
            . 'COALESCE(ctt_cur.tag, ctt_def.tag) AS tag '
            . 'FROM ' . Tables::CIPHERS_TAGS . ' ct '
            . 'LEFT JOIN ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' ctt_cur '
            . '    ON ctt_cur.tag_id = ct.id AND ctt_cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' ctt_def '
            . '    ON ctt_def.tag_id = ct.id AND ctt_def.language = ? '
            . 'WHERE ct.app_id IN (' . $placeholders . ') AND ct.published = 1 '
            . 'ORDER BY ct.app_id ASC, ct.sort_order ASC, ct.id ASC',
            array_merge([$language, $defaultLanguage], $cipherIds)
        );

        $result = [];

        foreach ($rows as $row) {
            $cipherId = (int) ($row['cipher_id'] ?? 0);
            $tag      = (string) ($row['tag'] ?? '');

            if ($cipherId > 0 && $tag !== '') {
                $result[$cipherId][] = $tag;
            }
        }

        return $result;
    }

    /**
     * Проверяет уникальность alias среди шифров.
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
}
