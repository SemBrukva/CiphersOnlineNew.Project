<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр нигилистов (Nihilist) в категорию классических шифров.
 */
class SeedNihilistCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр нигилистов и его контент.
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
     * Удаляет шифр нигилистов и связанные с ним сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['nihilist']
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
            [$categoryId, 'nihilist']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'nihilist', 'api', 96, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 96, 1, $now, $cipherId]
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
        $settings = (string) json_encode(['ciphers-nihilist-key' => 'RUSSIAN']);

        $block = $this->upsertBlock($cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How the Nihilist cipher works', '<p>The Nihilist cipher was used by Russian revolutionaries in the 1880s. It combines a keyed Polybius square with an additive numeric key, turning a message into a stream of numbers.</p><p>First, a keyword mixes a 5×5 Polybius square (I and J share a cell). Each plaintext letter becomes a two-digit number — its row and column, counted from 1. A second keyword is converted to numbers the same way and repeated to match the length of the message. The two number streams are added together: each sum is one group of the ciphertext. Because a sum can exceed 99, the groups are separated by spaces. To decrypt, the key numbers are subtracted and the coordinates are read back off the square.</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает шифр нигилистов', '<p>Шифр нигилистов применяли русские революционеры в 1880-х годах. Он объединяет квадрат Полибия с ключом и аддитивный числовой ключ, превращая сообщение в поток чисел.</p><p>Сначала ключевое слово перемешивает квадрат Полибия 5×5 (буквы I и J делят одну ячейку). Каждая буква открытого текста становится двузначным числом — её строкой и столбцом, считая от 1. Второе ключевое слово так же переводится в числа и повторяется под длину сообщения. Два потока чисел складываются: каждая сумма — это группа шифротекста. Так как сумма может превышать 99, группы разделяются пробелами. При расшифровке числа ключа вычитаются, а координаты считываются обратно по квадрату.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $settings, $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encrypt with two keys', 'DYNAMITE', '37 106 62 36 67 47 86 26', 'ZEBRAS', 'Square key ZEBRAS builds the 5×5 grid; additive key RUSSIAN is added to each coordinate.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Шифрование с двумя ключами', 'DYNAMITE', '37 106 62 36 67 47 86 26', 'ZEBRAS', 'Ключ квадрата ZEBRAS строит сетку 5×5; аддитивный ключ RUSSIAN прибавляется к каждой координате.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'decrypt', $settings, $now);
        $this->upsertExampleTranslation($example2, 'en', 'Decrypt with the same keys', '37 106 62 36 67 47 86 26', 'DYNAMITE', 'ZEBRAS', 'The same square key and additive key are required to recover the plaintext.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Расшифровка теми же ключами', '37 106 62 36 67 47 86 26', 'DYNAMITE', 'ZEBRAS', 'Для восстановления текста нужны те же ключ квадрата и аддитивный ключ.', $now);

        $faq1 = $this->upsertFaq($cipherId, 10, $now);
        $this->upsertFaqTranslation($faq1, 'en', 'Why does the Nihilist cipher use two keys?', 'The square key mixes the Polybius grid, so letters map to unpredictable coordinates. The additive key then adds a repeating number stream on top, hiding letter frequencies. Both keys are needed to encrypt and decrypt.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Почему в шифре нигилистов два ключа?', 'Ключ квадрата перемешивает сетку Полибия, поэтому буквы отображаются в непредсказуемые координаты. Аддитивный ключ добавляет сверху повторяющийся поток чисел, скрывая частоты букв. Оба ключа нужны для шифрования и расшифровки.', $now);

        $faq2 = $this->upsertFaq($cipherId, 20, $now);
        $this->upsertFaqTranslation($faq2, 'en', 'Why are some ciphertext groups three digits long?', 'Each plaintext coordinate (11–55) is added to a key coordinate (11–55), so a group can be as large as 110. That is why the groups have variable length and are separated by spaces.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Почему некоторые группы шифротекста трёхзначные?', 'Каждая координата открытого текста (11–55) складывается с координатой ключа (11–55), поэтому группа может достигать 110. Именно поэтому группы имеют переменную длину и разделяются пробелами.', $now);

        $faq3 = $this->upsertFaq($cipherId, 30, $now);
        $this->upsertFaqTranslation($faq3, 'en', 'What happens to the letters I and J?', 'In a classic 5×5 square there is room for only 25 letters, so I and J share one cell. When decrypting, that cell is shown as I. Other alphabets use a 6×6 or 7×7 square and keep every letter.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Что происходит с буквами I и J?', 'В классическом квадрате 5×5 помещается только 25 букв, поэтому I и J делят одну ячейку. При расшифровке эта ячейка показывается как I. Другие алфавиты используют квадрат 6×6 или 7×7 и сохраняют все буквы.', $now);

        $tag1 = $this->upsertTag($cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Polybius square', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Квадрат Полибия', $now);

        $tag2 = $this->upsertTag($cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Additive key', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Аддитивный ключ', $now);

        $tag3 = $this->upsertTag($cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Russian history', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Русская история', $now);
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
     * Возвращает переводы для шифра нигилистов.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Nihilist Cipher',
                'name_short' => 'Nihilist',
                'description' => 'Encrypt and decrypt text with the Nihilist cipher: a keyed Polybius square plus a repeating additive number key.',
                'description_stort' => 'Russian 1880s cipher: keyed Polybius square plus additive numeric key.',
                'meta_title' => 'Nihilist Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Nihilist cipher online: set the square key and additive key to encrypt or decrypt text instantly, with a step-by-step view.',
            ],
            'ru' => [
                'name' => 'Шифр нигилистов',
                'name_short' => 'Нигилистов',
                'description' => 'Онлайн-инструмент для шифрования и расшифровки шифром нигилистов: квадрат Полибия с ключом плюс повторяющийся аддитивный числовой ключ.',
                'description_stort' => 'Русский шифр 1880-х: квадрат Полибия с ключом плюс аддитивный числовой ключ.',
                'meta_title' => 'Шифр нигилистов онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр нигилистов онлайн: задайте ключ квадрата и аддитивный ключ, чтобы мгновенно шифровать или расшифровывать текст с пошаговым разбором.',
            ],
            'de' => [
                'name' => 'Nihilisten-Chiffre',
                'name_short' => 'Nihilist',
                'description' => 'Text mit der Nihilisten-Chiffre ver- und entschlüsseln: ein Polybios-Quadrat mit Schlüssel plus ein wiederholter additiver Zahlenschlüssel.',
                'description_stort' => 'Russische Chiffre der 1880er: Polybios-Quadrat mit Schlüssel plus additiver Zahlenschlüssel.',
                'meta_title' => 'Nihilisten-Chiffre Online | Ciphers Online',
                'meta_description' => 'Nihilisten-Chiffre online nutzen: Quadrat-Schlüssel und additiven Schlüssel setzen und Text sofort ver- oder entschlüsseln.',
            ],
            'es' => [
                'name' => 'Cifrado Nihilista',
                'name_short' => 'Nihilista',
                'description' => 'Cifra y descifra texto con el cifrado nihilista: un cuadrado de Polibio con clave más una clave numérica aditiva que se repite.',
                'description_stort' => 'Cifrado ruso de la década de 1880: cuadrado de Polibio con clave más clave numérica aditiva.',
                'meta_title' => 'Cifrado Nihilista Online | Ciphers Online',
                'meta_description' => 'Usa el cifrado nihilista online: define la clave del cuadrado y la clave aditiva para cifrar o descifrar texto al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre nihiliste',
                'name_short' => 'Nihiliste',
                'description' => 'Chiffrez et déchiffrez du texte avec le chiffre nihiliste : un carré de Polybe à clé et une clé numérique additive répétée.',
                'description_stort' => 'Chiffre russe des années 1880 : carré de Polybe à clé et clé numérique additive.',
                'meta_title' => 'Chiffre nihiliste en ligne | Ciphers Online',
                'meta_description' => 'Utilisez le chiffre nihiliste en ligne : définissez la clé du carré et la clé additive pour chiffrer ou déchiffrer instantanément.',
            ],
            'it' => [
                'name' => 'Cifrario nichilista',
                'name_short' => 'Nichilista',
                'description' => 'Cifra e decifra testo con il cifrario nichilista: un quadrato di Polibio con chiave più una chiave numerica additiva ripetuta.',
                'description_stort' => 'Cifrario russo degli anni 1880: quadrato di Polibio con chiave più chiave numerica additiva.',
                'meta_title' => 'Cifrario nichilista Online | Ciphers Online',
                'meta_description' => 'Usa il cifrario nichilista online: imposta la chiave del quadrato e la chiave additiva per cifrare o decifrare testo subito.',
            ],
            'pt' => [
                'name' => 'Cifra Niilista',
                'name_short' => 'Niilista',
                'description' => 'Criptografe e descriptografe texto com a cifra niilista: um quadrado de Políbio com chave mais uma chave numérica aditiva repetida.',
                'description_stort' => 'Cifra russa da década de 1880: quadrado de Políbio com chave mais chave numérica aditiva.',
                'meta_title' => 'Cifra Niilista Online | Ciphers Online',
                'meta_description' => 'Use a cifra niilista online: defina a chave do quadrado e a chave aditiva para cifrar ou decifrar texto instantaneamente.',
            ],
            'tr' => [
                'name' => 'Nihilist Şifresi',
                'name_short' => 'Nihilist',
                'description' => 'Metni Nihilist şifresiyle şifreleyin ve çözün: anahtarlı bir Polybius karesi ile tekrarlanan toplamsal sayı anahtarının birleşimi.',
                'description_stort' => '1880lerin Rus şifresi: anahtarlı Polybius karesi ve toplamsal sayı anahtarı.',
                'meta_title' => 'Nihilist Şifresi Online | Ciphers Online',
                'meta_description' => 'Nihilist şifresini online kullanın: kare anahtarını ve toplamsal anahtarı ayarlayıp metni anında şifreleyin veya çözün.',
            ],
        ];
    }
}
