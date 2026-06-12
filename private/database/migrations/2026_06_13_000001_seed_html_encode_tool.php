<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент HTML Encode / Decode в категорию «Кодирование».
 */
class SeedHtmlEncodeTool extends Migration
{
    /**
     * Создаёт инструмент, переводы, блоки, примеры и FAQ.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['encoding']
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
            ['html-encode']
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
            ['html-encode']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 50, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'html-encode', 'client', 50, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is HTML encoding?', '<p>HTML encoding converts special characters into HTML entities so that browsers display them as text rather than interpreting them as markup. For example, the less-than sign <code>&lt;</code> becomes <code>&amp;lt;</code>, and the ampersand <code>&amp;</code> becomes <code>&amp;amp;</code>.</p><p>This is essential for safely embedding user-supplied content in web pages, preventing cross-site scripting (XSS) attacks and rendering errors caused by unescaped characters.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое HTML-кодирование?', '<p>HTML-кодирование преобразует спецсимволы в HTML-entities, чтобы браузер отображал их как текст, а не интерпретировал как разметку. Например, знак меньше <code>&lt;</code> становится <code>&amp;lt;</code>, а амперсанд <code>&amp;</code> — <code>&amp;amp;</code>.</p><p>Это необходимо для безопасного встраивания пользовательского контента в веб-страницы: предотвращает атаки межсайтового скриптинга (XSS) и ошибки рендеринга из-за незащищённых символов.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Encoding vs decoding HTML entities', '<p>Encoding replaces the five HTML-special characters with their entity equivalents: <code>&amp;</code> → <code>&amp;amp;</code>, <code>&lt;</code> → <code>&amp;lt;</code>, <code>&gt;</code> → <code>&amp;gt;</code>, <code>"</code> → <code>&amp;quot;</code>, and <code>\'</code> → <code>&amp;#39;</code>.</p><p>Decoding is the reverse process: it converts any HTML entity — named (e.g. <code>&amp;eacute;</code>), decimal (<code>&amp;#233;</code>), or hexadecimal (<code>&amp;#xE9;</code>) — back to its Unicode character. This is useful for reading or processing HTML source code that contains escaped text.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Кодирование и декодирование HTML-entities', '<p>Кодирование заменяет пять HTML-спецсимволов их entity-эквивалентами: <code>&amp;</code> → <code>&amp;amp;</code>, <code>&lt;</code> → <code>&amp;lt;</code>, <code>&gt;</code> → <code>&amp;gt;</code>, <code>"</code> → <code>&amp;quot;</code>, <code>\'</code> → <code>&amp;#39;</code>.</p><p>Декодирование — обратная операция: превращает любую HTML-entity — именованную (например, <code>&amp;eacute;</code>), десятичную (<code>&amp;#233;</code>) или шестнадцатеричную (<code>&amp;#xE9;</code>) — обратно в Unicode-символ. Это полезно при работе с HTML-исходниками, содержащими экранированный текст.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'HTML tags', '<h1>Hello & "World"</h1>', '&lt;h1&gt;Hello &amp; &quot;World&quot;&lt;/h1&gt;', '', 'The < > " and & characters are all encoded as HTML entities.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'HTML-теги', '<h1>Hello & "World"</h1>', '&lt;h1&gt;Hello &amp; &quot;World&quot;&lt;/h1&gt;', '', 'Символы < > " и & закодированы в HTML-entities.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'XSS prevention', '<script>alert(\'xss\')</script>', '&lt;script&gt;alert(&#39;xss&#39;)&lt;/script&gt;', '', 'Encoding script tags makes injection code harmless in HTML output.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Защита от XSS', '<script>alert(\'xss\')</script>', '&lt;script&gt;alert(&#39;xss&#39;)&lt;/script&gt;', '', 'Кодирование тегов script делает инъекционный код безвредным в HTML-выводе.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'decrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Decode entities', '&lt;p&gt;Caf&eacute; &amp; na&iuml;ve&lt;/p&gt;', '<p>Café & naïve</p>', '', 'Named entities like &eacute; and &iuml; are converted back to their Unicode characters.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Декодирование entities', '&lt;p&gt;Caf&eacute; &amp; na&iuml;ve&lt;/p&gt;', '<p>Café & naïve</p>', '', 'Именованные entities &eacute; и &iuml; преобразуются обратно в Unicode-символы.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'Why should I HTML-encode user input?', 'Unencoded user input embedded directly in HTML can be interpreted as markup or script, allowing attackers to inject malicious code — a vulnerability known as Cross-Site Scripting (XSS). HTML encoding neutralises special characters so the browser treats them as plain text.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Зачем HTML-кодировать пользовательский ввод?', 'Незакодированный пользовательский ввод, встроенный в HTML, может быть интерпретирован как разметка или скрипт, что позволяет злоумышленникам внедрять вредоносный код — это уязвимость Cross-Site Scripting (XSS). HTML-кодирование нейтрализует спецсимволы, и браузер воспринимает их как обычный текст.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Which characters does HTML encoding replace?', 'The five characters that must always be encoded are: & (ampersand) → &amp;amp;, < (less-than) → &amp;lt;, > (greater-than) → &amp;gt;, " (double quote) → &amp;quot;, and \' (single quote/apostrophe) → &amp;#39;. Other characters such as accented letters are safe but may also be encoded as named or numeric entities for compatibility.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Какие символы заменяет HTML-кодирование?', 'Пять символов, которые всегда необходимо кодировать: & (амперсанд) → &amp;amp;, < (меньше) → &amp;lt;, > (больше) → &amp;gt;, " (двойные кавычки) → &amp;quot;, \' (апостроф) → &amp;#39;. Другие символы, например буквы с диакритикой, безопасны, но тоже могут быть закодированы как именованные или числовые entities для совместимости.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'What is the difference between HTML encoding and URL encoding?', 'HTML encoding escapes characters that have special meaning in HTML markup (&, <, >, ", \'). URL encoding (percent-encoding) escapes characters not allowed in URLs, such as spaces (%20) and reserved characters. Both are context-specific: use HTML encoding when outputting text inside an HTML document, and URL encoding when building query strings or path segments.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'В чём разница между HTML-кодированием и URL-кодированием?', 'HTML-кодирование экранирует символы, имеющие особое значение в HTML-разметке (&, <, >, ", \'). URL-кодирование (percent-encoding) экранирует символы, недопустимые в URL: пробелы (%20) и зарезервированные символы. Оба метода контекстно-зависимы: HTML-кодирование применяется при выводе текста внутри HTML-документа, URL-кодирование — при формировании строк запроса или сегментов пути.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Can HTML decoding be used to read escaped HTML source?', 'Yes. When you receive HTML source code that contains escaped entities — for example from an API response or a database field — you can paste it into the decoder to see the original characters. The decoder handles named entities (&amp;eacute;), decimal entities (&amp;#233;), and hexadecimal entities (&amp;#xE9;).', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Можно ли HTML-декодирование использовать для чтения экранированного HTML-кода?', 'Да. Если вы получаете HTML-исходник с entities — например, в ответе API или поле базы данных — его можно вставить в декодер, чтобы увидеть исходные символы. Декодер поддерживает именованные entities (&amp;eacute;), десятичные entities (&amp;#233;) и шестнадцатеричные entities (&amp;#xE9;).', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'HTML encoding', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'HTML-кодирование', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'XSS prevention', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Защита от XSS', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'HTML entities', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'HTML entities', $now);
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
     * Возвращает переводы инструмента HTML Encode / Decode.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'HTML Encode / Decode',
                'name_short'        => 'HTML Encode',
                'description'       => 'Convert HTML special characters to entities and back. Encode &, <, >, ", \' to prevent XSS and rendering issues; decode any HTML entities — named, decimal, or hexadecimal — back to their original characters.',
                'description_stort' => 'Encode HTML special characters as entities and decode them back.',
                'meta_title'        => 'HTML Encode / Decode Online | HTML Entity Converter',
                'meta_description'  => 'Encode HTML special characters to entities (&amp;, &lt;, &gt;, &quot;, &#39;) and decode any HTML entity back to text. Free online tool, works in your browser.',
            ],
            'ru' => [
                'name'              => 'HTML кодирование / декодирование',
                'name_short'        => 'HTML Encode',
                'description'       => 'Преобразуйте HTML-спецсимволы в entities и обратно. Кодируйте &, <, >, ", \' для защиты от XSS и ошибок рендеринга; декодируйте любые HTML-entities — именованные, десятичные или шестнадцатеричные.',
                'description_stort' => 'Кодируйте HTML-спецсимволы в entities и декодируйте обратно.',
                'meta_title'        => 'HTML кодирование / декодирование онлайн | HTML entity конвертер',
                'meta_description'  => 'Кодируйте HTML-спецсимволы в entities (&amp;, &lt;, &gt;, &quot;, &#39;) и декодируйте любые HTML entities обратно в текст. Бесплатный онлайн-инструмент, работает в браузере.',
            ],
            'de' => [
                'name'              => 'HTML Kodieren / Dekodieren',
                'name_short'        => 'HTML Encode',
                'description'       => 'Konvertiert HTML-Sonderzeichen in Entitäten und zurück. Kodieren Sie &, <, >, ", \' zur XSS-Prävention; dekodieren Sie beliebige HTML-Entitäten — benannt, dezimal oder hexadezimal.',
                'description_stort' => 'HTML-Sonderzeichen in Entitäten kodieren und zurück dekodieren.',
                'meta_title'        => 'HTML Kodieren / Dekodieren Online | HTML-Entitäten-Konverter',
                'meta_description'  => 'HTML-Sonderzeichen in Entitäten (&amp;, &lt;, &gt;, &quot;, &#39;) kodieren und beliebige HTML-Entitäten zurück in Text dekodieren. Kostenloses Online-Tool.',
            ],
            'es' => [
                'name'              => 'Codificar / Decodificar HTML',
                'name_short'        => 'HTML Encode',
                'description'       => 'Convierte caracteres especiales HTML a entidades y viceversa. Codifica &, <, >, ", \' para prevenir XSS; decodifica cualquier entidad HTML — nombrada, decimal o hexadecimal.',
                'description_stort' => 'Codifica caracteres especiales HTML como entidades y decifra entidades de vuelta.',
                'meta_title'        => 'Codificar / Decodificar HTML Online | Conversor de entidades HTML',
                'meta_description'  => 'Codifica caracteres especiales HTML a entidades (&amp;, &lt;, &gt;, &quot;, &#39;) y decodifica entidades HTML a texto. Herramienta gratuita en línea.',
            ],
            'fr' => [
                'name'              => 'Encoder / Décoder HTML',
                'name_short'        => 'HTML Encode',
                'description'       => 'Convertit les caractères spéciaux HTML en entités et inversement. Encodez &, <, >, ", \' pour prévenir les failles XSS; décodez toute entité HTML — nommée, décimale ou hexadécimale.',
                'description_stort' => 'Encodez les caractères spéciaux HTML en entités et décodez-les.',
                'meta_title'        => 'Encoder / Décoder HTML en ligne | Convertisseur d\'entités HTML',
                'meta_description'  => 'Encodez les caractères spéciaux HTML en entités (&amp;, &lt;, &gt;, &quot;, &#39;) et décodez des entités HTML en texte. Outil gratuit en ligne.',
            ],
            'it' => [
                'name'              => 'Codifica / Decodifica HTML',
                'name_short'        => 'HTML Encode',
                'description'       => 'Converte i caratteri speciali HTML in entità e viceversa. Codifica &, <, >, ", \' per prevenire XSS; decodifica qualsiasi entità HTML — denominata, decimale o esadecimale.',
                'description_stort' => 'Codifica i caratteri speciali HTML come entità e decodificali.',
                'meta_title'        => 'Codifica / Decodifica HTML Online | Convertitore di entità HTML',
                'meta_description'  => 'Codifica i caratteri speciali HTML in entità (&amp;, &lt;, &gt;, &quot;, &#39;) e decodifica le entità HTML in testo. Strumento gratuito online.',
            ],
            'pt' => [
                'name'              => 'Codificar / Decodificar HTML',
                'name_short'        => 'HTML Encode',
                'description'       => 'Converte caracteres especiais HTML em entidades e vice-versa. Codifique &, <, >, ", \' para prevenir XSS; decodifique qualquer entidade HTML — nomeada, decimal ou hexadecimal.',
                'description_stort' => 'Codifique caracteres especiais HTML como entidades e decodifique-os.',
                'meta_title'        => 'Codificar / Decodificar HTML Online | Conversor de entidades HTML',
                'meta_description'  => 'Codifique caracteres especiais HTML em entidades (&amp;, &lt;, &gt;, &quot;, &#39;) e decodifique entidades HTML em texto. Ferramenta gratuita online.',
            ],
            'tr' => [
                'name'              => 'HTML Kodla / Çöz',
                'name_short'        => 'HTML Encode',
                'description'       => 'HTML özel karakterlerini varlıklara ve geri dönüştürür. XSS\'i önlemek için &, <, >, ", \' karakterlerini kodlayın; adlandırılmış, ondalık veya onaltılık HTML varlıklarını çözün.',
                'description_stort' => 'HTML özel karakterlerini varlık olarak kodlayın ve geri çözün.',
                'meta_title'        => 'HTML Kodla / Çöz Online | HTML Varlık Dönüştürücü',
                'meta_description'  => 'HTML özel karakterlerini varlıklara (&amp;, &lt;, &gt;, &quot;, &#39;) kodlayın ve HTML varlıklarını metne çözün. Ücretsiz çevrimiçi araç.',
            ],
        ];
    }
}
