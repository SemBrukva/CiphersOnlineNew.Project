<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр столбцовой перестановки в категорию классических шифров.
 */
class SeedColumnarTranspositionCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр столбцовой перестановки и его контент.
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
     * Удаляет шифр столбцовой перестановки и связанные с ним сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['columnar-transposition']
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
            [$categoryId, 'columnar-transposition']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'columnar-transposition', 'api', 90, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 90, 1, $now, $cipherId]
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
            'title' => 'How the Columnar Transposition cipher works',
            'text' => '<p>The Columnar Transposition cipher writes the message into rows under a keyword. The keyword defines the order in which columns are read: columns are sorted alphabetically by the keyword characters, with repeated characters kept in their original order.</p><p>This tool does not add padding characters. Short final rows stay short, and decryption calculates the original column lengths from the ciphertext length and key.</p>',
        ], $now);
        $this->upsertTranslation(Tables::CIPHERS_BLOCKS_TRANSLATIONS, 'block_id', $block, 'ru', [
            'title' => 'Как работает шифр столбцовой перестановки',
            'text' => '<p>Шифр столбцовой перестановки записывает сообщение строками под ключевым словом. Ключ задаёт порядок чтения столбцов: столбцы сортируются по символам ключа в алфавитном порядке, а повторяющиеся символы сохраняют исходный порядок.</p><p>Этот инструмент не добавляет символы padding. Неполная последняя строка остаётся неполной, а при расшифровке длины столбцов вычисляются по длине шифротекста и ключу.</p>',
        ], $now);

        $example1 = $this->upsertChild(Tables::CIPHERS_EXAMPLES, 'app_id', $cipherId, 10, ['published' => 1, 'direction' => 'encrypt', 'delimiter' => ''], $now);
        $this->upsertTranslation(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, 'example_id', $example1, 'en', [
            'title' => 'Encrypt with SECRET',
            'input' => 'WEAREDISCOVERED',
            'output' => 'ACDESEEVROWIRDE',
            'description' => 'The keyword SECRET sorts columns into alphabetical key order.',
            'key' => 'SECRET',
            'shift' => 0,
        ], $now);
        $this->upsertTranslation(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, 'example_id', $example1, 'ru', [
            'title' => 'Шифрование с ключом SECRET',
            'input' => 'WEAREDISCOVERED',
            'output' => 'ACDESEEVROWIRDE',
            'description' => 'Ключ SECRET сортирует столбцы по алфавитному порядку символов ключа.',
            'key' => 'SECRET',
            'shift' => 0,
        ], $now);

        $example2 = $this->upsertChild(Tables::CIPHERS_EXAMPLES, 'app_id', $cipherId, 20, ['published' => 1, 'direction' => 'decrypt', 'delimiter' => ''], $now);
        $this->upsertTranslation(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, 'example_id', $example2, 'en', [
            'title' => 'Decrypt with SECRET',
            'input' => 'ACDESEEVROWIRDE',
            'output' => 'WEAREDISCOVERED',
            'description' => 'The same keyword restores the original row-wise message.',
            'key' => 'SECRET',
            'shift' => 0,
        ], $now);
        $this->upsertTranslation(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, 'example_id', $example2, 'ru', [
            'title' => 'Расшифровка с ключом SECRET',
            'input' => 'ACDESEEVROWIRDE',
            'output' => 'WEAREDISCOVERED',
            'description' => 'То же ключевое слово восстанавливает исходное сообщение, записанное строками.',
            'key' => 'SECRET',
            'shift' => 0,
        ], $now);

        $faq1 = $this->upsertChild(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, ['show_in_category' => 0, 'published' => 1], $now);
        $this->upsertTranslation(Tables::CIPHERS_FAQ_TRANSLATIONS, 'faq_id', $faq1, 'en', [
            'question' => 'Is Columnar Transposition a substitution cipher?',
            'answer' => 'No. It does not replace characters. It changes only their order, so it is a transposition cipher.',
        ], $now);
        $this->upsertTranslation(Tables::CIPHERS_FAQ_TRANSLATIONS, 'faq_id', $faq1, 'ru', [
            'question' => 'Столбцовая перестановка — это шифр замены?',
            'answer' => 'Нет. Он не заменяет символы, а меняет только их порядок, поэтому относится к шифрам перестановки.',
        ], $now);

        $faq2 = $this->upsertChild(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, ['show_in_category' => 0, 'published' => 1], $now);
        $this->upsertTranslation(Tables::CIPHERS_FAQ_TRANSLATIONS, 'faq_id', $faq2, 'en', [
            'question' => 'What is the key in Columnar Transposition?',
            'answer' => 'The key is a word or phrase whose sorted characters determine the order in which columns are read.',
        ], $now);
        $this->upsertTranslation(Tables::CIPHERS_FAQ_TRANSLATIONS, 'faq_id', $faq2, 'ru', [
            'question' => 'Что является ключом в столбцовой перестановке?',
            'answer' => 'Ключом является слово или фраза: отсортированные символы ключа задают порядок чтения столбцов.',
        ], $now);

        $tag1 = $this->upsertChild(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, ['published' => 1], $now);
        $this->upsertTranslation(Tables::CIPHERS_TAGS_TRANSLATIONS, 'tag_id', $tag1, 'en', ['tag' => 'Transposition'], $now);
        $this->upsertTranslation(Tables::CIPHERS_TAGS_TRANSLATIONS, 'tag_id', $tag1, 'ru', ['tag' => 'Перестановка'], $now);

        $tag2 = $this->upsertChild(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, ['published' => 1], $now);
        $this->upsertTranslation(Tables::CIPHERS_TAGS_TRANSLATIONS, 'tag_id', $tag2, 'en', ['tag' => 'Keyword cipher'], $now);
        $this->upsertTranslation(Tables::CIPHERS_TAGS_TRANSLATIONS, 'tag_id', $tag2, 'ru', ['tag' => 'Шифр с ключевым словом'], $now);
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
     * Возвращает переводы для шифра столбцовой перестановки.
     *
     * @return array<string, array<string, string>>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Columnar Transposition Cipher',
                'name_short' => 'Columnar Transposition',
                'description' => 'Encrypt and decrypt text with the Columnar Transposition cipher using a keyword that controls column order.',
                'description_stort' => 'Keyword-based columnar transposition cipher.',
                'meta_title' => 'Columnar Transposition Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Columnar Transposition cipher online: enter a keyword and encrypt or decrypt text instantly.',
            ],
            'ru' => [
                'name' => 'Шифр столбцовой перестановки',
                'name_short' => 'Столбцовая перестановка',
                'description' => 'Онлайн-инструмент для шифрования и расшифровки столбцовой перестановкой с ключевым словом, задающим порядок столбцов.',
                'description_stort' => 'Шифр перестановки столбцов с ключевым словом.',
                'meta_title' => 'Шифр столбцовой перестановки Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр столбцовой перестановки онлайн: введите ключевое слово и мгновенно шифруйте или расшифровывайте текст.',
            ],
            'de' => [
                'name' => 'Spaltentranspositions-Chiffre',
                'name_short' => 'Spaltentransposition',
                'description' => 'Text mit einer Spaltentransposition und einem Schlüsselwort für die Spaltenreihenfolge ver- und entschlüsseln.',
                'description_stort' => 'Schlüsselwortbasierte Spaltentransposition.',
                'meta_title' => 'Spaltentranspositions-Chiffre Online | Ciphers Online',
                'meta_description' => 'Spaltentransposition online nutzen: Schlüsselwort eingeben und Text sofort ver- oder entschlüsseln.',
            ],
            'es' => [
                'name' => 'Cifrado de transposición columnar',
                'name_short' => 'Transposición columnar',
                'description' => 'Cifra y descifra texto con transposición columnar usando una palabra clave que controla el orden de columnas.',
                'description_stort' => 'Transposición columnar basada en palabra clave.',
                'meta_title' => 'Cifrado de transposición columnar online | Ciphers Online',
                'meta_description' => 'Usa la transposición columnar online: introduce una palabra clave y cifra o descifra texto al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre de transposition par colonnes',
                'name_short' => 'Transposition par colonnes',
                'description' => 'Chiffrez et déchiffrez du texte avec une transposition par colonnes et un mot-clé qui contrôle l’ordre des colonnes.',
                'description_stort' => 'Transposition par colonnes avec mot-clé.',
                'meta_title' => 'Chiffre de transposition par colonnes en ligne | Ciphers Online',
                'meta_description' => 'Utilisez la transposition par colonnes en ligne : saisissez un mot-clé et chiffrez ou déchiffrez instantanément.',
            ],
            'it' => [
                'name' => 'Cifrario a trasposizione colonnare',
                'name_short' => 'Trasposizione colonnare',
                'description' => 'Cifra e decifra testo con la trasposizione colonnare usando una parola chiave che controlla l’ordine delle colonne.',
                'description_stort' => 'Trasposizione colonnare basata su parola chiave.',
                'meta_title' => 'Cifrario a trasposizione colonnare online | Ciphers Online',
                'meta_description' => 'Usa la trasposizione colonnare online: inserisci una parola chiave e cifra o decifra testo subito.',
            ],
            'pt' => [
                'name' => 'Cifra de transposição colunar',
                'name_short' => 'Transposição colunar',
                'description' => 'Criptografe e descriptografe texto com transposição colunar usando uma palavra-chave que controla a ordem das colunas.',
                'description_stort' => 'Transposição colunar baseada em palavra-chave.',
                'meta_title' => 'Cifra de transposição colunar online | Ciphers Online',
                'meta_description' => 'Use a transposição colunar online: digite uma palavra-chave e cifre ou decifre texto instantaneamente.',
            ],
            'tr' => [
                'name' => 'Sütunlu yer değiştirme şifresi',
                'name_short' => 'Sütunlu yer değiştirme',
                'description' => 'Sütun sırasını belirleyen bir anahtar sözcükle sütunlu yer değiştirme şifresi kullanarak metni şifreleyin ve çözün.',
                'description_stort' => 'Anahtar sözcüklü sütunlu yer değiştirme.',
                'meta_title' => 'Sütunlu yer değiştirme şifresi online | Ciphers Online',
                'meta_description' => 'Sütunlu yer değiştirme aracını online kullanın: anahtar sözcük girin ve metni anında şifreleyin veya çözün.',
            ],
        ];
    }
}
