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
