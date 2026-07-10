<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Scytale (скитала) в категорию классических шифров.
 */
class SeedScytaleCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр Scytale и его контент.
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
     * Удаляет шифр Scytale и связанные с ним сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['scytale']
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
            [$categoryId, 'scytale']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'scytale', 'api', 85, 1, $now, $now]
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
        $block = $this->upsertBlock($cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How the Scytale cipher works', '<p>The Scytale is one of the oldest known transposition ciphers, used in ancient Sparta. A strip of parchment was wound around a rod of a fixed diameter and the message was written along its length. When unwound, the letters appeared scrambled.</p><p>Mathematically, the plaintext is written into a grid row by row using a chosen number of columns (the rod diameter). The ciphertext is then read column by column. Only a rod of the same diameter restores the original order.</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает шифр Scytale', '<p>Scytale (скитала) — один из древнейших известных шифров перестановки, применявшийся в древней Спарте. Полоса пергамента наматывалась на жезл фиксированного диаметра, и сообщение писалось вдоль него. После разматывания буквы оказывались перемешаны.</p><p>Математически открытый текст записывается в сетку по строкам с выбранным числом столбцов (диаметр жезла), а шифротекст считывается по столбцам. Восстановить порядок можно только жезлом того же диаметра.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', '', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encrypt with diameter 4', 'WEAREDISCOVEREDFLEEATONCE', 'WECRLTEEDOEEOAIVDENRSEFAC', 4, 'A classic Scytale example with a rod diameter of four columns.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Шифрование с диаметром 4', 'WEAREDISCOVEREDFLEEATONCE', 'WECRLTEEDOEEOAIVDENRSEFAC', 4, 'Классический пример Scytale с диаметром жезла в четыре столбца.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'decrypt', '', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Decrypt with diameter 4', 'WECRLTEEDOEEOAIVDENRSEFAC', 'WEAREDISCOVEREDFLEEATONCE', 4, 'The same rod diameter is required to restore the plaintext.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Расшифровка с диаметром 4', 'WECRLTEEDOEEOAIVDENRSEFAC', 'WEAREDISCOVEREDFLEEATONCE', 4, 'Для восстановления текста нужен жезл того же диаметра.', $now);

        $faq1 = $this->upsertFaq($cipherId, 10, $now);
        $this->upsertFaqTranslation($faq1, 'en', 'Is Scytale a substitution cipher?', 'No. Scytale does not replace letters. It only changes their order, so it is a transposition cipher.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Scytale — это шифр замены?', 'Нет. Scytale не заменяет буквы, а только меняет их порядок, поэтому это шифр перестановки.', $now);

        $faq2 = $this->upsertFaq($cipherId, 20, $now);
        $this->upsertFaqTranslation($faq2, 'en', 'What is the key in the Scytale cipher?', 'The key is the rod diameter — the number of columns used to lay out the text. Decryption needs the same value.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Что является ключом в шифре Scytale?', 'Ключом является диаметр жезла — количество столбцов, по которым раскладывается текст. Для расшифровки нужно то же значение.', $now);

        $faq3 = $this->upsertFaq($cipherId, 30, $now);
        $this->upsertFaqTranslation($faq3, 'en', 'Does Scytale work with non-English alphabets?', 'Yes. Because it only rearranges characters, it works with any alphabet and Unicode text without configuration.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Работает ли Scytale с не английскими алфавитами?', 'Да. Поскольку шифр только переставляет символы, он работает с любым алфавитом и Unicode-текстом без настройки.', $now);

        $tag1 = $this->upsertTag($cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Transposition', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Перестановка', $now);

        $tag2 = $this->upsertTag($cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Ancient Greece', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Древняя Греция', $now);

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
     * Возвращает переводы для шифра Scytale.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Scytale Cipher',
                'name_short' => 'Scytale',
                'description' => 'Encrypt and decrypt text with the ancient Scytale cipher using a configurable rod diameter.',
                'description_stort' => 'Ancient Spartan transposition cipher with a custom rod diameter.',
                'meta_title' => 'Scytale Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Scytale cipher online: choose the rod diameter and encrypt or decrypt text instantly.',
            ],
            'ru' => [
                'name' => 'Шифр Scytale',
                'name_short' => 'Scytale',
                'description' => 'Онлайн-инструмент для шифрования и расшифровки древним шифром Scytale с настраиваемым диаметром жезла.',
                'description_stort' => 'Древний спартанский шифр перестановки с выбором диаметра жезла.',
                'meta_title' => 'Шифр Scytale Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр Scytale онлайн: выберите диаметр жезла и мгновенно шифруйте или расшифровывайте текст.',
            ],
            'de' => [
                'name' => 'Skytale-Chiffre',
                'name_short' => 'Skytale',
                'description' => 'Text mit der antiken Skytale-Chiffre und anpassbarem Stabdurchmesser ver- und entschlüsseln.',
                'description_stort' => 'Antike spartanische Transposition mit wählbarem Stabdurchmesser.',
                'meta_title' => 'Skytale-Chiffre Online | Ciphers Online',
                'meta_description' => 'Skytale online nutzen: Stabdurchmesser wählen und Text sofort ver- oder entschlüsseln.',
            ],
            'es' => [
                'name' => 'Cifrado Escítala',
                'name_short' => 'Escítala',
                'description' => 'Cifra y descifra texto con el antiguo cifrado escítala usando un diámetro de vara configurable.',
                'description_stort' => 'Antigua transposición espartana con diámetro de vara personalizable.',
                'meta_title' => 'Cifrado Escítala Online | Ciphers Online',
                'meta_description' => 'Usa la escítala online: elige el diámetro de la vara y cifra o descifra texto al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre Scytale',
                'name_short' => 'Scytale',
                'description' => 'Chiffrez et déchiffrez du texte avec l\'antique chiffre scytale et un diamètre de bâton configurable.',
                'description_stort' => 'Transposition spartiate antique avec diamètre de bâton personnalisé.',
                'meta_title' => 'Chiffre Scytale en ligne | Ciphers Online',
                'meta_description' => 'Utilisez la scytale en ligne : choisissez le diamètre du bâton et chiffrez ou déchiffrez instantanément.',
            ],
            'it' => [
                'name' => 'Cifrario Scitale',
                'name_short' => 'Scitale',
                'description' => 'Cifra e decifra testo con l\'antico cifrario scitale usando un diametro del bastone configurabile.',
                'description_stort' => 'Antica trasposizione spartana con diametro del bastone personalizzato.',
                'meta_title' => 'Cifrario Scitale Online | Ciphers Online',
                'meta_description' => 'Usa la scitale online: scegli il diametro del bastone e cifra o decifra testo subito.',
            ],
            'pt' => [
                'name' => 'Cifra Cítala',
                'name_short' => 'Cítala',
                'description' => 'Criptografe e descriptografe texto com a antiga cifra cítala usando um diâmetro de bastão configurável.',
                'description_stort' => 'Antiga transposição espartana com diâmetro de bastão configurável.',
                'meta_title' => 'Cifra Cítala Online | Ciphers Online',
                'meta_description' => 'Use a cítala online: escolha o diâmetro do bastão e cifre ou decifre texto instantaneamente.',
            ],
            'tr' => [
                'name' => 'Skital Şifresi',
                'name_short' => 'Skital',
                'description' => 'Ayarlanabilir çubuk çapıyla antik skital şifresini kullanarak metni şifreleyin ve çözün.',
                'description_stort' => 'Ayarlanabilir çubuk çaplı antik Sparta yer değiştirme şifresi.',
                'meta_title' => 'Skital Şifresi Online | Ciphers Online',
                'meta_description' => 'Skital aracını online kullanın: çubuk çapını seçin ve metni anında şifreleyin veya çözün.',
            ],
        ];
    }
}
