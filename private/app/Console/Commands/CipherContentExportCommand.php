<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Console\CommandInterface;
use App\Database\Database;
use App\Database\Tables;

/**
 * Экспортирует контент страницы шифра в JSON-файл для внешнего редактирования.
 */
final readonly class CipherContentExportCommand implements CommandInterface
{
    /**
     * Создаёт экземпляр команды.
     */
    public function __construct(private Database $db)
    {
    }

    /**
     * Выполняет экспорт контента.
     *
     * @param string[] $args args: <category_alias> <cipher_alias> <language> [output_path]
     */
    public function handle(array $args): int
    {
        $categoryAlias = trim((string) ($args[0] ?? ''));
        $cipherAlias = trim((string) ($args[1] ?? ''));
        $language = mb_strtolower(trim((string) ($args[2] ?? '')));
        $outputPath = (string) ($args[3] ?? '');

        if ($categoryAlias === '' || $cipherAlias === '' || $language === '') {
            $this->printUsage();
            return 1;
        }

        $defaultLanguage = (string) config('locale.locale', 'en');
        $cipher = $this->findCipher($categoryAlias, $cipherAlias);

        if ($cipher === null) {
            echo 'Шифр не найден по заданным alias.' . PHP_EOL;
            return 1;
        }

        $cipherId = (int) $cipher['cipher_id'];
        $categoryId = (int) $cipher['category_id'];

        $payload = [
            'meta' => [
                'schema' => 'cipher-content.v1',
                'exported_at' => date(DATE_ATOM),
                'category_alias' => $categoryAlias,
                'cipher_alias' => $cipherAlias,
                'language' => $language,
                'default_language' => $defaultLanguage,
                'cipher_id' => $cipherId,
                'category_id' => $categoryId,
            ],
            'cipher_translation' => $this->fetchCipherTranslation($cipherId, $language, $defaultLanguage),
            'blocks' => $this->fetchBlocks($cipherId, $language, $defaultLanguage),
            'faq' => $this->fetchFaq($cipherId, $language, $defaultLanguage),
            'examples' => $this->fetchExamples($cipherId, $language, $defaultLanguage),
            'tags' => $this->fetchTags($cipherId, $language, $defaultLanguage),
            'notes' => [
                'Сохраняйте id у каждой сущности без изменений.',
                'Менять нужно только текстовые поля в data.',
                'В blocks.data.text используйте HTML-разметку: минимум один <p>, можно несколько <p>; также допустимы списки <ul>/<ol> с <li>.',
                'Пустые строки допустимы: при импорте это удалит перевод для конкретного языка.',
                'Для добавления новых элементов в sections blocks/faq/examples/tags создавайте объект без id или с id=0: импорт создаст запись автоматически.',
                'Новые элементы (без id / с id=0) разрешено добавлять только в файле, где meta.language == meta.default_language.',
            ],
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            echo 'Не удалось сериализовать JSON.' . PHP_EOL;
            return 1;
        }

        if ($outputPath === '') {
            $outputPath = PRIVATE_PATH . '/storage/content/' . $categoryAlias . '.' . $cipherAlias . '.' . $language . '.json';
        }

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, $json . PHP_EOL);

        echo 'Экспорт завершён: ' . $outputPath . PHP_EOL;
        echo 'cipher_id=' . $cipherId . ', category_id=' . $categoryId . PHP_EOL;

        return 0;
    }

    /**
     * Выводит подсказку по использованию команды.
     */
    private function printUsage(): void
    {
        echo 'Использование:' . PHP_EOL;
        echo '  php bin/console cipher:content:export <category_alias> <cipher_alias> <language> [output_path]' . PHP_EOL;
        echo 'Пример:' . PHP_EOL;
        echo '  php bin/console cipher:content:export classical-ciphers playfair en' . PHP_EOL;
    }

    /**
     * Возвращает данные шифра и категории по alias.
     *
     * @return array<string, mixed>|null
     */
    private function findCipher(string $categoryAlias, string $cipherAlias): ?array
    {
        $row = $this->db->fetch(
            'SELECT c.id AS cipher_id, c.category_id AS category_id '
            . 'FROM ' . Tables::CIPHERS . ' c '
            . 'INNER JOIN ' . Tables::CIPHER_CATEGORIES . ' cat ON cat.id = c.category_id '
            . 'WHERE c.alias = ? AND cat.alias = ? '
            . 'LIMIT 1',
            [$cipherAlias, $categoryAlias]
        );

        return $row === false ? null : $row;
    }

    /**
     * Возвращает перевод шифра с fallback на язык по умолчанию.
     *
     * @return array<string, mixed>
     */
    private function fetchCipherTranslation(int $cipherId, string $language, string $defaultLanguage): array
    {
        $row = $this->db->fetch(
            'SELECT '
            . 'COALESCE(cur.name, def.name, \'\') AS name, '
            . 'COALESCE(cur.name_short, def.name_short, \'\') AS name_short, '
            . 'COALESCE(cur.description, def.description, \'\') AS description, '
            . 'COALESCE(cur.description_stort, def.description_stort, \'\') AS description_stort, '
            . 'COALESCE(cur.meta_title, def.meta_title, \'\') AS meta_title, '
            . 'COALESCE(cur.meta_description, def.meta_description, \'\') AS meta_description '
            . 'FROM ' . Tables::CIPHERS . ' c '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' cur ON cur.app_id = c.id AND cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_TRANSLATIONS . ' def ON def.app_id = c.id AND def.language = ? '
            . 'WHERE c.id = ? LIMIT 1',
            [$language, $defaultLanguage, $cipherId]
        );

        return [
            'id' => $cipherId,
            'data' => [
                'name' => (string) ($row['name'] ?? ''),
                'name_short' => (string) ($row['name_short'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'description_stort' => (string) ($row['description_stort'] ?? ''),
                'meta_title' => (string) ($row['meta_title'] ?? ''),
                'meta_description' => (string) ($row['meta_description'] ?? ''),
            ],
        ];
    }

    /**
     * Возвращает список информационных блоков с переводами.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchBlocks(int $cipherId, string $language, string $defaultLanguage): array
    {
        $rows = $this->db->fetchAll(
            'SELECT b.id, b.sort_order, b.published, '
            . 'COALESCE(cur.title, def.title, \'\') AS title, '
            . 'COALESCE(cur.text, def.text, \'\') AS text '
            . 'FROM ' . Tables::CIPHERS_BLOCKS . ' b '
            . 'LEFT JOIN ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' cur ON cur.block_id = b.id AND cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' def ON def.block_id = b.id AND def.language = ? '
            . 'WHERE b.app_id = ? ORDER BY b.sort_order ASC, b.id ASC',
            [$language, $defaultLanguage, $cipherId]
        );

        return array_map(static fn (array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'published' => ((int) ($row['published'] ?? 0)) === 1,
            'data' => [
                'title' => (string) ($row['title'] ?? ''),
                'text' => (string) ($row['text'] ?? ''),
            ],
        ], $rows);
    }

    /**
     * Возвращает список FAQ с переводами.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchFaq(int $cipherId, string $language, string $defaultLanguage): array
    {
        $rows = $this->db->fetchAll(
            'SELECT f.id, f.sort_order, f.published, '
            . 'COALESCE(cur.question, def.question, \'\') AS question, '
            . 'COALESCE(cur.answer, def.answer, \'\') AS answer '
            . 'FROM ' . Tables::CIPHERS_FAQ . ' f '
            . 'LEFT JOIN ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' cur ON cur.faq_id = f.id AND cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' def ON def.faq_id = f.id AND def.language = ? '
            . 'WHERE f.app_id = ? ORDER BY f.sort_order ASC, f.id ASC',
            [$language, $defaultLanguage, $cipherId]
        );

        return array_map(static fn (array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'published' => ((int) ($row['published'] ?? 0)) === 1,
            'data' => [
                'question' => (string) ($row['question'] ?? ''),
                'answer' => (string) ($row['answer'] ?? ''),
            ],
        ], $rows);
    }

    /**
     * Возвращает список примеров с переводами.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchExamples(int $cipherId, string $language, string $defaultLanguage): array
    {
        $rows = $this->db->fetchAll(
            'SELECT e.id, e.sort_order, e.published, '
            . 'COALESCE(cur.title, def.title, \'\') AS title, '
            . 'COALESCE(cur.`key`, def.`key`, \'\') AS `key`, '
            . 'COALESCE(cur.input, def.input, \'\') AS input, '
            . 'COALESCE(cur.output, def.output, \'\') AS output, '
            . 'COALESCE(cur.description, def.description, \'\') AS description '
            . 'FROM ' . Tables::CIPHERS_EXAMPLES . ' e '
            . 'LEFT JOIN ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' cur ON cur.example_id = e.id AND cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' def ON def.example_id = e.id AND def.language = ? '
            . 'WHERE e.app_id = ? ORDER BY e.sort_order ASC, e.id ASC',
            [$language, $defaultLanguage, $cipherId]
        );

        return array_map(static fn (array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'published' => ((int) ($row['published'] ?? 0)) === 1,
            'data' => [
                'title' => (string) ($row['title'] ?? ''),
                'key' => (string) ($row['key'] ?? ''),
                'input' => (string) ($row['input'] ?? ''),
                'output' => (string) ($row['output'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
            ],
        ], $rows);
    }

    /**
     * Возвращает список тегов с переводами.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchTags(int $cipherId, string $language, string $defaultLanguage): array
    {
        $rows = $this->db->fetchAll(
            'SELECT t.id, t.sort_order, t.published, '
            . 'COALESCE(cur.tag, def.tag, \'\') AS tag '
            . 'FROM ' . Tables::CIPHERS_TAGS . ' t '
            . 'LEFT JOIN ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' cur ON cur.tag_id = t.id AND cur.language = ? '
            . 'LEFT JOIN ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' def ON def.tag_id = t.id AND def.language = ? '
            . 'WHERE t.app_id = ? ORDER BY t.sort_order ASC, t.id ASC',
            [$language, $defaultLanguage, $cipherId]
        );

        return array_map(static fn (array $row): array => [
            'id' => (int) ($row['id'] ?? 0),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'published' => ((int) ($row['published'] ?? 0)) === 1,
            'data' => [
                'tag' => (string) ($row['tag'] ?? ''),
            ],
        ], $rows);
    }
}
