<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Porta и базовый контент страницы.
 */
class SeedPortaCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр Porta, блоки, примеры и FAQ.
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
        $now        = date('Y-m-d H:i:s');
        $cipherId   = $this->upsertCipher($categoryId, $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertCipherTranslation($cipherId, $language, $translation, $now);
        }

        $this->seedBlocks($cipherId, $now);
        $this->seedExamples($cipherId, $now);
        $this->seedFaq($cipherId, $now);
    }

    /**
     * Удаляет шифр Porta вместе с контентом.
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
            [(int) $category['id'], 'porta']
        );
    }

    /**
     * Создаёт или обновляет запись шифра Porta.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'porta']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'porta', 'api', 56, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 56, 1, $now, $cipherId]
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
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS
            . ' WHERE app_id = ? AND language = ? LIMIT 1',
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
                . ' SET name = ?, name_short = ?, description = ?, description_stort = ?, meta_title = ?, meta_description = ?, updated_at = ? '
                . 'WHERE id = ?',
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
     * Добавляет базовые блоки страницы.
     */
    private function seedBlocks(int $cipherId, string $now): void
    {
        foreach ($this->blockTranslations() as $sortOrder => $translations) {
            $blockId = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, $sortOrder, $now);

            foreach ($translations as $language => $translation) {
                $this->upsertTranslation(
                    Tables::CIPHERS_BLOCKS_TRANSLATIONS,
                    'block_id',
                    $blockId,
                    $language,
                    ['title' => $translation['title'], 'text' => $translation['text']],
                    $now
                );
            }
        }
    }

    /**
     * Добавляет базовые примеры страницы.
     */
    private function seedExamples(int $cipherId, string $now): void
    {
        foreach ($this->examples() as $sortOrder => $example) {
            $exampleId = $this->upsertExample($cipherId, $sortOrder, $example['direction'], $now);

            foreach ($example['translations'] as $language => $translation) {
                $this->upsertTranslation(
                    Tables::CIPHERS_EXAMPLES_TRANSLATIONS,
                    'example_id',
                    $exampleId,
                    $language,
                    [
                        'title'       => $translation['title'],
                        'input'       => $translation['input'],
                        'output'      => $translation['output'],
                        'description' => $translation['description'],
                        'key'         => $translation['key'],
                    ],
                    $now
                );
            }
        }
    }

    /**
     * Добавляет базовый FAQ страницы.
     */
    private function seedFaq(int $cipherId, string $now): void
    {
        foreach ($this->faqs() as $sortOrder => $translations) {
            $faqId = $this->upsertParent(
                Tables::CIPHERS_FAQ,
                'app_id',
                $cipherId,
                $sortOrder,
                $now,
                ['show_in_category' => 0]
            );

            foreach ($translations as $language => $translation) {
                $this->upsertTranslation(
                    Tables::CIPHERS_FAQ_TRANSLATIONS,
                    'faq_id',
                    $faqId,
                    $language,
                    ['question' => $translation['question'], 'answer' => $translation['answer']],
                    $now
                );
            }
        }
    }

    /**
     * Создаёт или обновляет родительскую запись контента.
     *
     * @param array<string, int|string> $extra Дополнительные поля.
     */
    private function upsertParent(string $table, string $foreignKey, int $ownerId, int $sortOrder, string $now, array $extra = []): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND sort_order = ? LIMIT 1',
            [$ownerId, $sortOrder]
        );

        if ($row !== false) {
            $id          = (int) $row['id'];
            $assignments = ['published = 1', 'updated_at = ?'];
            $params      = [$now];

            foreach ($extra as $column => $value) {
                $assignments[] = $column . ' = ?';
                $params[]      = $value;
            }

            $params[] = $id;
            $this->db->execute(
                'UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = ?',
                $params
            );

            return $id;
        }

        $columns = [$foreignKey, 'sort_order', 'published'];
        $values  = [$ownerId, $sortOrder, 1];

        foreach ($extra as $column => $value) {
            $columns[] = $column;
            $values[]  = $value;
        }

        $columns[] = 'created_at';
        $columns[] = 'updated_at';
        $values[]  = $now;
        $values[]  = $now;

        return (int) $this->db->insert(
            'INSERT INTO ' . $table
            . ' (' . implode(', ', $columns) . ') '
            . 'VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
            $values
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
            $id = (int) $row['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES
                . ' SET direction = ?, delimiter = ?, key_format = ?, published = 1, updated_at = ? WHERE id = ?',
                [$direction, '', '', $now, $id]
            );

            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES
            . ' (app_id, sort_order, published, direction, delimiter, key_format, created_at, updated_at) '
            . 'VALUES (?, ?, 1, ?, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $direction, '', '', $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод родительской записи.
     *
     * @param array<string, int|string> $columns Значения колонок перевода.
     */
    private function upsertTranslation(
        string $table,
        string $foreignKey,
        int $parentId,
        string $language,
        array $columns,
        string $now
    ): void {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND language = ? LIMIT 1',
            [$parentId, $language]
        );

        if ($row !== false) {
            $this->updateById($table, (int) $row['id'], $columns, $now);
            return;
        }

        $insertColumns = [$foreignKey, 'language'];
        $values        = [$parentId, $language];

        foreach ($columns as $column => $value) {
            $insertColumns[] = $column;
            $values[]        = $value;
        }

        $insertColumns[] = 'created_at';
        $insertColumns[] = 'updated_at';
        $values[]        = $now;
        $values[]        = $now;

        $this->db->insert(
            'INSERT INTO ' . $table
            . ' (' . implode(', ', $insertColumns) . ') '
            . 'VALUES (' . implode(', ', array_fill(0, count($insertColumns), '?')) . ')',
            $values
        );
    }

    /**
     * Обновляет строку по id набором колонок.
     *
     * @param array<string, int|string> $columns Значения колонок.
     */
    private function updateById(string $table, int $id, array $columns, string $now): void
    {
        $assignments = [];
        $values      = [];

        foreach ($columns as $column => $value) {
            $assignments[] = $column . ' = ?';
            $values[]      = $value;
        }

        $assignments[] = 'updated_at = ?';
        $values[]      = $now;
        $values[]      = $id;

        $this->db->execute(
            'UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = ?',
            $values
        );
    }

    /**
     * Возвращает переводы карточки шифра.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Porta Cipher',
                'name_short'        => 'Porta',
                'description'       => 'Encrypt and decrypt Latin text with the Porta cipher using a reciprocal polyalphabetic table and a keyword.',
                'description_stort' => 'Porta encryption and decryption with a keyword.',
                'meta_title'        => 'Porta Cipher Online | Ciphers Online',
                'meta_description'  => 'Use the Porta cipher online: enter a keyword and encrypt or decrypt Latin text with the reciprocal Porta table.',
            ],
            'ru' => [
                'name'              => 'Шифр Porta',
                'name_short'        => 'Porta',
                'description'       => 'Онлайн-инструмент для шифрования и расшифровки латинского текста шифром Porta с ключевым словом и взаимной полиалфавитной таблицей.',
                'description_stort' => 'Шифрование и расшифровка Porta по ключевому слову.',
                'meta_title'        => 'Шифр Porta Онлайн | Ciphers Online',
                'meta_description'  => 'Используйте шифр Porta онлайн: задайте ключевое слово и зашифруйте или расшифруйте латинский текст.',
            ],
            'de' => [
                'name'              => 'Porta-Chiffre',
                'name_short'        => 'Porta',
                'description'       => 'Lateinischen Text mit der Porta-Chiffre, einem Schlüsselwort und einer reziproken polyalphabetischen Tabelle ver- und entschlüsseln.',
                'description_stort' => 'Porta-Verfahren mit Schlüsselwort.',
                'meta_title'        => 'Porta-Chiffre Online | Ciphers Online',
                'meta_description'  => 'Porta-Chiffre online nutzen: Schlüsselwort eingeben und lateinischen Text sofort ver- oder entschlüsseln.',
            ],
            'es' => [
                'name'              => 'Cifrado Porta',
                'name_short'        => 'Porta',
                'description'       => 'Cifra y descifra texto latino con el cifrado Porta usando una palabra clave y una tabla polialfabética recíproca.',
                'description_stort' => 'Cifrado Porta con palabra clave.',
                'meta_title'        => 'Cifrado Porta Online | Ciphers Online',
                'meta_description'  => 'Usa el cifrado Porta online: introduce una palabra clave y cifra o descifra texto latino al instante.',
            ],
            'fr' => [
                'name'              => 'Chiffre de Porta',
                'name_short'        => 'Porta',
                'description'       => 'Chiffrez et déchiffrez du texte latin avec le chiffre de Porta, un mot-clé et une table polyalphabétique réciproque.',
                'description_stort' => 'Chiffrement Porta avec mot-clé.',
                'meta_title'        => 'Chiffre de Porta en ligne | Ciphers Online',
                'meta_description'  => 'Utilisez le chiffre de Porta en ligne : saisissez un mot-clé et chiffrez ou déchiffrez du texte latin.',
            ],
            'it' => [
                'name'              => 'Cifrario Porta',
                'name_short'        => 'Porta',
                'description'       => 'Cifra e decifra testo latino con il cifrario Porta usando una parola chiave e una tabella polialfabetica reciproca.',
                'description_stort' => 'Cifratura Porta con parola chiave.',
                'meta_title'        => 'Cifrario Porta Online | Ciphers Online',
                'meta_description'  => 'Usa il cifrario Porta online: inserisci una parola chiave e cifra o decifra testo latino.',
            ],
            'pt' => [
                'name'              => 'Cifra Porta',
                'name_short'        => 'Porta',
                'description'       => 'Cifre e decifre texto latino com a cifra Porta usando uma palavra-chave e uma tabela polialfabética recíproca.',
                'description_stort' => 'Cifra Porta com palavra-chave.',
                'meta_title'        => 'Cifra Porta Online | Ciphers Online',
                'meta_description'  => 'Use a cifra Porta online: informe uma palavra-chave e cifre ou decifre texto latino instantaneamente.',
            ],
            'tr' => [
                'name'              => 'Porta Şifresi',
                'name_short'        => 'Porta',
                'description'       => 'Bir anahtar sözcük ve karşılıklı polialfabetik tablo kullanarak Latin metnini Porta şifresiyle şifreleyin veya çözün.',
                'description_stort' => 'Anahtar sözcükle Porta şifreleme ve çözme.',
                'meta_title'        => 'Porta Şifresi Online | Ciphers Online',
                'meta_description'  => 'Porta şifresini online kullanın: anahtar sözcük girin ve Latin metnini anında şifreleyin ya da çözün.',
            ],
        ];
    }

    /**
     * Возвращает базовые переводы блоков.
     *
     * @return array<int, array<string, array{title: string, text: string}>>
     */
    private function blockTranslations(): array
    {
        return [
            10 => [
                'en' => [
                    'title' => 'How the Porta Cipher works',
                    'text'  => '<p>The Porta Cipher is a reciprocal polyalphabetic substitution cipher. The keyword selects one of thirteen paired alphabets for each letter of the message.</p>',
                ],
                'ru' => [
                    'title' => 'Как работает шифр Porta',
                    'text'  => '<p>Шифр Porta — взаимный многоалфавитный шифр подстановки. Ключевое слово выбирает одну из тринадцати парных строк таблицы для каждой буквы сообщения.</p>',
                ],
                'de' => [
                    'title' => 'Wie die Porta-Chiffre funktioniert',
                    'text'  => '<p>Die Porta-Chiffre ist eine reziproke polyalphabetische Substitution. Das Schlüsselwort wählt für jeden Buchstaben eine von dreizehn Alphabetpaaren.</p>',
                ],
                'es' => [
                    'title' => 'Cómo funciona el cifrado Porta',
                    'text'  => '<p>El cifrado Porta es una sustitución polialfabética recíproca. La palabra clave selecciona una de trece parejas de alfabetos para cada letra.</p>',
                ],
                'fr' => [
                    'title' => 'Fonctionnement du chiffre de Porta',
                    'text'  => '<p>Le chiffre de Porta est une substitution polyalphabétique réciproque. Le mot-clé sélectionne l’une des treize paires d’alphabets pour chaque lettre.</p>',
                ],
                'it' => [
                    'title' => 'Come funziona il cifrario Porta',
                    'text'  => '<p>Il cifrario Porta è una sostituzione polialfabetica reciproca. La parola chiave seleziona una delle tredici coppie di alfabeti per ogni lettera.</p>',
                ],
                'pt' => [
                    'title' => 'Como a cifra Porta funciona',
                    'text'  => '<p>A cifra Porta é uma substituição polialfabética recíproca. A palavra-chave seleciona uma das treze duplas de alfabetos para cada letra.</p>',
                ],
                'tr' => [
                    'title' => 'Porta şifresi nasıl çalışır',
                    'text'  => '<p>Porta şifresi karşılıklı bir polialfabetik yerine koyma şifresidir. Anahtar sözcük her harf için on üç alfabe çiftinden birini seçer.</p>',
                ],
            ],
            20 => [
                'en' => [
                    'title' => 'When to use this tool',
                    'text'  => '<p>Use this page to test Porta encryption, decrypt a known Porta message, and compare the method with Vigenere or Beaufort-style classical ciphers.</p>',
                ],
                'ru' => [
                    'title' => 'Когда использовать инструмент',
                    'text'  => '<p>Используйте страницу, чтобы проверить шифрование Porta, расшифровать известное сообщение и сравнить метод с Виженером или Бофором.</p>',
                ],
                'de' => [
                    'title' => 'Wann dieses Werkzeug nützlich ist',
                    'text'  => '<p>Nutzen Sie die Seite, um Porta-Verschlüsselung zu testen, bekannte Porta-Nachrichten zu entschlüsseln und das Verfahren mit Vigenere oder Beaufort zu vergleichen.</p>',
                ],
                'es' => [
                    'title' => 'Cuándo usar esta herramienta',
                    'text'  => '<p>Usa esta página para probar Porta, descifrar un mensaje conocido y comparar el método con cifrados clásicos como Vigenere o Beaufort.</p>',
                ],
                'fr' => [
                    'title' => 'Quand utiliser cet outil',
                    'text'  => '<p>Utilisez cette page pour tester Porta, déchiffrer un message connu et comparer la méthode avec Vigenere ou Beaufort.</p>',
                ],
                'it' => [
                    'title' => 'Quando usare questo strumento',
                    'text'  => '<p>Usa questa pagina per provare Porta, decifrare un messaggio noto e confrontare il metodo con cifrari classici come Vigenere o Beaufort.</p>',
                ],
                'pt' => [
                    'title' => 'Quando usar esta ferramenta',
                    'text'  => '<p>Use esta página para testar Porta, decifrar uma mensagem conhecida e comparar o método com cifras clássicas como Vigenere ou Beaufort.</p>',
                ],
                'tr' => [
                    'title' => 'Bu araç ne zaman kullanılır',
                    'text'  => '<p>Porta şifrelemeyi denemek, bilinen bir Porta mesajını çözmek ve yöntemi Vigenere veya Beaufort ile karşılaştırmak için bu sayfayı kullanın.</p>',
                ],
            ],
        ];
    }

    /**
     * Возвращает базовые примеры.
     *
     * @return array<int, array{direction: string, translations: array<string, array{title: string, input: string, output: string, key: string, description: string}>}>
     */
    private function examples(): array
    {
        return [
            10 => [
                'direction' => 'encrypt',
                'translations' => $this->translatedExample('HELLO WORLD', 'OYTUB CHJUQ', 'PORTA', 'Porta example'),
            ],
            20 => [
                'direction' => 'encrypt',
                'translations' => $this->translatedExample('DEFEND THE EAST WALL', 'ZTTZLZ KWS ZPJK HOTN', 'SECRET', 'Military message'),
            ],
            30 => [
                'direction' => 'decrypt',
                'translations' => $this->translatedExample('OYTUB CHJUQ', 'HELLO WORLD', 'PORTA', 'Decode example'),
            ],
        ];
    }

    /**
     * Возвращает переводы одного примера.
     *
     * @return array<string, array{title: string, input: string, output: string, key: string, description: string}>
     */
    private function translatedExample(string $input, string $output, string $key, string $title): array
    {
        return array_fill_keys(['en', 'ru', 'de', 'es', 'fr', 'it', 'pt', 'tr'], [
            'title'       => $title,
            'input'       => $input,
            'output'      => $output,
            'key'         => $key,
            'description' => 'Basic Porta cipher example.',
        ]);
    }

    /**
     * Возвращает базовый FAQ.
     *
     * @return array<int, array<string, array{question: string, answer: string}>>
     */
    private function faqs(): array
    {
        return [
            10 => $this->translatedFaq(
                'What alphabet does Porta use?',
                'This basic implementation uses the historical Latin alphabet A-Z. Non-Latin characters are left unchanged.'
            ),
            20 => $this->translatedFaq(
                'Is encryption the same as decryption?',
                'Yes. Porta is reciprocal: applying the same table with the same keyword converts ciphertext back to plaintext.'
            ),
            30 => $this->translatedFaq(
                'What happens to spaces and punctuation?',
                'Spaces, punctuation, numbers, and unsupported characters are preserved in the output.'
            ),
        ];
    }

    /**
     * Возвращает переводы одного FAQ.
     *
     * @return array<string, array{question: string, answer: string}>
     */
    private function translatedFaq(string $question, string $answer): array
    {
        return array_fill_keys(['en', 'ru', 'de', 'es', 'fr', 'it', 'pt', 'tr'], [
            'question' => $question,
            'answer'   => $answer,
        ]);
    }
}
