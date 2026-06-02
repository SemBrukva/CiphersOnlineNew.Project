<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Database\Database;
use App\Database\Tables;
use RuntimeException;
use Throwable;

/**
 * Импортирует контент страницы категории шифров из JSON-файла в БД.
 */
final readonly class CipherCategoryContentImportCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Database $db)
    {
    }

    /**
     * Выполняет импорт контента категории.
     *
     * @param string[] $args args: <json_path> [--dry-run]
     */
    public function handle(array $args): int
    {
        $jsonPath = trim((string) ($args[0] ?? ''));
        $dryRun = in_array('--dry-run', $args, true);

        if ($jsonPath === '') {
            $this->printUsage();
            return 1;
        }

        $payload = $this->readPayload($jsonPath);
        if ($payload === null) {
            return 1;
        }

        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $categoryAlias = trim((string) ($meta['category_alias'] ?? ''));
        $language = mb_strtolower(trim((string) ($meta['language'] ?? '')));
        $defaultLanguage = mb_strtolower(trim((string) ($meta['default_language'] ?? '')));

        if ($categoryAlias === '' || $language === '') {
            echo 'В JSON отсутствуют обязательные meta-поля: category_alias, language.' . PHP_EOL;
            return 1;
        }

        if ($defaultLanguage === '') {
            $defaultLanguage = mb_strtolower((string) config('locale.locale', 'en'));
        }

        $categoryId = $this->findCategoryId($categoryAlias);
        if ($categoryId === null) {
            echo 'Категория не найдена по alias из файла.' . PHP_EOL;
            return 1;
        }

        $now = date('Y-m-d H:i:s');
        $summary = [
            'category_translation' => 0,
            'blocks' => 0, 'blocks_created' => 0,
            'tasks' => 0, 'tasks_created' => 0,
            'used_together' => 0, 'used_together_created' => 0,
            'faq' => 0, 'faq_created' => 0,
        ];

        try {
            $this->db->transaction(function () use ($payload, $categoryId, $language, $defaultLanguage, $now, $dryRun, &$summary): void {
                $translation = is_array($payload['category_translation'] ?? null) ? $payload['category_translation'] : null;
                if ($translation !== null) {
                    $this->upsertTranslation(
                        Tables::CIPHER_CATEGORY_TRANSLATIONS,
                        'category_id',
                        $categoryId,
                        $language,
                        $this->readData($translation),
                        ['name', 'name_short', 'description', 'meta_title', 'meta_description'],
                        $now
                    );
                    $summary['category_translation']++;
                }

                foreach ($this->readItems($payload, 'blocks') as $item) {
                    $entityId = $this->resolveSimpleEntity(
                        Tables::CIPHERS_CATEGORIES_BLOCKS,
                        Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS,
                        'block_id',
                        $categoryId,
                        $language,
                        $defaultLanguage,
                        $item,
                        $now,
                        $summary['blocks_created']
                    );
                    $this->upsertTranslation(
                        Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS,
                        'block_id',
                        $entityId,
                        $language,
                        $this->readData($item),
                        ['title', 'text'],
                        $now
                    );
                    $summary['blocks']++;
                }

                foreach ($this->readItems($payload, 'tasks') as $item) {
                    $entityId = $this->resolveTask($categoryId, $language, $defaultLanguage, $item, $now, $summary['tasks_created']);
                    $this->upsertTranslation(
                        Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS,
                        'task_id',
                        $entityId,
                        $language,
                        $this->readData($item),
                        ['title', 'description'],
                        $now
                    );
                    $summary['tasks']++;
                }

                foreach ($this->readItems($payload, 'used_together') as $item) {
                    $entityId = $this->resolveUsedTogether($categoryId, $language, $defaultLanguage, $item, $now, $summary['used_together_created']);
                    $this->upsertTranslation(
                        Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS,
                        'used_together_id',
                        $entityId,
                        $language,
                        $this->readData($item),
                        ['title'],
                        $now
                    );
                    $summary['used_together']++;
                }

                foreach ($this->readItems($payload, 'faq') as $item) {
                    $entityId = $this->resolveSimpleEntity(
                        Tables::CIPHERS_CATEGORIES_FAQ,
                        Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS,
                        'faq_id',
                        $categoryId,
                        $language,
                        $defaultLanguage,
                        $item,
                        $now,
                        $summary['faq_created']
                    );
                    $this->upsertTranslation(
                        Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS,
                        'faq_id',
                        $entityId,
                        $language,
                        $this->readData($item),
                        ['question', 'answer'],
                        $now
                    );
                    $summary['faq']++;
                }

                if ($dryRun) {
                    throw new DryRunRollbackException();
                }
            });
        } catch (DryRunRollbackException) {
            echo 'Dry-run завершён: изменения откатаны.' . PHP_EOL;
        } catch (Throwable $e) {
            echo 'Ошибка импорта: ' . $e->getMessage() . PHP_EOL;
            return 1;
        }

        if (!$dryRun) {
            echo 'Импорт завершён: изменения сохранены.' . PHP_EOL;
        }

        echo 'category_alias=' . $categoryAlias . ', language=' . $language . PHP_EOL;
        echo 'Обновлено: category_translation=' . $summary['category_translation']
            . ', blocks=' . $summary['blocks'] . ' (создано: ' . $summary['blocks_created'] . ')'
            . ', tasks=' . $summary['tasks'] . ' (создано: ' . $summary['tasks_created'] . ')'
            . ', used_together=' . $summary['used_together'] . ' (создано: ' . $summary['used_together_created'] . ')'
            . ', faq=' . $summary['faq'] . ' (создано: ' . $summary['faq_created'] . ')' . PHP_EOL;

        return 0;
    }

    /**
     * Выводит подсказку по использованию команды.
     */
    private function printUsage(): void
    {
        echo 'Использование:' . PHP_EOL;
        echo '  php bin/console cipher:category:content:import <json_path> [--dry-run]' . PHP_EOL;
    }

    /**
     * Читает JSON-файл импорта.
     *
     * @return array<string, mixed>|null
     */
    private function readPayload(string $jsonPath): ?array
    {
        if (!is_file($jsonPath)) {
            echo 'Файл не найден: ' . $jsonPath . PHP_EOL;
            return null;
        }

        $raw = file_get_contents($jsonPath);
        $payload = $raw === false ? null : json_decode($raw, true);

        if (!is_array($payload)) {
            echo 'Некорректный JSON в файле.' . PHP_EOL;
            return null;
        }

        return $payload;
    }

    /**
     * Возвращает ID категории по alias.
     */
    private function findCategoryId(string $categoryAlias): ?int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            [$categoryAlias]
        );

        return $row === false ? null : (int) $row['id'];
    }

    /**
     * Возвращает список сущностей секции.
     *
     * @param  array<string, mixed> $payload Данные импортируемого JSON.
     * @return array<int, array<string, mixed>>
     */
    private function readItems(array $payload, string $section): array
    {
        $items = $payload[$section] ?? [];

        return is_array($items)
            ? array_values(array_filter($items, static fn (mixed $item): bool => is_array($item)))
            : [];
    }

    /**
     * Возвращает текстовые поля сущности.
     *
     * @param  array<string, mixed> $item Импортируемая сущность.
     * @return array<string, mixed>
     */
    private function readData(array $item): array
    {
        return is_array($item['data'] ?? null) ? $item['data'] : [];
    }

    /**
     * Проверяет, разрешено ли создание новой сущности для языка.
     */
    private function assertCreationAllowed(string $language, string $defaultLanguage, string $section): void
    {
        if ($language !== $defaultLanguage) {
            throw new RuntimeException(
                'Добавление новых элементов в секции ' . $section
                . ' разрешено только для default_language (текущий: ' . $language
                . ', default: ' . $defaultLanguage . ').'
            );
        }
    }

    /**
     * Проверяет принадлежность сущности категории.
     */
    private function assertOwnership(string $table, int $categoryId, int $entityId): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE id = ? AND category_id = ? LIMIT 1',
            [$entityId, $categoryId]
        );

        if ($row === false) {
            throw new RuntimeException('Сущность не принадлежит категории: ' . $table . ' #' . $entityId);
        }
    }

    /**
     * Создаёт или находит простую сущность категории.
     *
     * @param array<string, mixed> $item Импортируемая сущность.
     */
    private function resolveSimpleEntity(
        string $entityTable,
        string $translationTable,
        string $translationForeignKey,
        int $categoryId,
        string $language,
        string $defaultLanguage,
        array $item,
        string $now,
        int &$created
    ): int {
        $entityId = (int) ($item['id'] ?? 0);
        if ($entityId > 0) {
            $this->assertOwnership($entityTable, $categoryId, $entityId);
            return $entityId;
        }

        $this->assertCreationAllowed($language, $defaultLanguage, $entityTable);
        $sortOrder = $this->sanitizeSortOrder($item);
        $reusedId = $this->findReusableEntityIdBySortOrder(
            $entityTable,
            $translationTable,
            $translationForeignKey,
            $categoryId,
            $sortOrder,
            $language
        );

        if ($reusedId !== null) {
            return $reusedId;
        }

        $created++;
        return (int) $this->db->insert(
            'INSERT INTO ' . $entityTable . ' (category_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$categoryId, $sortOrder, $this->sanitizePublished($item), $now, $now]
        );
    }

    /**
     * Создаёт или находит задачу категории.
     *
     * @param array<string, mixed> $item Импортируемая задача.
     */
    private function resolveTask(int $categoryId, string $language, string $defaultLanguage, array $item, string $now, int &$created): int
    {
        $entityId = (int) ($item['id'] ?? 0);
        if ($entityId > 0) {
            $this->assertOwnership(Tables::CIPHERS_CATEGORIES_TASKS, $categoryId, $entityId);
            return $entityId;
        }

        $this->assertCreationAllowed($language, $defaultLanguage, 'tasks');
        $cipherId = $this->findCipherId($categoryId, (string) ($item['cipher_alias'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_TASKS . ' WHERE category_id = ? AND relation_cipher_id = ? LIMIT 1',
            [$categoryId, $cipherId]
        );

        if ($existing !== false) {
            return (int) $existing['id'];
        }

        $created++;
        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_TASKS
            . ' (category_id, relation_cipher_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$categoryId, $cipherId, $this->sanitizeSortOrder($item), $this->sanitizePublished($item), $now, $now]
        );
    }

    /**
     * Создаёт или находит связку инструментов категории.
     *
     * @param array<string, mixed> $item Импортируемая связка.
     */
    private function resolveUsedTogether(int $categoryId, string $language, string $defaultLanguage, array $item, string $now, int &$created): int
    {
        $entityId = (int) ($item['id'] ?? 0);
        if ($entityId > 0) {
            $this->assertOwnership(Tables::CIPHERS_CATEGORIES_USED_TOGETHER, $categoryId, $entityId);
            return $entityId;
        }

        $this->assertCreationAllowed($language, $defaultLanguage, 'used_together');
        $firstId = $this->findCipherId($categoryId, (string) ($item['first_cipher_alias'] ?? ''));
        $secondId = $this->findCipherId($categoryId, (string) ($item['second_cipher_alias'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER
            . ' WHERE category_id = ? AND relation_cipher_first_id = ? AND relation_cipher_second_id = ? LIMIT 1',
            [$categoryId, $firstId, $secondId]
        );

        if ($existing !== false) {
            return (int) $existing['id'];
        }

        $created++;
        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER
            . ' (category_id, relation_cipher_first_id, relation_cipher_second_id, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, $firstId, $secondId, $this->sanitizeSortOrder($item), $this->sanitizePublished($item), $now, $now]
        );
    }

    /**
     * Ищет существующую сущность по sort_order без перевода для языка.
     */
    private function findReusableEntityIdBySortOrder(
        string $entityTable,
        string $translationTable,
        string $translationForeignKey,
        int $categoryId,
        int $sortOrder,
        string $language
    ): ?int {
        $row = $this->db->fetch(
            'SELECT e.id FROM ' . $entityTable . ' e '
            . 'LEFT JOIN ' . $translationTable . ' t ON t.' . $translationForeignKey . ' = e.id AND t.language = ? '
            . 'WHERE e.category_id = ? AND e.sort_order = ? AND t.id IS NULL ORDER BY e.id ASC LIMIT 1',
            [$language, $categoryId, $sortOrder]
        );

        return $row === false ? null : (int) $row['id'];
    }

    /**
     * Возвращает ID шифра категории по alias.
     */
    private function findCipherId(int $categoryId, string $cipherAlias): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, trim($cipherAlias)]
        );

        if ($row === false) {
            throw new RuntimeException('Связанный шифр не найден в категории: ' . $cipherAlias);
        }

        return (int) $row['id'];
    }

    /**
     * Создаёт, обновляет или удаляет перевод сущности.
     *
     * @param array<string, mixed> $data   Текстовые данные.
     * @param string[]             $fields Имена текстовых полей.
     */
    private function upsertTranslation(
        string $table,
        string $foreignKey,
        int $entityId,
        string $language,
        array $data,
        array $fields,
        string $now
    ): void {
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = trim((string) ($data[$field] ?? ''));
        }

        $existing = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND language = ? LIMIT 1',
            [$entityId, $language]
        );

        if (!array_filter($values, static fn (string $value): bool => $value !== '')) {
            if ($existing !== false) {
                $this->db->execute('DELETE FROM ' . $table . ' WHERE id = ?', [(int) $existing['id']]);
            }
            return;
        }

        if ($existing === false) {
            $columns = array_merge([$foreignKey, 'language'], $fields, ['created_at', 'updated_at']);
            $bindings = array_merge([$entityId, $language], array_values($values), [$now, $now]);
            $this->db->insert(
                'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES ('
                . implode(', ', array_fill(0, count($columns), '?')) . ')',
                $bindings
            );
            return;
        }

        $sets = implode(', ', array_map(static fn (string $field): string => $field . ' = ?', $fields));
        $this->db->execute(
            'UPDATE ' . $table . ' SET ' . $sets . ', updated_at = ? WHERE id = ?',
            array_merge(array_values($values), [$now, (int) $existing['id']])
        );
    }

    /**
     * Нормализует порядок сортировки сущности.
     *
     * @param array<string, mixed> $item Импортируемая сущность.
     */
    private function sanitizeSortOrder(array $item): int
    {
        return max(0, min(999999, (int) ($item['sort_order'] ?? 0)));
    }

    /**
     * Нормализует признак публикации сущности.
     *
     * @param array<string, mixed> $item Импортируемая сущность.
     */
    private function sanitizePublished(array $item): int
    {
        return (bool) ($item['published'] ?? true) ? 1 : 0;
    }
}
