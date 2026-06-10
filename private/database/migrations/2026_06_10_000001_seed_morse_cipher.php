<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет азбуку Морзе в категорию классических шифров.
 */
class SeedMorseCipher extends Migration
{
    /**
     * Создаёт или обновляет запись азбуки Морзе, переводы и базовый контент.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['classical-ciphers']
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
     * Удаляет запись азбуки Морзе и связанные сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['morse-code']
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
     * Создаёт или обновляет запись инструмента азбуки Морзе.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'morse-code']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'morse-code', 'client', 110, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['client', 110, 1, $now, $cipherId]
        );

        return $cipherId;
    }

    /**
     * Создаёт или обновляет перевод шифра.
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
        $this->upsertBlockTranslation($block, 'en', 'How Morse code works', '<p>Morse code represents each letter and digit with a unique sequence of dots (short signals) and dashes (long signals). A dot lasts one unit; a dash lasts three units.</p><p>Gaps between elements of the same character last one unit. Gaps between characters last three units. Gaps between words last seven units.</p><p>The standard used here is the International Morse Code (ITU-R M.1677-1).</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает азбука Морзе', '<p>Азбука Морзе представляет каждую букву и цифру уникальной последовательностью точек (коротких сигналов) и тире (длинных сигналов). Точка длится одну единицу, тире — три единицы.</p><p>Пауза между элементами одного знака — одна единица, между знаками — три единицы, между словами — семь единиц.</p><p>Стандарт, используемый здесь, — Международная азбука Морзе (ITU-R M.1677-1).</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encode SOS', 'SOS', '... --- ...', '', 'SOS is the universal distress signal in Morse code — three dots, three dashes, three dots.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Кодирование SOS', 'SOS', '... --- ...', '', 'SOS — универсальный сигнал бедствия в азбуке Морзе: три точки, три тире, три точки.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Encode HELLO WORLD', 'HELLO WORLD', '.... . .-.. .-.. --- / .-- --- .-. .-.. -..', '', 'Letters are separated by spaces. Words are separated by " / ".', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Кодирование HELLO WORLD', 'HELLO WORLD', '.... . .-.. .-.. --- / .-- --- .-. .-.. -..', '', 'Буквы разделяются пробелами. Слова разделяются знаком " / ".', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'decrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Decode Morse to text', '.. / .- -- / --- -.-', 'I AM OK', '', 'Words separated by " / " are decoded into individual space-delimited words.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Декодирование в текст', '.. / .- -- / --- -.-', 'I AM OK', '', 'Слова, разделённые знаком " / ", декодируются в отдельные слова через пробел.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'How are words separated in Morse code?', 'Letters within a word are separated by a single space. Words are separated by " / " (space-slash-space). This is the standard notation used by most Morse code converters.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Как слова разделяются в азбуке Морзе?', 'Буквы в слове разделяются одним пробелом. Слова разделяются знаком " / " (пробел, косая черта, пробел). Это стандартная нотация, используемая большинством конвертеров азбуки Морзе.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Can I listen to the Morse code audio?', 'Yes. After encoding or decoding, use the audio player below the result to play the Morse code as sound. You can also choose the speed (WPM), tone frequency, and download a WAV file.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Можно ли прослушать азбуку Морзе?', 'Да. После кодирования или декодирования используйте аудиоплеер под результатом, чтобы воспроизвести азбуку Морзе как звук. Можно также выбрать скорость (WPM), частоту тона и скачать WAV-файл.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'What does WPM mean?', 'WPM stands for Words Per Minute. It controls the speed of the Morse audio. The standard training speed is 5 WPM for beginners; 20 WPM is typical for proficient operators. This tool supports 5 to 35 WPM.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Что означает WPM?', 'WPM — слов в минуту. Это параметр скорости воспроизведения азбуки Морзе. Стандартная скорость обучения — 5 WPM для начинающих, 20 WPM — для опытных операторов. Инструмент поддерживает от 5 до 35 WPM.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Audio playback', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Воспроизведение звука', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'International standard', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Международный стандарт', $now);
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
     * Возвращает переводы для азбуки Морзе.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Morse Code',
                'name_short' => 'Morse Code',
                'description' => 'Encode text to Morse code dots and dashes, decode Morse signals back to text, and listen to the result with the built-in audio player.',
                'description_stort' => 'Text to Morse code encoder and decoder with audio playback.',
                'meta_title' => 'Morse Code Translator Online | Ciphers Online',
                'meta_description' => 'Convert text to Morse code and back online. Play the Morse audio, adjust speed and tone, or download a WAV file.',
            ],
            'ru' => [
                'name' => 'Азбука Морзе',
                'name_short' => 'Азбука Морзе',
                'description' => 'Преобразуйте текст в точки и тире азбуки Морзе, декодируйте сигналы обратно в текст и прослушайте результат с помощью встроенного аудиоплеера.',
                'description_stort' => 'Кодирование и декодирование текста в азбуку Морзе с воспроизведением звука.',
                'meta_title' => 'Азбука Морзе Онлайн | Ciphers Online',
                'meta_description' => 'Конвертируйте текст в азбуку Морзе онлайн. Воспроизводите сигналы Морзе, настраивайте скорость и тон или скачивайте WAV-файл.',
            ],
            'de' => [
                'name' => 'Morsecode',
                'name_short' => 'Morsecode',
                'description' => 'Text in Morsecode-Punkte und -Striche umwandeln, Morsesignale zurück in Text dekodieren und das Ergebnis mit dem integrierten Audioplayer anhören.',
                'description_stort' => 'Text-Morsecode-Kodierer und -Dekodierer mit Audiowiedergabe.',
                'meta_title' => 'Morsecode-Übersetzer Online | Ciphers Online',
                'meta_description' => 'Text online in Morsecode umwandeln und zurück. Morsesignale abspielen, Geschwindigkeit und Ton anpassen oder eine WAV-Datei herunterladen.',
            ],
            'es' => [
                'name' => 'Código Morse',
                'name_short' => 'Código Morse',
                'description' => 'Convierte texto a puntos y rayas del código Morse, decodifica señales Morse a texto y escucha el resultado con el reproductor de audio integrado.',
                'description_stort' => 'Codificador y decodificador de texto a código Morse con reproducción de audio.',
                'meta_title' => 'Traductor de Código Morse Online | Ciphers Online',
                'meta_description' => 'Convierte texto a código Morse online. Reproduce el audio, ajusta la velocidad y el tono, o descarga un archivo WAV.',
            ],
            'fr' => [
                'name' => 'Code Morse',
                'name_short' => 'Code Morse',
                'description' => 'Encodez du texte en points et tirets Morse, décodez des signaux Morse en texte et écoutez le résultat avec le lecteur audio intégré.',
                'description_stort' => 'Encodeur et décodeur texte vers code Morse avec lecture audio.',
                'meta_title' => 'Traducteur Code Morse en ligne | Ciphers Online',
                'meta_description' => 'Convertissez du texte en code Morse en ligne. Lisez les signaux, ajustez la vitesse et la tonalité, ou téléchargez un fichier WAV.',
            ],
            'it' => [
                'name' => 'Codice Morse',
                'name_short' => 'Codice Morse',
                'description' => 'Converti testo in punti e trattini del codice Morse, decodifica segnali Morse in testo e ascolta il risultato con il lettore audio integrato.',
                'description_stort' => 'Codificatore e decodificatore testo-codice Morse con riproduzione audio.',
                'meta_title' => 'Traduttore Codice Morse Online | Ciphers Online',
                'meta_description' => 'Converti testo in codice Morse online. Riproduci i segnali, regola velocità e tono, o scarica un file WAV.',
            ],
            'pt' => [
                'name' => 'Código Morse',
                'name_short' => 'Código Morse',
                'description' => 'Converta texto em pontos e traços do código Morse, decodifique sinais Morse em texto e ouça o resultado com o reprodutor de áudio integrado.',
                'description_stort' => 'Codificador e decodificador de texto para código Morse com reprodução de áudio.',
                'meta_title' => 'Tradutor de Código Morse Online | Ciphers Online',
                'meta_description' => 'Converta texto em código Morse online. Reproduza os sinais, ajuste a velocidade e o tom, ou baixe um arquivo WAV.',
            ],
            'tr' => [
                'name' => 'Mors Alfabesi',
                'name_short' => 'Mors Alfabesi',
                'description' => 'Metni Mors alfabesi nokta ve tirelerine dönüştürün, Mors sinyallerini metne geri çözün ve sonucu yerleşik ses oynatıcısıyla dinleyin.',
                'description_stort' => 'Ses oynatmalı metin-Mors alfabesi kodlayıcı ve çözücü.',
                'meta_title' => 'Çevrimiçi Mors Alfabesi Çevirici | Ciphers Online',
                'meta_description' => 'Metni çevrimiçi Mors alfabesine dönüştürün. Mors sinyallerini oynatın, hız ve tonu ayarlayın ya da WAV dosyası indirin.',
            ],
        ];
    }
}
