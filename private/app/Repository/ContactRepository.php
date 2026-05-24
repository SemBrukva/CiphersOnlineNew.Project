<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;
use App\Database\Tables;

/**
 * Репозиторий обращений из формы контактов.
 */
final class ContactRepository extends AbstractRepository
{
    /**
     * Создаёт экземпляр репозитория контактов.
     */
    public function __construct(Database $db)
    {
        parent::__construct($db, Tables::CONTACTS);
    }

    /**
     * Создаёт новое обращение и возвращает id записи.
     */
    public function create(
        ?int $userId,
        string $name,
        string $email,
        string $message,
        string $ip
    ): int {
        return $this->insert([
            'user_id' => $userId,
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'is_read' => 0,
            'ip' => $ip,
        ]);
    }
}
