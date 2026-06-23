<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент «Cipher Identifier» в категорию «Анализ текста и криптоанализ».
 */
class SeedCipherIdentifierTool extends Migration
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
            ['cipher-identifier']
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
            ['cipher-identifier']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'api', 35, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'cipher-identifier', 'api', 35, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'How the Cipher Identifier works', '<p>The Cipher Identifier analyses a piece of text and returns a ranked list of candidate cipher types, each with a confidence score. It works in two stages: first, fast pattern matching checks for hard signals such as Base64 character sets, hexadecimal-only text, binary streams, Morse code symbols, or JWT dot-separated structure. Second, statistical detectors measure the Index of Coincidence (IoC) and chi-squared letter frequency to distinguish monoalphabetic ciphers (Caesar, Atbash, Affine) from polyalphabetic ones (Vigenère, Beaufort, Autokey) and transposition ciphers (Rail Fence, Columnar).</p><p>When a single candidate reaches 70 % confidence and leads the next candidate by at least 10 percentage points, the tool automatically forwards the text to the appropriate brute-force or cracking tool and returns both the ranked list and the pre-computed result.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Как работает определитель шифра', '<p>Определитель шифра анализирует введённый текст и возвращает ранжированный список возможных типов шифра, каждый с оценкой уверенности. Работа ведётся в два этапа: сначала быстрое сопоставление с образцом проверяет жёсткие сигналы — символьный набор Base64, текст только из шестнадцатеричных цифр, бинарные потоки, символы азбуки Морзе или JWT-структуру из трёх частей. Затем статистические детекторы измеряют индекс совпадений (IoC) и критерий хи-квадрат частот букв, чтобы отличить моноалфавитные шифры (Цезарь, Атбаш, Аффинный) от полиалфавитных (Виженер, Бофор, Автоключ) и перестановочных (заборный, перестановка по столбцам).</p><p>Когда один кандидат достигает уверенности 70 % и опережает следующего на 10 и более процентных пунктов, инструмент автоматически отправляет текст в соответствующий инструмент перебора и возвращает одновременно ранжированный список и предвычисленный результат.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Supported cipher types', '<p>The tool currently recognises 28 different cipher and encoding types: <strong>Encodings</strong> — Base64, Hexadecimal, Binary, URL encoding, Unicode escape, JWT; <strong>Classic symbols</strong> — Morse code, Bacon, A1Z26, Polybius Square; <strong>Monoalphabetic ciphers</strong> — Caesar, ROT-13, Atbash, Affine, Simple Substitution, XOR; <strong>Polyalphabetic ciphers</strong> — Vigenère, Beaufort, Autokey, Gronsfeld, Alberti; <strong>Polybius-derived</strong> — Bifid, Trifid; <strong>Transposition</strong> — Rail Fence, Columnar Transposition; <strong>Polygraphic</strong> — Playfair, Hill; <strong>Stream</strong> — Vernam.</p><p>Select "Auto-detect alphabet" to let the tool infer the language from letter distribution, or manually choose a language to restrict analysis to that alphabet.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Поддерживаемые типы шифров', '<p>Инструмент распознаёт 28 различных типов шифров и кодировок: <strong>Кодировки</strong> — Base64, шестнадцатеричная, двоичная, URL-кодировка, Unicode-эскейп, JWT; <strong>Символьные классики</strong> — азбука Морзе, Бэкон, A1Z26, квадрат Полибия; <strong>Моноалфавитные</strong> — Цезарь, ROT-13, Атбаш, Аффинный, простая замена, XOR; <strong>Полиалфавитные</strong> — Виженер, Бофор, Автоключ, Гронсфельд, Альберти; <strong>Производные квадрата Полибия</strong> — Бифид, Трифид; <strong>Перестановочные</strong> — заборный, перестановка по столбцам; <strong>Полиграфические</strong> — Плейфэйр, Хилл; <strong>Поточные</strong> — Вернам.</p><p>Выберите «Автоопределение алфавита», чтобы инструмент сам определил язык по распределению букв, или вручную укажите язык для ограничения анализа этим алфавитом.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Morse code', '.... . .-.. .-.. --- / .-- --- .-. .-.. -..', '', '', 'The tool detects Morse code charset (dots, dashes, slashes) and returns ~95 % confidence.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Азбука Морзе', '.... . .-.. .-.. --- / .-- --- .-. .-.. -..', '', '', 'Инструмент распознаёт символьный набор Морзе (точки, тире, слэши) и возвращает ~95 % уверенности.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Base64', 'SGVsbG8gV29ybGQh', '', '', 'Base64 charset with length divisible by 4 → ~90 % confidence.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Base64', 'SGVsbG8gV29ybGQh', '', '', 'Символьный набор Base64 с длиной кратной 4 → ~90 % уверенности.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Caesar cipher', 'KHOOR ZRUOG WKLV LV D WHVW', '', '', 'High IoC and strong chi-squared signal identifies Caesar cipher (shift 3) with ~65 % confidence; the tool auto-triggers Caesar brute force.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Шифр Цезаря', 'KHOOR ZRUOG WKLV LV D WHVW', '', '', 'Высокий IoC и сильный сигнал хи-квадрат определяет шифр Цезаря (сдвиг 3) с ~65 % уверенности; автоматически запускается перебор Цезаря.', $now);

        $example4 = $this->upsertExample($cipherId, 40, 'encrypt', $now);
        $this->upsertExampleTranslation($example4, 'en', 'Vigenère cipher', 'LXFOPVEFRNHR XVLRFXJRXR LBFXGZRNHG', '', '', 'Lower IoC in polyalphabetic zone → Vigenère / Beaufort / Autokey candidates. Auto-triggers Vigenère cracker when confidence is high enough.', $now);
        $this->upsertExampleTranslation($example4, 'ru', 'Шифр Виженера', 'LXFOPVEFRNHR XVLRFXJRXR LBFXGZRNHG', '', '', 'Низкий IoC в зоне полиалфавитных шифров → кандидаты: Виженер / Бофор / Автоключ. При достаточной уверенности автоматически запускается взломщик Виженера.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'How accurate is the Cipher Identifier?', 'Accuracy depends heavily on text length and cipher type. Encoding detectors (Base64, Morse, Binary) are highly accurate even on short samples because they rely on strict character set rules. Statistical detectors for classical ciphers need at least 20 letters to be reliable, and performance improves significantly with 50+ characters. Polyalphabetic ciphers are harder to distinguish from each other, so the tool may return several candidates with similar confidence scores — use the individual cipher tools to confirm.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Насколько точен определитель шифра?', 'Точность сильно зависит от длины текста и типа шифра. Детекторы кодировок (Base64, Морзе, двоичная) работают надёжно даже на коротких образцах, опираясь на строгие правила символьных наборов. Статистические детекторы для классических шифров требуют не менее 20 букв, а при 50+ символах результат значительно улучшается. Полиалфавитные шифры сложнее различить между собой, поэтому инструмент может вернуть несколько кандидатов с близкими оценками — используйте соответствующие инструменты для подтверждения.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'What is the Index of Coincidence and why is it useful?', 'The Index of Coincidence (IoC) measures how unevenly letters are distributed in a text. In natural language, some letters appear much more often than others, giving a high IoC (about 0.065–0.078 for most languages). A Caesar or Atbash cipher preserves these frequencies, so the IoC stays high. Vigenère and similar polyalphabetic ciphers spread the letters more evenly, producing a low IoC close to the theoretical random value. Comparing the measured IoC to language-specific reference values allows the tool to narrow down cipher candidates quickly.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Что такое индекс совпадений и почему он полезен?', 'Индекс совпадений (IoC) измеряет неравномерность распределения букв в тексте. В естественном языке одни буквы встречаются значительно чаще других, что даёт высокий IoC (около 0,065–0,078 для большинства языков). Шифр Цезаря или Атбаш сохраняют эти частоты, поэтому IoC остаётся высоким. Виженер и подобные полиалфавитные шифры распределяют буквы равномернее, давая низкий IoC, близкий к теоретическому значению для случайного текста. Сравнение измеренного IoC с эталонными значениями для каждого языка позволяет быстро сузить круг кандидатов.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Why does the tool show multiple candidates instead of one answer?', 'Many classical ciphers share statistical fingerprints, especially when the text is short or the cipher introduces only subtle changes. Vigenère, Beaufort, and Autokey all produce similar IoC values; Affine and Caesar both have high IoC with monoalphabetic patterns. Rather than guessing wrongly, the tool returns a ranked list so you can investigate the top candidates. If one candidate clearly dominates (≥ 70 % confidence, 10-point lead), automatic forwarding to a cracking tool gives you an instant result.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Почему инструмент показывает несколько кандидатов вместо одного ответа?', 'Многие классические шифры имеют схожие статистические признаки, особенно при коротком тексте или незначительных изменениях, которые вносит шифр. Виженер, Бофор и Автоключ дают близкие значения IoC; Аффинный и Цезарь оба имеют высокий IoC с моноалфавитным паттерном. Вместо того чтобы ошибочно угадывать, инструмент возвращает ранжированный список, чтобы вы могли изучить главных кандидатов. Если один кандидат явно доминирует (≥ 70 % уверенности, отрыв не менее 10 п.п.), автоматическая переадресация во взломщик даёт мгновенный результат.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Cipher identifier', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Определитель шифра', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Cryptanalysis', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Криптоанализ', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Frequency analysis', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Частотный анализ', $now);
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
     * Возвращает переводы инструмента определения шифра.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Cipher Identifier',
                'name_short'        => 'Cipher Identifier',
                'description'       => 'Automatically identify the type of cipher or encoding used in a text. Paste any ciphertext and get a ranked list of cipher candidates with confidence scores. Supports 28 cipher types including Caesar, Vigenère, Base64, Morse code, and more.',
                'description_stort' => 'Paste any ciphertext to automatically detect the cipher or encoding type.',
                'meta_title'        => 'Cipher Identifier — Detect Any Cipher Online',
                'meta_description'  => 'Automatically detect the cipher type from any ciphertext. Supports Caesar, Vigenère, Playfair, Base64, Morse code, and 23 more cipher types. Free online cipher identifier tool.',
            ],
            'ru' => [
                'name'              => 'Определитель шифра',
                'name_short'        => 'Определитель',
                'description'       => 'Автоматически определите тип шифра или кодировки, использованной в тексте. Вставьте любой зашифрованный текст и получите ранжированный список кандидатов с оценкой уверенности. Поддерживает 28 типов шифров: Цезарь, Виженер, Base64, азбука Морзе и другие.',
                'description_stort' => 'Вставьте любой зашифрованный текст для автоматического определения типа шифра.',
                'meta_title'        => 'Определитель шифра — распознать любой шифр онлайн',
                'meta_description'  => 'Автоматически определите тип шифра по любому зашифрованному тексту онлайн. Поддерживает Цезарь, Виженер, Плейфэйр, Base64, азбуку Морзе и ещё 23 типа шифров.',
            ],
            'de' => [
                'name'              => 'Verschlüsselungserkenner',
                'name_short'        => 'Chiffre erkennen',
                'description'       => 'Automatisch den Typ einer Verschlüsselung oder Kodierung in einem Text erkennen. Fügen Sie beliebigen Geheimtext ein und erhalten Sie eine gerankte Liste von Verschlüsselungskandidaten mit Konfidenzwerten. Unterstützt 28 Verschlüsselungstypen.',
                'description_stort' => 'Geheimtext einfügen und den Verschlüsselungstyp automatisch erkennen lassen.',
                'meta_title'        => 'Verschlüsselungserkenner — Jede Chiffre online erkennen',
                'meta_description'  => 'Automatisch den Verschlüsselungstyp aus beliebigem Geheimtext erkennen. Unterstützt Caesar, Vigenère, Playfair, Base64, Morsecode und 23 weitere Typen.',
            ],
            'es' => [
                'name'              => 'Identificador de cifrado',
                'name_short'        => 'Identificar cifrado',
                'description'       => 'Identifica automáticamente el tipo de cifrado o codificación utilizado en un texto. Pega cualquier texto cifrado y obtén una lista clasificada de candidatos con puntuaciones de confianza. Soporta 28 tipos de cifrado.',
                'description_stort' => 'Pega cualquier texto cifrado para detectar automáticamente el tipo de cifrado.',
                'meta_title'        => 'Identificador de cifrado — Detectar cualquier cifra online',
                'meta_description'  => 'Detecta automáticamente el tipo de cifrado de cualquier texto cifrado. Soporta César, Vigenère, Playfair, Base64, código Morse y 23 tipos más.',
            ],
            'fr' => [
                'name'              => 'Identifiant de chiffrement',
                'name_short'        => 'Identifier le chiffre',
                'description'       => 'Identifiez automatiquement le type de chiffrement ou d\'encodage utilisé dans un texte. Collez n\'importe quel texte chiffré et obtenez une liste classée de candidats avec des scores de confiance. Supporte 28 types de chiffrement.',
                'description_stort' => 'Collez n\'importe quel texte chiffré pour détecter automatiquement le type de chiffrement.',
                'meta_title'        => 'Identifiant de chiffrement — Détecter n\'importe quel chiffre en ligne',
                'meta_description'  => 'Détectez automatiquement le type de chiffrement à partir de n\'importe quel texte chiffré. Supporte César, Vigenère, Playfair, Base64, code Morse et 23 types supplémentaires.',
            ],
            'it' => [
                'name'              => 'Identificatore di cifratura',
                'name_short'        => 'Identifica cifratura',
                'description'       => 'Identifica automaticamente il tipo di cifratura o codifica usato in un testo. Incolla qualsiasi testo cifrato e ottieni un elenco classificato di candidati con punteggi di confidenza. Supporta 28 tipi di cifratura.',
                'description_stort' => 'Incolla qualsiasi testo cifrato per rilevare automaticamente il tipo di cifratura.',
                'meta_title'        => 'Identificatore di cifratura — Rilevare qualsiasi cifra online',
                'meta_description'  => 'Rileva automaticamente il tipo di cifratura da qualsiasi testo cifrato. Supporta Cesare, Vigenère, Playfair, Base64, codice Morse e altri 23 tipi.',
            ],
            'pt' => [
                'name'              => 'Identificador de cifra',
                'name_short'        => 'Identificar cifra',
                'description'       => 'Identifique automaticamente o tipo de cifra ou codificação usada em um texto. Cole qualquer texto cifrado e obtenha uma lista classificada de candidatos com pontuações de confiança. Suporta 28 tipos de cifra.',
                'description_stort' => 'Cole qualquer texto cifrado para detectar automaticamente o tipo de cifra.',
                'meta_title'        => 'Identificador de cifra — Detectar qualquer cifra online',
                'meta_description'  => 'Detecte automaticamente o tipo de cifra de qualquer texto cifrado. Suporta César, Vigenère, Playfair, Base64, código Morse e mais 23 tipos.',
            ],
            'tr' => [
                'name'              => 'Şifre Tanımlayıcı',
                'name_short'        => 'Şifre tanımla',
                'description'       => 'Bir metinde kullanılan şifre veya kodlama türünü otomatik olarak tanımlayın. Herhangi bir şifreli metin yapıştırın ve güven puanlarıyla birlikte şifre adaylarının sıralı listesini alın. 28 şifre türünü destekler.',
                'description_stort' => 'Şifre türünü otomatik algılamak için herhangi bir şifreli metin yapıştırın.',
                'meta_title'        => 'Şifre Tanımlayıcı — Herhangi bir şifreyi çevrimiçi tespit et',
                'meta_description'  => 'Herhangi bir şifreli metinden şifre türünü otomatik olarak tespit edin. Sezar, Vigenère, Playfair, Base64, Mors kodu ve 23 tür daha desteklenir.',
            ],
        ];
    }
}
