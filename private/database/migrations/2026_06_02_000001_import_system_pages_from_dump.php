<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Импортирует локализованные системные страницы из SQL-дампа.
 */
class ImportSystemPagesFromDump extends Migration
{
    /** @var string Путь к SQL-дампу внутри репозитория. */
    private const string DUMP_PATH = DATABASE_PATH . '/dumps/system_pages.sql';

    /**
     * Загружает системные страницы с обновлением уже существующих записей.
     */
    public function up(): void
    {
        $pages = $this->loadPages();
        $driver = (string) config('database.default', 'sqlite');

        $this->deleteLegacyPlaceholder();

        $sql = $driver === 'sqlite'
            ? 'INSERT INTO ' . Tables::SYSTEM_PAGES . ' (language, alias, name, text, published)
                VALUES (?, ?, ?, ?, ?)
                ON CONFLICT(language, alias) DO UPDATE SET
                    name = excluded.name,
                    text = excluded.text,
                    published = excluded.published'
            : 'INSERT INTO ' . Tables::SYSTEM_PAGES . ' (language, alias, name, text, published)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    text = VALUES(text),
                    published = VALUES(published)';

        $this->db->transaction(function () use ($pages, $sql): void {
            foreach ($pages as $page) {
                $this->db->execute($sql, $page);
            }
        });
    }

    /**
     * Удаляет страницы, импортированные из дампа.
     */
    public function down(): void
    {
        $this->db->transaction(function (): void {
            foreach ($this->loadPages() as [$language, $alias]) {
                $this->db->execute(
                    'DELETE FROM ' . Tables::SYSTEM_PAGES . ' WHERE language = ? AND alias = ?',
                    [$language, $alias]
                );
            }
        });
    }

    /**
     * Извлекает записи системных страниц из SQL-дампа.
     *
     * @return array<int, array{0:string, 1:string, 2:string, 3:string, 4:int}>
     */
    private function loadPages(): array
    {
        if (!is_file(self::DUMP_PATH)) {
            throw new RuntimeException('Не найден SQL-дамп: ' . self::DUMP_PATH);
        }

        $dump = (string) file_get_contents(self::DUMP_PATH);
        $string = "'((?:\\\\.|[^'])*)'";
        $pattern = '/INSERT INTO `system_pages` .*? VALUES \(\d+, '
            . $string . ', ' . $string . ', ' . $string . ', ' . $string . ', ([01])\);/s';

        if (!preg_match_all($pattern, $dump, $matches, PREG_SET_ORDER)) {
            throw new RuntimeException('SQL-дамп не содержит записей system_pages: ' . self::DUMP_PATH);
        }

        return array_map(
            static fn (array $match): array => [
                stripcslashes($match[1]),
                stripcslashes($match[2]),
                stripcslashes($match[3]),
                stripcslashes($match[4]),
                (int) $match[5],
            ],
            $matches
        );
    }

    /**
     * Удаляет старую тестовую страницу, которую создавал bin/setup.php.
     */
    private function deleteLegacyPlaceholder(): void
    {
        $this->db->execute(
            'DELETE FROM ' . Tables::SYSTEM_PAGES . ' WHERE language = ? AND alias = ? AND name = ? AND text = ?',
            ['en', 'privacy', 'Privacy Policy', '<p>This is the privacy policy page.</p>']
        );
    }
}
