<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр масонов (Pigpen) в категорию codes-and-alphabets.
 */
class SeedPigpenCipher extends Migration
{
    /**
     * Создаёт или обновляет запись инструмента, переводы и базовый контент.
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
            ['pigpen']
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
            [$categoryId, 'pigpen']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'pigpen', 'client', 140, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['client', 140, 1, $now, $cipherId]
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
     * Заполняет блоки, примеры, FAQ и теги.
     */
    private function seedContent(int $cipherId, string $now): void
    {
        $block = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How the Pigpen Cipher works', '<p>The Pigpen cipher (also called the Masonic or Freemason cipher) replaces each letter with a geometric symbol — a fragment of a grid, sometimes with a dot. It was used by Freemasons in the 18th century to keep records private.</p><p>In the standard variant, the first nine letters (A–I) are placed in a tic-tac-toe grid, the next nine (J–R) in a second grid marked with dots, and the last eight (S–Z) in two X-shaped grids. Each letter\'s symbol is simply the part of the grid that surrounds it.</p><p>Several variants exist that differ in how letters are distributed across the grids and crosses. This tool supports the Standard (Masonic), the alternating grid-and-cross Variant, and the Rosicrucian arrangement, where a single grid holds three letters per cell distinguished by dot position.</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает шифр масонов', '<p>Шифр масонов (также известный как масонский шифр или шифр вольных каменщиков) заменяет каждую букву геометрическим символом — фрагментом решётки, иногда с точкой. Его использовали масоны в XVIII веке для ведения тайных записей.</p><p>В стандартном варианте первые девять букв (A–I) размещаются в решётке «крестики-нолики», следующие девять (J–R) — во второй решётке с точками, а последние восемь (S–Z) — в двух решётках в форме крестов (X). Символ буквы — это просто та часть решётки, что её окружает.</p><p>Существует несколько вариантов, отличающихся распределением букв по решёткам и крестам. Этот инструмент поддерживает стандартный (масонский), чередующийся «решётка-крест» и розенкрейцерский варианты, где одна решётка содержит по три буквы в ячейке, различаемые положением точки.</p>', $now);

        $standardSettings    = json_encode(['ciphers-pigpen-variant' => 'standard'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $rosicrucianSettings = json_encode(['ciphers-pigpen-variant' => 'rosicrucian'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now, $standardSettings);
        $this->upsertExampleTranslation($example1, 'en', 'Encode PIGPEN CIPHER', 'PIGPEN CIPHER', '', '', 'Standard variant: each letter becomes a grid or cross symbol. Spaces separate words.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Зашифровать PIGPEN CIPHER', 'PIGPEN CIPHER', '', '', 'Стандартный вариант: каждая буква превращается в символ из линий решётки или креста. Пробелы разделяют слова.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now, $rosicrucianSettings);
        $this->upsertExampleTranslation($example2, 'en', 'Encode HELLO WORLD', 'HELLO WORLD', '', '', 'Rosicrucian variant: a single grid holds three letters per cell, told apart by dot position.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Зашифровать HELLO WORLD', 'HELLO WORLD', '', '', 'Розенкрейцерский вариант: одна решётка содержит по три буквы в ячейке, различаемые положением точки.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'What is the difference between the Pigpen variants?', 'All variants use the same geometric symbols but distribute the 26 letters differently. The Standard (Masonic) variant fills two grids (A–I, J–R with dots) and two crosses (S–V, W–Z with dots). The alternating Variant places nine letters in a grid, four in a cross, then repeats with dots. The Rosicrucian variant uses a single grid where each cell holds three letters, told apart by whether the dot is on the left, centre or right.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Чем отличаются варианты шифра масонов?', 'Все варианты используют одни и те же геометрические символы, но по-разному распределяют 26 букв. Стандартный (масонский) вариант заполняет две решётки (A–I, J–R с точками) и два креста (S–V, W–Z с точками). Чередующийся вариант помещает девять букв в решётку, четыре в крест, затем повторяет это с точками. Розенкрейцерский вариант использует одну решётку, где каждая ячейка содержит три буквы, различаемые положением точки — слева, по центру или справа.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Is the Pigpen cipher secure?', 'No. The Pigpen cipher is a simple monoalphabetic substitution: each letter always maps to the same symbol. It can be broken with basic frequency analysis just like any substitution cipher. Its value today is educational and recreational rather than for real security.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Надёжен ли шифр масонов?', 'Нет. Шифр масонов — это простая одноалфавитная замена: каждая буква всегда соответствует одному и тому же символу. Он вскрывается обычным частотным анализом, как и любой шифр замены. Сегодня его ценность скорее образовательная и развлекательная, чем в реальной защите данных.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Visual cipher', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Визуальный шифр', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Freemason', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Масоны', $now);
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
    private function upsertExample(int $cipherId, int $sortOrder, string $direction, string $now, ?string $settings = null): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET direction = ?, delimiter = ?, settings = ?, published = 1, updated_at = ? WHERE id = ?',
                [$direction, '', $settings, $now, (int) $row['id']]
            );
            return (int) $row['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, direction, delimiter, settings, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $direction, '', $settings, $now, $now]
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
     * Возвращает переводы инструмента для всех поддерживаемых языков.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'             => 'Pigpen Cipher',
                'name_short'       => 'Pigpen',
                'description'      => 'Encode text into the geometric grid-and-dot symbols of the Pigpen (Masonic) cipher. Choose between the Standard, alternating Variant and Rosicrucian layouts.',
                'description_stort' => 'Encode text as Pigpen (Masonic) grid-and-dot symbols with selectable variants.',
                'meta_title'       => 'Pigpen Cipher Online | Ciphers Online',
                'meta_description' => 'Convert text to Pigpen (Masonic / Freemason) cipher symbols online. Supports Standard, Variant and Rosicrucian layouts. Runs entirely in your browser.',
            ],
            'ru' => [
                'name'             => 'Шифр масонов (Pigpen)',
                'name_short'       => 'Шифр масонов',
                'description'      => 'Зашифруйте текст геометрическими символами из линий и точек шифра масонов (Pigpen). Выберите стандартную, чередующуюся или розенкрейцерскую раскладку.',
                'description_stort' => 'Шифрует текст в символы шифра масонов с выбором варианта раскладки.',
                'meta_title'       => 'Шифр масонов (Pigpen) онлайн | Ciphers Online',
                'meta_description' => 'Преобразуйте текст в символы шифра масонов (Pigpen) онлайн. Поддерживаются стандартный, чередующийся и розенкрейцерский варианты. Работает прямо в браузере.',
            ],
            'de' => [
                'name'             => 'Freimaurer-Chiffre (Pigpen)',
                'name_short'       => 'Pigpen',
                'description'      => 'Kodieren Sie Text in die geometrischen Gitter- und Punktsymbole der Freimaurer-Chiffre (Pigpen). Wählen Sie zwischen Standard-, alternierender Variante und Rosenkreuzer-Layout.',
                'description_stort' => 'Text als Gitter- und Punktsymbole der Pigpen-Chiffre mit wählbaren Varianten kodieren.',
                'meta_title'       => 'Freimaurer-Chiffre (Pigpen) Online | Ciphers Online',
                'meta_description' => 'Text online in Symbole der Freimaurer-Chiffre (Pigpen) umwandeln. Unterstützt Standard-, Varianten- und Rosenkreuzer-Layout. Läuft vollständig im Browser.',
            ],
            'es' => [
                'name'             => 'Cifrado masónico (Pigpen)',
                'name_short'       => 'Pigpen',
                'description'      => 'Codifica texto en los símbolos geométricos de rejilla y puntos del cifrado masónico (Pigpen). Elige entre los diseños Estándar, Variante alterna y Rosacruz.',
                'description_stort' => 'Codifica texto como símbolos de rejilla y puntos del cifrado Pigpen con variantes seleccionables.',
                'meta_title'       => 'Cifrado Masónico (Pigpen) Online | Ciphers Online',
                'meta_description' => 'Convierte texto en símbolos del cifrado masónico (Pigpen) online. Compatible con los diseños Estándar, Variante y Rosacruz. Funciona por completo en tu navegador.',
            ],
            'fr' => [
                'name'             => 'Chiffre des francs-maçons (Pigpen)',
                'name_short'       => 'Pigpen',
                'description'      => 'Encodez du texte avec les symboles géométriques à grille et à points du chiffre des francs-maçons (Pigpen). Choisissez entre les dispositions Standard, Variante alternée et Rose-Croix.',
                'description_stort' => 'Encode du texte en symboles à grille et à points du chiffre Pigpen avec variantes au choix.',
                'meta_title'       => 'Chiffre des Francs-maçons (Pigpen) en Ligne | Ciphers Online',
                'meta_description' => 'Convertissez du texte en symboles du chiffre des francs-maçons (Pigpen) en ligne. Dispositions Standard, Variante et Rose-Croix prises en charge. Fonctionne entièrement dans votre navigateur.',
            ],
            'it' => [
                'name'             => 'Cifrario massonico (Pigpen)',
                'name_short'       => 'Pigpen',
                'description'      => 'Codifica il testo nei simboli geometrici a griglia e a punti del cifrario massonico (Pigpen). Scegli tra i layout Standard, Variante alternata e Rosacroce.',
                'description_stort' => 'Codifica il testo come simboli a griglia e a punti del cifrario Pigpen con varianti selezionabili.',
                'meta_title'       => 'Cifrario Massonico (Pigpen) Online | Ciphers Online',
                'meta_description' => 'Converti il testo in simboli del cifrario massonico (Pigpen) online. Supporta i layout Standard, Variante e Rosacroce. Funziona interamente nel tuo browser.',
            ],
            'pt' => [
                'name'             => 'Cifra maçônica (Pigpen)',
                'name_short'       => 'Pigpen',
                'description'      => 'Codifique texto nos símbolos geométricos de grade e pontos da cifra maçônica (Pigpen). Escolha entre os layouts Padrão, Variante alternada e Rosa-cruz.',
                'description_stort' => 'Codifica texto como símbolos de grade e pontos da cifra Pigpen com variantes selecionáveis.',
                'meta_title'       => 'Cifra Maçônica (Pigpen) Online | Ciphers Online',
                'meta_description' => 'Converta texto em símbolos da cifra maçônica (Pigpen) online. Suporta os layouts Padrão, Variante e Rosa-cruz. Funciona inteiramente no seu navegador.',
            ],
            'tr' => [
                'name'             => 'Mason Şifresi (Pigpen)',
                'name_short'       => 'Pigpen',
                'description'      => 'Metni Mason şifresinin (Pigpen) geometrik ızgara ve nokta sembollerine kodlayın. Standart, dönüşümlü Varyant ve Gül-Haç düzenleri arasından seçim yapın.',
                'description_stort' => 'Metni seçilebilir varyantlarla Pigpen şifresinin ızgara ve nokta sembollerine kodlar.',
                'meta_title'       => 'Mason Şifresi (Pigpen) Çevrimiçi | Ciphers Online',
                'meta_description' => 'Metni çevrimiçi olarak Mason şifresinin (Pigpen) sembollerine dönüştürün. Standart, Varyant ve Gül-Haç düzenlerini destekler. Tamamen tarayıcınızda çalışır.',
            ],
        ];
    }
}
