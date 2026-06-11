<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент «Caesar Brute Force» в категорию «Анализ текста и криптоанализ».
 */
class SeedCaesarBruteForceTool extends Migration
{
    /**
     * Создаёт инструмент, переводы, блоки, примеры и FAQ.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['text-analysis']
        );

        if ($category === false) {
            return;
        }

        $now      = date('Y-m-d H:i:s');
        $cipherId = $this->upsertCipher((int) $category['id'], $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertCipherTranslation($cipherId, $language, $translation, $now);
        }

        $this->seedContent($cipherId, $now);
    }

    /**
     * Удаляет инструмент и связанные сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['caesar-brute-force']
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
     * Создаёт или обновляет запись инструмента.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['caesar-brute-force']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'api', 20, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'caesar-brute-force', 'api', 20, 1, $now, $now]
        );
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
     * Заполняет блоки, примеры и FAQ.
     */
    private function seedContent(int $cipherId, string $now): void
    {
        $block1 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $this->upsertBlockTranslation($block1, 'en', 'How Caesar brute force works', '<p>Caesar brute force is a cryptanalysis technique that tries every possible shift value for the Caesar cipher. Since the Caesar cipher uses a fixed shift of one letter, the total number of possible keys equals the alphabet size (26 for English, 33 for Russian, etc.).</p><p>By decrypting the ciphertext with each possible shift and displaying all results, the analyst can quickly identify the correct plaintext by reading the output that forms recognizable words.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Как работает перебор сдвигов Цезаря', '<p>Перебор сдвигов шифра Цезаря — это метод криптоанализа, при котором перебираются все возможные значения сдвига. Поскольку шифр Цезаря использует фиксированный сдвиг на одну букву, общее количество возможных ключей равно размеру алфавита (26 для английского, 33 для русского и т.д.).</p><p>Расшифровывая зашифрованный текст с каждым возможным сдвигом и отображая все результаты, аналитик может быстро определить правильный открытый текст, найдя строку с узнаваемыми словами.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'When to use Caesar brute force', '<p>Use this tool whenever you suspect a text has been encrypted with a Caesar cipher but you do not know the shift value. Simply paste the ciphertext, select the appropriate language alphabet, and scan the results for the shift that produces readable text.</p><p>This technique also works for ROT-13 (shift 13 in English), which is a common special case of the Caesar cipher used on the internet to hide spoilers and puzzle solutions.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Когда использовать перебор Цезаря', '<p>Используйте этот инструмент, когда вы подозреваете, что текст зашифрован шифром Цезаря, но не знаете значение сдвига. Просто вставьте зашифрованный текст, выберите нужный алфавит и найдите в результатах строку с читаемым текстом.</p><p>Этот метод также работает для ROT-13 (сдвиг 13 для английского), который является распространённым частным случаем шифра Цезаря, используемым в интернете для скрытия спойлеров и решений головоломок.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'ROT-13', 'URYYB JBEYQ', '', '', 'Decoded with shift 13: HELLO WORLD. ROT-13 is a Caesar cipher with shift 13.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'ROT-13', 'URYYB JBEYQ', '', '', 'Расшифруется со сдвигом 13: HELLO WORLD. ROT-13 — это шифр Цезаря со сдвигом 13.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Shift 3', 'KHOOR ZRUOG', '', '', 'The classic Caesar cipher with shift 3 encodes HELLO WORLD as KHOOR ZRUOG.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Сдвиг 3', 'KHOOR ZRUOG', '', '', 'Классический шифр Цезаря со сдвигом 3 шифрует HELLO WORLD в KHOOR ZRUOG.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Shift 7', 'HAAHJR HA KHDU', '', '', 'ATTACK AT DAWN encoded with shift 7. Scan all shifts to find the original.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Сдвиг 7', 'HAAHJR HA KHDU', '', '', 'ATTACK AT DAWN зашифровано со сдвигом 7. Перебор покажет все варианты.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'How many possible keys does Caesar cipher have?', 'The number of possible keys equals the size of the alphabet used. For English (26 letters), there are 26 possible shifts (0 to 25). For Russian (33 letters), there are 33 possible shifts. This small key space makes Caesar cipher trivially breakable by brute force.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Сколько возможных ключей у шифра Цезаря?', 'Количество возможных ключей равно размеру используемого алфавита. Для английского (26 букв) существует 26 возможных сдвигов (от 0 до 25). Для русского (33 буквы) — 33 возможных сдвига. Такое маленькое пространство ключей делает шифр Цезаря тривиально взламываемым перебором.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'What is ROT-13 and how is it related to Caesar cipher?', 'ROT-13 is a special case of the Caesar cipher with a shift of exactly 13. Because the English alphabet has 26 letters, applying ROT-13 twice returns the original text. ROT-13 is commonly used on the internet to hide spoilers and puzzle answers.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Что такое ROT-13 и как он связан с шифром Цезаря?', 'ROT-13 — это частный случай шифра Цезаря со сдвигом ровно 13. Поскольку английский алфавит содержит 26 букв, применение ROT-13 дважды возвращает исходный текст. ROT-13 широко используется в интернете для скрытия спойлеров и ответов на загадки.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Is Caesar cipher secure?', 'No. The Caesar cipher is one of the weakest encryption methods. With only 25 meaningful shifts in English (shift 0 is the original text), it can be broken instantly by brute force, by frequency analysis, or even by visual inspection of the output. It should never be used for protecting sensitive data.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Надёжен ли шифр Цезаря?', 'Нет. Шифр Цезаря — один из самых слабых методов шифрования. Имея лишь 25 значимых сдвигов для английского (сдвиг 0 — это исходный текст), он может быть взломан мгновенно перебором, частотным анализом или даже визуальным осмотром результатов. Его никогда не следует использовать для защиты конфиденциальных данных.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Caesar cipher', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Шифр Цезаря', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Brute force', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Перебор', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Cryptanalysis', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Криптоанализ', $now);
    }

    /**
     * Создаёт или обновляет родительскую запись (блок, пример, FAQ, тег).
     *
     * @param array<string, int|string> $extra
     */
    private function upsertParent(string $table, string $foreignKey, int $cipherId, int $sortOrder, string $now, array $extra = []): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $assignments = ['published = 1', 'updated_at = ?'];
            $values      = [$now];
            foreach ($extra as $field => $value) {
                $assignments[] = $field . ' = ?';
                $values[]      = $value;
            }
            $values[] = (int) $row['id'];
            $this->db->execute('UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = ?', $values);

            return (int) $row['id'];
        }

        $columns      = [$foreignKey, 'sort_order', 'published', 'created_at', 'updated_at', ...array_keys($extra)];
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
            'title'       => $title,
            'input'       => $input,
            'output'      => $output,
            'key'         => $key,
            'shift'       => 0,
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

        $columns      = array_map(static fn (string $field): string => '`' . $field . '`', [$foreignKey, 'language', ...array_keys($data), 'created_at', 'updated_at']);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            [$parentId, $language, ...array_values($data), $now, $now]
        );
    }

    /**
     * Возвращает переводы инструмента перебора шифра Цезаря.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Caesar Brute Force',
                'name_short'        => 'Caesar Brute Force',
                'description'       => 'Automatically try all possible Caesar cipher shift values and display every decryption result at once. Identify the correct plaintext in seconds without knowing the key.',
                'description_stort' => 'Try all possible Caesar cipher shifts to find the correct decryption instantly.',
                'meta_title'        => 'Caesar Cipher Brute Force | Try All Shifts Online',
                'meta_description'  => 'Automatically try all possible Caesar cipher shifts online. Instantly find the correct decryption without knowing the key — perfect for cryptanalysis and CTF challenges.',
            ],
            'ru' => [
                'name'              => 'Перебор шифра Цезаря',
                'name_short'        => 'Перебор Цезаря',
                'description'       => 'Автоматически перебирайте все возможные значения сдвига шифра Цезаря и просматривайте все варианты дешифровки сразу. Определите правильный открытый текст за секунды, не зная ключа.',
                'description_stort' => 'Переберите все сдвиги шифра Цезаря для мгновенной дешифровки без знания ключа.',
                'meta_title'        => 'Перебор шифра Цезаря онлайн | Все сдвиги',
                'meta_description'  => 'Автоматически перебирайте все возможные сдвиги шифра Цезаря онлайн. Мгновенно найдите правильную дешифровку без знания ключа — для криптоанализа и CTF-задач.',
            ],
            'de' => [
                'name'              => 'Caesar Brute Force',
                'name_short'        => 'Caesar Brute Force',
                'description'       => 'Alle möglichen Caesar-Verschiebungswerte automatisch ausprobieren und alle Entschlüsselungsergebnisse auf einmal anzeigen. Den korrekten Klartext in Sekunden identifizieren, ohne den Schlüssel zu kennen.',
                'description_stort' => 'Alle Caesar-Verschiebungen ausprobieren, um die korrekte Entschlüsselung sofort zu finden.',
                'meta_title'        => 'Caesar Brute Force | Alle Verschiebungen online',
                'meta_description'  => 'Alle möglichen Caesar-Verschiebungen online automatisch ausprobieren. Den korrekten Klartext sofort ohne Schlüsselkenntnis finden – für Kryptoanalyse und CTF.',
            ],
            'es' => [
                'name'              => 'Fuerza bruta César',
                'name_short'        => 'Fuerza bruta César',
                'description'       => 'Prueba automáticamente todos los posibles valores de desplazamiento del cifrado César y muestra todos los resultados de descifrado a la vez. Identifica el texto original en segundos sin conocer la clave.',
                'description_stort' => 'Prueba todos los desplazamientos del cifrado César para encontrar el descifrado correcto al instante.',
                'meta_title'        => 'César Fuerza Bruta | Todos los desplazamientos online',
                'meta_description'  => 'Prueba automáticamente todos los desplazamientos posibles del cifrado César online. Encuentra el descifrado correcto sin conocer la clave — para criptoanálisis y CTF.',
            ],
            'fr' => [
                'name'              => 'César force brute',
                'name_short'        => 'César force brute',
                'description'       => 'Essayez automatiquement toutes les valeurs de décalage possibles du chiffre de César et affichez tous les résultats de déchiffrement en même temps. Identifiez le texte clair correct en quelques secondes sans connaître la clé.',
                'description_stort' => 'Essayez tous les décalages du chiffre de César pour trouver le déchiffrement correct instantanément.',
                'meta_title'        => 'César force brute | Tous les décalages en ligne',
                'meta_description'  => 'Essayez automatiquement tous les décalages possibles du chiffre César en ligne. Trouvez instantanément le bon déchiffrement sans connaître la clé — pour la cryptanalyse et les CTF.',
            ],
            'it' => [
                'name'              => 'César forza bruta',
                'name_short'        => 'César forza bruta',
                'description'       => 'Prova automaticamente tutti i possibili valori di scorrimento del cifrario di César e visualizza tutti i risultati di decifratura contemporaneamente. Identifica il testo in chiaro corretto in pochi secondi senza conoscere la chiave.',
                'description_stort' => 'Prova tutti gli scorrimenti del cifrario César per trovare la decifratura corretta all\'istante.',
                'meta_title'        => 'César forza bruta | Tutti gli scorrimenti online',
                'meta_description'  => 'Prova automaticamente tutti i possibili scorrimenti del cifrario César online. Trova immediatamente la decifratura corretta senza conoscere la chiave — per la crittoanalisi e le sfide CTF.',
            ],
            'pt' => [
                'name'              => 'César força bruta',
                'name_short'        => 'César força bruta',
                'description'       => 'Tente automaticamente todos os valores de deslocamento possíveis da cifra de César e exiba todos os resultados de decifração de uma vez. Identifique o texto simples correto em segundos sem conhecer a chave.',
                'description_stort' => 'Tente todos os deslocamentos da cifra César para encontrar a decifração correta instantaneamente.',
                'meta_title'        => 'César Força Bruta | Todos os deslocamentos online',
                'meta_description'  => 'Tente automaticamente todos os deslocamentos possíveis da cifra César online. Encontre instantaneamente a decifração correta sem conhecer a chave — para criptoanálise e CTF.',
            ],
            'tr' => [
                'name'              => 'César kaba kuvvet',
                'name_short'        => 'César kaba kuvvet',
                'description'       => 'César şifresinin tüm olası kaydırma değerlerini otomatik olarak deneyin ve tüm deşifre sonuçlarını aynı anda görüntüleyin. Anahtarı bilmeden saniyeler içinde doğru düz metni belirleyin.',
                'description_stort' => 'Anahtarı bilmeden doğru deşifreyi anında bulmak için tüm César kaydırmalarını deneyin.',
                'meta_title'        => 'César Kaba Kuvvet | Tüm kaydırmalar çevrimiçi',
                'meta_description'  => 'Tüm olası César şifresi kaydırmalarını çevrimiçi otomatik deneyin. Anahtarı bilmeden doğru deşifreyi anında bulun — kriptoanalizis ve CTF yarışmaları için.',
            ],
        ];
    }
}
