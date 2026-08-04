<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет клиентский инструмент «Шрифт Брайля» (Grade 1) в категорию codes-and-alphabets.
 * Базовый inline-контент (en/ru); подробный контент прорабатывается отдельно.
 */
class SeedBrailleCipher extends Migration
{
    /**
     * Создаёт или обновляет инструмент, переводы карточки и базовый контент.
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

        $categoryId = (int) $category['id'];
        $now = date('Y-m-d H:i:s');

        $cipherId = $this->upsertCipher($categoryId, 'braille', 150, $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }

        $this->upsertContent($cipherId, $this->content(), $now);
    }

    /**
     * Удаляет добавленный инструмент.
     */
    public function down(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['codes-and-alphabets']
        );

        if ($category === false) {
            return;
        }

        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ?',
            [(int) $category['id'], 'braille']
        );
    }

    /**
     * Создаёт или обновляет запись инструмента.
     */
    private function upsertCipher(int $categoryId, string $alias, int $sortOrder, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, $alias]
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, $alias, 'client', $sortOrder, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['client', $sortOrder, 1, $now, $cipherId]
        );

        return $cipherId;
    }

    /**
     * Создаёт или обновляет перевод карточки инструмента.
     *
     * @param array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string} $translation Данные перевода.
     */
    private function upsertTranslation(int $cipherId, string $language, array $translation, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ? AND language = ? LIMIT 1',
            [$cipherId, $language]
        );

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_TRANSLATIONS
                . ' (app_id, language, name, name_short, description, description_stort, meta_title, meta_description, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $cipherId,
                    $language,
                    $translation['name'],
                    $translation['name_short'],
                    $translation['description'],
                    $translation['description_stort'],
                    $translation['meta_title'],
                    $translation['meta_description'],
                    $now,
                    $now,
                ]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_TRANSLATIONS
            . ' SET name = ?, name_short = ?, description = ?, description_stort = ?, meta_title = ?, meta_description = ?, updated_at = ? '
            . 'WHERE id = ?',
            [
                $translation['name'],
                $translation['name_short'],
                $translation['description'],
                $translation['description_stort'],
                $translation['meta_title'],
                $translation['meta_description'],
                $now,
                (int) $existing['id'],
            ]
        );
    }

    /**
     * Создаёт базовый контент страницы: блок, примеры, FAQ, теги.
     *
     * @param array<string, mixed> $content Контент инструмента.
     */
    private function upsertContent(int $cipherId, array $content, string $now): void
    {
        $block = $this->upsertBlock($cipherId, 10, $now);
        foreach ($content['block'] as $language => $data) {
            $this->upsertBlockTranslation($block, $language, $data['title'], $data['text'], $now);
        }

        foreach ($content['examples'] as $example) {
            $exampleId = $this->upsertExample($cipherId, $example['sort'], $example['direction'] ?? '', $example['settings'] ?? null, $now);
            foreach ($example['translations'] as $language => $data) {
                $this->upsertExampleTranslation($exampleId, $language, $data['title'], $data['input'], $data['output'], $data['description'], $now);
            }
        }

        foreach ($content['faq'] as $faq) {
            $faqId = $this->upsertFaq($cipherId, $faq['sort'], $now);
            foreach ($faq['translations'] as $language => $data) {
                $this->upsertFaqTranslation($faqId, $language, $data['question'], $data['answer'], $now);
            }
        }

        foreach ($content['tags'] as $tag) {
            $tagId = $this->upsertTag($cipherId, $tag['sort'], $now);
            foreach ($tag['translations'] as $language => $value) {
                $this->upsertTagTranslation($tagId, $language, $value, $now);
            }
        }
    }

    /**
     * Создаёт или обновляет блок контента по сортировке.
     */
    private function upsertBlock(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_BLOCKS . ' SET published = 1, updated_at = ? WHERE id = ?', [$now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
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
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' (block_id, language, title, text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$blockId, $language, $title, $text, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет пример по сортировке. `$direction` — 'encrypt' / 'decrypt';
     * `$settings` — JSON настроек инструмента либо null.
     */
    private function upsertExample(int $cipherId, int $sortOrder, string $direction, ?string $settings, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET published = 1, direction = ?, settings = ?, updated_at = ? WHERE id = ?', [$direction, $settings, $now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, direction, settings, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $direction, $settings, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(int $exampleId, string $language, string $title, string $input, string $output, string $description, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' SET title = ?, input = ?, output = ?, description = ?, updated_at = ? WHERE id = ?',
                [$title, $input, $output, $description, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' (example_id, language, title, input, output, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$exampleId, $language, $title, $input, $output, $description, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет FAQ по сортировке.
     */
    private function upsertFaq(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_FAQ . ' SET published = 1, show_in_category = 0, updated_at = ? WHERE id = ?', [$now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_FAQ . ' (app_id, sort_order, show_in_category, published, created_at, updated_at) VALUES (?, ?, 0, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
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
            'INSERT INTO ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' (faq_id, language, question, answer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$faqId, $language, $question, $answer, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет тег по сортировке.
     */
    private function upsertTag(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_TAGS . ' SET published = 1, updated_at = ? WHERE id = ?', [$now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_TAGS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод тега.
     */
    private function upsertTagTranslation(int $tagId, string $language, string $tag, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' WHERE tag_id = ? AND language = ? LIMIT 1',
            [$tagId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' SET tag = ?, updated_at = ? WHERE id = ?',
                [$tag, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' (tag_id, language, tag, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$tagId, $language, $tag, $now, $now]
        );
    }

    /**
     * Возвращает переводы карточки инструмента на 8 языках.
     *
     * @return array<string, array<string, string>>
     */
    private function translations(): array
    {
        return [
            'en' => ['name' => 'Braille Translator', 'name_short' => 'Braille', 'description' => 'Convert text to and from Braille (Grade 1) for 8 languages in your browser. Output as Unicode dots, dot numbers or Braille ASCII, with number and capital signs and a visual dot-cell grid. Data is processed locally.', 'description_stort' => 'Convert text to and from Braille (Grade 1), 8 languages, right in the browser.', 'meta_title' => 'Braille Translator (Text to Braille) | Ciphers Online', 'meta_description' => 'Translate text to Braille and back online. Grade 1 Braille for 8 languages, Unicode dots, dot numbers and Braille ASCII, visual cells — all in your browser.'],
            'ru' => ['name' => 'Переводчик шрифта Брайля', 'name_short' => 'Брайль', 'description' => 'Перевод текста в шрифт Брайля (Grade 1) и обратно для 8 языков прямо в браузере. Вывод в виде точек Unicode, номеров точек или Braille ASCII, с числовым знаком и знаком заглавной и визуальной сеткой точек. Данные обрабатываются локально.', 'description_stort' => 'Перевод текста в Брайль (Grade 1) и обратно, 8 языков, прямо в браузере.', 'meta_title' => 'Переводчик Брайля (текст в Брайль) | Ciphers Online', 'meta_description' => 'Переведите текст в шрифт Брайля и обратно онлайн. Брайль Grade 1 для 8 языков, точки Unicode, номера точек и Braille ASCII, визуальные клетки — прямо в браузере.'],
            'de' => ['name' => 'Braille-Übersetzer', 'name_short' => 'Braille', 'description' => 'Text im Browser in Braille (Grade 1) und zurück umwandeln, für 8 Sprachen. Ausgabe als Unicode-Punkte, Punktnummern oder Braille-ASCII, mit Zahlen- und Großschreibungszeichen und einer visuellen Punktzellen-Ansicht. Die Daten werden lokal verarbeitet.', 'description_stort' => 'Text in Braille (Grade 1) und zurück umwandeln, 8 Sprachen, direkt im Browser.', 'meta_title' => 'Braille-Übersetzer (Text zu Braille) | Ciphers Online', 'meta_description' => 'Text online in Braille und zurück übersetzen. Braille Grade 1 für 8 Sprachen, Unicode-Punkte, Punktnummern und Braille-ASCII, visuelle Zellen — im Browser.'],
            'es' => ['name' => 'Traductor de braille', 'name_short' => 'Braille', 'description' => 'Convierte texto a braille (grado 1) y viceversa para 8 idiomas en tu navegador. Salida en puntos Unicode, números de puntos o braille ASCII, con signos de número y mayúscula y una cuadrícula visual de celdas. Los datos se procesan localmente.', 'description_stort' => 'Convierte texto a braille (grado 1) y viceversa, 8 idiomas, en el navegador.', 'meta_title' => 'Traductor de braille (texto a braille) | Ciphers Online', 'meta_description' => 'Traduce texto a braille y viceversa online. Braille de grado 1 para 8 idiomas, puntos Unicode, números de puntos y braille ASCII, celdas visuales, en tu navegador.'],
            'fr' => ['name' => 'Traducteur braille', 'name_short' => 'Braille', 'description' => 'Convertissez du texte en braille (grade 1) et inversement pour 8 langues dans votre navigateur. Sortie en points Unicode, numéros de points ou braille ASCII, avec signes numérique et majuscule et une grille visuelle de cellules. Les données sont traitées localement.', 'description_stort' => 'Convertissez du texte en braille (grade 1) et inversement, 8 langues, dans le navigateur.', 'meta_title' => 'Traducteur braille (texte en braille) | Ciphers Online', 'meta_description' => 'Traduisez du texte en braille et inversement en ligne. Braille grade 1 pour 8 langues, points Unicode, numéros de points et braille ASCII, cellules visuelles, dans votre navigateur.'],
            'it' => ['name' => 'Traduttore braille', 'name_short' => 'Braille', 'description' => 'Converti testo in braille (grado 1) e viceversa per 8 lingue nel browser. Output in punti Unicode, numeri dei punti o braille ASCII, con segni di numero e maiuscola e una griglia visiva di celle. I dati sono elaborati localmente.', 'description_stort' => 'Converti testo in braille (grado 1) e viceversa, 8 lingue, nel browser.', 'meta_title' => 'Traduttore braille (testo in braille) | Ciphers Online', 'meta_description' => 'Traduci testo in braille e viceversa online. Braille di grado 1 per 8 lingue, punti Unicode, numeri dei punti e braille ASCII, celle visive, nel browser.'],
            'pt' => ['name' => 'Tradutor de braille', 'name_short' => 'Braille', 'description' => 'Converta texto em braille (grau 1) e vice-versa para 8 idiomas no navegador. Saída em pontos Unicode, números dos pontos ou braille ASCII, com sinais de número e maiúscula e uma grade visual de células. Os dados são processados localmente.', 'description_stort' => 'Converta texto em braille (grau 1) e vice-versa, 8 idiomas, no navegador.', 'meta_title' => 'Tradutor de braille (texto para braille) | Ciphers Online', 'meta_description' => 'Traduza texto para braille e vice-versa online. Braille de grau 1 para 8 idiomas, pontos Unicode, números dos pontos e braille ASCII, células visuais, no navegador.'],
            'tr' => ['name' => 'Braille Çevirici', 'name_short' => 'Braille', 'description' => 'Metni tarayıcınızda 8 dil için Braille’e (1. derece) ve tersine dönüştürün. Çıktı Unicode noktaları, nokta numaraları veya Braille ASCII biçiminde; sayı ve büyük harf işaretleri ve görsel nokta hücresi ızgarası ile. Veriler yerel olarak işlenir.', 'description_stort' => 'Metni Braille’e (1. derece) ve tersine dönüştürün, 8 dil, tarayıcıda.', 'meta_title' => 'Braille Çevirici (Metinden Braille’e) | Ciphers Online', 'meta_description' => 'Metni online olarak Braille’e ve tersine çevirin. 8 dil için 1. derece Braille, Unicode noktaları, nokta numaraları ve Braille ASCII, görsel hücreler — tarayıcınızda.'],
        ];
    }

    /**
     * Возвращает базовый контент инструмента (en/ru).
     *
     * @return array<string, mixed>
     */
    private function content(): array
    {
        return [
            'block' => [
                'en' => ['title' => 'How Braille works', 'text' => '<p>Braille represents each character as a cell of up to six raised dots arranged in two columns of three. The dots are numbered 1-2-3 down the left column and 4-5-6 down the right, so the letter <strong>h</strong> is dots 1-2-5. This tool implements <strong>Grade 1</strong> (uncontracted) Braille, where every letter maps directly to one cell.</p><p>Digits reuse the letters <code>a-j</code> preceded by a <strong>number sign</strong> (⠼); a <strong>capital sign</strong> (⠠) marks an upper-case letter. Choose your language so accented letters and Cyrillic map to the correct national cells, and pick the output format: Unicode Braille glyphs, dot numbers, or Braille ASCII. The visual grid shows each cell as filled and empty dots.</p>'],
                'ru' => ['title' => 'Как работает шрифт Брайля', 'text' => '<p>Шрифт Брайля представляет каждый символ клеткой из шести приподнятых точек в два столбца по три. Точки нумеруются 1-2-3 в левом столбце и 4-5-6 в правом, поэтому буква <strong>h</strong> — это точки 1-2-5. Инструмент реализует <strong>Grade 1</strong> (без стяжений): каждая буква соответствует ровно одной клетке.</p><p>Цифры используют буквы <code>a-j</code> с предваряющим <strong>числовым знаком</strong> (⠼); <strong>знак заглавной</strong> (⠠) помечает прописную букву. Выберите язык, чтобы буквы с диакритикой и кириллица отображались в правильные национальные клетки, и формат вывода: глифы Unicode, номера точек или Braille ASCII. Визуальная сетка показывает каждую клетку заполненными и пустыми точками.</p>'],
            ],
            'examples' => [
                ['sort' => 10, 'direction' => 'encrypt', 'settings' => '{"ciphers-braille-format":"unicode","ciphers-braille-case":"keep"}', 'translations' => [
                    'en' => ['title' => 'Text to Braille', 'input' => 'Hello World', 'output' => '⠠⠓⠑⠇⠇⠕ ⠠⠺⠕⠗⠇⠙', 'description' => 'Each capital gets the ⠠ sign; the rest maps letter by letter.'],
                    'ru' => ['title' => 'Текст в Брайль', 'input' => 'Hello World', 'output' => '⠠⠓⠑⠇⠇⠕ ⠠⠺⠕⠗⠇⠙', 'description' => 'Каждая заглавная получает знак ⠠; остальное отображается буква за буквой.'],
                ]],
                ['sort' => 20, 'direction' => 'encrypt', 'settings' => '{"ciphers-braille-format":"dots","ciphers-braille-case":"ignore"}', 'translations' => [
                    'en' => ['title' => 'Dot numbers with digits', 'input' => 'Braille 2026', 'output' => '12 1235 1 24 123 123 15 / 3456 12 245 12 124', 'description' => 'Digits follow the ⠼ number sign (3456); / marks a space.'],
                    'ru' => ['title' => 'Номера точек с цифрами', 'input' => 'Braille 2026', 'output' => '12 1235 1 24 123 123 15 / 3456 12 245 12 124', 'description' => 'Цифры идут после числового знака ⠼ (3456); / обозначает пробел.'],
                ]],
                ['sort' => 30, 'direction' => 'decrypt', 'settings' => '{"ciphers-braille-format":"unicode","ciphers-braille-case":"keep"}', 'translations' => [
                    'en' => ['title' => 'Braille to text', 'input' => '⠠⠓⠑⠇⠇⠕ ⠠⠺⠕⠗⠇⠙', 'output' => 'Hello World', 'description' => 'Decoding restores letters and capitals from the cells.'],
                    'ru' => ['title' => 'Брайль в текст', 'input' => '⠠⠓⠑⠇⠇⠕ ⠠⠺⠕⠗⠇⠙', 'output' => 'Hello World', 'description' => 'Декодирование восстанавливает буквы и заглавные из клеток.'],
                ]],
            ],
            'faq' => [
                ['sort' => 10, 'translations' => [
                    'en' => ['question' => 'What is the difference between Grade 1 and Grade 2 Braille?', 'answer' => 'Grade 1 (uncontracted) spells every word out letter by letter, one cell per character — that is what this tool produces. Grade 2 (contracted) adds hundreds of contractions and short-forms (for example a single cell for "and" or "the") and is mostly used for English. This translator focuses on accurate Grade 1 across 8 languages.'],
                    'ru' => ['question' => 'Чем отличается Брайль Grade 1 от Grade 2?', 'answer' => 'Grade 1 (без стяжений) записывает слово буква за буквой, по одной клетке на символ — именно это выдаёт инструмент. Grade 2 (со стяжениями) добавляет сотни сокращений (например, одна клетка для «and» или «the») и используется в основном для английского. Этот переводчик делает акцент на точном Grade 1 для 8 языков.'],
                ]],
                ['sort' => 20, 'translations' => [
                    'en' => ['question' => 'Why does the same cell decode differently in another language?', 'answer' => 'Braille cells are reused across languages: the same dot pattern stands for different accented or Cyrillic letters depending on the national table. Pick the matching language in the settings so the decoder chooses the right letters.'],
                    'ru' => ['question' => 'Почему одна и та же клетка в другом языке декодируется иначе?', 'answer' => 'Клетки Брайля переиспользуются в разных языках: один и тот же узор точек в национальных таблицах означает разные буквы с диакритикой или кириллицу. Выберите нужный язык в настройках, чтобы декодер подобрал правильные буквы.'],
                ]],
                ['sort' => 30, 'translations' => [
                    'en' => ['question' => 'What is Braille ASCII?', 'answer' => 'Braille ASCII (the North American Braille Computer Code) maps each of the 64 six-dot cells to a printable ASCII character. It is a compact, language-neutral way to type and store Braille, and this tool can read and write it as one of the output formats.'],
                    'ru' => ['question' => 'Что такое Braille ASCII?', 'answer' => 'Braille ASCII (North American Braille Computer Code) сопоставляет каждой из 64 шеститочечных клеток печатный ASCII-символ. Это компактный, независимый от языка способ вводить и хранить Брайль; инструмент умеет читать и писать его как один из форматов вывода.'],
                ]],
            ],
            'tags' => [
                ['sort' => 10, 'translations' => ['en' => 'Braille alphabet', 'ru' => 'Алфавит Брайля']],
                ['sort' => 20, 'translations' => ['en' => 'Accessibility', 'ru' => 'Доступность']],
                ['sort' => 30, 'translations' => ['en' => 'Grade 1', 'ru' => 'Grade 1']],
            ],
        ];
    }
}
