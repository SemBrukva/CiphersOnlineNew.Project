<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент «Anagram Solver» в категорию «Коды и алфавиты».
 */
class SeedAnagramSolver extends Migration
{
    /**
     * Создаёт инструмент, переводы, блоки, примеры и FAQ.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['codes-and-alphabets']
        );

        if ($category === false) {
            return;
        }

        $now      = date('Y-m-d H:i:s');
        $cipherId = $this->upsertCipher((int) $category['id'], $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertCipherTranslation($cipherId, $language, $translation, $now);
        }

        $this->seedContent($cipherId, $now);
    }

    /**
     * Удаляет инструмент и связанные сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['anagram-solver']
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
     * Создаёт или обновляет запись инструмента.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['anagram-solver']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'api', 90, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'anagram-solver', 'api', 90, 1, $now, $now]
        );
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
     * Заполняет блоки, примеры, FAQ и теги.
     */
    private function seedContent(int $cipherId, string $now): void
    {
        $block1 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $this->upsertBlockTranslation($block1, 'en', 'How the anagram solver works', '<p>An anagram is a rearrangement of all letters of a word or phrase to form another valid word or phrase. This tool uses curated dictionaries for eight languages (English, Russian, Spanish, French, German, Italian, Portuguese, Turkish) to find anagrams instantly.</p><p>The solver supports four modes: <strong>Anagram</strong> finds all words using exactly the same letters, <strong>Word Finder</strong> returns every word that can be built from a subset of letters (Scrabble-style), <strong>Pattern</strong> matches words against fixed letters and <code>?</code> wildcards, and <strong>Multi-word</strong> splits letters across 2–3 dictionary words.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Как работает поиск анаграмм', '<p>Анаграмма — это перестановка всех букв слова или фразы, образующая другое осмысленное слово или фразу. Этот инструмент использует подобранные словари для восьми языков (английский, русский, испанский, французский, немецкий, итальянский, португальский, турецкий) и мгновенно находит анаграммы.</p><p>Поддерживаются четыре режима: <strong>Анаграмма</strong> — все слова из тех же букв; <strong>Word Finder</strong> — слова из подмножества букв (Scrabble-стиль); <strong>Шаблон</strong> — совпадение с фиксированными буквами и подстановочными <code>?</code>; <strong>Многословный</strong> — разбиение букв на 2–3 словарных слова.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'When to use this tool', '<p>Use the anagram solver for word puzzles, Scrabble or Words With Friends moves, crossword hints, name generation, and cryptography practice. Each result shows the word length and Scrabble score in the selected language so you can pick the highest-scoring play at a glance.</p><p>For longer inputs (10+ letters) prefer Multi-word mode — it finds phrases like <code>listen</code> = <code>silent ten</code>.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Когда использовать инструмент', '<p>Используйте поиск анаграмм для словесных головоломок, ходов в Scrabble или «Эрудит», подсказок к кроссвордам, генерации имён и тренировки криптографических навыков. Каждый результат показывает длину слова и Scrabble-балл для выбранного языка, чтобы сразу видеть самый выгодный вариант.</p><p>Для длинных входов (10+ букв) удобнее режим «Многословный» — он находит фразы вроде <code>listen</code> = <code>silent ten</code>.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Classic: LISTEN', 'listen', '', '', 'Anagram mode finds SILENT, TINSEL, ENLIST.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Классика: LISTEN', 'listen', '', '', 'Режим Anagram находит SILENT, TINSEL, ENLIST.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Scrabble rack: CIPHER', 'cipher', '', '', 'Word Finder mode returns CIPHER, PRICE, RICH, HIRE, PIER and more.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Scrabble: CIPHER', 'cipher', '', '', 'Режим Word Finder возвращает CIPHER, PRICE, RICH, HIRE, PIER и другие.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Crossword: h?ll?', 'h?ll?', '', '', 'Pattern mode finds HELLO, HOLLY, HALLO.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Кроссворд: h?ll?', 'h?ll?', '', '', 'Режим Шаблон находит HELLO, HOLLY, HALLO.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'What is the difference between Anagram and Word Finder mode?', 'Anagram mode returns only words that use <em>every</em> letter of the input. Word Finder returns every dictionary word that can be built from any <em>subset</em> of the input letters — the Scrabble-rack mode. Use Anagram for puzzles like "rearrange these letters into a word" and Word Finder when you want all playable options.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Чем режим Anagram отличается от Word Finder?', 'Режим Anagram возвращает только слова, использующие <em>все</em> буквы входа. Word Finder возвращает любое словарное слово, которое можно составить из <em>подмножества</em> букв — стиль Scrabble-стойки. Используйте Anagram для задач «переставьте буквы в слово», а Word Finder — когда нужны все возможные ходы.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Which languages are supported?', 'Eight languages: English, Russian, Spanish, French, German, Italian, Portuguese, and Turkish. Each dictionary is built from open Hunspell wordlists with affix expansion, then indexed by signature for instant lookup. Scrabble scores are language-specific and follow the official national rules.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Какие языки поддерживаются?', 'Восемь языков: английский, русский, испанский, французский, немецкий, итальянский, португальский и турецкий. Каждый словарь собран из открытых Hunspell-словарей с раскрытием аффиксов и проиндексирован по сигнатуре для мгновенного поиска. Очки Scrabble соответствуют национальным правилам каждого языка.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'How does Multi-word mode handle long inputs?', 'Multi-word splits the input letters across 2–3 dictionary words, then deduplicates by sorted phrase. Results are capped at a hard limit to keep response times under a second; raise the minimum word length filter to reduce noisy two-letter combinations.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Как Многословный режим работает с длинными входами?', 'Режим разбивает буквы на 2–3 словарных слова и убирает дубликаты по отсортированной фразе. Результаты ограничены жёстким лимитом, чтобы ответ укладывался в секунду; увеличьте минимальную длину слова, чтобы убрать шум из двухбуквенных комбинаций.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Anagram', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Анаграмма', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Word puzzle', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Словесная головоломка', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Scrabble', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Scrabble', $now);
    }

    /**
     * Создаёт или обновляет родительскую запись (блок, пример, FAQ, тег).
     *
     * @param array<string, int|string> $extra
     */
    private function upsertParent(string $table, string $foreignKey, int $cipherId, int $sortOrder, string $now, array $extra = []): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $assignments = ['published = 1', 'updated_at = ?'];
            $values      = [$now];
            foreach ($extra as $field => $value) {
                $assignments[] = $field . ' = ?';
                $values[]      = $value;
            }
            $values[] = (int) $row['id'];
            $this->db->execute('UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = ?', $values);

            return (int) $row['id'];
        }

        $columns      = [$foreignKey, 'sort_order', 'published', 'created_at', 'updated_at', ...array_keys($extra)];
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
            'title'       => $title,
            'input'       => $input,
            'output'      => $output,
            'key'         => $key,
            'shift'       => 0,
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

        $columns      = array_map(static fn (string $field): string => '`' . $field . '`', [$foreignKey, 'language', ...array_keys($data), 'created_at', 'updated_at']);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            [$parentId, $language, ...array_values($data), $now, $now]
        );
    }

    /**
     * Возвращает переводы инструмента поиска анаграмм.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Anagram Solver',
                'name_short'        => 'Anagram Solver',
                'description'       => 'Find anagrams, Scrabble plays, crossword patterns, and multi-word rearrangements across eight languages. Built on curated dictionaries with Scrabble scoring.',
                'description_stort' => 'Multilingual anagram solver with word finder, pattern, and multi-word modes.',
                'meta_title'        => 'Anagram Solver Online | 8 languages, Scrabble, patterns',
                'meta_description'  => 'Solve anagrams, find Scrabble words, match crossword patterns, and split letters into phrases. Eight languages supported: en, ru, es, fr, de, it, pt, tr.',
            ],
            'ru' => [
                'name'              => 'Поиск анаграмм',
                'name_short'        => 'Анаграммы',
                'description'       => 'Находите анаграммы, слова для Scrabble, шаблоны кроссвордов и многословные перестановки на восьми языках. На основе подобранных словарей с подсчётом Scrabble-баллов.',
                'description_stort' => 'Многоязычный поиск анаграмм с режимами word finder, шаблона и многословных перестановок.',
                'meta_title'        => 'Поиск анаграмм онлайн | 8 языков, Scrabble, шаблоны',
                'meta_description'  => 'Решайте анаграммы, ищите слова для Scrabble, подбирайте шаблоны для кроссвордов и разбивайте буквы на фразы. Восемь языков: en, ru, es, fr, de, it, pt, tr.',
            ],
            'de' => [
                'name'              => 'Anagramm-Löser',
                'name_short'        => 'Anagramm-Löser',
                'description'       => 'Findet Anagramme, Scrabble-Wörter, Kreuzwort-Muster und mehrteilige Wortumstellungen in acht Sprachen. Basierend auf kuratierten Wörterbüchern mit Scrabble-Punkten.',
                'description_stort' => 'Mehrsprachiger Anagramm-Löser mit Wortfinder, Muster- und Mehrwortmodus.',
                'meta_title'        => 'Anagramm-Löser online | 8 Sprachen, Scrabble, Muster',
                'meta_description'  => 'Anagramme lösen, Scrabble-Wörter finden, Kreuzwort-Muster abgleichen, Buchstaben zu Phrasen zerlegen. Acht Sprachen: en, ru, es, fr, de, it, pt, tr.',
            ],
            'es' => [
                'name'              => 'Buscador de anagramas',
                'name_short'        => 'Anagramas',
                'description'       => 'Encuentra anagramas, jugadas de Scrabble, patrones de crucigrama y reordenamientos de varias palabras en ocho idiomas. Con diccionarios curados y puntuación Scrabble.',
                'description_stort' => 'Buscador multilingüe de anagramas con modos word finder, patrón y multi-palabra.',
                'meta_title'        => 'Buscador de anagramas online | 8 idiomas, Scrabble',
                'meta_description'  => 'Resuelve anagramas, busca palabras para Scrabble, hace coincidir patrones de crucigrama y divide letras en frases. Ocho idiomas: en, ru, es, fr, de, it, pt, tr.',
            ],
            'fr' => [
                'name'              => 'Solveur d\'anagrammes',
                'name_short'        => 'Anagrammes',
                'description'       => 'Trouvez des anagrammes, des coups au Scrabble, des motifs de mots croisés et des permutations multi-mots dans huit langues. Dictionnaires soignés et score Scrabble.',
                'description_stort' => 'Solveur d\'anagrammes multilingue avec modes word finder, motif et multi-mots.',
                'meta_title'        => 'Solveur d\'anagrammes en ligne | 8 langues, Scrabble',
                'meta_description'  => 'Résolvez des anagrammes, trouvez des mots Scrabble, faites correspondre des motifs et divisez les lettres en phrases. Huit langues : en, ru, es, fr, de, it, pt, tr.',
            ],
            'it' => [
                'name'              => 'Risolutore di anagrammi',
                'name_short'        => 'Anagrammi',
                'description'       => 'Trova anagrammi, mosse di Scrabble, schemi di cruciverba e permutazioni multi-parola in otto lingue. Dizionari curati con punteggio Scrabble.',
                'description_stort' => 'Risolutore di anagrammi multilingue con modalità word finder, pattern e multi-parola.',
                'meta_title'        => 'Risolutore di anagrammi online | 8 lingue, Scrabble',
                'meta_description'  => 'Risolvi anagrammi, trova parole per Scrabble, abbina schemi di cruciverba e dividi lettere in frasi. Otto lingue: en, ru, es, fr, de, it, pt, tr.',
            ],
            'pt' => [
                'name'              => 'Solucionador de anagramas',
                'name_short'        => 'Anagramas',
                'description'       => 'Encontre anagramas, jogadas de Scrabble, padrões de palavras cruzadas e rearranjos de várias palavras em oito idiomas. Dicionários curados e pontuação Scrabble.',
                'description_stort' => 'Solucionador multilíngue de anagramas com modos word finder, padrão e multi-palavra.',
                'meta_title'        => 'Solucionador de anagramas online | 8 idiomas, Scrabble',
                'meta_description'  => 'Resolva anagramas, encontre palavras para Scrabble, combine padrões de palavras cruzadas e divida letras em frases. Oito idiomas: en, ru, es, fr, de, it, pt, tr.',
            ],
            'tr' => [
                'name'              => 'Anagram çözücü',
                'name_short'        => 'Anagram',
                'description'       => 'Sekiz dilde anagram, Scrabble hamlesi, bulmaca deseni ve çok kelimeli düzenleme bulun. Özenle hazırlanmış sözlükler ve Scrabble puanı.',
                'description_stort' => 'Word finder, desen ve çok kelimeli modlarıyla çok dilli anagram çözücü.',
                'meta_title'        => 'Anagram çözücü çevrimiçi | 8 dil, Scrabble',
                'meta_description'  => 'Anagram çöz, Scrabble kelimesi bul, bulmaca desenine uygun kelimeler ara ve harfleri ifadelere böl. Sekiz dil: en, ru, es, fr, de, it, pt, tr.',
            ],
        ];
    }
}
