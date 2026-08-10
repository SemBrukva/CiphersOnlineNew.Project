<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет клиентский инструмент «Фонетический алфавит NATO» в категорию codes-and-alphabets.
 * Включает справочную таблицу A–Z / 0–9 с кодовыми словами и произношением.
 */
class SeedNatoPhoneticCipher extends Migration
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

        $cipherId = $this->upsertCipher($categoryId, 'nato-phonetic', 160, $now);

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
            [(int) $category['id'], 'nato-phonetic']
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
     * Создаёт базовый контент страницы: блоки, примеры, FAQ, теги.
     *
     * @param array<string, mixed> $content Контент инструмента.
     */
    private function upsertContent(int $cipherId, array $content, string $now): void
    {
        foreach ($content['blocks'] as $block) {
            $blockId = $this->upsertBlock($cipherId, $block['sort'], $now);
            foreach ($block['translations'] as $language => $data) {
                $this->upsertBlockTranslation($blockId, $language, $data['title'], $data['text'], $now);
            }
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
            'en' => ['name' => 'NATO Phonetic Alphabet', 'name_short' => 'NATO Phonetic', 'description' => 'Spell text with the NATO/ICAO phonetic alphabet (Alfa, Bravo, Charlie) and decode code words back to text — right in your browser. Includes aviation numbers, US police (LAPD) and German (DIN 5009) spelling alphabets, audio pronunciation and a printable reference chart.', 'description_stort' => 'Convert text to the NATO phonetic alphabet and back, with audio and a reference chart.', 'meta_title' => 'NATO Phonetic Alphabet Translator (Alfa Bravo Charlie) | Ciphers Online', 'meta_description' => 'Translate text to the NATO phonetic alphabet and back online. Alfa Bravo Charlie spelling, aviation, police and German variants, audio pronunciation and a printable chart.'],
            'ru' => ['name' => 'Фонетический алфавит НАТО', 'name_short' => 'Алфавит НАТО', 'description' => 'Передавайте текст по буквам фонетическим алфавитом НАТО/ИКАО (Alfa, Bravo, Charlie) и декодируйте кодовые слова обратно — прямо в браузере. Есть авиационные числа, алфавиты полиции США (LAPD) и немецкий (DIN 5009), озвучивание и справочная таблица для печати.', 'description_stort' => 'Перевод текста в фонетический алфавит НАТО и обратно, с озвучиванием и таблицей.', 'meta_title' => 'Фонетический алфавит НАТО (Alfa Bravo Charlie) | Ciphers Online', 'meta_description' => 'Переведите текст в фонетический алфавит НАТО и обратно онлайн. Alfa Bravo Charlie, авиационный, полицейский и немецкий варианты, озвучивание и таблица для печати.'],
            'de' => ['name' => 'NATO-Buchstabieralphabet', 'name_short' => 'NATO-Alphabet', 'description' => 'Buchstabieren Sie Text mit dem NATO/ICAO-Buchstabieralphabet (Alfa, Bravo, Charlie) und decodieren Sie Codewörter zurück — direkt im Browser. Mit Luftfahrt-Zahlen, US-Polizei (LAPD) und deutschem (DIN 5009) Buchstabieralphabet, Audioausgabe und einer druckbaren Referenztabelle.', 'description_stort' => 'Text ins NATO-Buchstabieralphabet und zurück umwandeln, mit Audio und Tabelle.', 'meta_title' => 'NATO-Buchstabieralphabet (Alfa Bravo Charlie) | Ciphers Online', 'meta_description' => 'Text online ins NATO-Buchstabieralphabet und zurück übersetzen. Alfa Bravo Charlie, Luftfahrt-, Polizei- und deutsche Variante, Audioausgabe und druckbare Tabelle.'],
            'es' => ['name' => 'Alfabeto fonético de la OTAN', 'name_short' => 'Alfabeto OTAN', 'description' => 'Deletrea texto con el alfabeto fonético de la OTAN/OACI (Alfa, Bravo, Charlie) y decodifica las palabras clave a texto, en tu navegador. Incluye números de aviación, alfabetos de la policía de EE. UU. (LAPD) y alemán (DIN 5009), pronunciación en audio y una tabla de referencia imprimible.', 'description_stort' => 'Convierte texto al alfabeto fonético de la OTAN y viceversa, con audio y tabla.', 'meta_title' => 'Alfabeto fonético de la OTAN (Alfa Bravo Charlie) | Ciphers Online', 'meta_description' => 'Traduce texto al alfabeto fonético de la OTAN y viceversa online. Alfa Bravo Charlie, variantes de aviación, policía y alemana, pronunciación en audio y tabla imprimible.'],
            'fr' => ['name' => 'Alphabet phonétique de l’OTAN', 'name_short' => 'Alphabet OTAN', 'description' => 'Épelez du texte avec l’alphabet phonétique de l’OTAN/OACI (Alfa, Bravo, Charlie) et décodez les mots-codes en texte, directement dans votre navigateur. Avec chiffres aéronautiques, alphabets de la police américaine (LAPD) et allemand (DIN 5009), prononciation audio et un tableau de référence imprimable.', 'description_stort' => 'Convertissez du texte en alphabet phonétique de l’OTAN et inversement, avec audio et tableau.', 'meta_title' => 'Alphabet phonétique de l’OTAN (Alfa Bravo Charlie) | Ciphers Online', 'meta_description' => 'Traduisez du texte en alphabet phonétique de l’OTAN et inversement en ligne. Alfa Bravo Charlie, variantes aviation, police et allemande, prononciation audio et tableau imprimable.'],
            'it' => ['name' => 'Alfabeto fonetico NATO', 'name_short' => 'Alfabeto NATO', 'description' => 'Compita il testo con l’alfabeto fonetico NATO/ICAO (Alfa, Bravo, Charlie) e decodifica le parole in codice in testo, nel browser. Include numeri per l’aviazione, alfabeti della polizia USA (LAPD) e tedesco (DIN 5009), pronuncia audio e una tabella di riferimento stampabile.', 'description_stort' => 'Converti testo nell’alfabeto fonetico NATO e viceversa, con audio e tabella.', 'meta_title' => 'Alfabeto fonetico NATO (Alfa Bravo Charlie) | Ciphers Online', 'meta_description' => 'Traduci testo nell’alfabeto fonetico NATO e viceversa online. Alfa Bravo Charlie, varianti aviazione, polizia e tedesca, pronuncia audio e tabella stampabile.'],
            'pt' => ['name' => 'Alfabeto fonético da OTAN', 'name_short' => 'Alfabeto OTAN', 'description' => 'Soletre texto com o alfabeto fonético da OTAN/OACI (Alfa, Bravo, Charlie) e decodifique as palavras-código de volta para texto, no navegador. Inclui números de aviação, alfabetos da polícia dos EUA (LAPD) e alemão (DIN 5009), pronúncia em áudio e uma tabela de referência para impressão.', 'description_stort' => 'Converta texto no alfabeto fonético da OTAN e vice-versa, com áudio e tabela.', 'meta_title' => 'Alfabeto fonético da OTAN (Alfa Bravo Charlie) | Ciphers Online', 'meta_description' => 'Traduza texto para o alfabeto fonético da OTAN e vice-versa online. Alfa Bravo Charlie, variantes de aviação, polícia e alemã, pronúncia em áudio e tabela para impressão.'],
            'tr' => ['name' => 'NATO Fonetik Alfabesi', 'name_short' => 'NATO Alfabesi', 'description' => 'Metni NATO/ICAO fonetik alfabesiyle (Alfa, Bravo, Charlie) hecelendirin ve kod sözcüklerini tekrar metne çevirin — tarayıcınızda. Havacılık sayıları, ABD polisi (LAPD) ve Almanca (DIN 5009) alfabeleri, sesli okuma ve yazdırılabilir başvuru tablosu içerir.', 'description_stort' => 'Metni NATO fonetik alfabesine ve tersine dönüştürün, ses ve tablo ile.', 'meta_title' => 'NATO Fonetik Alfabesi (Alfa Bravo Charlie) | Ciphers Online', 'meta_description' => 'Metni online olarak NATO fonetik alfabesine ve tersine çevirin. Alfa Bravo Charlie, havacılık, polis ve Almanca çeşitleri, sesli okuma ve yazdırılabilir tablo.'],
        ];
    }

    /**
     * Возвращает базовый контент инструмента (en/ru) и справочную таблицу.
     *
     * @return array<string, mixed>
     */
    private function content(): array
    {
        return [
            'blocks' => [
                ['sort' => 10, 'translations' => [
                    'en' => ['title' => 'How the NATO phonetic alphabet works', 'text' => '<p>The <strong>NATO phonetic alphabet</strong> — officially the International Radiotelephony Spelling Alphabet — assigns an unambiguous code word to each letter: <strong>A</strong> is <em>Alfa</em>, <strong>B</strong> is <em>Bravo</em>, <strong>C</strong> is <em>Charlie</em>, and so on. Because the words sound distinct even over noisy radio or a bad phone line, it is the standard way to spell names, call signs and codes aloud.</p><p>This tool converts text to code words and back. Pick a <strong>spelling alphabet</strong> — NATO/ICAO, the aviation number words (<em>Tree, Fower, Fife, Niner</em>), the US police (LAPD) set (<em>Adam, Boy, Charles</em>) or the German DIN&nbsp;5009 table (<em>Anton, Berta, Cäsar</em>) — choose how the words are separated, and press <em>Listen</em> to hear the pronunciation. Everything runs locally in your browser.</p>'],
                    'ru' => ['title' => 'Как работает фонетический алфавит НАТО', 'text' => '<p><strong>Фонетический алфавит НАТО</strong> (официально — International Radiotelephony Spelling Alphabet) сопоставляет каждой букве однозначное кодовое слово: <strong>A</strong> — это <em>Alfa</em>, <strong>B</strong> — <em>Bravo</em>, <strong>C</strong> — <em>Charlie</em> и так далее. Слова звучат чётко даже по зашумлённой радиосвязи или плохой телефонной линии, поэтому это стандартный способ произносить имена, позывные и коды вслух.</p><p>Инструмент переводит текст в кодовые слова и обратно. Выберите <strong>фонетический алфавит</strong> — НАТО/ИКАО, авиационные числа (<em>Tree, Fower, Fife, Niner</em>), набор полиции США LAPD (<em>Adam, Boy, Charles</em>) или немецкую таблицу DIN&nbsp;5009 (<em>Anton, Berta, Cäsar</em>), — задайте разделитель слов и нажмите <em>Прослушать</em>, чтобы услышать произношение. Всё выполняется локально в браузере.</p>'],
                ]],
                ['sort' => 20, 'translations' => [
                    'en' => ['title' => 'NATO phonetic alphabet chart', 'text' => $this->referenceTable('en')],
                    'ru' => ['title' => 'Таблица фонетического алфавита НАТО', 'text' => $this->referenceTable('ru')],
                ]],
            ],
            'examples' => [
                ['sort' => 10, 'direction' => 'encrypt', 'settings' => '{"ciphers-nato-variant":"nato","ciphers-nato-separator":"space","ciphers-nato-show-letter":"words"}', 'translations' => [
                    'en' => ['title' => 'Spell a name', 'input' => 'John Smith', 'output' => 'Juliett Oscar Hotel November / Sierra Mike India Tango Hotel', 'description' => 'Each input word becomes a group of code words separated by / .'],
                    'ru' => ['title' => 'Передать имя по буквам', 'input' => 'John Smith', 'output' => 'Juliett Oscar Hotel November / Sierra Mike India Tango Hotel', 'description' => 'Каждое исходное слово превращается в группу кодовых слов, разделённых / .'],
                ]],
                ['sort' => 20, 'direction' => 'encrypt', 'settings' => '{"ciphers-nato-variant":"aviation","ciphers-nato-separator":"space","ciphers-nato-show-letter":"words"}', 'translations' => [
                    'en' => ['title' => 'Aviation numbers', 'input' => 'Runway 25', 'output' => 'Romeo Uniform November Whiskey Alfa Yankee / Two Fife', 'description' => 'The aviation variant says Fife for 5 and Niner for 9.'],
                    'ru' => ['title' => 'Авиационные числа', 'input' => 'Runway 25', 'output' => 'Romeo Uniform November Whiskey Alfa Yankee / Two Fife', 'description' => 'В авиационном варианте 5 читается как Fife, а 9 — как Niner.'],
                ]],
                ['sort' => 30, 'direction' => 'decrypt', 'settings' => '{"ciphers-nato-variant":"nato","ciphers-nato-separator":"space","ciphers-nato-show-letter":"words"}', 'translations' => [
                    'en' => ['title' => 'Decode code words', 'input' => 'Charlie Alfa Tango', 'output' => 'CAT', 'description' => 'Decoding is lenient: Alpha, Juliet and X-ray are accepted too.'],
                    'ru' => ['title' => 'Декодировать кодовые слова', 'input' => 'Charlie Alfa Tango', 'output' => 'CAT', 'description' => 'Декодирование терпимо к вариантам: принимаются также Alpha, Juliet и X-ray.'],
                ]],
            ],
            'faq' => [
                ['sort' => 10, 'translations' => [
                    'en' => ['question' => 'Is it Alfa or Alpha, Juliett or Juliet?', 'answer' => 'The official ICAO/NATO spellings are Alfa and Juliett — with an F and a double T — so that speakers of any language pronounce them correctly. The common English spellings Alpha and Juliet mean the same letters; this tool outputs the official forms but accepts both when decoding.'],
                    'ru' => ['question' => 'Как правильно: Alfa или Alpha, Juliett или Juliet?', 'answer' => 'Официальные написания ИКАО/НАТО — Alfa и Juliett (с F и двойной T), чтобы носители любого языка произносили их верно. Привычные английские Alpha и Juliet означают те же буквы; инструмент выводит официальные формы, но при декодировании принимает оба варианта.'],
                ]],
                ['sort' => 20, 'translations' => [
                    'en' => ['question' => 'Why do pilots say Niner, Fife and Tree?', 'answer' => 'In aviation and military radio, several digits are altered so they cannot be confused: nine becomes “niner” (to avoid the German “nein”), five becomes “fife” and three becomes “tree”. Select the Aviation variant to use these number words.'],
                    'ru' => ['question' => 'Почему пилоты говорят Niner, Fife и Tree?', 'answer' => 'В авиационной и военной радиосвязи некоторые цифры изменяют, чтобы их нельзя было спутать: nine становится «niner» (чтобы не путать с немецким «nein»), five — «fife», three — «tree». Выберите вариант «Авиационный», чтобы использовать эти слова.'],
                ]],
                ['sort' => 30, 'translations' => [
                    'en' => ['question' => 'Can I hear how the words are pronounced?', 'answer' => 'Yes. In encode mode press the Listen button and your browser will read the code words aloud using its built-in speech synthesis. You can also adjust the reading speed. No audio is sent anywhere — it is generated on your device.'],
                    'ru' => ['question' => 'Можно ли услышать, как произносятся слова?', 'answer' => 'Да. В режиме кодирования нажмите кнопку «Прослушать», и браузер прочитает кодовые слова вслух встроенным синтезом речи. Скорость чтения можно регулировать. Аудио никуда не отправляется — оно создаётся на вашем устройстве.'],
                ]],
            ],
            'tags' => [
                ['sort' => 10, 'translations' => ['en' => 'NATO alphabet', 'ru' => 'Алфавит НАТО']],
                ['sort' => 20, 'translations' => ['en' => 'Phonetic alphabet', 'ru' => 'Фонетический алфавит']],
                ['sort' => 30, 'translations' => ['en' => 'Alfa Bravo Charlie', 'ru' => 'Alfa Bravo Charlie']],
            ],
        ];
    }

    /**
     * Строит HTML справочной таблицы NATO (буква, кодовое слово, произношение).
     */
    private function referenceTable(string $lang): string
    {
        $headers = [
            'en' => ['Letter', 'Code word', 'Pronunciation'],
            'ru' => ['Буква', 'Кодовое слово', 'Произношение'],
        ];
        $numbersTitle = ['en' => 'Numbers', 'ru' => 'Цифры'];

        $letters = [
            ['A', 'Alfa', 'AL-FAH'], ['B', 'Bravo', 'BRAH-VOH'], ['C', 'Charlie', 'CHAR-LEE'],
            ['D', 'Delta', 'DELL-TAH'], ['E', 'Echo', 'ECK-OH'], ['F', 'Foxtrot', 'FOKS-TROT'],
            ['G', 'Golf', 'GOLF'], ['H', 'Hotel', 'HOH-TELL'], ['I', 'India', 'IN-DEE-AH'],
            ['J', 'Juliett', 'JEW-LEE-ETT'], ['K', 'Kilo', 'KEY-LOH'], ['L', 'Lima', 'LEE-MAH'],
            ['M', 'Mike', 'MIKE'], ['N', 'November', 'NO-VEM-BER'], ['O', 'Oscar', 'OSS-CAH'],
            ['P', 'Papa', 'PAH-PAH'], ['Q', 'Quebec', 'KEH-BECK'], ['R', 'Romeo', 'ROW-ME-OH'],
            ['S', 'Sierra', 'SEE-AIR-RAH'], ['T', 'Tango', 'TANG-GO'], ['U', 'Uniform', 'YOU-NEE-FORM'],
            ['V', 'Victor', 'VIK-TAH'], ['W', 'Whiskey', 'WISS-KEY'], ['X', 'X-ray', 'ECKS-RAY'],
            ['Y', 'Yankee', 'YANG-KEY'], ['Z', 'Zulu', 'ZOO-LOO'],
        ];
        $numbers = [
            ['0', 'Zero', 'ZEE-RO'], ['1', 'One', 'WUN'], ['2', 'Two', 'TOO'], ['3', 'Three', 'TREE'],
            ['4', 'Four', 'FOWER'], ['5', 'Five', 'FIFE'], ['6', 'Six', 'SIX'], ['7', 'Seven', 'SEV-EN'],
            ['8', 'Eight', 'AIT'], ['9', 'Nine', 'NIN-ER'],
        ];

        $h = $headers[$lang];
        $rows = static function (array $data): string {
            $out = '';
            foreach ($data as [$char, $word, $say]) {
                $out .= '<tr><td><strong>' . $char . '</strong></td><td>' . $word . '</td><td>' . $say . '</td></tr>';
            }
            return $out;
        };

        return '<div class="nato-chart-wrap"><table class="nato-chart">'
            . '<thead><tr><th>' . $h[0] . '</th><th>' . $h[1] . '</th><th>' . $h[2] . '</th></tr></thead>'
            . '<tbody>' . $rows($letters) . '</tbody>'
            . '<tbody><tr class="nato-chart__group"><td colspan="3">' . $numbersTitle[$lang] . '</td></tr>' . $rows($numbers) . '</tbody>'
            . '</table></div>';
    }
}
