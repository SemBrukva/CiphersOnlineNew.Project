<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет ROT47 в категорию классических шифров.
 */
class SeedRot47Cipher extends Migration
{
    /**
     * Создаёт или обновляет шифр rot47, переводы и базовый контент.
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
     * Удаляет шифр rot47.
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
            [(int) $category['id'], 'rot47']
        );
    }

    /**
     * Создаёт или обновляет запись инструмента ROT47.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'rot47']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'rot47', 'api', 76, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 76, 1, $now, $cipherId]
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
     * Создаёт базовый контент страницы ROT47.
     */
    private function upsertContent(int $cipherId, string $now): void
    {
        $block = $this->upsertBlock($cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How ROT47 works', 'ROT47 is a rotation cipher applied to the 94 printable ASCII characters (codes 33–126), shifting each one by 47 positions. Because 47 is half of 94, applying ROT47 twice restores the original text. Unlike ROT13 it also transforms digits and punctuation, but leaves spaces and non-ASCII characters untouched.', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает ROT47', 'ROT47 — это шифр вращения по 94 печатным символам ASCII (коды 33–126) со сдвигом на 47 позиций. Так как 47 — это половина от 94, повторное применение ROT47 возвращает исходный текст. В отличие от ROT13 он преобразует также цифры и знаки препинания, но не трогает пробелы и не-ASCII символы.', $now);

        $example1 = $this->upsertExample($cipherId, 10, $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encode a greeting', 'Hello, World!', 'w6==@[ (@C=5P', 'ROT47 shifts every printable ASCII character by 47 positions, including letters, the comma and the exclamation mark.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Кодирование приветствия', 'Hello, World!', 'w6==@[ (@C=5P', 'ROT47 сдвигает каждый печатный ASCII-символ на 47 позиций, включая буквы, запятую и восклицательный знак.', $now);

        $example2 = $this->upsertExample($cipherId, 20, $now);
        $this->upsertExampleTranslation($example2, 'en', 'Decode ROT47 text', 'w6==@[ (@C=5P', 'Hello, World!', 'The same operation decodes ROT47 because the cipher is reciprocal.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Декодирование ROT47', 'w6==@[ (@C=5P', 'Hello, World!', 'То же преобразование расшифровывает ROT47, потому что шифр самообратный.', $now);

        $faq1 = $this->upsertFaq($cipherId, 10, $now);
        $this->upsertFaqTranslation($faq1, 'en', 'Is ROT47 secure encryption?', 'No. ROT47 is an obfuscation method, not secure encryption. It has no secret key and is trivial to reverse.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'ROT47 безопасен как шифрование?', 'Нет. ROT47 — это способ обфускации, а не безопасное шифрование. У него нет секретного ключа, и результат легко обратить.', $now);

        $faq2 = $this->upsertFaq($cipherId, 20, $now);
        $this->upsertFaqTranslation($faq2, 'en', 'How is ROT47 different from ROT13?', 'ROT13 rotates only the 26 Latin letters, while ROT47 rotates the full range of 94 printable ASCII characters, so digits and punctuation are transformed too.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Чем ROT47 отличается от ROT13?', 'ROT13 вращает только 26 латинских букв, тогда как ROT47 вращает весь диапазон из 94 печатных символов ASCII, поэтому цифры и знаки препинания тоже преобразуются.', $now);

        $tag1 = $this->upsertTag($cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Keyless cipher', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Шифр без ключа', $now);

        $tag2 = $this->upsertTag($cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'ASCII rotation', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Вращение ASCII', $now);
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
     * Возвращает переводы карточки ROT47.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'ROT47 Cipher',
                'name_short' => 'ROT47',
                'description' => 'Encode and decode text with ROT47, a keyless rotation over the 94 printable ASCII characters by 47 positions.',
                'description_stort' => 'Keyless ROT47 encoding and decoding.',
                'meta_title' => 'ROT47 Cipher Online | Ciphers Online',
                'meta_description' => 'Use ROT47 online to encode or decode text instantly with a fixed 47-position shift over printable ASCII.',
            ],
            'ru' => [
                'name' => 'Шифр ROT47',
                'name_short' => 'ROT47',
                'description' => 'Онлайн-инструмент для кодирования и декодирования текста шифром ROT47 — вращением по 94 печатным символам ASCII со сдвигом на 47 позиций без ключа.',
                'description_stort' => 'Кодирование и декодирование ROT47 без ключа.',
                'meta_title' => 'Шифр ROT47 Онлайн | Ciphers Online',
                'meta_description' => 'Используйте ROT47 онлайн: кодируйте или декодируйте текст фиксированным сдвигом на 47 позиций по печатному ASCII.',
            ],
            'de' => [
                'name' => 'ROT47-Chiffre',
                'name_short' => 'ROT47',
                'description' => 'Text mit ROT47 kodieren und dekodieren: eine schlüssellose Rotation über die 94 druckbaren ASCII-Zeichen um 47 Positionen.',
                'description_stort' => 'Schlüssellose ROT47-Kodierung und Dekodierung.',
                'meta_title' => 'ROT47-Chiffre Online | Ciphers Online',
                'meta_description' => 'ROT47 online nutzen: Text sofort mit festem 47-Positionen-Versatz über druckbares ASCII kodieren oder dekodieren.',
            ],
            'es' => [
                'name' => 'Cifrado ROT47',
                'name_short' => 'ROT47',
                'description' => 'Codifica y decodifica texto con ROT47, una rotación sin clave de 47 posiciones sobre los 94 caracteres ASCII imprimibles.',
                'description_stort' => 'Codificación y decodificación ROT47 sin clave.',
                'meta_title' => 'Cifrado ROT47 Online | Ciphers Online',
                'meta_description' => 'Usa ROT47 online para codificar o decodificar texto al instante con un desplazamiento fijo de 47 posiciones sobre ASCII imprimible.',
            ],
            'fr' => [
                'name' => 'Chiffre ROT47',
                'name_short' => 'ROT47',
                'description' => 'Encodez et décodez du texte avec ROT47, une rotation sans clé de 47 positions sur les 94 caractères ASCII imprimables.',
                'description_stort' => 'Encodage et décodage ROT47 sans clé.',
                'meta_title' => 'Chiffre ROT47 en ligne | Ciphers Online',
                'meta_description' => 'Utilisez ROT47 en ligne pour encoder ou décoder instantanément du texte avec un décalage fixe de 47 positions sur l’ASCII imprimable.',
            ],
            'it' => [
                'name' => 'Cifrario ROT47',
                'name_short' => 'ROT47',
                'description' => 'Codifica e decodifica testo con ROT47, una rotazione senza chiave di 47 posizioni sui 94 caratteri ASCII stampabili.',
                'description_stort' => 'Codifica e decodifica ROT47 senza chiave.',
                'meta_title' => 'Cifrario ROT47 Online | Ciphers Online',
                'meta_description' => 'Usa ROT47 online per codificare o decodificare subito testo con uno spostamento fisso di 47 posizioni sull’ASCII stampabile.',
            ],
            'pt' => [
                'name' => 'Cifra ROT47',
                'name_short' => 'ROT47',
                'description' => 'Codifique e decodifique texto com ROT47, uma rotação sem chave de 47 posições sobre os 94 caracteres ASCII imprimíveis.',
                'description_stort' => 'Codificação e decodificação ROT47 sem chave.',
                'meta_title' => 'Cifra ROT47 Online | Ciphers Online',
                'meta_description' => 'Use ROT47 online para codificar ou decodificar texto instantaneamente com deslocamento fixo de 47 posições sobre ASCII imprimível.',
            ],
            'tr' => [
                'name' => 'ROT47 Şifresi',
                'name_short' => 'ROT47',
                'description' => 'Metni ROT47 ile kodlayın ve çözün: 94 yazdırılabilir ASCII karakteri üzerinde 47 konumluk anahtarsız döndürme.',
                'description_stort' => 'Anahtarsız ROT47 kodlama ve çözme.',
                'meta_title' => 'ROT47 Şifresi Online | Ciphers Online',
                'meta_description' => 'ROT47 aracını online kullanın: metni yazdırılabilir ASCII üzerinde 47 konumluk sabit kaydırmayla anında kodlayın veya çözün.',
            ],
        ];
    }
}
