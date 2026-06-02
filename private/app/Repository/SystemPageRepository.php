<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;
use App\Database\Tables;

/**
 * Репозиторий системных страниц (Privacy Policy и т.д.).
 */
final class SystemPageRepository extends AbstractRepository
{
    /**
     * Создаёт экземпляр репозитория системных страниц.
     */
    public function __construct(Database $db)
    {
        parent::__construct($db, Tables::SYSTEM_PAGES);
    }

    /**
     * Возвращает опубликованную страницу по алиасу и языку.
     *
     * @return array<string, mixed>|null
     */
    public function findPublishedByAliasAndLanguage(string $alias, string $language): ?array
    {
        $row = $this->db->fetch(
            'SELECT * FROM ' . $this->table . ' WHERE alias = ? AND language = ? AND published = 1',
            [$alias, $language]
        );

        return $row === false ? null : $row;
    }

    /**
     * Возвращает опубликованную страницу по алиасу без привязки к языку.
     *
     * @return array<string, mixed>|null
     */
    public function findPublishedByAlias(string $alias): ?array
    {
        $row = $this->db->fetch(
            'SELECT * FROM ' . $this->table . ' WHERE alias = ? AND published = 1 LIMIT 1',
            [$alias]
        );

        return $row === false ? null : $row;
    }

    /**
     * Возвращает опубликованные страницы для меню и sitemap.
     *
     * @return array<int, array{alias:string, name:string}>
     */
    public function listPublishedForNavigation(string $language): array
    {
        return $this->db->fetchAll(
            'SELECT alias, name FROM ' . $this->table . ' WHERE language = ? AND published = 1 ORDER BY id',
            [$language]
        );
    }
}
