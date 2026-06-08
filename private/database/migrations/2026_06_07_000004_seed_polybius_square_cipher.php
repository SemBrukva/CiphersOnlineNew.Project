<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр квадрата Полибия в категорию классических шифров.
 */
class SeedPolybiusSquareCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр квадрата Полибия и его контент.
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
            $this->upsertTranslation(Tables::CIPHERS_TRANSLATIONS, 'app_id', $cipherId, $language, $translation, $now);
        }

        $this->seedContent($cipherId, $now);
    }

    /**
     * Удаляет шифр квадрата Полибия и связанные с ним сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['polybius-square']
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
            [$categoryId, 'polybius-square']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'polybius-square', 'api', 100, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 100, 1, $now, $cipherId]
        );

        return $cipherId;
    }

    /**
     * Заполняет блоки, примеры, FAQ и теги страницы.
     */
    private function seedContent(int $cipherId, string $now): void
    {
        $block = $this->upsertChild(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, ['published' => 1], $now);
        $this->upsertTranslation(Tables::CIPHERS_BLOCKS_TRANSLATIONS, 'block_id', $block, 'en', [
            'title' => 'How the Polybius Square cipher works',
            'text' => '<p>The Polybius Square cipher places letters in a grid and replaces each letter with its row and column coordinates. In the classical English square, I and J share one cell so the alphabet fits into a 5x5 grid.</p><p>This tool supports multiple alphabets by building a row-column grid from the selected alphabet. You can choose how coordinate pairs are separated.</p>',
        ], $now);
        $this->upsertTranslation(Tables::CIPHERS_BLOCKS_TRANSLATIONS, 'block_id', $block, 'ru', [
            'title' => 'Как работает квадрат Полибия',
            'text' => '<p>Квадрат Полибия размещает буквы в таблице и заменяет каждую букву координатами строки и столбца. В классическом английском квадрате I и J занимают одну ячейку, чтобы алфавит поместился в сетку 5x5.</p><p>Этот инструмент поддерживает несколько алфавитов: таблица строится из выбранного алфавита, а разделитель координат можно настроить.</p>',
        ], $now);

        $example1 = $this->upsertChild(Tables::CIPHERS_EXAMPLES, 'app_id', $cipherId, 10, ['published' => 1, 'direction' => 'encrypt', 'delimiter' => 'space'], $now);
        $this->upsertTranslation(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, 'example_id', $example1, 'en', [
            'title' => 'Encrypt HELLO',
            'input' => 'HELLO',
            'output' => '23 15 31 31 34',
            'description' => 'Alphabet: English, delimiter: space, mode: encrypt.',
            'key' => '',
            'shift' => 0,
        ], $now);
        $this->upsertTranslation(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, 'example_id', $example1, 'ru', [
            'title' => 'Шифрование HELLO',
            'input' => 'HELLO',
            'output' => '23 15 31 31 34',
            'description' => 'Алфавит: English, разделитель: пробел, режим: шифрование.',
            'key' => '',
            'shift' => 0,
        ], $now);

        $example2 = $this->upsertChild(Tables::CIPHERS_EXAMPLES, 'app_id', $cipherId, 20, ['published' => 1, 'direction' => 'decrypt', 'delimiter' => 'space'], $now);
        $this->upsertTranslation(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, 'example_id', $example2, 'en', [
            'title' => 'Decrypt coordinates',
            'input' => '23 15 31 31 34',
            'output' => 'hello',
            'description' => 'Coordinates 23, 15, 31, 31, 34 decode to HELLO in the English square.',
            'key' => '',
            'shift' => 0,
        ], $now);
        $this->upsertTranslation(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, 'example_id', $example2, 'ru', [
            'title' => 'Расшифровка координат',
            'input' => '23 15 31 31 34',
            'output' => 'hello',
            'description' => 'Координаты 23, 15, 31, 31, 34 декодируются как HELLO в английском квадрате.',
            'key' => '',
            'shift' => 0,
        ], $now);

        $faq1 = $this->upsertChild(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, ['show_in_category' => 0, 'published' => 1], $now);
        $this->upsertTranslation(Tables::CIPHERS_FAQ_TRANSLATIONS, 'faq_id', $faq1, 'en', [
            'question' => 'Why do I and J share a coordinate?',
            'answer' => 'The classical English Polybius square uses 25 cells. Combining I and J lets 26 English letters fit into a 5x5 grid.',
        ], $now);
        $this->upsertTranslation(Tables::CIPHERS_FAQ_TRANSLATIONS, 'faq_id', $faq1, 'ru', [
            'question' => 'Почему I и J имеют одну координату?',
            'answer' => 'Классический английский квадрат Полибия содержит 25 ячеек. Объединение I и J позволяет поместить 26 букв английского алфавита в сетку 5x5.',
        ], $now);

        $faq2 = $this->upsertChild(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, ['show_in_category' => 0, 'published' => 1], $now);
        $this->upsertTranslation(Tables::CIPHERS_FAQ_TRANSLATIONS, 'faq_id', $faq2, 'en', [
            'question' => 'Is Polybius Square secure?',
            'answer' => 'No. It is a simple substitution cipher and is best used for learning, puzzles, and historical demonstrations.',
        ], $now);
        $this->upsertTranslation(Tables::CIPHERS_FAQ_TRANSLATIONS, 'faq_id', $faq2, 'ru', [
            'question' => 'Надёжен ли квадрат Полибия?',
            'answer' => 'Нет. Это простая подстановка, подходящая для обучения, головоломок и исторических демонстраций.',
        ], $now);

        $tag1 = $this->upsertChild(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, ['published' => 1], $now);
        $this->upsertTranslation(Tables::CIPHERS_TAGS_TRANSLATIONS, 'tag_id', $tag1, 'en', ['tag' => 'Substitution'], $now);
        $this->upsertTranslation(Tables::CIPHERS_TAGS_TRANSLATIONS, 'tag_id', $tag1, 'ru', ['tag' => 'Подстановка'], $now);

        $tag2 = $this->upsertChild(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, ['published' => 1], $now);
        $this->upsertTranslation(Tables::CIPHERS_TAGS_TRANSLATIONS, 'tag_id', $tag2, 'en', ['tag' => 'Coordinate cipher'], $now);
        $this->upsertTranslation(Tables::CIPHERS_TAGS_TRANSLATIONS, 'tag_id', $tag2, 'ru', ['tag' => 'Координатный шифр'], $now);
    }

    /**
     * Создаёт или обновляет дочернюю сущность по sort_order.
     *
     * @param  array<string, mixed> $data Поля для вставки или обновления.
     * @return int                         Идентификатор сущности.
     */
    private function upsertChild(string $table, string $foreignKey, int $parentId, int $sortOrder, array $data, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND sort_order = ? LIMIT 1',
            [$parentId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->updateRow($table, $id, $data + ['updated_at' => $now]);
            return $id;
        }

        $columns = array_merge([$foreignKey, 'sort_order'], array_keys($data), ['created_at', 'updated_at']);
        $values = array_merge([$parentId, $sortOrder], array_values($data), [$now, $now]);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        return (int) $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            $values
        );
    }

    /**
     * Создаёт или обновляет перевод сущности.
     *
     * @param array<string, mixed> $data Поля перевода.
     */
    private function upsertTranslation(string $table, string $foreignKey, int $parentId, string $language, array $data, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND language = ? LIMIT 1',
            [$parentId, $language]
        );

        if ($row !== false) {
            $this->updateRow($table, (int) $row['id'], $data + ['updated_at' => $now]);
            return;
        }

        $columns = array_merge([$foreignKey, 'language'], array_keys($data), ['created_at', 'updated_at']);
        $values = array_merge([$parentId, $language], array_values($data), [$now, $now]);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            $values
        );
    }

    /**
     * Обновляет строку таблицы по id.
     *
     * @param array<string, mixed> $data Поля для обновления.
     */
    private function updateRow(string $table, int $id, array $data): void
    {
        $assignments = implode(', ', array_map(static fn (string $column): string => $column . ' = ?', array_keys($data)));

        $this->db->execute(
            'UPDATE ' . $table . ' SET ' . $assignments . ' WHERE id = ?',
            array_merge(array_values($data), [$id])
        );
    }

    /**
     * Возвращает переводы для шифра квадрата Полибия.
     *
     * @return array<string, array<string, string>>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Polybius Square Cipher',
                'name_short' => 'Polybius Square',
                'description' => 'Encode letters as row-column coordinates and decode coordinate pairs back into text with the Polybius Square cipher.',
                'description_stort' => 'Coordinate substitution with a letter grid.',
                'meta_title' => 'Polybius Square Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Polybius Square cipher online to convert text into row-column coordinates and decode coordinates back into letters.',
            ],
            'ru' => [
                'name' => 'Шифр квадрат Полибия',
                'name_short' => 'Квадрат Полибия',
                'description' => 'Онлайн-инструмент для шифрования букв координатами строки и столбца и расшифровки координат обратно в текст.',
                'description_stort' => 'Координатная подстановка по буквенной таблице.',
                'meta_title' => 'Шифр квадрат Полибия Онлайн | Ciphers Online',
                'meta_description' => 'Используйте квадрат Полибия онлайн: переводите текст в координаты строк и столбцов и декодируйте координаты обратно в буквы.',
            ],
            'de' => [
                'name' => 'Polybius-Quadrat-Chiffre',
                'name_short' => 'Polybius-Quadrat',
                'description' => 'Text als Zeilen- und Spaltenkoordinaten kodieren und Koordinatenpaare mit dem Polybius-Quadrat zurück in Text dekodieren.',
                'description_stort' => 'Koordinatenersetzung mit Buchstabengitter.',
                'meta_title' => 'Polybius-Quadrat-Chiffre Online | Ciphers Online',
                'meta_description' => 'Nutzen Sie die Polybius-Quadrat-Chiffre online, um Text in Koordinaten umzuwandeln und zurück zu dekodieren.',
            ],
            'es' => [
                'name' => 'Cifrado del cuadrado de Polibio',
                'name_short' => 'Cuadrado de Polibio',
                'description' => 'Codifica letras como coordenadas de fila y columna y decodifica pares de coordenadas con el cuadrado de Polibio.',
                'description_stort' => 'Sustitución por coordenadas en una cuadrícula.',
                'meta_title' => 'Cifrado del cuadrado de Polibio online | Ciphers Online',
                'meta_description' => 'Usa el cuadrado de Polibio online para convertir texto en coordenadas y decodificarlas de nuevo a letras.',
            ],
            'fr' => [
                'name' => 'Chiffre du carré de Polybe',
                'name_short' => 'Carré de Polybe',
                'description' => 'Encodez les lettres en coordonnées ligne-colonne et décodez les paires de coordonnées avec le carré de Polybe.',
                'description_stort' => 'Substitution par coordonnées dans une grille.',
                'meta_title' => 'Chiffre du carré de Polybe en ligne | Ciphers Online',
                'meta_description' => 'Utilisez le carré de Polybe en ligne pour transformer du texte en coordonnées et les décoder en lettres.',
            ],
            'it' => [
                'name' => 'Cifrario del quadrato di Polibio',
                'name_short' => 'Quadrato di Polibio',
                'description' => 'Codifica lettere come coordinate riga-colonna e decodifica coppie di coordinate con il quadrato di Polibio.',
                'description_stort' => 'Sostituzione a coordinate con griglia di lettere.',
                'meta_title' => 'Cifrario del quadrato di Polibio online | Ciphers Online',
                'meta_description' => 'Usa il quadrato di Polibio online per convertire testo in coordinate e decodificarle in lettere.',
            ],
            'pt' => [
                'name' => 'Cifra do quadrado de Políbio',
                'name_short' => 'Quadrado de Políbio',
                'description' => 'Codifique letras como coordenadas de linha e coluna e decodifique pares de coordenadas com o quadrado de Políbio.',
                'description_stort' => 'Substituição por coordenadas em uma grade.',
                'meta_title' => 'Cifra do quadrado de Políbio online | Ciphers Online',
                'meta_description' => 'Use o quadrado de Políbio online para converter texto em coordenadas e decodificá-las de volta para letras.',
            ],
            'tr' => [
                'name' => 'Polybius karesi şifresi',
                'name_short' => 'Polybius karesi',
                'description' => 'Harfleri satır-sütun koordinatları olarak kodlayın ve koordinat çiftlerini Polybius karesiyle metne geri çözün.',
                'description_stort' => 'Harf ızgarasında koordinatlı yerine koyma.',
                'meta_title' => 'Polybius karesi şifresi online | Ciphers Online',
                'meta_description' => 'Polybius karesi aracını online kullanarak metni koordinatlara dönüştürün ve koordinatları harflere geri çözün.',
            ],
        ];
    }
}
