<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;
use App\Database\Tables;

/**
 * Репозиторий для управления HTTP-редиректами.
 */
final class RedirectRepository extends AbstractRepository
{
    /**
     * Создаёт экземпляр репозитория редиректов.
     */
    public function __construct(Database $db)
    {
        parent::__construct($db, Tables::REDIRECTS);
    }

    /**
     * Возвращает все редиректы для страницы списка в админке.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForAdmin(): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM ' . $this->table . ' ORDER BY hit_count DESC, id DESC'
        );
    }

    /**
     * Возвращает список активных редиректов для middleware.
     *
     * @return array<int, array{id:int, from_path:string, to_path:string, status_code:int}>
     */
    public function listActive(): array
    {
        return $this->db->fetchAll(
            'SELECT id, from_path, to_path, status_code FROM ' . $this->table . ' WHERE is_active = 1'
        );
    }

    /**
     * Инкрементирует счётчик переходов редиректа.
     */
    public function incrementHitCount(int $id): void
    {
        $this->db->execute(
            'UPDATE ' . $this->table . ' SET hit_count = hit_count + 1, updated_at = ? WHERE id = ?',
            [date('Y-m-d H:i:s'), $id]
        );
    }
}
