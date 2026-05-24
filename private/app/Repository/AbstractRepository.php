<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database\Database;

/**
 * Базовый репозиторий с общими CRUD-операциями для таблиц.
 */
abstract class AbstractRepository
{
    /**
     * Создаёт экземпляр репозитория.
     */
    public function __construct(
        protected readonly Database $db,
        protected readonly string $table
    ) {
    }

    /**
     * Возвращает запись по первичному ключу id.
     *
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array
    {
        $row = $this->db->fetch('SELECT * FROM ' . $this->table . ' WHERE id = ?', [$id]);

        return $row === false ? null : $row;
    }

    /**
     * Возвращает первую запись, удовлетворяющую условиям where.
     *
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>|null
     */
    public function findBy(array $criteria): ?array
    {
        if ($criteria === []) {
            return null;
        }

        [$whereSql, $bindings] = $this->buildWhere($criteria);
        $row = $this->db->fetch('SELECT * FROM ' . $this->table . ' WHERE ' . $whereSql . ' LIMIT 1', $bindings);

        return $row === false ? null : $row;
    }

    /**
     * Возвращает список записей по условиям where с сортировкой.
     *
     * @param array<string, mixed> $criteria
     * @param array<string, 'ASC'|'DESC'> $orderBy
     * @return array<int, array<string, mixed>>
     */
    public function findAll(array $criteria = [], array $orderBy = []): array
    {
        $sql = 'SELECT * FROM ' . $this->table;
        $bindings = [];

        if ($criteria !== []) {
            [$whereSql, $bindings] = $this->buildWhere($criteria);
            $sql .= ' WHERE ' . $whereSql;
        }

        if ($orderBy !== []) {
            $parts = [];
            foreach ($orderBy as $column => $direction) {
                $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
                $parts[] = $column . ' ' . $dir;
            }
            $sql .= ' ORDER BY ' . implode(', ', $parts);
        }

        return $this->db->fetchAll($sql, $bindings);
    }

    /**
     * Создаёт запись и возвращает её id.
     *
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $sql = 'INSERT INTO ' . $this->table
            . ' (' . implode(', ', $columns) . ')'
            . ' VALUES (' . $placeholders . ')';

        return (int) $this->db->insert($sql, array_values($data));
    }

    /**
     * Обновляет запись по id и возвращает количество затронутых строк.
     *
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): int
    {
        if ($data === []) {
            return 0;
        }

        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = $column . ' = ?';
        }

        $sql = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $bindings = array_values($data);
        $bindings[] = $id;

        return $this->db->execute($sql, $bindings);
    }

    /**
     * Удаляет запись по id и возвращает количество затронутых строк.
     */
    public function delete(int|string $id): int
    {
        return $this->db->execute('DELETE FROM ' . $this->table . ' WHERE id = ?', [$id]);
    }

    /**
     * Строит SQL-условие WHERE и биндинги из ассоциативного массива.
     *
     * @param array<string, mixed> $criteria
     * @return array{string, array<int, mixed>}
     */
    protected function buildWhere(array $criteria): array
    {
        $clauses = [];
        $bindings = [];

        foreach ($criteria as $column => $value) {
            if ($value === null) {
                $clauses[] = $column . ' IS NULL';
                continue;
            }

            $clauses[] = $column . ' = ?';
            $bindings[] = $value;
        }

        return [implode(' AND ', $clauses), $bindings];
    }
}
