<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр «Танцующие человечки» в категорию codes-and-alphabets.
 */
class SeedDancingMenCipher extends Migration
{
    /**
     * Создаёт или обновляет запись инструмента, переводы и базовый контент.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['codes-and-alphabets']
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
     * Удаляет запись инструмента и связанные сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['dancing-men']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS . ' WHERE id = ?', [$cipherId]);
    }

    /**
     * Создаёт или обновляет запись инструмента.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'dancing-men']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'dancing-men', 'client', 130, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['client', 130, 1, $now, $cipherId]
        );

        return $cipherId;
    }

    /**
     * Создаёт или обновляет перевод инструмента.
     *
     * @param array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string} $translation
     */
    private function upsertCipherTranslation(int $cipherId, string $language, array $translation, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ? AND language = ? LIMIT 1',
            [$cipherId, $language]
        );

        $values = [
            $translation['name'],
            $translation['name_short'],
            $translation['description'],
            $translation['description_stort'],
            $translation['meta_title'],
            $translation['meta_description'],
        ];

        if ($existing !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_TRANSLATIONS
                . ' SET name = ?, name_short = ?, description = ?, description_stort = ?, meta_title = ?, meta_description = ?, updated_at = ? WHERE id = ?',
                [...$values, $now, (int) $existing['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_TRANSLATIONS
            . ' (app_id, language, name, name_short, description, description_stort, meta_title, meta_description, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$cipherId, $language, ...$values, $now, $now]
        );
    }

    /**
     * Заполняет блоки, примеры, FAQ и теги.
     */
    private function seedContent(int $cipherId, string $now): void
    {
        $block = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How the Dancing Men Cipher works', '<p>The Dancing Men cipher was invented by Arthur Conan Doyle for his Sherlock Holmes story "The Adventure of the Dancing Men" (1903). Each letter of the alphabet is represented by a stick figure in a unique pose.</p><p>The pose is determined by the positions of the arms (down, horizontal, or up) and legs (together, left out, right out, or both out). This gives 3 × 3 × 4 = 36 possible combinations — enough to cover both the 26-letter English alphabet and the 33-letter Russian alphabet.</p><p>In the original story, some figures hold a flag to indicate the end of a word. This tool uses spaces for word separation instead.</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает шифр «Пляшущие человечки»', '<p>Шифр «Пляшущие человечки» придумал Артур Конан Дойль для рассказа о Шерлоке Холмсе «Пляшущие человечки» (1903). Каждая буква алфавита изображается фигуркой человечка в уникальной позе.</p><p>Поза определяется положением рук (вниз, горизонтально или вверх) и ног (вместе, левая в сторону, правая в сторону или обе в стороны). Это даёт 3 × 3 × 4 = 36 возможных комбинаций — достаточно для 26-буквенного английского и 33-буквенного русского алфавитов.</p><p>В оригинальном рассказе некоторые фигурки держат флажок, обозначающий конец слова. В этом инструменте слова разделяются пробелами.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encode SHERLOCK HOLMES', 'SHERLOCK HOLMES', '', '', 'Each letter becomes a unique stick figure pose. Spaces separate words.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Зашифровать SHERLOCK HOLMES', 'SHERLOCK HOLMES', '', '', 'Каждая буква превращается в уникальную позу человечка. Пробелы разделяют слова.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Encode HELLO WORLD', 'HELLO WORLD', '', '', 'Try switching to Russian alphabet to encode Cyrillic text.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Зашифровать ПРИВЕТ МИР', 'ПРИВЕТ МИР', '', '', 'Русский алфавит поддерживает все 33 буквы кириллицы.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'Is this the exact cipher from the Sherlock Holmes story?', 'The original cipher from "The Adventure of the Dancing Men" (1903) uses a specific set of 26 hand-drawn figures for the English alphabet. This tool uses a systematic encoding scheme based on arm and leg positions, which covers both English (26 letters) and Russian (33 letters). The figures look similar but the exact pose-to-letter mapping is adapted for full alphabet coverage.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Это точный шифр из рассказа о Шерлоке Холмсе?', 'Оригинальный шифр из рассказа «Пляшущие человечки» (1903) использует набор из 26 нарисованных от руки фигурок для английского алфавита. Этот инструмент применяет систематическую схему кодирования на основе положений рук и ног, охватывающую английский (26 букв) и русский (33 буквы) алфавиты. Фигурки похожи, но соответствие поза—буква адаптировано для полного покрытия алфавита.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'How do I decode dancing men figures back to text?', 'Decoding requires recognising each figure\'s arm and leg positions. Arms can point down, horizontally, or up; legs can be together, left out, right out, or both out. Each unique combination maps to one letter. Use the letter chart below the tool as a reference when decoding manually.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Как декодировать фигурки обратно в текст?', 'Для декодирования нужно распознать положения рук и ног каждой фигурки. Руки могут быть опущены, горизонтальны или подняты; ноги — вместе, левая в сторону, правая в сторону или обе в стороны. Каждая уникальная комбинация соответствует одной букве. Используйте таблицу букв под инструментом в качестве справочника при ручном декодировании.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Visual cipher', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Визуальный шифр', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Sherlock Holmes', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Шерлок Холмс', $now);
    }

    /**
     * Создаёт или обновляет родительскую запись контентной секции.
     *
     * @param array<string, int> $extra
     */
    private function upsertParent(string $table, string $foreignKey, int $cipherId, int $sortOrder, string $now, array $extra = []): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $assignments = ['published = 1', 'updated_at = ?'];
            $values = [$now];
            foreach ($extra as $field => $value) {
                $assignments[] = $field . ' = ?';
                $values[] = $value;
            }
            $values[] = (int) $row['id'];
            $this->db->execute('UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = ?', $values);

            return (int) $row['id'];
        }

        $columns = [$foreignKey, 'sort_order', 'published', 'created_at', 'updated_at', ...array_keys($extra)];
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        return (int) $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            [$cipherId, $sortOrder, 1, $now, $now, ...array_values($extra)]
        );
    }

    /**
     * Создаёт или обновляет пример.
     */
    private function upsertExample(int $cipherId, int $sortOrder, string $direction, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET direction = ?, delimiter = ?, published = 1, updated_at = ? WHERE id = ?',
                [$direction, '', $now, (int) $row['id']]
            );
            return (int) $row['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, direction, delimiter, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $direction, '', $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод блока.
     */
    private function upsertBlockTranslation(int $blockId, string $language, string $title, string $text, string $now): void
    {
        $this->upsertTranslation(Tables::CIPHERS_BLOCKS_TRANSLATIONS, 'block_id', $blockId, $language, ['title' => $title, 'text' => $text], $now);
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(int $exampleId, string $language, string $title, string $input, string $output, string $key, string $description, string $now): void
    {
        $this->upsertTranslation(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, 'example_id', $exampleId, $language, [
            'title' => $title,
            'input' => $input,
            'output' => $output,
            'key' => $key,
            'shift' => 0,
            'description' => $description,
        ], $now);
    }

    /**
     * Создаёт или обновляет перевод FAQ.
     */
    private function upsertFaqTranslation(int $faqId, string $language, string $question, string $answer, string $now): void
    {
        $this->upsertTranslation(Tables::CIPHERS_FAQ_TRANSLATIONS, 'faq_id', $faqId, $language, ['question' => $question, 'answer' => $answer], $now);
    }

    /**
     * Создаёт или обновляет перевод тега.
     */
    private function upsertTagTranslation(int $tagId, string $language, string $tag, string $now): void
    {
        $this->upsertTranslation(Tables::CIPHERS_TAGS_TRANSLATIONS, 'tag_id', $tagId, $language, ['tag' => $tag], $now);
    }

    /**
     * Создаёт или обновляет перевод дочерней сущности.
     *
     * @param array<string, int|string> $data
     */
    private function upsertTranslation(string $table, string $foreignKey, int $parentId, string $language, array $data, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND language = ? LIMIT 1',
            [$parentId, $language]
        );

        if ($existing !== false) {
            $assignments = array_map(static fn (string $field): string => '`' . $field . '` = ?', array_keys($data));
            $this->db->execute(
                'UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ', updated_at = ? WHERE id = ?',
                [...array_values($data), $now, (int) $existing['id']]
            );
            return;
        }

        $columns = array_map(static fn (string $field): string => '`' . $field . '`', [$foreignKey, 'language', ...array_keys($data), 'created_at', 'updated_at']);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            [$parentId, $language, ...array_values($data), $now, $now]
        );
    }

    /**
     * Возвращает переводы инструмента для всех поддерживаемых языков.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'             => 'Dancing Men Cipher',
                'name_short'       => 'Dancing Men',
                'description'      => 'Encode text using stick figure symbols from Arthur Conan Doyle\'s Sherlock Holmes story "The Adventure of the Dancing Men". Supports English and Russian alphabets.',
                'description_stort' => 'Encode text as dancing stick figure symbols from the Sherlock Holmes story.',
                'meta_title'       => 'Dancing Men Cipher Online | Ciphers Online',
                'meta_description' => 'Convert text to dancing men stick figures online. Based on the Sherlock Holmes cipher by Arthur Conan Doyle. Supports English and Russian.',
            ],
            'ru' => [
                'name'             => 'Пляшущие человечки',
                'name_short'       => 'Пляшущие человечки',
                'description'      => 'Зашифруйте текст с помощью фигурок человечков из рассказа Артура Конан Дойля «Пляшущие человечки». Поддерживаются английский и русский алфавиты.',
                'description_stort' => 'Шифрует текст в фигурки пляшущих человечков из рассказа про Шерлока Холмса.',
                'meta_title'       => 'Пляшущие Человечки Онлайн | Ciphers Online',
                'meta_description' => 'Преобразуйте текст в фигурки пляшущих человечков онлайн. Шифр по рассказу Артура Конан Дойля о Шерлоке Холмсе. Поддерживаются английский и русский языки.',
            ],
            'de' => [
                'name'             => 'Tanzende Männchen Chiffre',
                'name_short'       => 'Tanzende Männchen',
                'description'      => 'Text mithilfe von Strichmännchen-Symbolen aus Arthur Conan Doyles Sherlock-Holmes-Geschichte „Die tanzenden Männchen" kodieren. Englisches und russisches Alphabet werden unterstützt.',
                'description_stort' => 'Text als tanzende Strichmännchen aus der Sherlock-Holmes-Geschichte kodieren.',
                'meta_title'       => 'Tanzende Männchen Chiffre Online | Ciphers Online',
                'meta_description' => 'Text online in tanzende Strichmännchen umwandeln. Basiert auf der Sherlock-Holmes-Chiffre von Arthur Conan Doyle. Englisch und Russisch werden unterstützt.',
            ],
            'es' => [
                'name'             => 'Cifrado de los bailarines',
                'name_short'       => 'Bailarines',
                'description'      => 'Codifica texto mediante figuras de palitos del relato de Sherlock Holmes de Arthur Conan Doyle «La aventura de los bailarines». Compatible con los alfabetos inglés y ruso.',
                'description_stort' => 'Codifica texto como figuras de palitos bailarines del relato de Sherlock Holmes.',
                'meta_title'       => 'Cifrado de Bailarines Online | Ciphers Online',
                'meta_description' => 'Convierte texto en figuras de palitos bailarines online. Basado en el cifrado de Sherlock Holmes de Arthur Conan Doyle. Compatible con inglés y ruso.',
            ],
            'fr' => [
                'name'             => 'Chiffre des hommes dansants',
                'name_short'       => 'Hommes dansants',
                'description'      => 'Encodez du texte avec des symboles de personnages bâtons tirés de la nouvelle Sherlock Holmes d\'Arthur Conan Doyle «L\'Aventure des hommes qui dansent». Alphabets anglais et russe pris en charge.',
                'description_stort' => 'Encode du texte en personnages bâtons dansants issus de la nouvelle Sherlock Holmes.',
                'meta_title'       => 'Chiffre des Hommes Dansants en Ligne | Ciphers Online',
                'meta_description' => 'Convertissez du texte en personnages bâtons dansants en ligne. Basé sur le chiffre Sherlock Holmes d\'Arthur Conan Doyle. Anglais et russe pris en charge.',
            ],
            'it' => [
                'name'             => 'Cifrario degli omini danzanti',
                'name_short'       => 'Omini danzanti',
                'description'      => 'Codifica il testo usando figurine di stecchini dal racconto di Sherlock Holmes di Arthur Conan Doyle «L\'Avventura degli omini danzanti». Alfabeti inglese e russo supportati.',
                'description_stort' => 'Codifica il testo come figurine di stecchini danzanti dal racconto di Sherlock Holmes.',
                'meta_title'       => 'Cifrario degli Omini Danzanti Online | Ciphers Online',
                'meta_description' => 'Converti il testo in figurine di stecchini danzanti online. Basato sul cifrario Sherlock Holmes di Arthur Conan Doyle. Supporta inglese e russo.',
            ],
            'pt' => [
                'name'             => 'Cifra dos Homenzinhos Dançantes',
                'name_short'       => 'Homenzinhos Dançantes',
                'description'      => 'Codifique texto usando figuras de palito do conto de Sherlock Holmes de Arthur Conan Doyle «A Aventura dos Homenzinhos Dançantes». Alfabetos inglês e russo suportados.',
                'description_stort' => 'Codifica texto como figuras de palito dançantes do conto de Sherlock Holmes.',
                'meta_title'       => 'Cifra dos Homenzinhos Dançantes Online | Ciphers Online',
                'meta_description' => 'Converta texto em figuras de palito dançantes online. Baseado na cifra Sherlock Holmes de Arthur Conan Doyle. Suporta inglês e russo.',
            ],
            'tr' => [
                'name'             => 'Dans Eden Adamlar Şifresi',
                'name_short'       => 'Dans Eden Adamlar',
                'description'      => 'Arthur Conan Doyle\'ın «Dans Eden Adamlar Macerası» adlı Sherlock Holmes hikâyesindeki çöp adam sembollerini kullanarak metin şifreleyin. İngilizce ve Rusça alfabe desteklenir.',
                'description_stort' => 'Sherlock Holmes hikâyesindeki dans eden çöp adam figürleri olarak metin şifreler.',
                'meta_title'       => 'Dans Eden Adamlar Şifresi Çevrimiçi | Ciphers Online',
                'meta_description' => 'Metni çevrimiçi olarak dans eden çöp adam figürlerine dönüştürün. Arthur Conan Doyle\'ın Sherlock Holmes şifresine dayanmaktadır. İngilizce ve Rusça desteklenir.',
            ],
        ];
    }
}
