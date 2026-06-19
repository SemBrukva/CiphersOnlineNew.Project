<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет базовые блоки, примеры и FAQ для шифра Autokey.
 */
class SeedAutokeyCipherBasicContent extends Migration
{
    /**
     * Создаёт или обновляет базовый контент страницы Autokey.
     */
    public function up(): void
    {
        $cipher = $this->findCipher();

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $now = date('Y-m-d H:i:s');

        $block1 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);

        foreach ($this->blockTranslations() as $language => $blocks) {
            $this->upsertBlockTranslation($block1, $language, $blocks[10]['title'], $blocks[10]['text'], $now);
            $this->upsertBlockTranslation($block2, $language, $blocks[20]['title'], $blocks[20]['text'], $now);
        }

        foreach ($this->examples() as $sortOrder => $example) {
            $exampleId = $this->upsertExample($cipherId, $sortOrder, $example['direction'], $now);

            foreach ($example['translations'] as $language => $translation) {
                $this->upsertExampleTranslation(
                    $exampleId,
                    $language,
                    $translation['title'],
                    $translation['input'],
                    $translation['output'],
                    $translation['key'],
                    $translation['alphabet'],
                    $translation['description'],
                    $now
                );
            }
        }

        foreach ($this->faqs() as $sortOrder => $faq) {
            $faqId = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, $sortOrder, $now, ['show_in_category' => 0]);

            foreach ($faq as $language => $translation) {
                $this->upsertFaqTranslation($faqId, $language, $translation['question'], $translation['answer'], $now);
            }
        }
    }

    /**
     * Удаляет базовый контент страницы Autokey.
     */
    public function down(): void
    {
        $cipher = $this->findCipher();

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];

        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ?', [$cipherId]);
    }

    /**
     * Находит запись шифра Autokey.
     *
     * @return array<string, mixed>|false
     */
    private function findCipher(): array|false
    {
        return $this->db->fetch(
            'SELECT c.id FROM ' . Tables::CIPHERS . ' c '
            . 'JOIN ' . Tables::CIPHER_CATEGORIES . ' cc ON cc.id = c.category_id '
            . 'WHERE cc.alias = ? AND c.alias = ? LIMIT 1',
            ['classical-ciphers', 'autokey']
        );
    }

    /**
     * Создаёт или обновляет родительскую контентную запись.
     *
     * @param array<string, int|string> $extra Дополнительные поля для обновления и вставки.
     */
    private function upsertParent(string $table, string $foreignKey, int $ownerId, int $sortOrder, string $now, array $extra = []): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND sort_order = ? LIMIT 1',
            [$ownerId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $assignments = ['published = 1', 'updated_at = ?'];
            $params = [$now];

            foreach ($extra as $column => $value) {
                $assignments[] = $column . ' = ?';
                $params[] = $value;
            }

            $params[] = $id;
            $this->db->execute(
                'UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = ?',
                $params
            );

            return $id;
        }

        $columns = [$foreignKey, 'sort_order', 'published'];
        $values = [$ownerId, $sortOrder, 1];

        foreach ($extra as $column => $value) {
            $columns[] = $column;
            $values[] = $value;
        }

        $columns[] = 'created_at';
        $columns[] = 'updated_at';
        $values[] = $now;
        $values[] = $now;

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        return (int) $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
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
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS
            . ' (block_id, language, title, text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$blockId, $language, $title, $text, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(
        int $exampleId,
        string $language,
        string $title,
        string $input,
        string $output,
        string $key,
        string $alphabet,
        string $description,
        string $now
    ): void {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($row !== false) {
            $columns = [
                'title' => $title,
                'input' => $input,
                'output' => $output,
                'description' => $description,
                'key' => $key,
            ];

            $this->updateById(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, (int) $row['id'], $columns, $now);

            return;
        }

        $columns = ['example_id', 'language', 'title', 'input', 'output', 'description', 'key'];
        $values = [$exampleId, $language, $title, $input, $output, $description, $key];

        $columns[] = 'created_at';
        $columns[] = 'updated_at';
        $values[] = $now;
        $values[] = $now;

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS
            . ' (' . implode(', ', $columns) . ') '
            . 'VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
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
        $values = [];

        foreach ($columns as $column => $value) {
            $assignments[] = $column . ' = ?';
            $values[] = $value;
        }

        $assignments[] = 'updated_at = ?';
        $values[] = $now;
        $values[] = $id;

        $this->db->execute(
            'UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = ?',
            $values
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
            'INSERT INTO ' . Tables::CIPHERS_FAQ_TRANSLATIONS
            . ' (faq_id, language, question, answer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$faqId, $language, $question, $answer, $now, $now]
        );
    }

    /**
     * Возвращает базовые переводы блоков.
     *
     * @return array<string, array<int, array{title: string, text: string}>>
     */
    private function blockTranslations(): array
    {
        return [
            'en' => [
                10 => ['title' => 'How the Autokey Cipher works', 'text' => '<p>The Autokey Cipher is a polyalphabetic substitution cipher. It starts with a short keyword, then continues the key stream with the message text itself.</p>'],
                20 => ['title' => 'When to use this tool', 'text' => '<p>Use this page to test Autokey encryption and decryption, compare it with Vigenere-style ciphers, and prepare examples for learning materials.</p>'],
            ],
            'ru' => [
                10 => ['title' => 'Как работает шифр Autokey', 'text' => '<p>Шифр Autokey — многоалфавитный шифр подстановки. Он начинается с короткого ключевого слова, а затем продолжает ключевой поток самим текстом сообщения.</p>'],
                20 => ['title' => 'Когда использовать этот инструмент', 'text' => '<p>Используйте страницу для проверки шифрования и расшифровки Autokey, сравнения с шифрами типа Виженера и подготовки учебных примеров.</p>'],
            ],
            'de' => [
                10 => ['title' => 'Wie die Autokey-Chiffre funktioniert', 'text' => '<p>Die Autokey-Chiffre ist eine polyalphabetische Substitution. Sie beginnt mit einem kurzen Schlüsselwort und setzt den Schlüsselstrom mit dem Nachrichtentext selbst fort.</p>'],
                20 => ['title' => 'Wann dieses Tool nützlich ist', 'text' => '<p>Nutzen Sie diese Seite zum Testen von Autokey-Ver- und Entschlüsselung, zum Vergleich mit Vigenere-ähnlichen Verfahren und für einfache Lernbeispiele.</p>'],
            ],
            'es' => [
                10 => ['title' => 'Cómo funciona el cifrado Autokey', 'text' => '<p>El cifrado Autokey es una sustitución polialfabética. Empieza con una palabra clave corta y después continúa el flujo de clave con el propio texto del mensaje.</p>'],
                20 => ['title' => 'Cuándo usar esta herramienta', 'text' => '<p>Usa esta página para probar el cifrado y descifrado Autokey, compararlo con cifrados tipo Vigenere y preparar ejemplos de aprendizaje.</p>'],
            ],
            'fr' => [
                10 => ['title' => 'Fonctionnement du chiffre Autokey', 'text' => '<p>Le chiffre Autokey est une substitution polyalphabétique. Il commence par un court mot-clé, puis poursuit le flux de clé avec le texte du message lui-même.</p>'],
                20 => ['title' => 'Quand utiliser cet outil', 'text' => '<p>Utilisez cette page pour tester le chiffrement et le déchiffrement Autokey, le comparer aux chiffres de type Vigenere et préparer des exemples pédagogiques.</p>'],
            ],
            'it' => [
                10 => ['title' => 'Come funziona il cifrario Autokey', 'text' => '<p>Il cifrario Autokey è una sostituzione polialfabetica. Inizia con una breve parola chiave e poi continua il flusso della chiave con il testo stesso del messaggio.</p>'],
                20 => ['title' => 'Quando usare questo strumento', 'text' => '<p>Usa questa pagina per testare cifratura e decifratura Autokey, confrontarla con cifrari simili a Vigenere e preparare esempi didattici.</p>'],
            ],
            'pt' => [
                10 => ['title' => 'Como a cifra Autokey funciona', 'text' => '<p>A cifra Autokey é uma substituição polialfabética. Ela começa com uma palavra-chave curta e depois continua o fluxo da chave com o próprio texto da mensagem.</p>'],
                20 => ['title' => 'Quando usar esta ferramenta', 'text' => '<p>Use esta página para testar cifragem e decifragem Autokey, comparar com cifras do tipo Vigenere e preparar exemplos de estudo.</p>'],
            ],
            'tr' => [
                10 => ['title' => 'Autokey Şifresi nasıl çalışır', 'text' => '<p>Autokey Şifresi çok alfabeli bir yerine koyma şifresidir. Kısa bir anahtar sözcükle başlar ve anahtar akışını ileti metninin kendisiyle sürdürür.</p>'],
                20 => ['title' => 'Bu araç ne zaman kullanılır', 'text' => '<p>Bu sayfayı Autokey şifreleme ve çözmeyi test etmek, Vigenere benzeri şifrelerle karşılaştırmak ve öğrenme örnekleri hazırlamak için kullanın.</p>'],
            ],
        ];
    }

    /**
     * Возвращает базовые примеры.
     *
     * @return array<int, array{direction: string, translations: array<string, array{title: string, input: string, output: string, key: string, alphabet: string, description: string}>}>
     */
    private function examples(): array
    {
        $localized = [
            'en' => [
                'alphabet' => 'en',
                'plain' => 'ATTACK AT DAWN',
                'cipher' => 'QNXEPV YT WTWP',
                'key' => 'QUEENLY',
                'short_plain' => 'HELLO',
                'short_cipher' => 'RIJSS',
                'short_key' => 'KEY',
            ],
            'ru' => [
                'alphabet' => 'ru',
                'plain' => 'привет',
                'cipher' => 'ъьжщфг',
                'key' => 'ключ',
                'short_plain' => 'мир',
                'short_cipher' => 'ччф',
                'short_key' => 'код',
            ],
            'de' => [
                'alphabet' => 'de',
                'plain' => 'uber',
                'cipher' => 'qbpü',
                'key' => 'wald',
                'short_plain' => 'tag',
                'short_cipher' => 'cee',
                'short_key' => 'key',
            ],
            'es' => [
                'alphabet' => 'es',
                'plain' => 'nino',
                'cipher' => 'fwxb',
                'key' => 'sol',
                'short_plain' => 'sol',
                'short_cipher' => 'djk',
                'short_key' => 'luz',
            ],
            'fr' => [
                'alphabet' => 'fr',
                'plain' => 'etre',
                'cipher' => 'pfâh',
                'key' => 'joie',
                'short_plain' => 'ami',
                'short_cipher' => 'càn',
                'short_key' => 'cle',
            ],
            'it' => [
                'alphabet' => 'it',
                'plain' => 'ciao',
                'cipher' => 'epio',
                'key' => 'chiave',
                'short_plain' => 'roma',
                'short_cipher' => 'tvua',
                'short_key' => 'chiave',
            ],
            'pt' => [
                'alphabet' => 'pt',
                'plain' => 'ola',
                'cipher' => 'gãl',
                'key' => 'sol',
                'short_plain' => 'casa',
                'short_cipher' => 'êhsv',
                'short_key' => 'chave',
            ],
            'tr' => [
                'alphabet' => 'tr',
                'plain' => 'guc',
                'cipher' => 'but',
                'key' => 'tas',
                'short_plain' => 'ada',
                'short_cipher' => 'kml',
                'short_key' => 'kilit',
            ],
        ];

        $examples = [
            10 => ['direction' => 'encrypt', 'translations' => []],
            20 => ['direction' => 'decrypt', 'translations' => []],
            30 => ['direction' => 'encrypt', 'translations' => []],
        ];

        foreach ($localized as $language => $data) {
            $examples[10]['translations'][$language] = [
                'title' => $language === 'ru' ? 'Шифрование текста Autokey' : 'Encrypt Autokey text',
                'input' => $data['plain'],
                'output' => $data['cipher'],
                'key' => $data['key'],
                'alphabet' => $data['alphabet'],
                'description' => $language === 'ru' ? 'Базовый пример шифрования для последующей проработки.' : 'Basic Autokey encryption example.',
            ];
            $examples[20]['translations'][$language] = [
                'title' => $language === 'ru' ? 'Расшифровка текста Autokey' : 'Decrypt Autokey text',
                'input' => $data['cipher'],
                'output' => $data['plain'],
                'key' => $data['key'],
                'alphabet' => $data['alphabet'],
                'description' => $language === 'ru' ? 'Базовый пример расшифровки с тем же начальным ключом.' : 'Basic Autokey decryption example with the same initial key.',
            ];
            $examples[30]['translations'][$language] = [
                'title' => $language === 'ru' ? 'Короткий пример Autokey' : 'Short Autokey example',
                'input' => $data['short_plain'],
                'output' => $data['short_cipher'],
                'key' => $data['short_key'],
                'alphabet' => $data['alphabet'],
                'description' => $language === 'ru' ? 'Короткий базовый пример для последующей проработки.' : 'Short baseline example for later refinement.',
            ];
        }

        return $examples;
    }

    /**
     * Возвращает базовые FAQ.
     *
     * @return array<int, array<string, array{question: string, answer: string}>>
     */
    private function faqs(): array
    {
        return [
            10 => $this->localizedFaq(
                'What is the Autokey Cipher?',
                'Что такое шифр Autokey?',
                'Autokey is a polyalphabetic cipher that uses an initial keyword and then extends the key stream with message text.',
                'Autokey — многоалфавитный шифр, который использует начальный ключ и затем продолжает ключевой поток текстом сообщения.'
            ),
            20 => $this->localizedFaq(
                'Is Autokey the same as Vigenere?',
                'Autokey — это то же самое, что Виженер?',
                'No. It is related to Vigenere, but the key stream is extended with message text instead of repeating the keyword.',
                'Нет. Он родственен Виженеру, но ключевой поток продолжается текстом сообщения, а не повторением ключевого слова.'
            ),
            30 => $this->localizedFaq(
                'Do I need the same initial key to decrypt?',
                'Нужен ли тот же начальный ключ для расшифровки?',
                'Yes. Decryption needs the same initial key and alphabet that were used for encryption.',
                'Да. Для расшифровки нужны тот же начальный ключ и тот же алфавит, которые использовались при шифровании.'
            ),
        ];
    }

    /**
     * Создаёт FAQ для всех локалей с базовыми ru/en текстами.
     *
     * @return array<string, array{question: string, answer: string}>
     */
    private function localizedFaq(string $englishQuestion, string $russianQuestion, string $englishAnswer, string $russianAnswer): array
    {
        $result = [];
        foreach (['en', 'de', 'es', 'fr', 'it', 'pt', 'tr'] as $language) {
            $result[$language] = ['question' => $englishQuestion, 'answer' => $englishAnswer];
        }

        $result['ru'] = ['question' => $russianQuestion, 'answer' => $russianAnswer];

        return $result;
    }
}
