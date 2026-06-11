<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент частотного анализа в категорию «Анализ текста и криптоанализ».
 */
class SeedFrequencyAnalysisTool extends Migration
{
    /**
     * Создаёт инструмент, переводы, блоки, примеры и FAQ.
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
     * Удаляет инструмент и связанные сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['frequency-analysis']
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
            ['frequency-analysis']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 10, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'frequency-analysis', 'client', 10, 1, $now, $now]
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
     * Заполняет блоки, примеры и FAQ.
     */
    private function seedContent(int $cipherId, string $now): void
    {
        $block1 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $this->upsertBlockTranslation($block1, 'en', 'How frequency analysis works', '<p>Frequency analysis is the study of how often each character appears in a text. In any natural language, letters do not appear with equal frequency — some letters appear much more often than others. In English, the most common letters are E, T, A, O, I, N, S, H, and R.</p><p>By comparing the frequency distribution of an unknown text against the known distribution for a language, a cryptanalyst can identify substitutions and begin to decode classical ciphers such as Caesar, monoalphabetic, and Vigenère ciphers.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Как работает частотный анализ', '<p>Частотный анализ — это изучение того, как часто каждый символ встречается в тексте. В любом естественном языке буквы появляются с разной частотой: одни встречаются значительно чаще других. В русском языке наиболее частые буквы — О, Е, А, И, Н, Т, С, Р, В.</p><p>Сравнивая частотное распределение неизвестного текста с известным распределением для языка, криптоаналитик может выявить замены и начать расшифровку классических шифров — Цезаря, моноалфавитного и шифра Виженера.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Using frequency analysis to break ciphers', '<p>To use this tool for cryptanalysis, paste a ciphertext and observe which characters appear most frequently. Compare the top characters with the expected letter frequencies for the suspected language. For Caesar cipher, a consistent shift between the observed peaks and the expected peaks reveals the key.</p><p>For polyalphabetic ciphers like Vigenère, you first need to determine the key length using the Kasiski test or index of coincidence, and then apply frequency analysis to each subgroup of letters separately.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Применение частотного анализа для взлома шифров', '<p>Чтобы использовать этот инструмент для криптоанализа, вставьте зашифрованный текст и наблюдайте, какие символы встречаются чаще всего. Сравните первые символы с ожидаемыми частотами букв предполагаемого языка. Для шифра Цезаря постоянный сдвиг между наблюдаемыми пиками и ожидаемыми раскрывает ключ.</p><p>Для полиалфавитных шифров, таких как шифр Виженера, сначала нужно определить длину ключа с помощью теста Казиски или индекса совпадений, а затем применить частотный анализ к каждой подгруппе букв отдельно.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'English text analysis', 'The quick brown fox jumps over the lazy dog', '', '', 'This pangram contains every letter of the English alphabet at least once.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Анализ английского текста', 'The quick brown fox jumps over the lazy dog', '', '', 'Эта панграмма содержит каждую букву английского алфавита хотя бы один раз.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Caesar ciphertext', 'KHOOR ZRUOG', '', '', 'HELLO WORLD encoded with Caesar cipher (shift 3). K, H, U, O, Z, G dominate — shifted from H, E, L, W, O, D.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Зашифрованный шифром Цезаря', 'KHOOR ZRUOG', '', '', 'HELLO WORLD, зашифрованное шифром Цезаря (сдвиг 3). Доминируют K, H, U, O — это сдвинутые H, E, L, W.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Hamlet quote', 'To be or not to be that is the question', '', '', 'A famous English sentence for testing natural language letter distribution.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Цитата из Гамлета', 'To be or not to be that is the question', '', '', 'Известная английская фраза для проверки естественного распределения букв в тексте.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'What is frequency analysis?', 'Frequency analysis is a method of studying how often each character or letter appears in a text. It is one of the oldest and most powerful techniques in cryptanalysis, used to break classical substitution ciphers.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Что такое частотный анализ?', 'Частотный анализ — это метод изучения того, как часто каждый символ или буква встречается в тексте. Это один из старейших и наиболее мощных методов криптоанализа, используемый для взлома классических шифров замены.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Which ciphers can frequency analysis break?', 'Frequency analysis is effective against monoalphabetic substitution ciphers such as Caesar cipher and simple substitution ciphers. It is less effective against polyalphabetic ciphers like Vigenère and ineffective against modern ciphers such as AES.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Какие шифры можно взломать с помощью частотного анализа?', 'Частотный анализ эффективен против моноалфавитных шифров замены, таких как шифр Цезаря и простые шифры подстановки. Он менее эффективен против полиалфавитных шифров (шифр Виженера) и неэффективен против современных шифров, таких как AES.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'What are the most common letters in English?', 'In English, the most frequent letters in order are approximately: E, T, A, O, I, N, S, H, R, D, L, C, U, M, W, F, G, Y, P, B. The letter E accounts for roughly 12–13% of all letters in typical English text.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Какие буквы наиболее часто встречаются в английском?', 'В английском языке наиболее частые буквы приблизительно в порядке убывания: E, T, A, O, I, N, S, H, R, D, L, C, U, M, W, F, G, Y, P, B. Буква E составляет примерно 12–13% всех букв в типичном английском тексте.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'What is the index of coincidence?', 'The index of coincidence (IC) measures the probability that two randomly chosen letters from a text are the same. For English, the IC is approximately 0.065. For random text, it is approximately 0.038. IC helps distinguish monoalphabetic from polyalphabetic ciphers.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Что такое индекс совпадений?', 'Индекс совпадений (ИС) измеряет вероятность того, что две случайно выбранные буквы из текста совпадают. Для английского языка ИС составляет примерно 0,065, для случайного текста — примерно 0,038. ИС помогает отличить моноалфавитный шифр от полиалфавитного.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Frequency analysis', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Частотный анализ', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Cryptanalysis', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Криптоанализ', $now);
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

        $columns = array_map(static fn (string $field): string => '`' . $field . '`', [$foreignKey, 'language', ...array_keys($data), 'created_at', 'updated_at']);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            [$parentId, $language, ...array_values($data), $now, $now]
        );
    }

    /**
     * Возвращает переводы инструмента частотного анализа.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'             => 'Frequency Analysis',
                'name_short'       => 'Frequency Analysis',
                'description'      => 'Analyze the frequency of each character in a text. Used in cryptanalysis to break classical substitution ciphers by comparing letter distributions against known language patterns.',
                'description_stort' => 'Count letter and character frequencies to analyze text or break classical ciphers.',
                'meta_title'       => 'Frequency Analysis Tool | Text & Cipher Cryptanalysis',
                'meta_description' => 'Analyze character frequencies in any text online. Compare letter distributions to break Caesar, substitution, and other classical ciphers using frequency analysis.',
            ],
            'ru' => [
                'name'             => 'Частотный анализ',
                'name_short'       => 'Частотный анализ',
                'description'      => 'Анализируйте частоту каждого символа в тексте. Используется в криптоанализе для взлома классических шифров замены путём сравнения частотного распределения букв с известными языковыми паттернами.',
                'description_stort' => 'Подсчитайте частоты букв и символов для анализа текста или взлома классических шифров.',
                'meta_title'       => 'Частотный анализ текста онлайн | Криптоанализ',
                'meta_description' => 'Анализируйте частоты символов в любом тексте онлайн. Сравнивайте частотные распределения букв для взлома шифров Цезаря и других классических шифров.',
            ],
            'de' => [
                'name'             => 'Häufigkeitsanalyse',
                'name_short'       => 'Häufigkeitsanalyse',
                'description'      => 'Analysieren Sie die Häufigkeit jedes Zeichens in einem Text. Wird in der Kryptoanalyse verwendet, um klassische Substitutionschiffren zu knacken.',
                'description_stort' => 'Buchstaben- und Zeichenhäufigkeiten zählen zur Textanalyse oder zum Knacken klassischer Chiffren.',
                'meta_title'       => 'Häufigkeitsanalyse | Textanalyse & Kryptoanalyse',
                'meta_description' => 'Zeichenhäufigkeiten in beliebigen Texten online analysieren. Buchstabenverteilungen vergleichen, um klassische Chiffren zu knacken.',
            ],
            'es' => [
                'name'             => 'Análisis de frecuencias',
                'name_short'       => 'Análisis de frecuencias',
                'description'      => 'Analiza la frecuencia de cada carácter en un texto. Se usa en criptoanálisis para descifrar cifrados de sustitución clásicos comparando distribuciones de letras.',
                'description_stort' => 'Cuenta frecuencias de letras y caracteres para analizar texto o descifrar cifrados clásicos.',
                'meta_title'       => 'Análisis de frecuencias | Criptoanálisis de texto',
                'meta_description' => 'Analiza las frecuencias de caracteres en cualquier texto online. Compara distribuciones de letras para descifrar cifrados César y de sustitución.',
            ],
            'fr' => [
                'name'             => 'Analyse de fréquences',
                'name_short'       => 'Analyse de fréquences',
                'description'      => 'Analysez la fréquence de chaque caractère dans un texte. Utilisé en cryptanalyse pour casser les chiffres de substitution classiques.',
                'description_stort' => 'Comptez les fréquences de lettres et de caractères pour analyser un texte ou casser des chiffres classiques.',
                'meta_title'       => 'Analyse de fréquences | Cryptanalyse de texte',
                'meta_description' => 'Analysez les fréquences de caractères dans n\'importe quel texte en ligne. Comparez les distributions de lettres pour casser les chiffrements classiques.',
            ],
            'it' => [
                'name'             => 'Analisi delle frequenze',
                'name_short'       => 'Analisi delle frequenze',
                'description'      => 'Analizza la frequenza di ogni carattere in un testo. Utilizzato nella crittoanalisi per rompere i cifrari di sostituzione classici confrontando le distribuzioni delle lettere.',
                'description_stort' => 'Conta le frequenze di lettere e caratteri per analizzare un testo o rompere cifrari classici.',
                'meta_title'       => 'Analisi delle frequenze | Crittoanalisi del testo',
                'meta_description' => 'Analizza le frequenze dei caratteri in qualsiasi testo online. Confronta le distribuzioni delle lettere per rompere i cifrari classici.',
            ],
            'pt' => [
                'name'             => 'Análise de frequência',
                'name_short'       => 'Análise de frequência',
                'description'      => 'Analise a frequência de cada caractere em um texto. Usado em criptoanálise para quebrar cifras de substituição clássicas comparando distribuições de letras.',
                'description_stort' => 'Conte frequências de letras e caracteres para analisar texto ou quebrar cifras clássicas.',
                'meta_title'       => 'Análise de frequência | Criptoanálise de texto',
                'meta_description' => 'Analise frequências de caracteres em qualquer texto online. Compare distribuições de letras para quebrar cifras clássicas.',
            ],
            'tr' => [
                'name'             => 'Frekans analizi',
                'name_short'       => 'Frekans analizi',
                'description'      => 'Bir metindeki her karakterin frekansını analiz edin. Klasik şifreleri kırmak için kriptoanalizde kullanılır.',
                'description_stort' => 'Metin analizi veya klasik şifreleri kırmak için harf ve karakter frekanslarını sayın.',
                'meta_title'       => 'Frekans analizi | Metin kriptoanalizi',
                'meta_description' => 'Herhangi bir metindeki karakter frekanslarını çevrimiçi analiz edin. Klasik şifreleri kırmak için harf dağılımlarını karşılaştırın.',
            ],
        ];
    }
}
