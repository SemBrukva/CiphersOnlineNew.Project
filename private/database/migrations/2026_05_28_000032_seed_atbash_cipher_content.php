<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Заполняет контентные сущности для шифра Атбаш: блоки, примеры, FAQ и теги.
 */
class SeedAtbashCipherContent extends Migration
{
    /**
     * Добавляет или обновляет контент для страницы шифра Атбаш.
     */
    public function up(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['atbash']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $now = date('Y-m-d H:i:s');

        $block = $this->upsertBlock($cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How Atbash cipher works', 'Atbash mirrors each letter to its opposite position in the selected alphabet. It is symmetric: the same transformation is used for encryption and decryption.', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает шифр Атбаш', 'Атбаш отражает каждую букву в противоположную позицию выбранного алфавита. Шифр симметричный: одно и то же преобразование используется и для шифрования, и для расшифровки.', $now);

        $example1 = $this->upsertExample($cipherId, 10, $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encrypt HELLO WORLD', 'HELLO WORLD', 'SVOOL DLIOW', 'Alphabet: English. Atbash replaces letters with mirrored positions.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Шифрование HELLO WORLD', 'HELLO WORLD', 'SVOOL DLIOW', 'Алфавит: English. Атбаш заменяет буквы на зеркальные позиции.', $now);

        $example2 = $this->upsertExample($cipherId, 20, $now);
        $this->upsertExampleTranslation($example2, 'en', 'Decrypt SVOOL DLIOW', 'SVOOL DLIOW', 'HELLO WORLD', 'Alphabet: English. Applying Atbash again restores original text.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Расшифровка SVOOL DLIOW', 'SVOOL DLIOW', 'HELLO WORLD', 'Алфавит: English. Повторное применение Атбаша возвращает исходный текст.', $now);

        $faq1 = $this->upsertFaq($cipherId, 10, $now);
        $this->upsertFaqTranslation($faq1, 'en', 'Is Atbash secure for modern use?', 'No. Atbash is a classical monoalphabetic substitution and is easy to break with frequency analysis.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Подходит ли Атбаш для современной защиты?', 'Нет. Атбаш — классическая моноалфавитная подстановка, которая легко вскрывается частотным анализом.', $now);

        $faq2 = $this->upsertFaq($cipherId, 20, $now);
        $this->upsertFaqTranslation($faq2, 'en', 'Why encrypt and decrypt are identical?', 'Because Atbash is involutive: mirrored mapping applied twice returns each letter to its original position.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Почему шифрование и расшифровка одинаковые?', 'Потому что Атбаш инволютивен: зеркальное отображение, применённое дважды, возвращает буквы в исходные позиции.', $now);

        $faq3 = $this->upsertFaq($cipherId, 30, $now);
        $this->upsertFaqTranslation($faq3, 'en', 'Can I work with non-Latin alphabets?', 'Yes. Choose the appropriate alphabet in settings and the tool will mirror letters within it.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Можно ли использовать не латиницу?', 'Да. Выберите нужный алфавит в настройках, и инструмент выполнит зеркальную замену внутри него.', $now);

        $tag1 = $this->upsertTag($cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Mirror substitution', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Зеркальная подстановка', $now);

        $tag2 = $this->upsertTag($cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Symmetric cipher', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Симметричный шифр', $now);

        $tag3 = $this->upsertTag($cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Classical cryptography', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Классическая криптография', $now);

        $tag4 = $this->upsertTag($cipherId, 40, $now);
        $this->upsertTagTranslation($tag4, 'en', 'Monoalphabetic', $now);
        $this->upsertTagTranslation($tag4, 'ru', 'Моноалфавитный', $now);
    }

    /**
     * Удаляет контент шифра Атбаш.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['atbash']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];

        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ?', [$cipherId]);
    }

    /**
     * Создаёт или обновляет блок контента по сортировке.
     */
    private function upsertBlock(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_BLOCKS . ' SET published = 1, updated_at = ? WHERE id = ?',
                [$now, $id]
            );
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод блока.
     */
    private function upsertBlockTranslation(int $blockId, string $language, string $title, string $text, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ? LIMIT 1',
            [$blockId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' SET title = ?, text = ?, updated_at = ? WHERE id = ?',
                [$title, $text, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' (block_id, language, title, text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$blockId, $language, $title, $text, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет пример по сортировке.
     */
    private function upsertExample(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET published = 1, updated_at = ? WHERE id = ?',
                [$now, $id]
            );
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(
        int $exampleId,
        string $language,
        string $title,
        string $input,
        string $output,
        string $description,
        string $now
    ): void {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' SET title = ?, input = ?, output = ?, description = ?, updated_at = ? WHERE id = ?',
                [$title, $input, $output, $description, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' (example_id, language, title, input, output, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$exampleId, $language, $title, $input, $output, $description, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет FAQ по сортировке.
     */
    private function upsertFaq(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_FAQ . ' SET published = 1, show_in_category = 0, updated_at = ? WHERE id = ?',
                [$now, $id]
            );
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_FAQ . ' (app_id, sort_order, show_in_category, published, created_at, updated_at) VALUES (?, ?, 0, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод FAQ.
     */
    private function upsertFaqTranslation(int $faqId, string $language, string $question, string $answer, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' WHERE faq_id = ? AND language = ? LIMIT 1',
            [$faqId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' SET question = ?, answer = ?, updated_at = ? WHERE id = ?',
                [$question, $answer, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' (faq_id, language, question, answer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$faqId, $language, $question, $answer, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет тег по сортировке.
     */
    private function upsertTag(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_TAGS . ' SET published = 1, updated_at = ? WHERE id = ?',
                [$now, $id]
            );
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_TAGS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод тега.
     */
    private function upsertTagTranslation(int $tagId, string $language, string $tag, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' WHERE tag_id = ? AND language = ? LIMIT 1',
            [$tagId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' SET tag = ?, updated_at = ? WHERE id = ?',
                [$tag, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' (tag_id, language, tag, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$tagId, $language, $tag, $now, $now]
        );
    }
}
