<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Заполняет контентные сущности для шифра Бэкона: блоки, примеры, FAQ и теги.
 */
class SeedBaconCipherContent extends Migration
{
    /**
     * Добавляет или обновляет контент для страницы шифра Бэкона.
     */
    public function up(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['bacon']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $now = date('Y-m-d H:i:s');

        $block = $this->upsertBlock($cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How Bacon cipher works', 'Bacon cipher maps each alphabet letter to a 5-symbol pattern built from A and B. Encryption converts letters to A/B groups, decryption restores letters from each 5-character group.', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает шифр Бэкона', 'Шифр Бэкона сопоставляет каждой букве алфавита 5-символьный шаблон из A и B. При шифровании буквы преобразуются в группы A/B, при расшифровке группы по 5 символов обратно переводятся в буквы.', $now);

        $example1 = $this->upsertExample($cipherId, 10, $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encrypt HELLO', 'HELLO', 'AABBB AABAA ABABB ABABB ABBBA', 'Alphabet: English. Encrypt mode converts each letter into five A/B symbols.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Шифрование HELLO', 'HELLO', 'AABBB AABAA ABABB ABABB ABBBA', 'Алфавит: English. Режим шифрования переводит каждую букву в пять символов A/B.', $now);

        $example2 = $this->upsertExample($cipherId, 20, $now);
        $this->upsertExampleTranslation($example2, 'en', 'Decrypt A/B groups', 'AABBB AABAA ABABB ABABB ABBBA', 'hello', 'Alphabet: English. Decrypt mode reads only A/B groups by chunks of five.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Расшифровка групп A/B', 'AABBB AABAA ABABB ABABB ABBBA', 'hello', 'Алфавит: English. Режим расшифровки читает только группы A/B блоками по пять.', $now);

        $faq1 = $this->upsertFaq($cipherId, 10, $now);
        $this->upsertFaqTranslation($faq1, 'en', 'What happens with spaces and punctuation?', 'Spaces are preserved as separators where possible, while unsupported symbols are ignored in Bacon conversion.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Что происходит с пробелами и знаками?', 'Пробелы по возможности сохраняются как разделители, а неподдерживаемые символы при преобразовании Бэкона игнорируются.', $now);

        $faq2 = $this->upsertFaq($cipherId, 20, $now);
        $this->upsertFaqTranslation($faq2, 'en', 'Why are groups exactly 5 characters?', 'The classical Bacon alphabet uses fixed-length 5-bit patterns, represented here as A/B groups.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Почему группы строго по 5 символов?', 'Классический алфавит Бэкона использует фиксированные 5-битные шаблоны, здесь они представлены как группы A/B.', $now);

        $faq3 = $this->upsertFaq($cipherId, 30, $now);
        $this->upsertFaqTranslation($faq3, 'en', 'Can I choose another alphabet?', 'Yes. The tool supports multiple alphabets and applies Bacon mapping to the selected one.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Можно выбрать другой алфавит?', 'Да. Инструмент поддерживает несколько алфавитов и применяет схему Бэкона к выбранному алфавиту.', $now);

        $tag1 = $this->upsertTag($cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'A/B encoding', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Кодирование A/B', $now);

        $tag2 = $this->upsertTag($cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', '5-bit groups', $now);
        $this->upsertTagTranslation($tag2, 'ru', '5-битные группы', $now);

        $tag3 = $this->upsertTag($cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Classical cryptography', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Классическая криптография', $now);

        $tag4 = $this->upsertTag($cipherId, 40, $now);
        $this->upsertTagTranslation($tag4, 'en', 'Text steganography basics', $now);
        $this->upsertTagTranslation($tag4, 'ru', 'Основы текстовой стеганографии', $now);
    }

    /**
     * Удаляет контент шифра Бэкона.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['bacon']
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
