<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Database\Database;
use App\Database\Tables;
use Throwable;

/**
 * Импортирует контент страницы шифра из JSON-файла в БД.
 */
final readonly class CipherContentImportCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Database $db)
    {
    }

    /**
     * Выполняет импорт контента.
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

        if (!is_file($jsonPath)) {
            echo 'Файл не найден: ' . $jsonPath . PHP_EOL;
            return 1;
        }

        $raw = file_get_contents($jsonPath);
        if ($raw === false) {
            echo 'Не удалось прочитать файл: ' . $jsonPath . PHP_EOL;
            return 1;
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            echo 'Некорректный JSON в файле.' . PHP_EOL;
            return 1;
        }

        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $categoryAlias = trim((string) ($meta['category_alias'] ?? ''));
        $cipherAlias = trim((string) ($meta['cipher_alias'] ?? ''));
        $language = mb_strtolower(trim((string) ($meta['language'] ?? '')));
        $defaultLanguage = mb_strtolower(trim((string) ($meta['default_language'] ?? '')));

        if ($categoryAlias === '' || $cipherAlias === '' || $language === '') {
            echo 'В JSON отсутствуют обязательные meta-поля: category_alias, cipher_alias, language.' . PHP_EOL;
            return 1;
        }

        if ($defaultLanguage === '') {
            $defaultLanguage = mb_strtolower((string) config('locale.locale', 'en'));
        }

        $cipher = $this->findCipher($categoryAlias, $cipherAlias);
        if ($cipher === null) {
            echo 'Шифр не найден по alias из файла.' . PHP_EOL;
            return 1;
        }

        $cipherId = (int) $cipher['cipher_id'];
        $now = date('Y-m-d H:i:s');
        $summary = [
            'cipher_translation' => 0,
            'blocks' => 0,
            'blocks_created' => 0,
            'faq' => 0,
            'faq_created' => 0,
            'examples' => 0,
            'examples_created' => 0,
            'tags' => 0,
            'tags_created' => 0,
        ];

        try {
            $this->db->transaction(function () use ($payload, $language, $cipherId, $now, $dryRun, &$summary): void {
                $cipherTranslation = is_array($payload['cipher_translation'] ?? null) ? $payload['cipher_translation'] : null;
                if ($cipherTranslation !== null) {
                    $data = is_array($cipherTranslation['data'] ?? null) ? $cipherTranslation['data'] : [];
                    $this->upsertCipherTranslation($cipherId, $language, $data, $now);
                    $summary['cipher_translation']++;
                }

                foreach ($this->readEntityItems($payload, 'blocks') as $item) {
                    $blockId = (int) ($item['id'] ?? 0);
                    $data = is_array($item['data'] ?? null) ? $item['data'] : [];
                    if ($blockId < 1) {
                        $this->assertCreationAllowedForLanguage($language, $defaultLanguage, 'blocks');
                        $resolved = $this->createOrReuseBlockEntity($cipherId, $language, $item, $now);
                        $blockId = $resolved['id'];
                        if ($resolved['created']) {
                            $summary['blocks_created']++;
                        }
                    } else {
                        $this->assertOwnership(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, $blockId, 'block');
                    }
                    $this->upsertBlockTranslation($blockId, $language, $data, $now);
                    $summary['blocks']++;
                }

                foreach ($this->readEntityItems($payload, 'faq') as $item) {
                    $faqId = (int) ($item['id'] ?? 0);
                    $data = is_array($item['data'] ?? null) ? $item['data'] : [];
                    if ($faqId < 1) {
                        $this->assertCreationAllowedForLanguage($language, $defaultLanguage, 'faq');
                        $resolved = $this->createOrReuseFaqEntity($cipherId, $language, $item, $now);
                        $faqId = $resolved['id'];
                        if ($resolved['created']) {
                            $summary['faq_created']++;
                        }
                    } else {
                        $this->assertOwnership(Tables::CIPHERS_FAQ, 'app_id', $cipherId, $faqId, 'faq');
                    }
                    $this->upsertFaqTranslation($faqId, $language, $data, $now);
                    $summary['faq']++;
                }

                foreach ($this->readEntityItems($payload, 'examples') as $item) {
                    $exampleId = (int) ($item['id'] ?? 0);
                    $data = is_array($item['data'] ?? null) ? $item['data'] : [];
                    if ($exampleId < 1) {
                        $this->assertCreationAllowedForLanguage($language, $defaultLanguage, 'examples');
                        $resolved = $this->createOrReuseExampleEntity($cipherId, $language, $item, $now);
                        $exampleId = $resolved['id'];
                        if ($resolved['created']) {
                            $summary['examples_created']++;
                        }
                    } else {
                        $this->assertOwnership(Tables::CIPHERS_EXAMPLES, 'app_id', $cipherId, $exampleId, 'example');
                    }
                    $this->upsertExampleTranslation($exampleId, $language, $data, $now);
                    $summary['examples']++;
                }

                foreach ($this->readEntityItems($payload, 'tags') as $item) {
                    $tagId = (int) ($item['id'] ?? 0);
                    $data = is_array($item['data'] ?? null) ? $item['data'] : [];
                    if ($tagId < 1) {
                        $this->assertCreationAllowedForLanguage($language, $defaultLanguage, 'tags');
                        $resolved = $this->createOrReuseTagEntity($cipherId, $language, $item, $now);
                        $tagId = $resolved['id'];
                        if ($resolved['created']) {
                            $summary['tags_created']++;
                        }
                    } else {
                        $this->assertOwnership(Tables::CIPHERS_TAGS, 'app_id', $cipherId, $tagId, 'tag');
                    }
                    $this->upsertTagTranslation($tagId, $language, $data, $now);
                    $summary['tags']++;
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

        echo 'cipher_alias=' . $cipherAlias . ', language=' . $language . PHP_EOL;
        echo 'Обновлено: cipher_translation=' . $summary['cipher_translation']
            . ', blocks=' . $summary['blocks'] . ' (создано: ' . $summary['blocks_created'] . ')'
            . ', faq=' . $summary['faq']
            . ' (создано: ' . $summary['faq_created'] . ')'
            . ', examples=' . $summary['examples'] . ' (создано: ' . $summary['examples_created'] . ')'
            . ', tags=' . $summary['tags'] . ' (создано: ' . $summary['tags_created'] . ')' . PHP_EOL;

        return 0;
    }

    /**
     * Выводит подсказку по использованию команды.
     */
    private function printUsage(): void
    {
        echo 'Использование:' . PHP_EOL;
        echo '  php bin/console cipher:content:import <json_path> [--dry-run]' . PHP_EOL;
    }

    /**
     * Возвращает шифр по alias категории и alias шифра.
     *
     * @return array<string, mixed>|null
     */
    private function findCipher(string $categoryAlias, string $cipherAlias): ?array
    {
        $row = $this->db->fetch(
            'SELECT c.id AS cipher_id '
            . 'FROM ' . Tables::CIPHERS . ' c '
            . 'INNER JOIN ' . Tables::CIPHER_CATEGORIES . ' cat ON cat.id = c.category_id '
            . 'WHERE c.alias = ? AND cat.alias = ? '
            . 'LIMIT 1',
            [$cipherAlias, $categoryAlias]
        );

        return $row === false ? null : $row;
    }

    /**
     * Возвращает список сущностей указанной секции.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readEntityItems(array $payload, string $section): array
    {
        $items = $payload[$section] ?? [];
        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, static fn (mixed $item): bool => is_array($item)));
    }

    /**
     * Проверяет принадлежность сущности конкретному шифру.
     */
    private function assertOwnership(string $table, string $foreignKey, int $cipherId, int $entityId, string $entityName): void
    {
        if ($entityId < 1) {
            throw new \RuntimeException('Некорректный id у сущности: ' . $entityName);
        }

        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE id = ? AND ' . $foreignKey . ' = ? LIMIT 1',
            [$entityId, $cipherId]
        );

        if ($row === false) {
            throw new \RuntimeException('Сущность не принадлежит шифру: ' . $entityName . ' #' . $entityId);
        }
    }

    /**
     * Проверяет, разрешено ли создание новой сущности для текущего языка.
     */
    private function assertCreationAllowedForLanguage(string $language, string $defaultLanguage, string $section): void
    {
        if ($language !== $defaultLanguage) {
            throw new \RuntimeException(
                'Добавление новых элементов в секции ' . $section
                . ' разрешено только для default_language (текущий: ' . $language
                . ', default: ' . $defaultLanguage . ').'
            );
        }
    }

    /**
     * Создаёт новую FAQ-сущность для шифра.
     *
     * @param array<string, mixed> $item Элемент FAQ из JSON.
     */
    private function createFaqEntity(int $cipherId, array $item, string $now): int
    {
        $sortOrder = max(0, min(999999, (int) ($item['sort_order'] ?? 0)));
        $published = (bool) ($item['published'] ?? true) ? 1 : 0;

        $id = $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_FAQ . ' (app_id, sort_order, published, show_in_category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $published, 0, $now, $now]
        );

        return (int) $id;
    }

    /**
     * Создаёт новую сущность info-блока для шифра.
     *
     * @param array<string, mixed> $item Элемент блока из JSON.
     */
    private function createBlockEntity(int $cipherId, array $item, string $now): int
    {
        $sortOrder = max(0, min(999999, (int) ($item['sort_order'] ?? 0)));
        $published = (bool) ($item['published'] ?? true) ? 1 : 0;

        $id = $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $published, $now, $now]
        );

        return (int) $id;
    }

    /**
     * Создаёт новую сущность примера для шифра.
     *
     * @param array<string, mixed> $item Элемент примера из JSON.
     */
    private function createExampleEntity(int $cipherId, array $item, string $now): int
    {
        $sortOrder = max(0, min(999999, (int) ($item['sort_order'] ?? 0)));
        $published = (bool) ($item['published'] ?? true) ? 1 : 0;

        $id = $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $published, $now, $now]
        );

        return (int) $id;
    }

    /**
     * Создаёт новую сущность тега для шифра.
     *
     * @param array<string, mixed> $item Элемент тега из JSON.
     */
    private function createTagEntity(int $cipherId, array $item, string $now): int
    {
        $sortOrder = max(0, min(999999, (int) ($item['sort_order'] ?? 0)));
        $published = (bool) ($item['published'] ?? true) ? 1 : 0;

        $id = $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_TAGS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $published, $now, $now]
        );

        return (int) $id;
    }

    /**
     * Создаёт или переиспользует FAQ-сущность для языка по sort_order.
     *
     * @param array<string, mixed> $item Элемент FAQ из JSON.
     */
    private function createOrReuseFaqEntity(int $cipherId, string $language, array $item, string $now): array
    {
        $sortOrder = max(0, min(999999, (int) ($item['sort_order'] ?? 0)));
        $reusedId = $this->findReusableEntityIdBySortOrder(
            Tables::CIPHERS_FAQ,
            Tables::CIPHERS_FAQ_TRANSLATIONS,
            'faq_id',
            $cipherId,
            $sortOrder,
            $language
        );

        if ($reusedId !== null) {
            return ['id' => $reusedId, 'created' => false];
        }

        return ['id' => $this->createFaqEntity($cipherId, $item, $now), 'created' => true];
    }

    /**
     * Создаёт или переиспользует сущность блока для языка по sort_order.
     *
     * @param array<string, mixed> $item Элемент блока из JSON.
     */
    private function createOrReuseBlockEntity(int $cipherId, string $language, array $item, string $now): array
    {
        $sortOrder = max(0, min(999999, (int) ($item['sort_order'] ?? 0)));
        $reusedId = $this->findReusableEntityIdBySortOrder(
            Tables::CIPHERS_BLOCKS,
            Tables::CIPHERS_BLOCKS_TRANSLATIONS,
            'block_id',
            $cipherId,
            $sortOrder,
            $language
        );

        if ($reusedId !== null) {
            return ['id' => $reusedId, 'created' => false];
        }

        return ['id' => $this->createBlockEntity($cipherId, $item, $now), 'created' => true];
    }

    /**
     * Создаёт или переиспользует сущность примера для языка по sort_order.
     *
     * @param array<string, mixed> $item Элемент примера из JSON.
     */
    private function createOrReuseExampleEntity(int $cipherId, string $language, array $item, string $now): array
    {
        $sortOrder = max(0, min(999999, (int) ($item['sort_order'] ?? 0)));
        $reusedId = $this->findReusableEntityIdBySortOrder(
            Tables::CIPHERS_EXAMPLES,
            Tables::CIPHERS_EXAMPLES_TRANSLATIONS,
            'example_id',
            $cipherId,
            $sortOrder,
            $language
        );

        if ($reusedId !== null) {
            return ['id' => $reusedId, 'created' => false];
        }

        return ['id' => $this->createExampleEntity($cipherId, $item, $now), 'created' => true];
    }

    /**
     * Создаёт или переиспользует сущность тега для языка по sort_order.
     *
     * @param array<string, mixed> $item Элемент тега из JSON.
     */
    private function createOrReuseTagEntity(int $cipherId, string $language, array $item, string $now): array
    {
        $sortOrder = max(0, min(999999, (int) ($item['sort_order'] ?? 0)));
        $reusedId = $this->findReusableEntityIdBySortOrder(
            Tables::CIPHERS_TAGS,
            Tables::CIPHERS_TAGS_TRANSLATIONS,
            'tag_id',
            $cipherId,
            $sortOrder,
            $language
        );

        if ($reusedId !== null) {
            return ['id' => $reusedId, 'created' => false];
        }

        return ['id' => $this->createTagEntity($cipherId, $item, $now), 'created' => true];
    }

    /**
     * Ищет существующую сущность с тем же sort_order, у которой нет перевода для языка.
     */
    private function findReusableEntityIdBySortOrder(
        string $entityTable,
        string $translationTable,
        string $translationForeignKey,
        int $cipherId,
        int $sortOrder,
        string $language
    ): ?int {
        $row = $this->db->fetch(
            'SELECT e.id '
            . 'FROM ' . $entityTable . ' e '
            . 'LEFT JOIN ' . $translationTable . ' t '
            . 'ON t.' . $translationForeignKey . ' = e.id AND t.language = ? '
            . 'WHERE e.app_id = ? AND e.sort_order = ? AND t.id IS NULL '
            . 'ORDER BY e.id ASC '
            . 'LIMIT 1',
            [$language, $cipherId, $sortOrder]
        );

        if ($row === false) {
            return null;
        }

        return (int) ($row['id'] ?? 0);
    }

    /**
     * Создаёт или обновляет перевод шифра для языка.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertCipherTranslation(int $cipherId, string $language, array $row, string $now): void
    {
        $name = trim((string) ($row['name'] ?? ''));
        $nameShort = trim((string) ($row['name_short'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $descriptionStort = trim((string) ($row['description_stort'] ?? ''));
        $metaTitle = trim((string) ($row['meta_title'] ?? ''));
        $metaDescription = trim((string) ($row['meta_description'] ?? ''));

        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ? AND language = ? LIMIT 1',
            [$cipherId, $language]
        );

        $hasAnyValue = $name !== '' || $nameShort !== '' || $description !== '' || $descriptionStort !== '' || $metaTitle !== '' || $metaDescription !== '';

        if (!$hasAnyValue) {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ? AND language = ?',
                    [$cipherId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_TRANSLATIONS . ' (app_id, language, name, name_short, description, description_stort, meta_title, meta_description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$cipherId, $language, $name, $nameShort, $description !== '' ? $description : null, $descriptionStort, $metaTitle, $metaDescription !== '' ? $metaDescription : null, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_TRANSLATIONS . ' SET name = ?, name_short = ?, description = ?, description_stort = ?, meta_title = ?, meta_description = ?, updated_at = ? WHERE id = ?',
            [$name, $nameShort, $description !== '' ? $description : null, $descriptionStort, $metaTitle, $metaDescription !== '' ? $metaDescription : null, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод info-блока.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertBlockTranslation(int $blockId, string $language, array $row, string $now): void
    {
        $title = trim((string) ($row['title'] ?? ''));
        $text = trim((string) ($row['text'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ? LIMIT 1',
            [$blockId, $language]
        );

        if ($title === '' && $text === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ?',
                    [$blockId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' (block_id, language, title, text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$blockId, $language, $title, $text, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' SET title = ?, text = ?, updated_at = ? WHERE id = ?',
            [$title, $text, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод FAQ.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertFaqTranslation(int $faqId, string $language, array $row, string $now): void
    {
        $question = trim((string) ($row['question'] ?? ''));
        $answer = trim((string) ($row['answer'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' WHERE faq_id = ? AND language = ? LIMIT 1',
            [$faqId, $language]
        );

        if ($question === '' && $answer === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' WHERE faq_id = ? AND language = ?',
                    [$faqId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' (faq_id, language, question, answer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$faqId, $language, $question, $answer, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' SET question = ?, answer = ?, updated_at = ? WHERE id = ?',
            [$question, $answer, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertExampleTranslation(int $exampleId, string $language, array $row, string $now): void
    {
        $title = trim((string) ($row['title'] ?? ''));
        $key = trim((string) ($row['key'] ?? ''));
        $input = trim((string) ($row['input'] ?? ''));
        $output = trim((string) ($row['output'] ?? ''));
        $description = trim((string) ($row['description'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($title === '' && $key === '' && $input === '' && $output === '' && $description === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ?',
                    [$exampleId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' (example_id, language, title, `key`, input, output, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$exampleId, $language, $title, $key, $input, $output, $description, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' SET title = ?, `key` = ?, input = ?, output = ?, description = ?, updated_at = ? WHERE id = ?',
            [$title, $key, $input, $output, $description, $now, (int) $existing['id']]
        );
    }

    /**
     * Создаёт или обновляет перевод тега.
     *
     * @param array<string, mixed> $row Данные перевода.
     */
    private function upsertTagTranslation(int $tagId, string $language, array $row, string $now): void
    {
        $tagValue = trim((string) ($row['tag'] ?? ''));
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' WHERE tag_id = ? AND language = ? LIMIT 1',
            [$tagId, $language]
        );

        if ($tagValue === '') {
            if ($existing !== false) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' WHERE tag_id = ? AND language = ?',
                    [$tagId, $language]
                );
            }

            return;
        }

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' (tag_id, language, tag, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                [$tagId, $language, $tagValue, $now, $now]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' SET tag = ?, updated_at = ? WHERE id = ?',
            [$tagValue, $now, (int) $existing['id']]
        );
    }
}
