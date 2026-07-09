<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Переносит флагманский сервис «Cipher Solver» на верхний уровень.
 *
 * Удаляет каталожную запись инструмента из категории «Анализ текста»,
 * создаёт самостоятельную категорию `cipher-solver` (контент заливается
 * командой `cipher:category:content:import`) и регистрирует 301-редиректы
 * со старого URL инструмента на новый URL категории для всех локалей.
 */
class PromoteCipherSolverToCategory extends Migration
{
    /**
     * Выполняет перенос: удаление инструмента, создание категории, сид редиректов.
     */
    public function up(): void
    {
        $this->removeLegacyTool();
        $this->createCategory();
        $this->seedRedirects();
    }

    /**
     * Откатывает перенос: удаляет редиректы и категорию (переводы уходят каскадно по FK).
     */
    public function down(): void
    {
        foreach ($this->redirectPairs() as [$from]) {
            $this->db->execute('DELETE FROM ' . Tables::REDIRECTS . ' WHERE from_path = ?', [$from]);
        }

        $this->db->execute('DELETE FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ?', ['cipher-solver']);
    }

    /**
     * Удаляет каталожную запись инструмента cipher-solver и весь связанный контент.
     */
    private function removeLegacyTool(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['cipher-solver']
        );

        if ($cipher === false) {
            return;
        }

        $id = (int) $cipher['id'];

        $this->deleteChildTranslations(Tables::CIPHERS_BLOCKS, Tables::CIPHERS_BLOCKS_TRANSLATIONS, 'block_id', $id);
        $this->deleteChildTranslations(Tables::CIPHERS_EXAMPLES, Tables::CIPHERS_EXAMPLES_TRANSLATIONS, 'example_id', $id);
        $this->deleteChildTranslations(Tables::CIPHERS_FAQ, Tables::CIPHERS_FAQ_TRANSLATIONS, 'faq_id', $id);
        $this->deleteChildTranslations(Tables::CIPHERS_TAGS, Tables::CIPHERS_TAGS_TRANSLATIONS, 'tag_id', $id);

        foreach ([
            Tables::CIPHERS_BLOCKS,
            Tables::CIPHERS_EXAMPLES,
            Tables::CIPHERS_FAQ,
            Tables::CIPHERS_TAGS,
            Tables::CIPHERS_TRANSLATIONS,
        ] as $table) {
            $this->db->execute('DELETE FROM ' . $table . ' WHERE app_id = ?', [$id]);
        }

        $this->db->execute('DELETE FROM ' . Tables::CIPHERS . ' WHERE id = ?', [$id]);
    }

    /**
     * Удаляет переводы дочерней сущности инструмента по её внешнему ключу.
     */
    private function deleteChildTranslations(string $parent, string $translations, string $fk, int $appId): void
    {
        $this->db->execute(
            'DELETE FROM ' . $translations . ' WHERE ' . $fk . ' IN (SELECT id FROM ' . $parent . ' WHERE app_id = ?)',
            [$appId]
        );
    }

    /**
     * Создаёт самостоятельную категорию cipher-solver, если её ещё нет.
     */
    private function createCategory(): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['cipher-solver']
        );

        if ($existing !== false) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHER_CATEGORIES
            . ' (alias, sort_order, published, category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            ['cipher-solver', 5, 1, 'encoding', $now, $now]
        );
    }

    /**
     * Регистрирует 301-редиректы со старого URL инструмента на URL категории по всем локалям.
     */
    private function seedRedirects(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($this->redirectPairs() as [$from, $to]) {
            $exists = $this->db->fetch(
                'SELECT id FROM ' . Tables::REDIRECTS . ' WHERE from_path = ? LIMIT 1',
                [$from]
            );

            if ($exists !== false) {
                continue;
            }

            $this->db->insert(
                'INSERT INTO ' . Tables::REDIRECTS
                . ' (from_path, to_path, status_code, is_active, hit_count, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$from, $to, 301, 1, 0, $now, $now]
            );
        }
    }

    /**
     * Возвращает пары «старый путь → новый путь» для каждой локали.
     *
     * Для локали по умолчанию — без префикса, для остальных — с префиксом `/{locale}`.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function redirectPairs(): array
    {
        $locales = (array) config('locale.locales', ['en']);
        $default = (string) config('locale.locale', 'en');

        $pairs = [];

        foreach ($locales as $locale) {
            $prefix = ((string) $locale === $default) ? '' : '/' . $locale;
            $pairs[] = [
                $prefix . '/text-analysis/cipher-solver',
                $prefix . '/cipher-solver',
            ];
        }

        return $pairs;
    }
}
