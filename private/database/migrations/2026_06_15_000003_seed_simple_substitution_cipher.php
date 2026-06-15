<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр простой замены в категорию классических шифров.
 */
class SeedSimpleSubstitutionCipher extends Migration
{
    /**
     * Создаёт или обновляет запись шифра, переводы и контент.
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
            $this->upsertCipherTranslation($cipherId, $language, $translation, $now);
        }

        $this->seedContent($cipherId, $now);
    }

    /**
     * Удаляет запись шифра простой замены и связанные сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['simple-substitution']
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
     * Создаёт или обновляет запись шифра простой замены.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'simple-substitution']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'simple-substitution', 'api', 15, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 15, 1, $now, $cipherId]
        );

        return $cipherId;
    }

    /**
     * Создаёт или обновляет перевод шифра.
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
        $this->upsertBlockTranslation($block, 'en', 'How the Simple Substitution Cipher works', '<p>The simple substitution cipher replaces each letter of the plaintext with a corresponding letter from a scrambled cipher alphabet (the key). The key is a permutation of the standard alphabet — each letter appears exactly once in a different position.</p><p>To encrypt, find each plaintext letter in the standard alphabet and substitute it with the letter at the same position in the key. To decrypt, reverse the process: find each ciphertext letter in the key and replace it with the corresponding standard alphabet letter.</p><p>Non-alphabetic characters (spaces, punctuation, digits) pass through unchanged. Letter case is preserved.</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает шифр простой замены', '<p>Шифр простой замены заменяет каждую букву открытого текста соответствующей буквой из перемешанного шифрового алфавита (ключа). Ключ — это перестановка стандартного алфавита, в которой каждая буква встречается ровно один раз, но на другой позиции.</p><p>При шифровании каждая буква открытого текста заменяется буквой из ключа, стоящей на той же позиции. При дешифровании процесс обратный: каждая буква шифртекста ищется в ключе, и возвращается соответствующая буква стандартного алфавита.</p><p>Небуквенные символы (пробелы, знаки препинания, цифры) остаются без изменений. Регистр букв сохраняется.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encrypt HELLO WORLD', 'HELLO WORLD', 'ITSSG VGKSR', 'QWERTYUIOPASDFGHJKLZXCVBNM', 'With the QWERTY key: H→I, E→T, L→S, O→G, W→V, R→K, D→R.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Шифрование HELLO WORLD', 'HELLO WORLD', 'ITSSG VGKSR', 'QWERTYUIOPASDFGHJKLZXCVBNM', 'С ключом QWERTY: H→I, E→T, L→S, O→G, W→V, R→K, D→R.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Encrypt ATTACK AT DAWN', 'ATTACK AT DAWN', 'QZZQEA QZ RQVF', 'QWERTYUIOPASDFGHJKLZXCVBNM', 'With the QWERTY key: A→Q, T→Z, C→E, K→A, D→R, W→V, N→F.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Шифрование ATTACK AT DAWN', 'ATTACK AT DAWN', 'QZZQEA QZ RQVF', 'QWERTYUIOPASDFGHJKLZXCVBNM', 'С ключом QWERTY: A→Q, T→Z, C→E, K→A, D→R, W→V, N→F.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'decrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Decrypt ciphertext', 'ITSSG VGKSR', 'HELLO WORLD', 'QWERTYUIOPASDFGHJKLZXCVBNM', 'Reverse lookup: I→H, T→E, S→L, G→O, V→W, K→R, R→D.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Дешифрование текста', 'ITSSG VGKSR', 'HELLO WORLD', 'QWERTYUIOPASDFGHJKLZXCVBNM', 'Обратный поиск: I→H, T→E, S→L, G→O, V→W, K→R, R→D.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'What is the key in a simple substitution cipher?', 'The key is a permutation of the full alphabet — every letter of the alphabet appears exactly once, but in a scrambled order. For example, if the standard English alphabet is ABCDEFGHIJKLMNOPQRSTUVWXYZ and the key is QWERTYUIOPASDFGHJKLZXCVBNM, then A is always replaced by Q, B by W, and so on. The same key is used for all letters throughout the message.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Что такое ключ в шифре простой замены?', 'Ключ — это перестановка полного алфавита: каждая буква встречается в ключе ровно один раз, но в другом порядке. Например, если стандартный английский алфавит — ABCDEFGHIJKLMNOPQRSTUVWXYZ, а ключ — QWERTYUIOPASDFGHJKLZXCVBNM, то A всегда заменяется на Q, B — на W и так далее. Один и тот же ключ применяется ко всем буквам сообщения.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'How many possible keys does a simple substitution cipher have?', 'For the 26-letter English alphabet there are 26! (about 4×10²⁶) possible keys — far more than a brute-force attack can try. Despite this enormous key space, the cipher is broken easily by frequency analysis, because each ciphertext letter always maps to the same plaintext letter.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Сколько возможных ключей у шифра простой замены?', 'Для 26-буквенного английского алфавита существует 26! (около 4×10²⁶) возможных ключей — гораздо больше, чем можно перебрать грубой силой. Несмотря на огромное пространство ключей, шифр легко взламывается частотным анализом: каждая буква шифртекста всегда соответствует одной и той же букве открытого текста.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'What is the difference between a simple substitution cipher and a Caesar cipher?', 'The Caesar cipher is a special case of simple substitution where the cipher alphabet is just the standard alphabet shifted by a fixed number of positions. Simple substitution allows any permutation of the alphabet as the key, giving far more possible keys, though both ciphers share the same weakness to frequency analysis.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Чем шифр простой замены отличается от шифра Цезаря?', 'Шифр Цезаря — это частный случай шифра простой замены, где шифровой алфавит представляет собой стандартный алфавит, сдвинутый на фиксированное количество позиций. Шифр простой замены допускает произвольную перестановку алфавита в качестве ключа, что даёт значительно больше возможных ключей, хотя оба шифра одинаково уязвимы к частотному анализу.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Monoalphabetic', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Моноалфавитный', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Substitution', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Замена', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Frequency analysis', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Частотный анализ', $now);
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
     * Возвращает переводы для шифра простой замены.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'             => 'Simple Substitution Cipher',
                'name_short'       => 'Substitution',
                'description'      => 'Encrypt and decrypt text using a classic monoalphabetic substitution cipher. Enter any permutation of the alphabet as the key to create your own secret code.',
                'description_stort'=> 'Classic letter-substitution cipher with a custom alphabet key.',
                'meta_title'       => 'Simple Substitution Cipher Online | Ciphers Online',
                'meta_description' => 'Encode and decode text with a simple substitution cipher online. Enter your own cipher alphabet as the key and encrypt or decrypt any message instantly.',
            ],
            'ru' => [
                'name'             => 'Шифр простой замены',
                'name_short'       => 'Простая замена',
                'description'      => 'Зашифруйте и расшифруйте текст с помощью классического моноалфавитного шифра замены. Введите произвольную перестановку алфавита в качестве ключа и создайте собственный тайный код.',
                'description_stort'=> 'Классический шифр замены букв с пользовательским ключом-алфавитом.',
                'meta_title'       => 'Шифр простой замены онлайн | Ciphers Online',
                'meta_description' => 'Зашифруйте и расшифруйте текст шифром простой замены онлайн. Введите свой шифровой алфавит и мгновенно закодируйте любое сообщение.',
            ],
            'de' => [
                'name'             => 'Einfache Substitutionschiffre',
                'name_short'       => 'Substitution',
                'description'      => 'Verschlüsseln und entschlüsseln Sie Text mit einer klassischen monoalphabetischen Substitutionschiffre. Geben Sie eine beliebige Permutation des Alphabets als Schlüssel ein.',
                'description_stort'=> 'Klassische Buchstabenersetzungschiffre mit benutzerdefiniertem Alphabet-Schlüssel.',
                'meta_title'       => 'Einfache Substitutionschiffre Online | Ciphers Online',
                'meta_description' => 'Text mit einfacher Substitutionschiffre online ver- und entschlüsseln. Eigenes Chiffre-Alphabet als Schlüssel eingeben.',
            ],
            'es' => [
                'name'             => 'Cifrado de Sustitución Simple',
                'name_short'       => 'Sustitución',
                'description'      => 'Cifra y descifra texto con un cifrado de sustitución monoalfabética clásico. Introduce cualquier permutación del alfabeto como clave para crear tu propio código secreto.',
                'description_stort'=> 'Cifrado clásico de sustitución de letras con clave de alfabeto personalizada.',
                'meta_title'       => 'Cifrado de Sustitución Simple Online | Ciphers Online',
                'meta_description' => 'Cifra y descifra texto con un cifrado de sustitución simple online. Introduce tu propio alfabeto cifrado y codifica cualquier mensaje al instante.',
            ],
            'fr' => [
                'name'             => 'Chiffre de Substitution Simple',
                'name_short'       => 'Substitution',
                'description'      => 'Chiffrez et déchiffrez du texte avec un chiffre de substitution monoalphabétique classique. Entrez n\'importe quelle permutation de l\'alphabet comme clé pour créer votre propre code secret.',
                'description_stort'=> 'Chiffre classique de substitution de lettres avec clé d\'alphabet personnalisée.',
                'meta_title'       => 'Chiffre de Substitution Simple en ligne | Ciphers Online',
                'meta_description' => 'Chiffrez et déchiffrez du texte avec un chiffre de substitution simple en ligne. Entrez votre propre alphabet chiffré et encodez n\'importe quel message instantanément.',
            ],
            'it' => [
                'name'             => 'Cifrario di Sostituzione Semplice',
                'name_short'       => 'Sostituzione',
                'description'      => 'Cifra e decifra testo con un cifrario di sostituzione monoalfabetico classico. Inserisci qualsiasi permutazione dell\'alfabeto come chiave per creare il tuo codice segreto.',
                'description_stort'=> 'Cifrario classico di sostituzione di lettere con chiave alfabeto personalizzata.',
                'meta_title'       => 'Cifrario di Sostituzione Semplice Online | Ciphers Online',
                'meta_description' => 'Cifra e decifra testo con un cifrario di sostituzione semplice online. Inserisci il tuo alfabeto cifrato e codifica qualsiasi messaggio all\'istante.',
            ],
            'pt' => [
                'name'             => 'Cifra de Substituição Simples',
                'name_short'       => 'Substituição',
                'description'      => 'Cifre e decifre texto com uma cifra de substituição monoalfabética clássica. Insira qualquer permutação do alfabeto como chave para criar seu próprio código secreto.',
                'description_stort'=> 'Cifra clássica de substituição de letras com chave de alfabeto personalizada.',
                'meta_title'       => 'Cifra de Substituição Simples Online | Ciphers Online',
                'meta_description' => 'Cifre e decifre texto com uma cifra de substituição simples online. Insira seu próprio alfabeto cifrado e codifique qualquer mensagem instantaneamente.',
            ],
            'tr' => [
                'name'             => 'Basit İkame Şifresi',
                'name_short'       => 'İkame',
                'description'      => 'Klasik monoalfabetik ikame şifresiyle metin şifreleyin ve çözün. Kendi gizli kodunuzu oluşturmak için alfabenin herhangi bir permütasyonunu anahtar olarak girin.',
                'description_stort'=> 'Özel alfabe anahtarlı klasik harf ikame şifresi.',
                'meta_title'       => 'Basit İkame Şifresi Çevrimiçi | Ciphers Online',
                'meta_description' => 'Basit ikame şifresiyle metni çevrimiçi şifreleyin ve çözün. Kendi şifre alfabenizi girin ve herhangi bir mesajı anında kodlayın.',
            ],
        ];
    }
}
