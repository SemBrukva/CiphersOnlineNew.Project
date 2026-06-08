<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет ROT13 в категорию классических шифров.
 */
class SeedRot13Cipher extends Migration
{
    /**
     * Создаёт или обновляет шифр rot13, переводы и базовый контент.
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
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }

        $this->upsertContent($cipherId, $now);
    }

    /**
     * Удаляет шифр rot13.
     */
    public function down(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['classical-ciphers']
        );

        if ($category === false) {
            return;
        }

        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ?',
            [(int) $category['id'], 'rot13']
        );
    }

    /**
     * Создаёт или обновляет запись инструмента ROT13.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'rot13']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'rot13', 'api', 75, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 75, 1, $now, $cipherId]
        );

        return $cipherId;
    }

    /**
     * Создаёт или обновляет перевод шифра.
     *
     * @param array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string} $translation Данные перевода.
     */
    private function upsertTranslation(int $cipherId, string $language, array $translation, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS
            . ' WHERE app_id = ? AND language = ? LIMIT 1',
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
     * Создаёт базовый контент страницы ROT13.
     */
    private function upsertContent(int $cipherId, string $now): void
    {
        $block = $this->upsertBlock($cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How ROT13 works', 'ROT13 is a Caesar shift fixed at 13 positions for the Latin alphabet. Because the English alphabet has 26 letters, applying ROT13 a second time restores the original text.', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает ROT13', 'ROT13 — это шифр Цезаря с фиксированным сдвигом на 13 позиций в латинском алфавите. Так как в английском алфавите 26 букв, повторное применение ROT13 возвращает исходный текст.', $now);

        $example1 = $this->upsertExample($cipherId, 10, $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encode HELLO', 'HELLO WORLD', 'URYYB JBEYQ', 'ROT13 shifts each Latin letter by 13 positions and preserves spaces.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Кодирование HELLO', 'HELLO WORLD', 'URYYB JBEYQ', 'ROT13 сдвигает каждую латинскую букву на 13 позиций и сохраняет пробелы.', $now);

        $example2 = $this->upsertExample($cipherId, 20, $now);
        $this->upsertExampleTranslation($example2, 'en', 'Decode ROT13 text', 'URYYB JBEYQ', 'HELLO WORLD', 'The same operation decodes ROT13 because the cipher is reciprocal.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Декодирование ROT13', 'URYYB JBEYQ', 'HELLO WORLD', 'То же преобразование расшифровывает ROT13, потому что шифр самообратный.', $now);

        $faq = $this->upsertFaq($cipherId, 10, $now);
        $this->upsertFaqTranslation($faq, 'en', 'Is ROT13 secure encryption?', 'No. ROT13 is an obfuscation method, not secure encryption. It has no secret key and is easy to reverse.', $now);
        $this->upsertFaqTranslation($faq, 'ru', 'ROT13 безопасен как шифрование?', 'Нет. ROT13 — это способ обфускации, а не безопасное шифрование. У него нет секретного ключа, и результат легко обратить.', $now);

        $tag1 = $this->upsertTag($cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Keyless cipher', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Шифр без ключа', $now);

        $tag2 = $this->upsertTag($cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Text obfuscation', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Обфускация текста', $now);
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
            $this->db->execute('UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET published = 1, updated_at = ? WHERE id = ?', [$now, $id]);
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
    private function upsertExampleTranslation(int $exampleId, string $language, string $title, string $input, string $output, string $description, string $now): void
    {
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

    /**
     * Возвращает переводы карточки ROT13.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'ROT13 Cipher',
                'name_short' => 'ROT13',
                'description' => 'Encode and decode Latin text with the classic ROT13 substitution, a fixed Caesar shift by 13 letters.',
                'description_stort' => 'Keyless ROT13 encoding and decoding.',
                'meta_title' => 'ROT13 Cipher Online | Ciphers Online',
                'meta_description' => 'Use ROT13 online to encode or decode Latin text instantly with a fixed 13-letter shift.',
            ],
            'ru' => [
                'name' => 'Шифр ROT13',
                'name_short' => 'ROT13',
                'description' => 'Онлайн-инструмент для кодирования и декодирования латинского текста классической заменой ROT13 со сдвигом на 13 букв.',
                'description_stort' => 'Кодирование и декодирование ROT13 без ключа.',
                'meta_title' => 'Шифр ROT13 Онлайн | Ciphers Online',
                'meta_description' => 'Используйте ROT13 онлайн: кодируйте или декодируйте латинский текст фиксированным сдвигом на 13 букв.',
            ],
            'de' => [
                'name' => 'ROT13-Chiffre',
                'name_short' => 'ROT13',
                'description' => 'Lateinischen Text mit der klassischen ROT13-Ersetzung kodieren und dekodieren: ein fester Caesar-Versatz um 13 Buchstaben.',
                'description_stort' => 'Schlüssellose ROT13-Kodierung und Dekodierung.',
                'meta_title' => 'ROT13-Chiffre Online | Ciphers Online',
                'meta_description' => 'ROT13 online nutzen: lateinischen Text sofort mit festem 13-Buchstaben-Versatz kodieren oder dekodieren.',
            ],
            'es' => [
                'name' => 'Cifrado ROT13',
                'name_short' => 'ROT13',
                'description' => 'Codifica y decodifica texto latino con la sustitución clásica ROT13, un desplazamiento César fijo de 13 letras.',
                'description_stort' => 'Codificación y decodificación ROT13 sin clave.',
                'meta_title' => 'Cifrado ROT13 Online | Ciphers Online',
                'meta_description' => 'Usa ROT13 online para codificar o decodificar texto latino al instante con un desplazamiento fijo de 13 letras.',
            ],
            'fr' => [
                'name' => 'Chiffre ROT13',
                'name_short' => 'ROT13',
                'description' => 'Encodez et décodez du texte latin avec la substitution classique ROT13, un décalage César fixe de 13 lettres.',
                'description_stort' => 'Encodage et décodage ROT13 sans clé.',
                'meta_title' => 'Chiffre ROT13 en ligne | Ciphers Online',
                'meta_description' => 'Utilisez ROT13 en ligne pour encoder ou décoder instantanément du texte latin avec un décalage fixe de 13 lettres.',
            ],
            'it' => [
                'name' => 'Cifrario ROT13',
                'name_short' => 'ROT13',
                'description' => 'Codifica e decodifica testo latino con la sostituzione classica ROT13, uno spostamento Cesare fisso di 13 lettere.',
                'description_stort' => 'Codifica e decodifica ROT13 senza chiave.',
                'meta_title' => 'Cifrario ROT13 Online | Ciphers Online',
                'meta_description' => 'Usa ROT13 online per codificare o decodificare subito testo latino con uno spostamento fisso di 13 lettere.',
            ],
            'pt' => [
                'name' => 'Cifra ROT13',
                'name_short' => 'ROT13',
                'description' => 'Codifique e decodifique texto latino com a substituição clássica ROT13, um deslocamento de César fixo de 13 letras.',
                'description_stort' => 'Codificação e decodificação ROT13 sem chave.',
                'meta_title' => 'Cifra ROT13 Online | Ciphers Online',
                'meta_description' => 'Use ROT13 online para codificar ou decodificar texto latino instantaneamente com deslocamento fixo de 13 letras.',
            ],
            'tr' => [
                'name' => 'ROT13 Şifresi',
                'name_short' => 'ROT13',
                'description' => 'Latin metnini klasik ROT13 yerine koymasıyla kodlayın ve çözün: 13 harflik sabit Sezar kaydırması.',
                'description_stort' => 'Anahtarsız ROT13 kodlama ve çözme.',
                'meta_title' => 'ROT13 Şifresi Online | Ciphers Online',
                'meta_description' => 'ROT13 aracını online kullanın: Latin metnini 13 harflik sabit kaydırmayla anında kodlayın veya çözün.',
            ],
        ];
    }
}
