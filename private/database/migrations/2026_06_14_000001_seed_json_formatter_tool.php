<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент JSON Formatter / Validator в категорию «Кодирование».
 */
class SeedJsonFormatterTool extends Migration
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
            ['json-formatter']
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
            ['json-formatter']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 60, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'json-formatter', 'client', 60, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is JSON formatting?', '<p>JSON (JavaScript Object Notation) is a lightweight data-interchange format that is easy for humans to read and write. Formatting — also called pretty-printing — adds consistent indentation and line breaks to make the structure clear at a glance.</p><p>A JSON formatter parses the raw JSON text, validates it for syntax correctness, and then serialises it back with a chosen indent width (2 spaces, 4 spaces, or a tab). Any syntax error is reported immediately, helping you locate the exact position of the mistake.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое форматирование JSON?', '<p>JSON (JavaScript Object Notation) — лёгкий формат обмена данными, удобный для чтения и записи. Форматирование (pretty-print) добавляет последовательные отступы и переносы строк, делая структуру сразу понятной.</p><p>Форматировщик JSON разбирает исходный текст, проверяет синтаксическую корректность и сериализует обратно с выбранным размером отступа (2 пробела, 4 пробела или табуляция). Любая синтаксическая ошибка выводится немедленно, помогая найти точное место ошибки.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Format vs minify JSON', '<p>Formatting (the <em>Format</em> tab) makes JSON human-readable by expanding each key-value pair onto its own line and adding indentation. Use this when debugging API responses, reading config files, or reviewing data structures.</p><p>Minification (the <em>Minify</em> tab) strips all unnecessary whitespace and line breaks, producing the most compact representation. Use this before embedding JSON in production code or sending it over the network to reduce payload size.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Форматирование и минификация JSON', '<p>Форматирование (вкладка <em>Форматировать</em>) делает JSON читабельным, размещая каждую пару ключ-значение на отдельной строке с отступами. Используйте при отладке ответов API, чтении конфигурационных файлов или анализе структур данных.</p><p>Минификация (вкладка <em>Минифицировать</em>) удаляет лишние пробелы и переносы строк, создавая наиболее компактное представление. Используйте перед встройкой JSON в продакшн-код или отправкой по сети для уменьшения размера данных.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Format object', '{"name":"Alice","age":30,"active":true}', "{\n  \"name\": \"Alice\",\n  \"age\": 30,\n  \"active\": true\n}", '', 'A flat JSON object is expanded with 2-space indentation for easy reading.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Форматировать объект', '{"name":"Alice","age":30,"active":true}', "{\n  \"name\": \"Alice\",\n  \"age\": 30,\n  \"active\": true\n}", '', 'Плоский JSON-объект разворачивается с отступом 2 пробела для удобного чтения.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Format array', '[{"id":1,"role":"admin"},{"id":2,"role":"user"}]', "[\n  {\n    \"id\": 1,\n    \"role\": \"admin\"\n  },\n  {\n    \"id\": 2,\n    \"role\": \"user\"\n  }\n]", '', 'A JSON array of objects is formatted with nested indentation.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Форматировать массив', '[{"id":1,"role":"admin"},{"id":2,"role":"user"}]', "[\n  {\n    \"id\": 1,\n    \"role\": \"admin\"\n  },\n  {\n    \"id\": 2,\n    \"role\": \"user\"\n  }\n]", '', 'Массив JSON-объектов форматируется с вложенными отступами.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'decrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Minify', "{\n  \"key\": \"value\",\n  \"count\": 42\n}", '{"key":"value","count":42}', '', 'Formatted JSON is compacted into a single line by removing all whitespace.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Минифицировать', "{\n  \"key\": \"value\",\n  \"count\": 42\n}", '{"key":"value","count":42}', '', 'Форматированный JSON сжимается в одну строку удалением всех пробелов.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'What indent size should I use?', 'Two spaces is the most common convention in JavaScript and web projects. Four spaces is standard in many other languages and is easier to read in deeply nested structures. Tab indentation preserves the original formatting intent while allowing each viewer\'s editor to display the preferred width. Choose the one that matches your project\'s coding style.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Какой размер отступа выбрать?', 'Два пробела — наиболее распространённое соглашение в JavaScript и веб-проектах. Четыре пробела — стандарт во многих других языках; удобны для чтения в глубоко вложенных структурах. Табуляция сохраняет исходное намерение форматирования, позволяя каждому редактору отображать предпочтительную ширину. Выбирайте то, что соответствует стилю вашего проекта.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Does the tool validate JSON?', 'Yes. The formatter first parses the input using the browser\'s built-in JSON parser. If the input is not valid JSON, an error message is shown immediately with a description of the problem (e.g. "Unexpected token"). No output is produced until the input is fixed.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Выполняет ли инструмент валидацию JSON?', 'Да. Форматировщик сначала разбирает ввод встроенным JSON-парсером браузера. Если ввод не является корректным JSON, немедленно выводится сообщение об ошибке с описанием проблемы (например, «Unexpected token»). Результат не выдаётся до устранения ошибки.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Is my JSON data sent to a server?', 'No. All processing happens entirely in your browser using JavaScript. Your JSON is never transmitted to any server. This makes the tool safe to use with sensitive configuration files, API keys, or private data structures.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Отправляются ли данные JSON на сервер?', 'Нет. Вся обработка происходит полностью в вашем браузере с помощью JavaScript. Ваш JSON никогда не передаётся на какой-либо сервер. Это делает инструмент безопасным для работы с конфиденциальными конфигурационными файлами, API-ключами или приватными структурами данных.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Why does minification not preserve key order?', 'The JSON specification does not guarantee key order in objects. When the formatter parses and re-serialises the JSON, the browser\'s JavaScript engine may reorder keys. In practice most engines preserve insertion order, but this is not guaranteed. If key order matters for your use case, copy and minify manually.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Почему минификация не сохраняет порядок ключей?', 'Спецификация JSON не гарантирует порядок ключей в объектах. При разборе и повторной сериализации браузерный движок JavaScript может изменить порядок ключей. На практике большинство движков сохраняет порядок вставки, но это не гарантировано. Если порядок ключей важен для вашего случая использования, минифицируйте вручную.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'JSON formatter', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'JSON форматировщик', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'JSON validator', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'JSON валидатор', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'JSON beautifier', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'JSON beautifier', $now);
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
     * Возвращает переводы инструмента JSON Formatter / Validator.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'JSON Formatter / Validator',
                'name_short'        => 'JSON Formatter',
                'description'       => 'Format, validate and minify JSON online. Paste raw or minified JSON to pretty-print it with 2 spaces, 4 spaces, or tab indentation. Switch to Minify mode to compact JSON for production use. Syntax errors are reported instantly.',
                'description_stort' => 'Format, validate and minify JSON in the browser.',
                'meta_title'        => 'JSON Formatter / Validator Online | JSON Beautifier & Minifier',
                'meta_description'  => 'Format and validate JSON with 2-space, 4-space, or tab indentation. Minify JSON for production. Syntax errors shown instantly. Free online tool, works entirely in your browser.',
            ],
            'ru' => [
                'name'              => 'JSON Форматировщик / Валидатор',
                'name_short'        => 'JSON Formatter',
                'description'       => 'Форматируйте, валидируйте и минифицируйте JSON онлайн. Вставьте сырой или минифицированный JSON для форматирования с отступами 2 пробела, 4 пробела или табуляция. Переключитесь в режим минификации для сжатия JSON. Синтаксические ошибки отображаются мгновенно.',
                'description_stort' => 'Форматирование, валидация и минификация JSON в браузере.',
                'meta_title'        => 'JSON Форматировщик / Валидатор онлайн | JSON Beautifier и Minifier',
                'meta_description'  => 'Форматируйте и валидируйте JSON с отступами 2 пробела, 4 пробела или табуляция. Минифицируйте JSON для продакшна. Ошибки синтаксиса выводятся мгновенно. Бесплатный инструмент, работает в браузере.',
            ],
            'de' => [
                'name'              => 'JSON Formatierer / Validator',
                'name_short'        => 'JSON Formatter',
                'description'       => 'JSON online formatieren, validieren und minifizieren. Fügen Sie rohes oder minifiziertes JSON ein, um es mit 2 Leerzeichen, 4 Leerzeichen oder Tabulator-Einrückung aufzubereiten. Syntaxfehler werden sofort angezeigt.',
                'description_stort' => 'JSON im Browser formatieren, validieren und minifizieren.',
                'meta_title'        => 'JSON Formatierer / Validator Online | JSON Beautifier & Minifier',
                'meta_description'  => 'JSON mit 2-Leerzeichen-, 4-Leerzeichen- oder Tabulator-Einrückung formatieren und validieren. JSON für die Produktion minifizieren. Syntaxfehler werden sofort angezeigt. Kostenloses Online-Tool.',
            ],
            'es' => [
                'name'              => 'Formateador / Validador JSON',
                'name_short'        => 'JSON Formatter',
                'description'       => 'Formatea, valida y minifica JSON en línea. Pega JSON sin procesar o minificado para embellecerlo con sangría de 2 espacios, 4 espacios o tabulador. Cambia al modo Minificar para compactar JSON. Los errores de sintaxis se muestran al instante.',
                'description_stort' => 'Formatea, valida y minifica JSON en el navegador.',
                'meta_title'        => 'Formateador / Validador JSON Online | JSON Beautifier y Minifier',
                'meta_description'  => 'Formatea y valida JSON con sangría de 2 espacios, 4 espacios o tabulador. Minifica JSON para producción. Errores de sintaxis mostrados al instante. Herramienta gratuita en línea.',
            ],
            'fr' => [
                'name'              => 'Formateur / Validateur JSON',
                'name_short'        => 'JSON Formatter',
                'description'       => 'Formatez, validez et minifiez du JSON en ligne. Collez du JSON brut ou minifié pour le mettre en forme avec une indentation de 2 espaces, 4 espaces ou tabulation. Les erreurs de syntaxe sont signalées instantanément.',
                'description_stort' => 'Formatez, validez et minifiez du JSON dans le navigateur.',
                'meta_title'        => 'Formateur / Validateur JSON en ligne | JSON Beautifier et Minifier',
                'meta_description'  => 'Formatez et validez du JSON avec une indentation de 2 espaces, 4 espaces ou tabulation. Minifiez du JSON pour la production. Erreurs de syntaxe affichées instantanément. Outil gratuit en ligne.',
            ],
            'it' => [
                'name'              => 'Formattatore / Validatore JSON',
                'name_short'        => 'JSON Formatter',
                'description'       => 'Formatta, valida e minimizza JSON online. Incolla JSON grezzo o minimizzato per abbellirlo con rientro a 2 spazi, 4 spazi o tabulazione. Gli errori di sintassi vengono segnalati istantaneamente.',
                'description_stort' => 'Formatta, valida e minimizza JSON nel browser.',
                'meta_title'        => 'Formattatore / Validatore JSON Online | JSON Beautifier e Minifier',
                'meta_description'  => 'Formatta e valida JSON con rientro a 2 spazi, 4 spazi o tabulazione. Minimizza JSON per la produzione. Errori di sintassi mostrati istantaneamente. Strumento gratuito online.',
            ],
            'pt' => [
                'name'              => 'Formatador / Validador JSON',
                'name_short'        => 'JSON Formatter',
                'description'       => 'Formate, valide e minifique JSON online. Cole JSON bruto ou minificado para embelezá-lo com recuo de 2 espaços, 4 espaços ou tabulação. Os erros de sintaxe são reportados instantaneamente.',
                'description_stort' => 'Formate, valide e minifique JSON no navegador.',
                'meta_title'        => 'Formatador / Validador JSON Online | JSON Beautifier e Minifier',
                'meta_description'  => 'Formate e valide JSON com recuo de 2 espaços, 4 espaços ou tabulação. Minifique JSON para produção. Erros de sintaxe mostrados instantaneamente. Ferramenta gratuita online.',
            ],
            'tr' => [
                'name'              => 'JSON Biçimleyici / Doğrulayıcı',
                'name_short'        => 'JSON Formatter',
                'description'       => 'JSON\'u çevrimiçi biçimlendirin, doğrulayın ve küçültün. Ham veya küçültülmüş JSON\'u 2 boşluk, 4 boşluk veya sekme girintisiyle güzelleştirmek için yapıştırın. Sözdizimi hataları anında gösterilir.',
                'description_stort' => 'JSON\'u tarayıcıda biçimlendirin, doğrulayın ve küçültün.',
                'meta_title'        => 'JSON Biçimleyici / Doğrulayıcı Online | JSON Beautifier ve Minifier',
                'meta_description'  => 'JSON\'u 2 boşluk, 4 boşluk veya sekme girintisiyle biçimlendirin ve doğrulayın. JSON\'u üretim için küçültün. Sözdizimi hataları anında gösterilir. Ücretsiz çevrimiçi araç.',
            ],
        ];
    }
}
