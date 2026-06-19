<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент «Vigenere Cracker» в категорию «Анализ текста и криптоанализ».
 */
class SeedVigenereCrackerTool extends Migration
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
            ['vigenere-cracker']
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
            ['vigenere-cracker']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'api', 30, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'vigenere-cracker', 'api', 30, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'How Vigenère cracker works', '<p>The Vigenère cracker uses two classical cryptanalysis techniques to automatically recover the key without any prior knowledge.</p><p><strong>Step 1 — Key length detection (Kasiski / Index of Coincidence):</strong> The tool computes the average Index of Coincidence (IC) for each candidate key length. A Vigenère cipher with key length <em>k</em> splits into <em>k</em> independent Caesar ciphers, each with IC close to the natural IC of the language. The tool picks the length whose average IC is highest.</p><p><strong>Step 2 — Key recovery (χ² frequency analysis):</strong> For each column (letters at positions 0, k, 2k, …), the tool tries all possible shifts and picks the shift that makes the column\'s letter frequencies best match the expected frequencies of the target language. This gives one key letter per column.</p><p>A length penalty (√len) prevents longer multiples of the true key from outscoring the correct one. The top-5 candidates are ranked by confidence and presented with individual decryptions.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Как работает взломщик Виженера', '<p>Взломщик Виженера использует два классических метода криптоанализа для автоматического восстановления ключа без каких-либо предварительных знаний.</p><p><strong>Шаг 1 — определение длины ключа (индекс совпадений):</strong> Инструмент вычисляет средний индекс совпадений (ИС) для каждой возможной длины ключа. Шифр Виженера с ключом длиной <em>k</em> распадается на <em>k</em> независимых шифров Цезаря, каждый с ИС, близким к естественному для данного языка. Выбирается длина с наибольшим средним ИС.</p><p><strong>Шаг 2 — восстановление ключа (χ²-анализ частот):</strong> Для каждого столбца (буквы на позициях 0, k, 2k, …) инструмент перебирает все возможные сдвиги и выбирает тот, при котором распределение букв столбца наилучшим образом совпадает с ожидаемыми частотами целевого языка. Это даёт по одной букве ключа для каждого столбца.</p><p>Штраф за длину (√длина) предотвращает победу кратных длин над истинной. Топ-5 кандидатов ранжируются по уверенности и отображаются вместе с соответствующими дешифровками.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'When to use the Vigenère cracker', '<p>Use this tool whenever you have a text encrypted with the Vigenère cipher and you do not know the key. The cracker works best with at least 30–50 letters — shorter texts produce unreliable results because frequency analysis requires a statistically significant sample.</p><p>The tool supports multiple alphabets (English, Russian, German, Spanish, French, Italian, Portuguese, Turkish) and can auto-detect the language from the ciphertext. It is commonly used in CTF competitions, classical cryptography courses, and historical cipher analysis.</p><p>Keep in mind that the Vigenère cracker assumes monoalphabetic columns, so it will not work on Beaufort, Autokey, or other Vigenère variants.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Когда использовать взломщик Виженера', '<p>Используйте этот инструмент, когда у вас есть текст, зашифрованный шифром Виженера, а ключ неизвестен. Взломщик работает лучше всего при наличии не менее 30–50 букв — на более коротких текстах результаты будут ненадёжными, поскольку частотный анализ требует статистически значимой выборки.</p><p>Инструмент поддерживает несколько алфавитов (английский, русский, немецкий, испанский, французский, итальянский, португальский, турецкий) и может автоматически определить язык шифртекста. Широко применяется в CTF-соревнованиях, классической криптографии и анализе исторических шифров.</p><p>Имейте в виду, что взломщик предполагает моноалфавитные столбцы, поэтому он не подойдёт для шифра Бофора, автоключа или других вариантов Виженера.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Key: KEY', 'SX UKW RRI ZOWR YJ RSQCC MR GEQ DLC GSPCX MP XGWIQ SX UKW RRI YQI MP AGCHMW MR GEQ DLC KKC YJ DYSJSWFXIQC MR GEQ DLC OTMML MP FCVMCP MR GEQ DLC OTMML MP MLMVCNYJSXW', '', '', 'Key KEY (3 letters), Dickens "A Tale of Two Cities" — 129 letters. The cracker will detect key length 3 and recover KEY.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Ключ: KEY', 'SX UKW RRI ZOWR YJ RSQCC MR GEQ DLC GSPCX MP XGWIQ SX UKW RRI YQI MP AGCHMW MR GEQ DLC KKC YJ DYSJSWFXIQC MR GEQ DLC OTMML MP FCVMCP MR GEQ DLC OTMML MP MLMVCNYJSXW', '', '', 'Ключ KEY (3 буквы), Диккенс «Повесть о двух городах» — 129 букв. Взломщик определит длину ключа 3 и восстановит KEY.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Key: LEMON', 'QSGF FNSDS NYH ESIPR KSNCW MUB ZYD TNELQFF MVAITSX RCEEL AB GSME QBYXUBRYX M BRH RMHVZR OCANIUJRO MZ ZVMIDHL LRP RROMOOGPH FC GSI BFBASEWGTSZ HULX MZY XIZ OEP GDSNEIP SDFEX', '', '', 'Key LEMON (5 letters), Gettysburg Address — 143 letters. The cracker will detect key length 5 and recover LEMON.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Ключ: LEMON', 'QSGF FNSDS NYH ESIPR KSNCW MUB ZYD TNELQFF MVAITSX RCEEL AB GSME QBYXUBRYX M BRH RMHVZR OCANIUJRO MZ ZVMIDHL LRP RROMOOGPH FC GSI BFBASEWGTSZ HULX MZY XIZ OEP GDSNEIP SDFEX', '', '', 'Ключ LEMON (5 букв), Геттисбергская речь — 143 буквы. Взломщик определит длину ключа 5 и восстановит LEMON.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Key: SECRET', 'LS DV SK FSV KS UW XJRX BK XJV UNWWVZSG OLGKLXJ XKJ RHTPGI MG LLG DMGV XQ JYYXIT KLX KPKEKL SRF RVKGAU FJ HMXTRKXGYU WSKLYPV SK LS VROX SVOJ EZSMPJX T KIC FJ MJSWSPXK', '', '', 'Key SECRET (6 letters), Shakespeare "Hamlet" — 133 letters. The cracker will detect key length 6 and recover SECRET.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Ключ: SECRET', 'LS DV SK FSV KS UW XJRX BK XJV UNWWVZSG OLGKLXJ XKJ RHTPGI MG LLG DMGV XQ JYYXIT KLX KPKEKL SRF RVKGAU FJ HMXTRKXGYU WSKLYPV SK LS VROX SVOJ EZSMPJX T KIC FJ MJSWSPXK', '', '', 'Ключ SECRET (6 букв), Шекспир «Гамлет» — 133 буквы. Взломщик определит длину ключа 6 и восстановит SECRET.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'How much ciphertext is needed for reliable cracking?', 'At least 30–50 letters are recommended for reliable results. The tool ensures at least 10 letters per key column for the chi-squared analysis to work well. Shorter texts may still produce a result, but it will be marked as unreliable. For key lengths above 5–6, having 100+ letters greatly improves accuracy.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Сколько шифртекста нужно для надёжного взлома?', 'Рекомендуется не менее 30–50 букв. Инструмент обеспечивает не менее 10 букв на столбец ключа для корректной работы χ²-анализа. На более коротких текстах результат всё равно выдаётся, но помечается как ненадёжный. Для длин ключа свыше 5–6 наличие 100+ букв значительно повышает точность.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'What is the Index of Coincidence?', 'The Index of Coincidence (IC) measures how often two randomly chosen letters from a text are the same. Natural English text has an IC around 0.065, while a perfectly random text has an IC around 0.038. Vigenère ciphertext with key length k has an average IC close to the natural language IC when the text is split into k columns — this property is used to determine the key length.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Что такое индекс совпадений?', 'Индекс совпадений (ИС) измеряет, как часто два случайно выбранных символа текста совпадают. Естественный английский текст имеет ИС около 0,065, а совершенно случайный текст — около 0,038. Шифртекст Виженера с ключом длиной k имеет средний ИС, близкий к естественному, при разбиении текста на k столбцов — это свойство используется для определения длины ключа.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Why might the cracker return the wrong key?', 'Several factors can reduce accuracy: very short ciphertext (fewer than 30 letters), non-standard text (proper names, abbreviations, numbers), mixed-language content, or keys longer than 20 characters. Also, Vigenère variants like Beaufort or Autokey use different encryption rules and will not be cracked correctly by this tool. If the top result looks wrong, try the other candidates in the table.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Почему взломщик может вернуть неправильный ключ?', 'Несколько факторов могут снизить точность: очень короткий шифртекст (менее 30 букв), нестандартный текст (имена собственные, аббревиатуры, числа), смешанный язык или ключи длиннее 20 символов. Также варианты Виженера — такие как шифр Бофора или автоключ — используют другие правила шифрования и не будут корректно взломаны этим инструментом. Если лучший результат выглядит неправильно, попробуйте другие кандидаты из таблицы.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Vigenère cipher', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Шифр Виженера', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Frequency analysis', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Частотный анализ', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Cryptanalysis', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Криптоанализ', $now);
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
     * Возвращает переводы инструмента взлома шифра Виженера.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Vigenère Cracker',
                'name_short'        => 'Vigenère Cracker',
                'description'       => 'Automatically crack the Vigenère cipher without knowing the key. Uses Index of Coincidence to detect key length and chi-squared frequency analysis to recover every key letter. Supports English, Russian, and 6 more alphabets.',
                'description_stort' => 'Crack Vigenère cipher automatically using frequency analysis — no key needed.',
                'meta_title'        => 'Vigenère Cipher Cracker | Automatic Key Recovery Online',
                'meta_description'  => 'Crack the Vigenère cipher online without knowing the key. Automatic key length detection via Index of Coincidence and chi-squared frequency analysis. Supports 8 languages.',
            ],
            'ru' => [
                'name'              => 'Взломщик Виженера',
                'name_short'        => 'Взломщик Виженера',
                'description'       => 'Автоматически взламывайте шифр Виженера без знания ключа. Использует индекс совпадений для определения длины ключа и χ²-анализ частот для восстановления каждой буквы ключа. Поддерживает русский, английский и ещё 6 алфавитов.',
                'description_stort' => 'Взломайте шифр Виженера автоматически с помощью частотного анализа — ключ не нужен.',
                'meta_title'        => 'Взломщик шифра Виженера | Автовосстановление ключа онлайн',
                'meta_description'  => 'Взламывайте шифр Виженера онлайн без знания ключа. Автоматическое определение длины ключа через индекс совпадений и χ²-анализ частот. Поддержка 8 языков.',
            ],
            'de' => [
                'name'              => 'Vigenère-Knacker',
                'name_short'        => 'Vigenère-Knacker',
                'description'       => 'Knacken Sie die Vigenère-Chiffre automatisch ohne den Schlüssel zu kennen. Nutzt den Koinzidenzindex zur Schlüssellängenerkennung und Chi-Quadrat-Häufigkeitsanalyse zur Schlüsselrückgewinnung. Unterstützt 8 Alphabete.',
                'description_stort' => 'Vigenère-Chiffre automatisch per Häufigkeitsanalyse knacken — ohne Schlüssel.',
                'meta_title'        => 'Vigenère-Chiffre knacken | Automatische Schlüsselwiederherstellung online',
                'meta_description'  => 'Vigenère-Chiffre online ohne Schlüsselkenntnis knacken. Automatische Schlüssellängenerkennung per Koinzidenzindex und Chi-Quadrat-Häufigkeitsanalyse. 8 Alphabete unterstützt.',
            ],
            'es' => [
                'name'              => 'Descifrador Vigenère',
                'name_short'        => 'Descifrador Vigenère',
                'description'       => 'Descifra automáticamente el cifrado Vigenère sin conocer la clave. Usa el Índice de Coincidencia para detectar la longitud de la clave y el análisis de frecuencias χ² para recuperar cada letra de la clave. Admite 8 alfabetos.',
                'description_stort' => 'Descifra el cifrado Vigenère automáticamente mediante análisis de frecuencias — sin clave.',
                'meta_title'        => 'Descifrador Vigenère | Recuperación automática de clave online',
                'meta_description'  => 'Descifra el cifrado Vigenère online sin conocer la clave. Detección automática de longitud de clave por Índice de Coincidencia y análisis χ². Compatible con 8 idiomas.',
            ],
            'fr' => [
                'name'              => 'Déchiffreur Vigenère',
                'name_short'        => 'Déchiffreur Vigenère',
                'description'       => 'Déchiffrez automatiquement le chiffre de Vigenère sans connaître la clé. Utilise l\'indice de coïncidence pour détecter la longueur de la clé et l\'analyse de fréquence χ² pour récupérer chaque lettre de la clé. Prend en charge 8 alphabets.',
                'description_stort' => 'Déchiffrez le chiffre de Vigenère automatiquement par analyse de fréquence — sans clé.',
                'meta_title'        => 'Déchiffreur Vigenère | Récupération automatique de clé en ligne',
                'meta_description'  => 'Déchiffrez le chiffre de Vigenère en ligne sans connaître la clé. Détection automatique de la longueur de clé par indice de coïncidence et analyse χ². 8 langues prises en charge.',
            ],
            'it' => [
                'name'              => 'Decifratore Vigenère',
                'name_short'        => 'Decifratore Vigenère',
                'description'       => 'Decifra automaticamente il cifrario di Vigenère senza conoscere la chiave. Usa l\'indice di coincidenza per rilevare la lunghezza della chiave e l\'analisi delle frequenze χ² per recuperare ogni lettera della chiave. Supporta 8 alfabeti.',
                'description_stort' => 'Decifra il cifrario di Vigenère automaticamente tramite analisi delle frequenze — senza chiave.',
                'meta_title'        => 'Decifratore Vigenère | Recupero automatico della chiave online',
                'meta_description'  => 'Decifra il cifrario di Vigenère online senza conoscere la chiave. Rilevamento automatico della lunghezza della chiave tramite indice di coincidenza e analisi χ². Supporta 8 lingue.',
            ],
            'pt' => [
                'name'              => 'Decifrador Vigenère',
                'name_short'        => 'Decifrador Vigenère',
                'description'       => 'Decifre automaticamente a cifra de Vigenère sem conhecer a chave. Usa o Índice de Coincidência para detectar o comprimento da chave e a análise de frequência χ² para recuperar cada letra da chave. Suporta 8 alfabetos.',
                'description_stort' => 'Decifre a cifra de Vigenère automaticamente por análise de frequência — sem chave.',
                'meta_title'        => 'Decifrador Vigenère | Recuperação automática de chave online',
                'meta_description'  => 'Decifre a cifra de Vigenère online sem conhecer a chave. Detecção automática do comprimento da chave pelo Índice de Coincidência e análise χ². Suporta 8 idiomas.',
            ],
            'tr' => [
                'name'              => 'Vigenère Kırıcı',
                'name_short'        => 'Vigenère Kırıcı',
                'description'       => 'Anahtarı bilmeden Vigenère şifresini otomatik olarak kırın. Anahtar uzunluğunu tespit etmek için Tesadüf İndeksi\'ni ve her anahtar harfini kurtarmak için χ² frekans analizini kullanır. 8 alfabeyi destekler.',
                'description_stort' => 'Vigenère şifresini frekans analizi ile otomatik olarak kırın — anahtar gerekmez.',
                'meta_title'        => 'Vigenère Şifre Kırıcı | Otomatik Anahtar Kurtarma Çevrimiçi',
                'meta_description'  => 'Vigenère şifresini çevrimiçi olarak anahtarı bilmeden kırın. Tesadüf İndeksi ve χ² frekans analizi ile otomatik anahtar uzunluğu tespiti. 8 dil desteklenir.',
            ],
        ];
    }
}
