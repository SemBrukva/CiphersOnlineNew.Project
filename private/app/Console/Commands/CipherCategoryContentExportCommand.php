<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Database\Database;
use App\Database\Tables;

/**
 * Экспортирует контент страницы категории шифров в JSON-файл для внешнего редактирования.
 */
final readonly class CipherCategoryContentExportCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Database $db)
    {
    }

    /**
     * Выполняет экспорт контента категории.
     *
     * @param string[] $args args: <category_alias> <language> [output_path]
     */
    public function handle(array $args): int
    {
        $categoryAlias = trim((string) ($args[0] ?? ''));
        $language = mb_strtolower(trim((string) ($args[1] ?? '')));
        $outputPath = (string) ($args[2] ?? '');

        if ($categoryAlias === '' || $language === '') {
            $this->printUsage();
            return 1;
        }

        $defaultLanguage = mb_strtolower((string) config('locale.locale', 'en'));
        $category = $this->findCategory($categoryAlias);

        if ($category === null) {
            echo 'Категория не найдена по заданному alias.' . PHP_EOL;
            return 1;
        }

        $categoryId = (int) $category['id'];
        $payload = [
            'meta' => [
                'schema' => 'cipher-category-content.v1',
                'exported_at' => date(DATE_ATOM),
                'category_alias' => $categoryAlias,
                'language' => $language,
                'default_language' => $defaultLanguage,
                'category_id' => $categoryId,
            ],
            'category_translation' => $this->fetchCategoryTranslation($categoryId, $language, $defaultLanguage),
            'blocks' => $this->fetchBlocks($categoryId, $language, $defaultLanguage),
            'tasks' => $this->fetchTasks($categoryId, $language, $defaultLanguage),
            'used_together' => $this->fetchUsedTogether($categoryId, $language, $defaultLanguage),
            'faq' => $this->fetchFaq($categoryId, $language, $defaultLanguage),
            'notes' => [
                'Сохраняйте id у каждой сущности без изменений.',
                'Менять нужно только текстовые поля в data.',
                'Пустые строки допустимы: при импорте это удалит перевод для конкретного языка.',
                'Новые элементы без id или с id=0 разрешено добавлять только в файле где meta.language == meta.default_language.',
                'Для новых tasks и used_together указывайте alias связанных шифров. Для связки с другой категорией используйте формат category_alias/cipher_alias.',
            ],
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            echo 'Не удалось сериализовать JSON.' . PHP_EOL;
            return 1;
        }

        if ($outputPath === '') {
            $outputPath = PRIVATE_PATH . '/storage/content/categories/' . $categoryAlias . '.' . $language . '.json';
        }

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, $json . PHP_EOL);

        echo 'Экспорт завершён: ' . $outputPath . PHP_EOL;
        echo 'category_id=' . $categoryId . PHP_EOL;

        return 0;
    }

    /**
     * Выводит подсказку по использованию команды.
     */
    private function printUsage(): void
    {
        echo 'Использование:' . PHP_EOL;
        echo '  php bin/console cipher:category:content:export <category_alias> <language> [output_path]' . PHP_EOL;
        echo 'Пример:' . PHP_EOL;
        echo '  php bin/console cipher:category:content:export encoding en' . PHP_EOL;
    }

    /**
     * Возвращает категорию по alias.
     *
     * @return array<string, mixed>|null
     */
    private function findCategory(string $categoryAlias): ?array
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            [$categoryAlias]
        );

        return $row === false ? null : $row;
    }

    /**
     * Возвращает перевод категории с fallback на язык по умолчанию.
     *
     * @return array<string, mixed>
     */
    private function fetchCategoryTranslation(int $categoryId, string $language, string $defaultLanguage): array
    {
        $row = $this->db->fetch(
            'SELECT COALESCE(cur.name, def.name, \'\') AS name, '
            . 'COALESCE(cur.name_short, def.name_short, \'\') AS name_short, '
            . 'COALESCE(cur.description, def.description, \'\') AS description, '
            . 'COALESCE(cur.meta_title, def.meta_title, \'\') AS meta_title, '
            . 'COALESCE(cur.meta_description, def.meta_description, \'\') AS meta_description '
            . 'FROM ' . Tables::CIPHER_CATEGORIES . ' c '
            . 'LEFT JOIN ' . Tables::CIPHER_CATEGORY_TRANSLATIONS . ' cur ON cur.category_id = c.id AND cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHER_CATEGORY_TRANSLATIONS . ' def ON def.category_id = c.id AND def.language = ? '
            . 'WHERE c.id = ? LIMIT 1',
            [$language, $defaultLanguage, $categoryId]
        );

        return [
            'id' => $categoryId,
            'data' => [
                'name' => (string) ($row['name'] ?? ''),
                'name_short' => (string) ($row['name_short'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'meta_title' => (string) ($row['meta_title'] ?? ''),
                'meta_description' => (string) ($row['meta_description'] ?? ''),
            ],
        ];
    }

    /**
     * Возвращает блоки категории с переводами.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchBlocks(int $categoryId, string $language, string $defaultLanguage): array
    {
        $rows = $this->fetchTranslatedEntities(
            Tables::CIPHERS_CATEGORIES_BLOCKS,
            Tables::CIPHERS_CATEGORIES_BLOCKS_TRANSLATIONS,
            'block_id',
            $categoryId,
            $language,
            $defaultLanguage,
            ['title', 'text']
        );

        return $this->mapEntities($rows, ['title', 'text']);
    }

    /**
     * Возвращает задачи категории с переводами и alias шифров.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchTasks(int $categoryId, string $language, string $defaultLanguage): array
    {
        $rows = $this->db->fetchAll(
            'SELECT e.id, e.sort_order, e.published, c.alias AS cipher_alias, '
            . 'COALESCE(cur.title, def.title, \'\') AS title, '
            . 'COALESCE(cur.description, def.description, \'\') AS description '
            . 'FROM ' . Tables::CIPHERS_CATEGORIES_TASKS . ' e '
            . 'INNER JOIN ' . Tables::CIPHERS . ' c ON c.id = e.relation_cipher_id '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS . ' cur ON cur.task_id = e.id AND cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_TASKS_TRANSLATIONS . ' def ON def.task_id = e.id AND def.language = ? '
            . 'WHERE e.category_id = ? ORDER BY e.sort_order ASC, e.id ASC',
            [$language, $defaultLanguage, $categoryId]
        );

        return array_map(fn (array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'published' => ((int) ($row['published'] ?? 0)) === 1,
            'cipher_alias' => (string) ($row['cipher_alias'] ?? ''),
            'data' => [
                'title' => (string) ($row['title'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
            ],
        ], $rows);
    }

    /**
     * Возвращает связки инструментов категории с переводами и alias шифров.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchUsedTogether(int $categoryId, string $language, string $defaultLanguage): array
    {
        $rows = $this->db->fetchAll(
            'SELECT e.id, e.sort_order, e.published, '
            . 'cf.category_id AS first_cipher_category_id, cf.alias AS first_cipher_alias, '
            . 'cs.category_id AS second_cipher_category_id, cs.alias AS second_cipher_alias, '
            . 'cfcat.alias AS first_cipher_category_alias, cscat.alias AS second_cipher_category_alias, '
            . 'COALESCE(cur.title, def.title, \'\') AS title '
            . 'FROM ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER . ' e '
            . 'INNER JOIN ' . Tables::CIPHERS . ' cf ON cf.id = e.relation_cipher_first_id '
            . 'INNER JOIN ' . Tables::CIPHERS . ' cs ON cs.id = e.relation_cipher_second_id '
            . 'INNER JOIN ' . Tables::CIPHER_CATEGORIES . ' cfcat ON cfcat.id = cf.category_id '
            . 'INNER JOIN ' . Tables::CIPHER_CATEGORIES . ' cscat ON cscat.id = cs.category_id '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS . ' cur ON cur.used_together_id = e.id AND cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_CATEGORIES_USED_TOGETHER_TRANSLATIONS . ' def ON def.used_together_id = e.id AND def.language = ? '
            . 'WHERE e.category_id = ? ORDER BY e.sort_order ASC, e.id ASC',
            [$language, $defaultLanguage, $categoryId]
        );

        return array_map(fn (array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'published' => ((int) ($row['published'] ?? 0)) === 1,
            'first_cipher_alias' => $this->formatRelatedCipherAlias($categoryId, $row, 'first'),
            'second_cipher_alias' => $this->formatRelatedCipherAlias($categoryId, $row, 'second'),
            'data' => ['title' => (string) ($row['title'] ?? '')],
        ], $rows);
    }

    /**
     * Форматирует alias связанного шифра для экспорта.
     *
     * @param array<string, mixed> $row    Строка связки.
     * @param string               $prefix Префикс first или second.
     */
    private function formatRelatedCipherAlias(int $currentCategoryId, array $row, string $prefix): string
    {
        $cipherAlias = (string) ($row[$prefix . '_cipher_alias'] ?? '');
        if ((int) ($row[$prefix . '_cipher_category_id'] ?? 0) === $currentCategoryId) {
            return $cipherAlias;
        }

        $categoryAlias = (string) ($row[$prefix . '_cipher_category_alias'] ?? '');
        return $categoryAlias === '' ? $cipherAlias : $categoryAlias . '/' . $cipherAlias;
    }

    /**
     * Возвращает FAQ категории с переводами.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchFaq(int $categoryId, string $language, string $defaultLanguage): array
    {
        $rows = $this->fetchTranslatedEntities(
            Tables::CIPHERS_CATEGORIES_FAQ,
            Tables::CIPHERS_CATEGORIES_FAQ_TRANSLATIONS,
            'faq_id',
            $categoryId,
            $language,
            $defaultLanguage,
            ['question', 'answer']
        );

        return $this->mapEntities($rows, ['question', 'answer']);
    }

    /**
     * Возвращает простые сущности категории с fallback-переводом.
     *
     * @param  string[] $fields Поля перевода.
     * @return array<int, array<string, mixed>>
     */
    private function fetchTranslatedEntities(
        string $entityTable,
        string $translationTable,
        string $translationForeignKey,
        int $categoryId,
        string $language,
        string $defaultLanguage,
        array $fields
    ): array {
        $select = implode(', ', array_map(
            static fn (string $field): string => 'COALESCE(cur.' . $field . ', def.' . $field . ', \'\') AS ' . $field,
            $fields
        ));

        return $this->db->fetchAll(
            'SELECT e.id, e.sort_order, e.published, ' . $select
            . ' FROM ' . $entityTable . ' e '
            . 'LEFT JOIN ' . $translationTable . ' cur ON cur.' . $translationForeignKey . ' = e.id AND cur.language = ? '
            . 'LEFT JOIN ' . $translationTable . ' def ON def.' . $translationForeignKey . ' = e.id AND def.language = ? '
            . 'WHERE e.category_id = ? ORDER BY e.sort_order ASC, e.id ASC',
            [$language, $defaultLanguage, $categoryId]
        );
    }

    /**
     * Преобразует простые сущности категории в экспортируемый формат.
     *
     * @param  array<int, array<string, mixed>> $rows   Строки БД.
     * @param  string[]                         $fields Поля перевода.
     * @return array<int, array<string, mixed>>
     */
    private function mapEntities(array $rows, array $fields): array
    {
        return array_map(static function (array $row) use ($fields): array {
            $data = [];
            foreach ($fields as $field) {
                $data[$field] = (string) ($row[$field] ?? '');
            }

            return [
                'id' => (int) ($row['id'] ?? 0),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'published' => ((int) ($row['published'] ?? 0)) === 1,
                'data' => $data,
            ];
        }, $rows);
    }
}
