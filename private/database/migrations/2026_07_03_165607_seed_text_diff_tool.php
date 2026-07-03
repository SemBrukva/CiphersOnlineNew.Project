<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент «Сравнение текстов» (Text Diff) в категорию text-analysis.
 */
class SeedTextDiffTool extends Migration
{
    /**
     * Создаёт или обновляет запись инструмента, переводы и базовый контент.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['text-analysis']
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
     * Удаляет запись инструмента и связанные сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['text-diff']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS . ' WHERE id = ?', [$cipherId]);
    }

    /**
     * Создаёт или обновляет запись инструмента.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'text-diff']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'text-diff', 'client', 60, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['client', 60, 1, $now, $cipherId]
        );

        return $cipherId;
    }

    /**
     * Создаёт или обновляет перевод инструмента.
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
     * Заполняет блоки, FAQ и теги.
     */
    private function seedContent(int $cipherId, string $now): void
    {
        $block = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How the Text Diff tool works', '<p>Paste your original text on the left and the changed text on the right — the comparison runs instantly in your browser. Nothing is sent to a server.</p><p>The tool aligns the two texts line by line using the Myers difference algorithm (the same family of algorithms used by <code>git diff</code>). Added lines are highlighted green, removed lines red, and modified lines show the exact words or characters that changed.</p><p>Use the granularity control to switch highlighting between whole lines, words, or single characters, and toggle between a side-by-side and a unified inline view. Options let you ignore case, whitespace, empty lines, or sort lines before comparing.</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает инструмент сравнения текстов', '<p>Вставьте исходный текст слева, а изменённый — справа. Сравнение выполняется мгновенно прямо в браузере: ничего не отправляется на сервер.</p><p>Инструмент выравнивает два текста построчно по алгоритму различий Майерса (того же семейства, что используется в <code>git diff</code>). Добавленные строки подсвечиваются зелёным, удалённые — красным, а в изменённых строках выделяются конкретные изменившиеся слова или символы.</p><p>Переключатель детализации меняет уровень подсветки — строки, слова или отдельные символы, — а вид можно переключать между двумя колонками рядом и единой лентой. Опции позволяют игнорировать регистр, пробелы, пустые строки или отсортировать строки перед сравнением.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, $now);
        $this->upsertExampleTranslation(
            $example1,
            'en',
            'Paragraph edit',
            "The quick brown fox jumps over the lazy dog.\nThis sentence stays exactly the same.\nThis line will be deleted.",
            "The quick brown cat jumps over the lazy dog.\nThis sentence stays exactly the same.",
            'One word changed and a whole line removed — watch the word-level highlighting.',
            $now
        );
        $this->upsertExampleTranslation(
            $example1,
            'ru',
            'Правка абзаца',
            "The quick brown fox jumps over the lazy dog.\nThis sentence stays exactly the same.\nThis line will be deleted.",
            "The quick brown cat jumps over the lazy dog.\nThis sentence stays exactly the same.",
            'Изменено одно слово и удалена целая строка — обратите внимание на пословную подсветку.',
            $now
        );

        $example2 = $this->upsertExample($cipherId, 20, $now);
        $this->upsertExampleTranslation(
            $example2,
            'en',
            'Code change',
            "function sum(a, b) {\n  return a + b\n}",
            "function sum(a, b, c) {\n  return a + b + c\n}",
            'A new parameter added to a function — compare by character to spot each edit.',
            $now
        );
        $this->upsertExampleTranslation(
            $example2,
            'ru',
            'Изменение кода',
            "function sum(a, b) {\n  return a + b\n}",
            "function sum(a, b, c) {\n  return a + b + c\n}",
            'В функцию добавлен новый параметр — сравните по символам, чтобы увидеть каждую правку.',
            $now
        );

        $example3 = $this->upsertExample($cipherId, 30, $now);
        $this->upsertExampleTranslation(
            $example3,
            'en',
            'Version bump',
            "react: 18.2.0\nvite: 5.0.0\ntypescript: 5.3.3",
            "react: 18.3.1\nvite: 6.4.3\ntypescript: 5.3.3\nzod: 3.23.8",
            'Compare dependency versions line by line, including a newly added one.',
            $now
        );
        $this->upsertExampleTranslation(
            $example3,
            'ru',
            'Обновление версий',
            "react: 18.2.0\nvite: 5.0.0\ntypescript: 5.3.3",
            "react: 18.3.1\nvite: 6.4.3\ntypescript: 5.3.3\nzod: 3.23.8",
            'Сравнение версий зависимостей построчно, включая одну добавленную.',
            $now
        );

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'Is my text uploaded anywhere?', 'No. The entire comparison happens locally in your browser using JavaScript. Your text never leaves your device and is not sent to any server, which makes the tool safe for confidential documents, code, and configuration files.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Загружается ли мой текст куда-либо?', 'Нет. Всё сравнение выполняется локально в браузере на JavaScript. Ваш текст не покидает устройство и не отправляется ни на какой сервер, поэтому инструментом безопасно пользоваться для конфиденциальных документов, кода и конфигурационных файлов.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'What is the difference between word and character highlighting?', 'Both modes align the texts line by line. Word highlighting marks the individual words that changed inside a modified line — best for prose. Character highlighting marks the exact characters that differ — useful for short strings, identifiers, or code where a single symbol matters. The "line" option turns intra-line highlighting off and marks whole changed lines.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Чем отличается подсветка по словам и по символам?', 'Оба режима выравнивают тексты построчно. Подсветка по словам отмечает конкретные изменившиеся слова внутри изменённой строки — удобно для прозы. Подсветка по символам отмечает точные различающиеся символы — полезно для коротких строк, идентификаторов и кода, где важен каждый знак. Режим «строки» отключает внутристрочную подсветку и отмечает изменённые строки целиком.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'How is the similarity percentage calculated?', 'Similarity is based on the length of the longest common subsequence of characters between the two texts, expressed as 2 × common / (length A + length B). A value of 100% means the texts are identical; 0% means they share nothing in common.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Как считается процент сходства?', 'Сходство основано на длине наибольшей общей подпоследовательности символов двух текстов и вычисляется как 2 × общее / (длина A + длина B). Значение 100 % означает, что тексты идентичны; 0 % — что у них нет ничего общего.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Text comparison', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Сравнение текста', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Diff checker', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Diff-инструмент', $now);
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
     * Создаёт или обновляет пример (пара текстов A/B хранится в input/output перевода).
     */
    private function upsertExample(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET direction = ?, delimiter = ?, published = 1, updated_at = ? WHERE id = ?',
                ['encrypt', '', $now, (int) $row['id']]
            );

            return (int) $row['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, direction, delimiter, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, 'encrypt', '', $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод примера. input = исходный текст (A), output = изменённый (B).
     */
    private function upsertExampleTranslation(int $exampleId, string $language, string $title, string $input, string $output, string $description, string $now): void
    {
        $this->upsertTranslation(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, 'example_id', $exampleId, $language, [
            'title' => $title,
            'input' => $input,
            'output' => $output,
            'key' => '',
            'shift' => 0,
            'description' => $description,
        ], $now);
    }

    /**
     * Создаёт или обновляет перевод блока.
     */
    private function upsertBlockTranslation(int $blockId, string $language, string $title, string $text, string $now): void
    {
        $this->upsertTranslation(Tables::CIPHERS_BLOCKS_TRANSLATIONS, 'block_id', $blockId, $language, ['title' => $title, 'text' => $text], $now);
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
     * Возвращает переводы инструмента для всех поддерживаемых языков.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Text Diff / Compare',
                'name_short'        => 'Text Diff',
                'description'       => 'Compare two texts side by side and highlight every difference — added, removed and changed lines, words or characters. Fast, private and fully in your browser.',
                'description_stort' => 'Compare two texts and highlight the differences.',
                'meta_title'        => 'Text Diff — Compare Two Texts Online | Ciphers Online',
                'meta_description'  => 'Free online text diff tool. Compare two texts side by side, highlight added, removed and changed content by line, word or character. Private, client-side, instant.',
            ],
            'ru' => [
                'name'              => 'Сравнение текстов (Diff)',
                'name_short'        => 'Сравнение текстов',
                'description'       => 'Сравните два текста рядом и подсветите каждое различие — добавленные, удалённые и изменённые строки, слова или символы. Быстро, приватно и полностью в браузере.',
                'description_stort' => 'Сравнивает два текста и подсвечивает различия.',
                'meta_title'        => 'Сравнение текстов онлайн (Diff) | Ciphers Online',
                'meta_description'  => 'Бесплатный онлайн-инструмент сравнения текстов. Сравнивайте два текста рядом, подсвечивайте добавленное, удалённое и изменённое по строкам, словам или символам. Приватно, на клиенте, мгновенно.',
            ],
            'de' => [
                'name'              => 'Text-Vergleich (Diff)',
                'name_short'        => 'Text-Diff',
                'description'       => 'Vergleichen Sie zwei Texte nebeneinander und heben Sie jeden Unterschied hervor — hinzugefügte, entfernte und geänderte Zeilen, Wörter oder Zeichen. Schnell, privat und komplett im Browser.',
                'description_stort' => 'Vergleicht zwei Texte und hebt die Unterschiede hervor.',
                'meta_title'        => 'Text-Diff — Zwei Texte online vergleichen | Ciphers Online',
                'meta_description'  => 'Kostenloses Online-Tool zum Textvergleich. Vergleichen Sie zwei Texte nebeneinander, heben Sie Hinzugefügtes, Entferntes und Geändertes nach Zeile, Wort oder Zeichen hervor. Privat, clientseitig, sofort.',
            ],
            'es' => [
                'name'              => 'Comparar textos (Diff)',
                'name_short'        => 'Comparar textos',
                'description'       => 'Compara dos textos en paralelo y resalta cada diferencia: líneas, palabras o caracteres añadidos, eliminados y modificados. Rápido, privado y totalmente en el navegador.',
                'description_stort' => 'Compara dos textos y resalta las diferencias.',
                'meta_title'        => 'Comparar Textos Online (Diff) | Ciphers Online',
                'meta_description'  => 'Herramienta online gratuita para comparar textos. Compara dos textos en paralelo y resalta lo añadido, eliminado y modificado por línea, palabra o carácter. Privado, del lado del cliente, instantáneo.',
            ],
            'fr' => [
                'name'              => 'Comparateur de textes (Diff)',
                'name_short'        => 'Comparer textes',
                'description'       => 'Comparez deux textes côte à côte et mettez en évidence chaque différence — lignes, mots ou caractères ajoutés, supprimés et modifiés. Rapide, privé et entièrement dans le navigateur.',
                'description_stort' => 'Compare deux textes et met en évidence les différences.',
                'meta_title'        => 'Comparateur de Textes en Ligne (Diff) | Ciphers Online',
                'meta_description'  => 'Outil en ligne gratuit de comparaison de textes. Comparez deux textes côte à côte et mettez en évidence les ajouts, suppressions et modifications par ligne, mot ou caractère. Privé, côté client, instantané.',
            ],
            'it' => [
                'name'              => 'Confronto testi (Diff)',
                'name_short'        => 'Confronta testi',
                'description'       => 'Confronta due testi affiancati ed evidenzia ogni differenza — righe, parole o caratteri aggiunti, rimossi e modificati. Veloce, privato e interamente nel browser.',
                'description_stort' => 'Confronta due testi ed evidenzia le differenze.',
                'meta_title'        => 'Confronto Testi Online (Diff) | Ciphers Online',
                'meta_description'  => 'Strumento online gratuito per confrontare testi. Confronta due testi affiancati ed evidenzia aggiunte, rimozioni e modifiche per riga, parola o carattere. Privato, lato client, istantaneo.',
            ],
            'pt' => [
                'name'              => 'Comparar textos (Diff)',
                'name_short'        => 'Comparar textos',
                'description'       => 'Compare dois textos lado a lado e destaque cada diferença — linhas, palavras ou caracteres adicionados, removidos e alterados. Rápido, privado e totalmente no navegador.',
                'description_stort' => 'Compara dois textos e destaca as diferenças.',
                'meta_title'        => 'Comparar Textos Online (Diff) | Ciphers Online',
                'meta_description'  => 'Ferramenta online gratuita para comparar textos. Compare dois textos lado a lado e destaque o que foi adicionado, removido e alterado por linha, palavra ou caractere. Privado, no cliente, instantâneo.',
            ],
            'tr' => [
                'name'              => 'Metin Karşılaştırma (Diff)',
                'name_short'        => 'Metin Karşılaştır',
                'description'       => 'İki metni yan yana karşılaştırın ve her farkı vurgulayın — eklenen, silinen ve değiştirilen satırlar, sözcükler veya karakterler. Hızlı, gizli ve tamamen tarayıcıda.',
                'description_stort' => 'İki metni karşılaştırır ve farkları vurgular.',
                'meta_title'        => 'Metin Karşılaştırma Aracı (Diff) | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi metin karşılaştırma aracı. İki metni yan yana karşılaştırın; eklenen, silinen ve değiştirilen içeriği satır, sözcük veya karaktere göre vurgulayın. Gizli, istemci tarafında, anında.',
            ],
        ];
    }
}
