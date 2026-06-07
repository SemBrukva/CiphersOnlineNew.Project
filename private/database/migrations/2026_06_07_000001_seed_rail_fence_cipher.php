<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Rail Fence в категорию классических шифров.
 */
class SeedRailFenceCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр Rail Fence и его контент.
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
     * Удаляет шифр Rail Fence и связанные с ним сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['rail-fence']
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
            [$categoryId, 'rail-fence']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'rail-fence', 'api', 80, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 80, 1, $now, $cipherId]
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
        $block = $this->upsertBlock($cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How the Rail Fence cipher works', '<p>The Rail Fence cipher is a classical transposition cipher. It writes the message diagonally across a chosen number of rails, then reads each rail from left to right.</p><p>With three rails, characters move down and up in a zigzag pattern. Decryption reconstructs the same pattern and places the ciphertext characters back into their original positions.</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает шифр Rail Fence', '<p>Rail Fence — классический шифр перестановки. Сообщение записывается зигзагом по выбранному количеству рельсов, а затем результат считывается построчно.</p><p>При трёх рельсах символы идут вниз и вверх по диагонали. Для расшифровки восстанавливается тот же шаблон, после чего символы шифротекста возвращаются на исходные позиции.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', '', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encrypt with 3 rails', 'WEAREDISCOVEREDFLEEATONCE', 'WECRLTEERDSOEEFEAOCAIVDEN', 3, 'A classic Rail Fence example with three rails.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Шифрование на 3 рельсах', 'WEAREDISCOVEREDFLEEATONCE', 'WECRLTEERDSOEEFEAOCAIVDEN', 3, 'Классический пример Rail Fence с тремя рельсами.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'decrypt', '', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Decrypt with 3 rails', 'WECRLTEERDSOEEFEAOCAIVDEN', 'WEAREDISCOVEREDFLEEATONCE', 3, 'The same rail count is required to restore the plaintext.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Расшифровка на 3 рельсах', 'WECRLTEERDSOEEFEAOCAIVDEN', 'WEAREDISCOVEREDFLEEATONCE', 3, 'Для восстановления текста нужно то же количество рельсов.', $now);

        $faq1 = $this->upsertFaq($cipherId, 10, $now);
        $this->upsertFaqTranslation($faq1, 'en', 'Is Rail Fence a substitution cipher?', 'No. Rail Fence does not replace letters. It changes only their order, so it is a transposition cipher.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Rail Fence — это шифр замены?', 'Нет. Rail Fence не заменяет буквы, а меняет только их порядок, поэтому это шифр перестановки.', $now);

        $faq2 = $this->upsertFaq($cipherId, 20, $now);
        $this->upsertFaqTranslation($faq2, 'en', 'What is the key in Rail Fence?', 'The key is the number of rails used for the zigzag pattern. The same number is needed for decryption.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Что является ключом в Rail Fence?', 'Ключом является количество рельсов в зигзаговом шаблоне. Для расшифровки нужно использовать то же число.', $now);

        $faq3 = $this->upsertFaq($cipherId, 30, $now);
        $this->upsertFaqTranslation($faq3, 'en', 'Is Rail Fence secure today?', 'No. It is easy to test possible rail counts automatically. Today it is mainly useful for learning, puzzles and demonstrations.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Безопасен ли Rail Fence сегодня?', 'Нет. Возможные количества рельсов легко перебрать автоматически. Сегодня этот шифр полезен в основном для обучения, головоломок и демонстраций.', $now);

        $tag1 = $this->upsertTag($cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Transposition', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Перестановка', $now);

        $tag2 = $this->upsertTag($cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Zigzag', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Зигзаг', $now);

        $tag3 = $this->upsertTag($cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Classical cipher', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Классический шифр', $now);
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
    private function upsertExample(int $cipherId, int $sortOrder, string $direction, string $delimiter, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET direction = ?, delimiter = ?, published = 1, updated_at = ? WHERE id = ?', [$direction, $delimiter, $now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, direction, delimiter, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $direction, $delimiter, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(int $exampleId, string $language, string $title, string $input, string $output, int $shift, string $description, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' SET title = ?, input = ?, output = ?, shift = ?, description = ?, updated_at = ? WHERE id = ?',
                [$title, $input, $output, $shift, $description, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' (example_id, language, title, input, output, shift, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$exampleId, $language, $title, $input, $output, $shift, $description, $now, $now]
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
     * Возвращает переводы для шифра Rail Fence.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Rail Fence Cipher',
                'name_short' => 'Rail Fence',
                'description' => 'Encrypt and decrypt text with the Rail Fence cipher using a configurable number of rails.',
                'description_stort' => 'Zigzag transposition cipher with custom rails.',
                'meta_title' => 'Rail Fence Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Rail Fence cipher online: choose rail count and encrypt or decrypt text instantly.',
            ],
            'ru' => [
                'name' => 'Шифр Rail Fence',
                'name_short' => 'Rail Fence',
                'description' => 'Онлайн-инструмент для шифрования и расшифровки Rail Fence с настраиваемым количеством рельсов.',
                'description_stort' => 'Зигзаговый шифр перестановки с выбором рельсов.',
                'meta_title' => 'Шифр Rail Fence Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр Rail Fence онлайн: выберите количество рельсов и мгновенно шифруйте или расшифровывайте текст.',
            ],
            'de' => [
                'name' => 'Rail-Fence-Chiffre',
                'name_short' => 'Rail Fence',
                'description' => 'Text mit der Rail-Fence-Chiffre und anpassbarer Anzahl von Schienen ver- und entschlüsseln.',
                'description_stort' => 'Zickzack-Transposition mit wählbaren Schienen.',
                'meta_title' => 'Rail-Fence-Chiffre Online | Ciphers Online',
                'meta_description' => 'Rail Fence online nutzen: Schienenzahl wählen und Text sofort ver- oder entschlüsseln.',
            ],
            'es' => [
                'name' => 'Cifrado Rail Fence',
                'name_short' => 'Rail Fence',
                'description' => 'Cifra y descifra texto con el cifrado Rail Fence usando un número configurable de rieles.',
                'description_stort' => 'Transposición en zigzag con rieles personalizables.',
                'meta_title' => 'Cifrado Rail Fence Online | Ciphers Online',
                'meta_description' => 'Usa Rail Fence online: elige el número de rieles y cifra o descifra texto al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre Rail Fence',
                'name_short' => 'Rail Fence',
                'description' => 'Chiffrez et déchiffrez du texte avec le chiffre Rail Fence et un nombre de rails configurable.',
                'description_stort' => 'Transposition en zigzag avec rails personnalisés.',
                'meta_title' => 'Chiffre Rail Fence en ligne | Ciphers Online',
                'meta_description' => 'Utilisez Rail Fence en ligne : choisissez le nombre de rails et chiffrez ou déchiffrez instantanément.',
            ],
            'it' => [
                'name' => 'Cifrario Rail Fence',
                'name_short' => 'Rail Fence',
                'description' => 'Cifra e decifra testo con il cifrario Rail Fence usando un numero di binari configurabile.',
                'description_stort' => 'Trasposizione a zigzag con binari personalizzati.',
                'meta_title' => 'Cifrario Rail Fence Online | Ciphers Online',
                'meta_description' => 'Usa Rail Fence online: scegli il numero di binari e cifra o decifra testo subito.',
            ],
            'pt' => [
                'name' => 'Cifra Rail Fence',
                'name_short' => 'Rail Fence',
                'description' => 'Criptografe e descriptografe texto com a cifra Rail Fence usando um número configurável de trilhos.',
                'description_stort' => 'Transposição em zigue-zague com trilhos configuráveis.',
                'meta_title' => 'Cifra Rail Fence Online | Ciphers Online',
                'meta_description' => 'Use Rail Fence online: escolha o número de trilhos e cifre ou decifre texto instantaneamente.',
            ],
            'tr' => [
                'name' => 'Rail Fence Şifresi',
                'name_short' => 'Rail Fence',
                'description' => 'Ayarlanabilir ray sayısıyla Rail Fence şifresi kullanarak metni şifreleyin ve çözün.',
                'description_stort' => 'Özel ray sayılı zikzak yer değiştirme şifresi.',
                'meta_title' => 'Rail Fence Şifresi Online | Ciphers Online',
                'meta_description' => 'Rail Fence aracını online kullanın: ray sayısını seçin ve metni anında şifreleyin veya çözün.',
            ],
        ];
    }
}
