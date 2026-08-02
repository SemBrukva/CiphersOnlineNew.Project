<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр ADFGVX в категорию классических шифров.
 */
class SeedAdfgvxCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр ADFGVX и его контент.
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

        $categoryId = (int) $category['id'];
        $now = date('Y-m-d H:i:s');
        $cipherId = $this->upsertCipher($categoryId, $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertCipherTranslation($cipherId, $language, $translation, $now);
        }

        $this->seedContent($cipherId, $now);
    }

    /**
     * Удаляет шифр ADFGVX и связанные с ним сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['adfgvx']
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
            [$categoryId, 'adfgvx']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'adfgvx', 'api', 95, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 95, 1, $now, $cipherId]
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
        $settings = (string) json_encode(['ciphers-adfgvx-key' => 'BATTLE']);

        $block = $this->upsertBlock($cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How the ADFGVX cipher works', '<p>ADFGVX is a German field cipher from World War I. It combines two techniques: fractionation through a 6×6 Polybius square and columnar transposition, which makes it far stronger than either step alone.</p><p>First, a keyword mixes a 6×6 square containing the 26 letters and 10 digits. The rows and columns are labelled with the letters A, D, F, G, V, X — chosen because they are easy to tell apart in Morse code. Each plaintext character becomes a pair of these letters. The resulting stream is then rearranged by a second keyword using columnar transposition to produce the ciphertext.</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает шифр ADFGVX', '<p>ADFGVX — немецкий полевой шифр времён Первой мировой войны. Он объединяет две техники: фракционирование через квадрат Полибия 6×6 и столбцовую перестановку, что делает его значительно сильнее каждого шага по отдельности.</p><p>Сначала ключевое слово перемешивает квадрат 6×6, содержащий 26 букв и 10 цифр. Строки и столбцы обозначаются буквами A, D, F, G, V, X — их выбрали за то, что они хорошо различимы в азбуке Морзе. Каждый символ открытого текста превращается в пару этих букв. Полученный поток затем переставляется вторым ключевым словом столбцовой перестановкой — так получается шифротекст.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $settings, $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encrypt with two keys', 'ATTACK AT DAWN', 'VVVVAAAAGFFXGFDFGAGGGXGX', 'PRIVACY', 'Square key PRIVACY builds the 6×6 grid; transposition key BATTLE rearranges the fractionated letters.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Шифрование с двумя ключами', 'ATTACK AT DAWN', 'VVVVAAAAGFFXGFDFGAGGGXGX', 'PRIVACY', 'Ключ квадрата PRIVACY строит сетку 6×6; ключ транспозиции BATTLE переставляет фракционированные буквы.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'decrypt', $settings, $now);
        $this->upsertExampleTranslation($example2, 'en', 'Decrypt with the same keys', 'VVVVAAAAGFFXGFDFGAGGGXGX', 'ATTACKATDAWN', 'PRIVACY', 'The same square key and transposition key are required to recover the plaintext.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Расшифровка теми же ключами', 'VVVVAAAAGFFXGFDFGAGGGXGX', 'ATTACKATDAWN', 'PRIVACY', 'Для восстановления текста нужны те же ключ квадрата и ключ транспозиции.', $now);

        $faq1 = $this->upsertFaq($cipherId, 10, $now);
        $this->upsertFaqTranslation($faq1, 'en', 'Why is it called ADFGVX?', 'The letters A, D, F, G, V and X label the rows and columns of the 6×6 square. They were chosen because their Morse code representations are very different, reducing transmission errors.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Почему шифр называется ADFGVX?', 'Буквы A, D, F, G, V и X обозначают строки и столбцы квадрата 6×6. Их выбрали потому, что их представления в азбуке Морзе сильно различаются, что снижает число ошибок при передаче.', $now);

        $faq2 = $this->upsertFaq($cipherId, 20, $now);
        $this->upsertFaqTranslation($faq2, 'en', 'Why does ADFGVX use two keys?', 'The square key mixes the 6×6 Polybius grid, and the transposition key drives the columnar transposition step. Both are required to encrypt and decrypt.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Почему в ADFGVX два ключа?', 'Ключ квадрата перемешивает сетку Полибия 6×6, а ключ транспозиции управляет этапом столбцовой перестановки. Оба нужны и для шифрования, и для расшифровки.', $now);

        $faq3 = $this->upsertFaq($cipherId, 30, $now);
        $this->upsertFaqTranslation($faq3, 'en', 'What is the difference between ADFGVX and ADFGX?', 'ADFGX is the earlier version with a 5×5 square for 25 letters only. ADFGVX extends it to a 6×6 square that also includes the digits 0–9.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Чем ADFGVX отличается от ADFGX?', 'ADFGX — более ранняя версия с квадратом 5×5 только для 25 букв. ADFGVX расширяет его до квадрата 6×6, куда добавлены и цифры 0–9.', $now);

        $tag1 = $this->upsertTag($cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Fractionation', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Фракционирование', $now);

        $tag2 = $this->upsertTag($cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Transposition', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Перестановка', $now);

        $tag3 = $this->upsertTag($cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'World War I', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Первая мировая война', $now);
    }

    /**
     * Создаёт или обновляет блок контента.
     */
    private function upsertBlock(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_BLOCKS . ' SET published = 1, updated_at = ? WHERE id = ?', [$now, $id]);
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
            $this->db->execute('UPDATE ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' SET title = ?, text = ?, updated_at = ? WHERE id = ?', [$title, $text, $now, (int) $row['id']]);
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' (block_id, language, title, text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$blockId, $language, $title, $text, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет пример.
     */
    private function upsertExample(int $cipherId, int $sortOrder, string $direction, string $settings, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET direction = ?, settings = ?, published = 1, updated_at = ? WHERE id = ?', [$direction, $settings, $now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, direction, settings, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $direction, $settings, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(int $exampleId, string $language, string $title, string $input, string $output, string $key, string $description, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' SET title = ?, input = ?, output = ?, key = ?, description = ?, updated_at = ? WHERE id = ?',
                [$title, $input, $output, $key, $description, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' (example_id, language, title, input, output, key, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$exampleId, $language, $title, $input, $output, $key, $description, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет FAQ.
     */
    private function upsertFaq(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_FAQ . ' SET published = 1, show_in_category = 0, updated_at = ? WHERE id = ?', [$now, $id]);
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
            $this->db->execute('UPDATE ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' SET question = ?, answer = ?, updated_at = ? WHERE id = ?', [$question, $answer, $now, (int) $row['id']]);
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' (faq_id, language, question, answer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$faqId, $language, $question, $answer, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет тег.
     */
    private function upsertTag(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_TAGS . ' SET published = 1, updated_at = ? WHERE id = ?', [$now, $id]);
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
            $this->db->execute('UPDATE ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' SET tag = ?, updated_at = ? WHERE id = ?', [$tag, $now, (int) $row['id']]);
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' (tag_id, language, tag, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$tagId, $language, $tag, $now, $now]
        );
    }

    /**
     * Возвращает переводы для шифра ADFGVX.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'ADFGVX Cipher',
                'name_short' => 'ADFGVX',
                'description' => 'Encrypt and decrypt text with the WWI ADFGVX cipher: a keyed 6×6 Polybius square combined with columnar transposition.',
                'description_stort' => 'German WWI cipher: 6×6 fractionation plus columnar transposition.',
                'meta_title' => 'ADFGVX Cipher Online | Ciphers Online',
                'meta_description' => 'Use the ADFGVX cipher online: set the square key and transposition key to encrypt or decrypt text instantly.',
            ],
            'ru' => [
                'name' => 'Шифр ADFGVX',
                'name_short' => 'ADFGVX',
                'description' => 'Онлайн-инструмент для шифрования и расшифровки шифром ADFGVX времён Первой мировой: квадрат Полибия 6×6 с ключом плюс столбцовая перестановка.',
                'description_stort' => 'Немецкий шифр Первой мировой: фракционирование 6×6 и столбцовая перестановка.',
                'meta_title' => 'Шифр ADFGVX Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр ADFGVX онлайн: задайте ключ квадрата и ключ транспозиции, чтобы мгновенно шифровать или расшифровывать текст.',
            ],
            'de' => [
                'name' => 'ADFGVX-Chiffre',
                'name_short' => 'ADFGVX',
                'description' => 'Text mit der ADFGVX-Chiffre aus dem Ersten Weltkrieg ver- und entschlüsseln: ein 6×6-Polybios-Quadrat mit Schlüssel und Spaltentransposition.',
                'description_stort' => 'Deutsche Chiffre aus dem Ersten Weltkrieg: 6×6-Fraktionierung und Spaltentransposition.',
                'meta_title' => 'ADFGVX-Chiffre Online | Ciphers Online',
                'meta_description' => 'ADFGVX online nutzen: Quadrat-Schlüssel und Transpositionsschlüssel setzen und Text sofort ver- oder entschlüsseln.',
            ],
            'es' => [
                'name' => 'Cifrado ADFGVX',
                'name_short' => 'ADFGVX',
                'description' => 'Cifra y descifra texto con el cifrado ADFGVX de la Primera Guerra Mundial: un cuadrado de Polibio 6×6 con clave y transposición por columnas.',
                'description_stort' => 'Cifrado alemán de la Primera Guerra Mundial: fraccionamiento 6×6 y transposición por columnas.',
                'meta_title' => 'Cifrado ADFGVX Online | Ciphers Online',
                'meta_description' => 'Usa el cifrado ADFGVX online: define la clave del cuadrado y la clave de transposición para cifrar o descifrar texto al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre ADFGVX',
                'name_short' => 'ADFGVX',
                'description' => 'Chiffrez et déchiffrez du texte avec le chiffre ADFGVX de la Première Guerre mondiale : un carré de Polybe 6×6 à clé combiné à une transposition par colonnes.',
                'description_stort' => 'Chiffre allemand de la Grande Guerre : fractionnement 6×6 et transposition par colonnes.',
                'meta_title' => 'Chiffre ADFGVX en ligne | Ciphers Online',
                'meta_description' => 'Utilisez le chiffre ADFGVX en ligne : définissez la clé du carré et la clé de transposition pour chiffrer ou déchiffrer instantanément.',
            ],
            'it' => [
                'name' => 'Cifrario ADFGVX',
                'name_short' => 'ADFGVX',
                'description' => 'Cifra e decifra testo con il cifrario ADFGVX della Prima guerra mondiale: un quadrato di Polibio 6×6 con chiave e trasposizione a colonne.',
                'description_stort' => 'Cifrario tedesco della Prima guerra mondiale: frazionamento 6×6 e trasposizione a colonne.',
                'meta_title' => 'Cifrario ADFGVX Online | Ciphers Online',
                'meta_description' => 'Usa il cifrario ADFGVX online: imposta la chiave del quadrato e la chiave di trasposizione per cifrare o decifrare testo subito.',
            ],
            'pt' => [
                'name' => 'Cifra ADFGVX',
                'name_short' => 'ADFGVX',
                'description' => 'Criptografe e descriptografe texto com a cifra ADFGVX da Primeira Guerra Mundial: um quadrado de Políbio 6×6 com chave e transposição por colunas.',
                'description_stort' => 'Cifra alemã da Primeira Guerra Mundial: fracionamento 6×6 e transposição por colunas.',
                'meta_title' => 'Cifra ADFGVX Online | Ciphers Online',
                'meta_description' => 'Use a cifra ADFGVX online: defina a chave do quadrado e a chave de transposição para cifrar ou decifrar texto instantaneamente.',
            ],
            'tr' => [
                'name' => 'ADFGVX Şifresi',
                'name_short' => 'ADFGVX',
                'description' => 'Metni Birinci Dünya Savaşı ADFGVX şifresiyle şifreleyin ve çözün: anahtarlı 6×6 Polybius karesi ile sütun transpozisyonunun birleşimi.',
                'description_stort' => 'Birinci Dünya Savaşı Alman şifresi: 6×6 bölümleme ve sütun transpozisyonu.',
                'meta_title' => 'ADFGVX Şifresi Online | Ciphers Online',
                'meta_description' => 'ADFGVX şifresini online kullanın: kare anahtarını ve transpozisyon anahtarını ayarlayıp metni anında şifreleyin veya çözün.',
            ],
        ];
    }
}
