<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;
use App\Database\Tables;

/**
 * Репозиторий для работы с пользователями.
 */
final class UserRepository extends AbstractRepository
{
    /**
     * Создаёт экземпляр репозитория пользователей.
     */
    public function __construct(Database $db)
    {
        parent::__construct($db, Tables::USERS);
    }

    /**
     * Возвращает пользователя по email.
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy(['email' => mb_strtolower(trim($email))]);
    }

    /**
     * Проверяет существование пользователя по email.
     */
    public function existsByEmail(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /**
     * Возвращает количество пользователей.
     */
    public function countAll(): int
    {
        $row = $this->db->fetch('SELECT COUNT(*) AS cnt FROM ' . $this->table);

        return (int) ($row['cnt'] ?? 0);
    }

    /**
     * Возвращает список пользователей для админ-дашборда.
     *
     * @return array<int, array{id:int, name:string, email:string, created_at:string|null}>
     */
    public function listForDashboard(): array
    {
        return $this->db->fetchAll(
            'SELECT id, name, email, created_at FROM ' . $this->table . ' ORDER BY id DESC'
        );
    }
}
