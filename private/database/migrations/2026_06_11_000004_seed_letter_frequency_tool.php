<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент визуализации частот букв в категорию «Анализ текста и криптоанализ».
 */
class SeedLetterFrequencyTool extends Migration
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
            ['letter-frequency']
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
            ['letter-frequency']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 20, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'letter-frequency', 'client', 20, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is letter frequency?', '<p>Letter frequency refers to how often each letter of the alphabet appears in a given text, expressed as a percentage of all letters. Every natural language has a characteristic frequency distribution: in English, <strong>E</strong> is the most common letter (~12.7%), while <strong>Z</strong> and <strong>Q</strong> are among the rarest. In Russian, <strong>О</strong> leads at ~11%, followed by <strong>Е</strong> and <strong>А</strong>.</p><p>This tool counts every letter in your text and visualises the result as a colour-coded heatmap — darker cells indicate higher frequency — alongside a sortable table that compares actual percentages to the expected values for the selected reference language.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое частота букв?', '<p>Частота букв — это то, как часто каждая буква алфавита встречается в данном тексте, выраженное в процентах от общего числа букв. Каждый естественный язык имеет характерное частотное распределение: в русском языке лидирует <strong>О</strong> (~11%), за ней следуют <strong>Е</strong> и <strong>А</strong>. В английском наиболее частая буква — <strong>E</strong> (~12,7%).</p><p>Инструмент подсчитывает каждую букву в вашем тексте и отображает результат в виде цветовой тепловой карты — тёмные ячейки соответствуют более высокой частоте — а также в виде таблицы с фактическими и ожидаемыми значениями для выбранного языка.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Using letter frequency in cryptanalysis', '<p>In classical cryptanalysis, letter frequency is the first tool a codebreaker reaches for. A Caesar cipher shifts every letter by a fixed amount, so the most frequent letter in the ciphertext is almost certainly the encrypted version of <strong>E</strong> (in English). Identify the shift, and the cipher is broken.</p><p>Simple substitution ciphers are more complex, but still vulnerable: each ciphertext letter maps consistently to one plaintext letter, so the overall frequency histogram retains the shape of the underlying language. By matching peaks in the ciphertext distribution to known language frequencies, a cryptanalyst can reconstruct the substitution alphabet one letter at a time.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Применение частот букв в криптоанализе', '<p>В классическом криптоанализе частота букв — первый инструмент, к которому обращается взломщик кодов. Шифр Цезаря сдвигает каждую букву на фиксированное число позиций, поэтому наиболее частая буква в зашифрованном тексте почти наверняка является зашифрованным вариантом <strong>О</strong> (в русском) или <strong>Е</strong> (в английском). Определите сдвиг — и шифр взломан.</p><p>Простые шифры подстановки сложнее, но всё равно уязвимы: каждая буква шифротекста однозначно соответствует одной букве открытого текста, поэтому гистограмма частот сохраняет форму, характерную для базового языка. Сопоставляя пики частот шифротекста с известными языковыми частотами, криптоаналитик может восстановить алфавит подстановки по одной букве.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'English pangram', 'The quick brown fox jumps over the lazy dog', '', '', 'A pangram containing every letter of the English alphabet. Observe which letters appear most and least often.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Английская панграмма', 'The quick brown fox jumps over the lazy dog', '', '', 'Панграмма, содержащая каждую букву английского алфавита. Обратите внимание, какие буквы встречаются чаще всего.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Caesar ciphertext', 'KHOOR ZRUOG', '', '', 'HELLO WORLD shifted by 3. Dominated by K, H, U, O, R — the heat shifts compared to natural English.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Шифр Цезаря', 'KHOOR ZRUOG', '', '', 'HELLO WORLD со сдвигом 3. Доминируют K, H, U, O, R — тепловая карта смещена относительно естественного английского.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Hamlet quote', 'To be or not to be that is the question', '', '', 'Classic English prose. Note the heavy weight of T, O, E, H — typical of natural English text.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Цитата из Гамлета', 'To be or not to be that is the question', '', '', 'Классическая английская проза. Обратите внимание на преобладание T, O, E, H — характерных для естественного текста на английском.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'What is a letter frequency heatmap?', 'A letter frequency heatmap shows every letter of the alphabet as a coloured cell. The darker (or more saturated) the cell, the more often that letter appears in the text. Cells for letters that do not appear at all are shown in a neutral, pale colour. This makes it instantly clear which letters dominate and which are absent.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Что такое тепловая карта частот букв?', 'Тепловая карта частот букв отображает каждую букву алфавита в виде цветной ячейки. Чем темнее (или насыщеннее) ячейка, тем чаще эта буква встречается в тексте. Ячейки букв, которые отсутствуют в тексте, отображаются нейтральным бледным цветом. Это позволяет сразу увидеть, какие буквы преобладают, а каких нет.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'How does this differ from the Frequency Analysis tool?', 'The Frequency Analysis tool is a comprehensive cryptanalysis workbench: it supports letters, all characters, words, bigrams, and trigrams, and computes the Index of Coincidence. Letter Frequency is a focused, visual tool that always shows the full alphabet as a heatmap, making it easy to spot at a glance which letters are missing or dominant — ideal for a quick visual inspection before diving into deeper analysis.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Чем этот инструмент отличается от «Частотного анализа»?', 'Инструмент «Частотный анализ» — это полноценная рабочая среда для криптоанализа: он поддерживает буквы, все символы, слова, биграммы и триграммы, а также вычисляет индекс совпадений. «Частота букв» — это сфокусированный визуальный инструмент, который всегда показывает полный алфавит в виде тепловой карты, позволяя с первого взгляда увидеть, какие буквы отсутствуют или преобладают. Идеален для быстрого визуального осмотра перед более глубоким анализом.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Which languages are supported?', 'The tool includes reference frequency profiles for eight languages: English, Russian, German, Spanish, French, Italian, Portuguese, and Turkish. Select the appropriate language to compare your text\'s letter distribution against the expected frequencies for that language.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Какие языки поддерживаются?', 'Инструмент включает эталонные частотные профили для восьми языков: английского, русского, немецкого, испанского, французского, итальянского, португальского и турецкого. Выберите нужный язык, чтобы сравнить частотное распределение букв вашего текста с ожидаемыми значениями для этого языка.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Letter frequency', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Частота букв', $now);

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
     * Возвращает переводы инструмента.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Letter Frequency',
                'name_short'        => 'Letter Frequency',
                'description'       => 'Visualise how often each letter of the alphabet appears in your text. The colour-coded heatmap and sortable table let you instantly compare actual letter distribution against known language frequencies — perfect for spotting patterns in ciphertexts.',
                'description_stort' => 'See letter frequencies as a heatmap and compare them to natural language distributions.',
                'meta_title'        => 'Letter Frequency Heatmap | Visual Text Analysis',
                'meta_description'  => 'Analyse letter frequency in any text online. Colour-coded alphabet heatmap instantly shows which letters dominate — ideal for cryptanalysis and text statistics.',
            ],
            'ru' => [
                'name'              => 'Частота букв',
                'name_short'        => 'Частота букв',
                'description'       => 'Визуализируйте, как часто каждая буква алфавита встречается в вашем тексте. Цветовая тепловая карта и сортируемая таблица позволяют мгновенно сравнить фактическое распределение букв с известными языковыми частотами — идеально для выявления паттернов в шифротекстах.',
                'description_stort' => 'Отобразите частоты букв в виде тепловой карты и сравните с естественными языковыми распределениями.',
                'meta_title'        => 'Частота букв — тепловая карта | Визуальный анализ текста',
                'meta_description'  => 'Анализируйте частоту букв в любом тексте онлайн. Цветовая тепловая карта алфавита мгновенно показывает, какие буквы преобладают — идеально для криптоанализа и статистики текста.',
            ],
            'de' => [
                'name'              => 'Buchstabenhäufigkeit',
                'name_short'        => 'Buchstabenhäufigkeit',
                'description'       => 'Visualisieren Sie, wie oft jeder Buchstabe des Alphabets in Ihrem Text vorkommt. Die farbcodierte Heatmap und die sortierbare Tabelle ermöglichen einen sofortigen Vergleich der tatsächlichen Buchstabenverteilung mit bekannten Sprachfrequenzen.',
                'description_stort' => 'Buchstabenhäufigkeiten als Heatmap anzeigen und mit natürlichen Sprachverteilungen vergleichen.',
                'meta_title'        => 'Buchstabenhäufigkeit Heatmap | Visuelle Textanalyse',
                'meta_description'  => 'Buchstabenhäufigkeiten in beliebigen Texten online analysieren. Farbcodierte Alphabet-Heatmap zeigt sofort, welche Buchstaben dominieren.',
            ],
            'es' => [
                'name'              => 'Frecuencia de letras',
                'name_short'        => 'Frecuencia de letras',
                'description'       => 'Visualiza con qué frecuencia aparece cada letra del alfabeto en tu texto. El mapa de calor codificado por colores y la tabla ordenable permiten comparar al instante la distribución real de letras con las frecuencias conocidas del idioma.',
                'description_stort' => 'Muestra las frecuencias de letras como mapa de calor y compáralas con distribuciones de idiomas naturales.',
                'meta_title'        => 'Frecuencia de letras | Análisis visual de texto',
                'meta_description'  => 'Analiza la frecuencia de letras en cualquier texto en línea. El mapa de calor del alfabeto muestra al instante qué letras dominan.',
            ],
            'fr' => [
                'name'              => 'Fréquence des lettres',
                'name_short'        => 'Fréquence des lettres',
                'description'       => 'Visualisez la fréquence d\'apparition de chaque lettre de l\'alphabet dans votre texte. La carte thermique codée par couleur et le tableau triable permettent de comparer instantanément la distribution réelle des lettres aux fréquences connues de la langue.',
                'description_stort' => 'Affichez les fréquences de lettres sous forme de carte thermique et comparez-les aux distributions linguistiques naturelles.',
                'meta_title'        => 'Fréquence des lettres | Analyse visuelle de texte',
                'meta_description'  => 'Analysez la fréquence des lettres dans n\'importe quel texte en ligne. La carte thermique de l\'alphabet montre instantanément quelles lettres dominent.',
            ],
            'it' => [
                'name'              => 'Frequenza delle lettere',
                'name_short'        => 'Frequenza delle lettere',
                'description'       => 'Visualizza la frequenza con cui ogni lettera dell\'alfabeto appare nel tuo testo. La mappa di calore codificata a colori e la tabella ordinabile consentono di confrontare istantaneamente la distribuzione reale delle lettere con le frequenze linguistiche note.',
                'description_stort' => 'Mostra le frequenze delle lettere come mappa di calore e confrontale con le distribuzioni linguistiche naturali.',
                'meta_title'        => 'Frequenza delle lettere | Analisi visiva del testo',
                'meta_description'  => 'Analizza la frequenza delle lettere in qualsiasi testo online. La mappa di calore dell\'alfabeto mostra istantaneamente quali lettere dominano.',
            ],
            'pt' => [
                'name'              => 'Frequência de letras',
                'name_short'        => 'Frequência de letras',
                'description'       => 'Visualize a frequência com que cada letra do alfabeto aparece no seu texto. O mapa de calor codificado por cores e a tabela classificável permitem comparar instantaneamente a distribuição real das letras com as frequências linguísticas conhecidas.',
                'description_stort' => 'Exiba as frequências de letras como mapa de calor e compare-as com distribuições linguísticas naturais.',
                'meta_title'        => 'Frequência de letras | Análise visual de texto',
                'meta_description'  => 'Analise a frequência de letras em qualquer texto online. O mapa de calor do alfabeto mostra instantaneamente quais letras dominam.',
            ],
            'tr' => [
                'name'              => 'Harf Frekansı',
                'name_short'        => 'Harf Frekansı',
                'description'       => 'Alfabenin her harfinin metninizde ne sıklıkla göründüğünü görselleştirin. Renk kodlu ısı haritası ve sıralanabilir tablo, gerçek harf dağılımını bilinen dil frekanslarıyla anında karşılaştırmanızı sağlar.',
                'description_stort' => 'Harf frekanslarını ısı haritası olarak gösterin ve doğal dil dağılımlarıyla karşılaştırın.',
                'meta_title'        => 'Harf Frekansı Isı Haritası | Görsel Metin Analizi',
                'meta_description'  => 'Herhangi bir metindeki harf frekansını çevrimiçi analiz edin. Alfabenin renk kodlu ısı haritası hangi harflerin baskın olduğunu anında gösterir.',
            ],
        ];
    }
}
