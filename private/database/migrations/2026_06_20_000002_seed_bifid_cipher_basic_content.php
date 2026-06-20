<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет базовые блоки, примеры и FAQ для шифра Бифид.
 */
class SeedBifidCipherBasicContent extends Migration
{
    /**
     * Создаёт или обновляет базовый контент страницы Bifid.
     */
    public function up(): void
    {
        $cipher = $this->findCipher();

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $now      = date('Y-m-d H:i:s');

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
     * Удаляет базовый контент страницы Bifid.
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
     * Находит запись шифра Bifid.
     *
     * @return array<string, mixed>|false
     */
    private function findCipher(): array|false
    {
        return $this->db->fetch(
            'SELECT c.id FROM ' . Tables::CIPHERS . ' c '
            . 'JOIN ' . Tables::CIPHER_CATEGORIES . ' cc ON cc.id = c.category_id '
            . 'WHERE cc.alias = ? AND c.alias = ? LIMIT 1',
            ['classical-ciphers', 'bifid']
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
                'title'       => $title,
                'input'       => $input,
                'output'      => $output,
                'description' => $description,
                'key'         => $key,
            ];

            $this->updateById(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, (int) $row['id'], $columns, $now);

            return;
        }

        $columns = ['example_id', 'language', 'title', 'input', 'output', 'description', 'key'];
        $values  = [$exampleId, $language, $title, $input, $output, $description, $key];

        $columns[] = 'created_at';
        $columns[] = 'updated_at';
        $values[]  = $now;
        $values[]  = $now;

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
                10 => [
                    'title' => 'How the Bifid Cipher works',
                    'text'  => '<p>The Bifid Cipher is a classical fractionating transposition cipher invented by Félix Delastelle. It combines a Polybius square with coordinate splitting: each letter is converted to a row and column index, the indices are pooled and recombined, then converted back to letters.</p>',
                ],
                20 => [
                    'title' => 'When to use this tool',
                    'text'  => '<p>Use this page to encrypt and decrypt text with the Bifid cipher, test keyword configurations, and explore how fractionation strengthens simple substitution ciphers.</p>',
                ],
            ],
            'ru' => [
                10 => [
                    'title' => 'Как работает шифр Бифид',
                    'text'  => '<p>Шифр Бифид — классический фракционирующий шифр перестановки, изобретённый Феликсом Делестелем. Он сочетает квадрат Полибия с разбиением координат: каждая буква преобразуется в индекс строки и столбца, индексы объединяются в общий поток, а затем снова делятся на пары и возвращаются к буквам.</p>',
                ],
                20 => [
                    'title' => 'Когда использовать этот инструмент',
                    'text'  => '<p>Используйте страницу для шифрования и расшифровки текста шифром Бифид, проверки различных ключей и изучения того, как фракционирование усиливает простую замену.</p>',
                ],
            ],
            'de' => [
                10 => [
                    'title' => 'Wie die Bifid-Chiffre funktioniert',
                    'text'  => '<p>Die Bifid-Chiffre ist eine klassische fraktionierende Transpositions-Chiffre, erfunden von Félix Delastelle. Sie kombiniert ein Polybios-Quadrat mit Koordinatenaufteilung: Jeder Buchstabe wird in Zeilen- und Spaltenindex umgewandelt, die Indizes werden zusammengefasst und neu kombiniert, dann wieder zu Buchstaben zurückverwandelt.</p>',
                ],
                20 => [
                    'title' => 'Wann dieses Tool nützlich ist',
                    'text'  => '<p>Nutzen Sie diese Seite zum Ver- und Entschlüsseln von Texten mit der Bifid-Chiffre, zum Testen von Schlüsselwörtern und zum Erkunden, wie Fraktionierung einfache Substitution verstärkt.</p>',
                ],
            ],
            'es' => [
                10 => [
                    'title' => 'Cómo funciona el cifrado Bifid',
                    'text'  => '<p>El cifrado Bifid es un cifrado de transposición fraccionada clásico inventado por Félix Delastelle. Combina un cuadrado de Polibio con la división de coordenadas: cada letra se convierte en índice de fila y columna, los índices se agrupan y recombinan, luego se convierten de nuevo en letras.</p>',
                ],
                20 => [
                    'title' => 'Cuándo usar esta herramienta',
                    'text'  => '<p>Usa esta página para cifrar y descifrar texto con Bifid, probar configuraciones de palabras clave y explorar cómo el fraccionamiento refuerza la sustitución simple.</p>',
                ],
            ],
            'fr' => [
                10 => [
                    'title' => 'Fonctionnement du chiffre de Bifid',
                    'text'  => '<p>Le chiffre de Bifid est un chiffre de transposition fractionnelle classique inventé par Félix Delastelle. Il combine un carré de Polybe avec un fractionnement des coordonnées : chaque lettre est convertie en indice de ligne et de colonne, les indices sont regroupés et recombinés, puis reconvertis en lettres.</p>',
                ],
                20 => [
                    'title' => 'Quand utiliser cet outil',
                    'text'  => '<p>Utilisez cette page pour chiffrer et déchiffrer du texte avec Bifid, tester des mots-clés et explorer comment le fractionnement renforce la substitution simple.</p>',
                ],
            ],
            'it' => [
                10 => [
                    'title' => 'Come funziona il cifrario Bifid',
                    'text'  => '<p>Il cifrario Bifid è un cifrario di trasposizione frazionante classico inventato da Félix Delastelle. Combina un quadrato di Polibio con la suddivisione delle coordinate: ogni lettera viene convertita in indice di riga e colonna, gli indici vengono raggruppati e ricombinati, poi riconvertiti in lettere.</p>',
                ],
                20 => [
                    'title' => 'Quando usare questo strumento',
                    'text'  => '<p>Usa questa pagina per cifrare e decifrare testo con Bifid, testare parole chiave e scoprire come il frazionamento rafforza la semplice sostituzione.</p>',
                ],
            ],
            'pt' => [
                10 => [
                    'title' => 'Como a cifra Bifid funciona',
                    'text'  => '<p>A cifra Bifid é uma cifra de transposição fracionada clássica inventada por Félix Delastelle. Ela combina um quadrado de Polibio com a divisão de coordenadas: cada letra é convertida em índice de linha e coluna, os índices são agrupados e recombinados e depois convertidos de volta em letras.</p>',
                ],
                20 => [
                    'title' => 'Quando usar esta ferramenta',
                    'text'  => '<p>Use esta página para cifrar e decifrar texto com Bifid, testar configurações de palavras-chave e explorar como o fracionamento reforça a substituição simples.</p>',
                ],
            ],
            'tr' => [
                10 => [
                    'title' => 'Bifid Şifresi nasıl çalışır',
                    'text'  => '<p>Bifid Şifresi, Félix Delastelle tarafından icat edilmiş klasik bir fraksiyonlama transpozisyon şifresidir. Polybius karesini koordinat bölümlemesiyle birleştirir: her harf satır ve sütun indeksine dönüştürülür, indeksler bir araya getirilip yeniden birleştirilir, ardından tekrar harflere çevrilir.</p>',
                ],
                20 => [
                    'title' => 'Bu araç ne zaman kullanılır',
                    'text'  => '<p>Bu sayfayı Bifid ile metin şifrelemek ve çözmek, anahtar sözcük yapılandırmalarını test etmek ve fraksiyonlamanın basit yerine koymayı nasıl güçlendirdiğini keşfetmek için kullanın.</p>',
                ],
            ],
        ];
    }

    /**
     * Возвращает базовые примеры.
     *
     * Примеры 10 и 20 используют EN-алфавит (en/it), пример 30 — португальский алфавит (pt).
     * Значения cipher-текста вычислены с BifidCipherService.
     *
     * @return array<int, array{direction: string, translations: array<string, array{title: string, input: string, output: string, key: string, alphabet: string, description: string}>}>
     */
    private function examples(): array
    {
        $examples = [
            10 => ['direction' => 'encrypt', 'translations' => []],
            20 => ['direction' => 'decrypt', 'translations' => []],
            30 => ['direction' => 'encrypt', 'translations' => []],
        ];

        $titles = [
            'en' => ['enc' => 'Encrypt with Bifid',     'dec' => 'Decrypt with Bifid',     'it_enc' => 'Italian alphabet example'],
            'ru' => ['enc' => 'Шифрование Бифид',       'dec' => 'Расшифровка Бифид',      'it_enc' => 'Пример с итальянским алфавитом'],
            'de' => ['enc' => 'Bifid verschlüsseln',    'dec' => 'Bifid entschlüsseln',    'it_enc' => 'Beispiel mit italienischem Alphabet'],
            'es' => ['enc' => 'Cifrar con Bifid',       'dec' => 'Descifrar con Bifid',    'it_enc' => 'Ejemplo con alfabeto italiano'],
            'fr' => ['enc' => 'Chiffrer avec Bifid',    'dec' => 'Déchiffrer avec Bifid',  'it_enc' => 'Exemple avec alphabet italien'],
            'it' => ['enc' => 'Cifrare con Bifid',      'dec' => 'Decifrare con Bifid',    'it_enc' => 'Esempio con alfabeto italiano'],
            'pt' => ['enc' => 'Cifrar com Bifid',       'dec' => 'Decifrar com Bifid',     'it_enc' => 'Exemplo com alfabeto italiano'],
            'tr' => ['enc' => 'Bifid ile şifrele',      'dec' => 'Bifid ile çöz',          'it_enc' => 'İtalyan alfabesiyle örnek'],
        ];

        $desc = [
            'en' => ['enc' => 'Basic Bifid encryption example.', 'dec' => 'Decryption using the same keyword.', 'it_enc' => 'Basic example for later refinement.'],
            'ru' => ['enc' => 'Базовый пример шифрования Бифид.', 'dec' => 'Расшифровка с тем же ключевым словом.', 'it_enc' => 'Базовый пример для последующей проработки.'],
            'de' => ['enc' => 'Einfaches Bifid-Verschlüsselungsbeispiel.', 'dec' => 'Entschlüsselung mit demselben Schlüsselwort.', 'it_enc' => 'Einfaches Beispiel zur späteren Überarbeitung.'],
            'es' => ['enc' => 'Ejemplo básico de cifrado Bifid.', 'dec' => 'Descifrado con la misma palabra clave.', 'it_enc' => 'Ejemplo básico para refinamiento posterior.'],
            'fr' => ['enc' => 'Exemple de chiffrement Bifid de base.', 'dec' => 'Déchiffrement avec le même mot-clé.', 'it_enc' => 'Exemple de base à affiner ultérieurement.'],
            'it' => ['enc' => 'Esempio di cifratura Bifid di base.', 'dec' => 'Decifratura con la stessa parola chiave.', 'it_enc' => 'Esempio di base da affinare in seguito.'],
            'pt' => ['enc' => 'Exemplo básico de cifra Bifid.', 'dec' => 'Decifragem com a mesma palavra-chave.', 'it_enc' => 'Exemplo básico para refinamento posterior.'],
            'tr' => ['enc' => 'Temel Bifid şifreleme örneği.', 'dec' => 'Aynı anahtar sözcükle çözme.', 'it_enc' => 'Daha sonra iyileştirilecek temel örnek.'],
        ];

        foreach (array_keys($titles) as $language) {
            // Пример 10: шифрование HELLO+KEYWORD → FHYCZ (EN-алфавит)
            $examples[10]['translations'][$language] = [
                'title'       => $titles[$language]['enc'],
                'input'       => 'HELLO',
                'output'      => 'FHYCZ',
                'key'         => 'KEYWORD',
                'alphabet'    => 'en',
                'description' => $desc[$language]['enc'],
            ];

            // Пример 20: расшифровка FHYCZ+KEYWORD → HELLO (EN-алфавит)
            $examples[20]['translations'][$language] = [
                'title'       => $titles[$language]['dec'],
                'input'       => 'FHYCZ',
                'output'      => 'HELLO',
                'key'         => 'KEYWORD',
                'alphabet'    => 'en',
                'description' => $desc[$language]['dec'],
            ];

            // Пример 30: шифрование ATTACKATDAWN+PLAYFAIR → YNBYIXMWSMWE (EN-алфавит)
            $examples[30]['translations'][$language] = [
                'title'       => $titles[$language]['it_enc'],
                'input'       => 'ATTACK AT DAWN',
                'output'      => 'YNBYIXMWSMWE',
                'key'         => 'PLAYFAIR',
                'alphabet'    => 'en',
                'description' => $desc[$language]['it_enc'],
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
                'What is the Bifid Cipher?',
                'Что такое шифр Бифид?',
                'The Bifid Cipher is a classical fractionating cipher invented by Félix Delastelle. It uses a Polybius square and splits letter coordinates across the ciphertext to add transposition on top of substitution.',
                'Шифр Бифид — классический фракционирующий шифр, изобретённый Феликсом Делестелем. Он использует квадрат Полибия и разбивает координаты букв по шифротексту, добавляя перестановку поверх замены.'
            ),
            20 => $this->localizedFaq(
                'Why is J removed from the English grid?',
                'Почему J удаляется из английской таблицы?',
                'The English alphabet has 26 letters, which does not fit a perfect square. Removing J (and treating it as I) gives 25 letters — exactly a 5×5 grid.',
                '26 букв английского алфавита не укладываются в правильный квадрат. Удаление J (с заменой на I) даёт 25 букв — ровно сетку 5×5.'
            ),
            30 => $this->localizedFaq(
                'Do I need the same key to decrypt?',
                'Нужен ли тот же ключ для расшифровки?',
                'Yes. You must use the same keyword and the same alphabet that were used during encryption.',
                'Да. Для расшифровки необходимо использовать то же ключевое слово и тот же алфавит, что и при шифровании.'
            ),
        ];
    }

    /**
     * Создаёт FAQ для всех локалей с базовыми en/ru текстами.
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
