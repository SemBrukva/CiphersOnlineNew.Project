<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Заполняет контентные сущности для шифра Вернама: блоки, примеры, FAQ и теги.
 */
class SeedVernamCipherContent extends Migration
{
    /**
     * Добавляет или обновляет контент для страницы шифра Вернама.
     */
    public function up(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['vernam']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $now = date('Y-m-d H:i:s');

        $block = $this->upsertBlock($cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How Vernam cipher works', 'The Vernam cipher applies byte-wise XOR between text and key. In encryption mode the result is encoded to Base64, and in decryption mode Base64 is decoded first and then XOR is applied with the same key.', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает шифр Вернама', 'Шифр Вернама выполняет побайтовый XOR между текстом и ключом. В режиме шифрования результат кодируется в Base64, а в режиме расшифровки сначала декодируется Base64, затем снова применяется XOR с тем же ключом.', $now);

        $example1 = $this->upsertExample($cipherId, 10, $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encrypt basic text', 'HELLO', 'AwAVBwo=', 'Key: KEY. Encrypt mode applies XOR and outputs Base64.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Шифрование простого текста', 'HELLO', 'AwAVBwo=', 'Ключ: KEY. В режиме шифрования выполняется XOR и возвращается Base64.', $now);

        $example2 = $this->upsertExample($cipherId, 20, $now);
        $this->upsertExampleTranslation($example2, 'en', 'Decrypt Base64 text', 'AwAVBwo=', 'HELLO', 'Key: KEY. Decrypt mode decodes Base64 and applies XOR.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Расшифровка Base64-строки', 'AwAVBwo=', 'HELLO', 'Ключ: KEY. В режиме расшифровки строка декодируется из Base64 и обрабатывается XOR.', $now);

        $faq1 = $this->upsertFaq($cipherId, 10, $now);
        $this->upsertFaqTranslation($faq1, 'en', 'Do I need the same key for decryption?', 'Yes. Vernam decryption requires exactly the same key that was used during encryption.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Нужен ли тот же ключ для расшифровки?', 'Да. Для расшифровки по Вернаму нужен тот же ключ, который использовался при шифровании.', $now);

        $faq2 = $this->upsertFaq($cipherId, 20, $now);
        $this->upsertFaqTranslation($faq2, 'en', 'Why is encrypted output in Base64?', 'XOR result is binary data. Base64 makes it safe to copy, store and transfer as text.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Почему результат шифрования в Base64?', 'После XOR получается бинарный набор байтов. Base64 делает его удобным для копирования, хранения и передачи как текста.', $now);

        $faq3 = $this->upsertFaq($cipherId, 30, $now);
        $this->upsertFaqTranslation($faq3, 'en', 'Can Vernam work with Unicode text?', 'Yes. The tool works byte-wise with UTF-8 data, so it supports multilingual input.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Работает ли Вернам с Unicode-текстом?', 'Да. Инструмент обрабатывает UTF-8 как набор байтов, поэтому поддерживает многоязычный ввод.', $now);

        $tag1 = $this->upsertTag($cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'XOR', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'XOR', $now);

        $tag2 = $this->upsertTag($cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Base64 output', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Вывод в Base64', $now);

        $tag3 = $this->upsertTag($cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Symmetric cipher', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Симметричный шифр', $now);

        $tag4 = $this->upsertTag($cipherId, 40, $now);
        $this->upsertTagTranslation($tag4, 'en', 'Key-based', $now);
        $this->upsertTagTranslation($tag4, 'ru', 'Ключевой метод', $now);
    }

    /**
     * Удаляет контент шифра Вернама.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['vernam']
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
