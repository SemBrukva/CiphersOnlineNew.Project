<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет Affine Cipher в категорию классических шифров.
 */
class SeedAffineCipher extends Migration
{
    /**
     * Создаёт или обновляет Affine Cipher и базовый контент страницы.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['classical-ciphers']
        );

        if ($category === false) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $cipherId = $this->upsertCipher((int) $category['id'], $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertCipherTranslation($cipherId, $language, $translation, $now);
        }

        $this->seedContent($cipherId, $now);
    }

    /**
     * Удаляет Affine Cipher и связанные с ним сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['affine']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];

        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS . ' WHERE id = ?', [$cipherId]);
    }

    /**
     * Создаёт или обновляет запись шифра.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'affine']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'affine', 'api', 85, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 85, 1, $now, $cipherId]
        );

        return $cipherId;
    }

    /**
     * Создаёт или обновляет перевод шифра.
     *
     * @param array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string} $translation Данные перевода.
     */
    private function upsertCipherTranslation(int $cipherId, string $language, array $translation, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ? AND language = ? LIMIT 1',
            [$cipherId, $language]
        );

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_TRANSLATIONS
                . ' (app_id, language, name, name_short, description, description_stort, meta_title, meta_description, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $cipherId,
                    $language,
                    $translation['name'],
                    $translation['name_short'],
                    $translation['description'],
                    $translation['description_stort'],
                    $translation['meta_title'],
                    $translation['meta_description'],
                    $now,
                    $now,
                ]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_TRANSLATIONS
            . ' SET name = ?, name_short = ?, description = ?, description_stort = ?, meta_title = ?, meta_description = ?, updated_at = ? '
            . 'WHERE id = ?',
            [
                $translation['name'],
                $translation['name_short'],
                $translation['description'],
                $translation['description_stort'],
                $translation['meta_title'],
                $translation['meta_description'],
                $now,
                (int) $existing['id'],
            ]
        );
    }

    /**
     * Заполняет блоки, примеры, FAQ и теги страницы.
     */
    private function seedContent(int $cipherId, string $now): void
    {
        $block = $this->upsertEntity(Tables::CIPHERS_BLOCKS, $cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How the Affine cipher works', '<p>The Affine cipher is a monoalphabetic substitution cipher. Each letter position x is transformed with the formula E(x) = (a * x + b) mod m, where m is the selected alphabet size.</p><p>To decrypt text, the multiplier a must have a modular inverse. That is why a must be coprime with the alphabet size. Spaces, digits, and punctuation are preserved unchanged.</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает Affine Cipher', '<p>Affine Cipher — моноалфавитный шифр замены. Позиция каждой буквы x преобразуется по формуле E(x) = (a * x + b) mod m, где m — размер выбранного алфавита.</p><p>Для расшифровки у множителя a должна существовать обратная величина по модулю. Поэтому a должен быть взаимно простым с размером алфавита. Пробелы, цифры и пунктуация сохраняются без изменений.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encrypt with a=5 and b=8', '5', 8, 'AFFINE CIPHER', 'IHHWVC SWFRCP', 'Classic English Affine example with multiplier 5 and shift 8.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Шифрование с a=5 и b=8', '5', 8, 'AFFINE CIPHER', 'IHHWVC SWFRCP', 'Классический пример Affine для английского алфавита.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'decrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Decrypt with a=5 and b=8', '5', 8, 'IHHWVC SWFRCP', 'AFFINE CIPHER', 'The same numeric keys restore the plaintext.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Расшифровка с a=5 и b=8', '5', 8, 'IHHWVC SWFRCP', 'AFFINE CIPHER', 'Те же числовые ключи восстанавливают исходный текст.', $now);

        $faq = $this->upsertEntity(Tables::CIPHERS_FAQ, $cipherId, 10, $now);
        $this->upsertFaqTranslation($faq, 'en', 'Why must a be coprime with the alphabet size?', 'Decryption needs the modular inverse of a. If a and the alphabet size have a common divisor, that inverse does not exist and several letters can map to the same ciphertext letter.', $now);
        $this->upsertFaqTranslation($faq, 'ru', 'Почему a должен быть взаимно простым с размером алфавита?', 'Для расшифровки нужна обратная величина множителя a по модулю. Если у a и размера алфавита есть общий делитель, обратной величины не существует, и несколько букв могут перейти в одну и ту же букву шифротекста.', $now);

        $tag1 = $this->upsertEntity(Tables::CIPHERS_TAGS, $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Substitution cipher', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Шифр замены', $now);

        $tag2 = $this->upsertEntity(Tables::CIPHERS_TAGS, $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Modular arithmetic', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Модульная арифметика', $now);
    }

    /**
     * Создаёт или обновляет простую контентную сущность.
     */
    private function upsertEntity(string $table, int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . $table . ' SET published = 1, updated_at = ? WHERE id = ?', [$now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . $table . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет пример.
     */
    private function upsertExample(int $cipherId, int $sortOrder, string $direction, string $now): int
    {
        $id = $this->upsertEntity(Tables::CIPHERS_EXAMPLES, $cipherId, $sortOrder, $now);
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET direction = ?, delimiter = ?, updated_at = ? WHERE id = ?',
            [$direction, '', $now, $id]
        );

        return $id;
    }

    /**
     * Создаёт или обновляет перевод блока.
     */
    private function upsertBlockTranslation(int $blockId, string $language, string $title, string $text, string $now): void
    {
        $this->upsertTranslatedRow(
            Tables::CIPHERS_BLOCKS_TRANSLATIONS,
            'block_id',
            $blockId,
            $language,
            ['title' => $title, 'text' => $text],
            $now
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(int $exampleId, string $language, string $title, string $key, int $shift, string $input, string $output, string $description, string $now): void
    {
        $this->upsertTranslatedRow(
            Tables::CIPHERS_EXAMPLES_TRANSLATIONS,
            'example_id',
            $exampleId,
            $language,
            [
                'title' => $title,
                'key' => $key,
                'shift' => $shift,
                'input' => $input,
                'output' => $output,
                'description' => $description,
            ],
            $now
        );
    }

    /**
     * Создаёт или обновляет перевод FAQ.
     */
    private function upsertFaqTranslation(int $faqId, string $language, string $question, string $answer, string $now): void
    {
        $this->upsertTranslatedRow(
            Tables::CIPHERS_FAQ_TRANSLATIONS,
            'faq_id',
            $faqId,
            $language,
            ['question' => $question, 'answer' => $answer],
            $now
        );
    }

    /**
     * Создаёт или обновляет перевод тега.
     */
    private function upsertTagTranslation(int $tagId, string $language, string $tag, string $now): void
    {
        $this->upsertTranslatedRow(
            Tables::CIPHERS_TAGS_TRANSLATIONS,
            'tag_id',
            $tagId,
            $language,
            ['tag' => $tag],
            $now
        );
    }

    /**
     * Создаёт или обновляет строку перевода с произвольными колонками.
     *
     * @param array<string, int|string> $data Данные перевода.
     */
    private function upsertTranslatedRow(string $table, string $foreignKey, int $entityId, string $language, array $data, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND language = ? LIMIT 1',
            [$entityId, $language]
        );

        if ($row !== false) {
            $assignments = implode(', ', array_map(static fn (string $column): string => $column . ' = ?', array_keys($data)));
            $this->db->execute(
                'UPDATE ' . $table . ' SET ' . $assignments . ', updated_at = ? WHERE id = ?',
                array_merge(array_values($data), [$now, (int) $row['id']])
            );
            return;
        }

        $columns = array_merge([$foreignKey, 'language'], array_keys($data), ['created_at', 'updated_at']);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            array_merge([$entityId, $language], array_values($data), [$now, $now])
        );
    }

    /**
     * Возвращает переводы Affine Cipher.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Affine Cipher',
                'name_short' => 'Affine',
                'description' => 'Encrypt and decrypt text with the Affine cipher using multiplier a, shift b, and selectable alphabets.',
                'description_stort' => 'Classical substitution cipher with two numeric keys.',
                'meta_title' => 'Affine Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Affine cipher online: choose alphabet, multiplier a and shift b, then encrypt or decrypt text instantly.',
            ],
            'ru' => [
                'name' => 'Affine Cipher',
                'name_short' => 'Affine',
                'description' => 'Шифруйте и расшифровывайте текст Affine Cipher с множителем a, сдвигом b и выбором алфавита.',
                'description_stort' => 'Классический шифр замены с двумя числовыми ключами.',
                'meta_title' => 'Affine Cipher Онлайн | Ciphers Online',
                'meta_description' => 'Используйте Affine Cipher онлайн: выберите алфавит, множитель a и сдвиг b, затем зашифруйте или расшифруйте текст.',
            ],
        ];
    }
}
