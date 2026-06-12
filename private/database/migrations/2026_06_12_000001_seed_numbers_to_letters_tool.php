<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент «Numbers to Letters Converter» в категорию «Codes and Alphabets».
 */
class SeedNumbersToLettersTool extends Migration
{
    /**
     * Создаёт или обновляет запись инструмента, переводы и контент.
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
            ['numbers-to-letters']
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
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'numbers-to-letters']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'numbers-to-letters', 'client', 120, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['client', 120, 1, $now, $cipherId]
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
        // Блок 1: Как работает
        $block1 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $this->upsertBlockTranslation($block1, 'en', 'How the converter works', '<p>This tool converts numbers to letters and letters to numbers using several encoding systems. Choose the one that matches your task.</p><p><strong>Positional (A=1):</strong> Each letter is mapped to its position in the alphabet, starting from 1. A=1, B=2 … Z=26. For Russian: А=1, Б=2 … Я=33.</p><p><strong>Positional (A=0):</strong> Same as above, but counting starts from 0. A=0, B=1 … Z=25.</p><p><strong>ASCII decimal:</strong> Each character is replaced by its decimal ASCII/Unicode code point. A=65, a=97, space=32.</p><p><strong>ASCII hex:</strong> Code points in uppercase hexadecimal. A=41, a=61.</p><p><strong>ASCII binary:</strong> Code points as 8-bit zero-padded binary. A=01000001.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Как работает конвертер', '<p>Инструмент переводит числа в буквы и буквы в числа по нескольким системам кодирования. Выберите ту, которая подходит для вашей задачи.</p><p><strong>Позиционный (А=1):</strong> Каждой букве соответствует её порядковый номер в алфавите, начиная с 1. А=1, Б=2 … Я=33. Для английского: A=1, B=2 … Z=26.</p><p><strong>Позиционный (А=0):</strong> Аналогично, но нумерация с нуля. А=0, Б=1 … Я=32.</p><p><strong>ASCII десятичный:</strong> Каждый символ заменяется его десятичным кодом ASCII/Unicode. A=65, a=97, пробел=32.</p><p><strong>ASCII шестнадцатеричный:</strong> Коды в виде шестнадцатеричных чисел верхнего регистра. A=41, a=61.</p><p><strong>ASCII двоичный:</strong> Коды в виде 8-битных двоичных чисел с нулями слева. A=01000001.</p>', $now);

        // Блок 2: Таблица алфавита
        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Supported languages', '<p>The positional mode supports eight languages with their native alphabets: English (26 letters), Russian (33 letters), German (29 letters including Ä, Ö, Ü), Spanish (27 letters including Ñ), French (40 letters with accents), Italian (26 letters), Portuguese (35 letters with accents), Turkish (29 letters including Ç, Ğ, İ, Ö, Ş, Ü).</p><p>Alphabet auto-detection analyses character frequencies to pick the most likely language. For ASCII, hex and binary modes the alphabet setting has no effect — characters are encoded by their Unicode code point directly.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Поддерживаемые языки', '<p>Позиционный режим поддерживает восемь языков с их родными алфавитами: английский (26 букв), русский (33 буквы), немецкий (29 букв, включая Ä, Ö, Ü), испанский (27 букв, включая Ñ), французский (40 букв с диакритикой), итальянский (26 букв), португальский (35 букв с диакритикой), турецкий (29 букв, включая Ç, Ğ, İ, Ö, Ş, Ü).</p><p>Автоопределение алфавита анализирует частоту символов, чтобы выбрать наиболее вероятный язык. В режимах ASCII, hex и двоичном настройка алфавита не влияет на результат — символы кодируются напрямую по кодовой точке Unicode.</p>', $now);

        // Примеры
        $ex1 = $this->upsertExample($cipherId, 10, 'encrypt', 'space', $now);
        $this->upsertExampleTranslation($ex1, 'en', 'Numbers to letters (A=1)', '8 5 12 12 15', 'hello', '', 'Positional 1-based, English alphabet, space delimiter.', $now);
        $this->upsertExampleTranslation($ex1, 'ru', 'Числа в буквы (А=1)', '8 5 12 12 15', 'hello', '', 'Позиционный с 1, английский алфавит, разделитель — пробел.', $now);

        $ex2 = $this->upsertExample($cipherId, 20, 'decrypt', 'space', $now);
        $this->upsertExampleTranslation($ex2, 'en', 'Letters to numbers (A=1)', 'Hello World', '8 5 12 12 15 23 15 18 12 4', '', 'Positional 1-based, English alphabet, space delimiter.', $now);
        $this->upsertExampleTranslation($ex2, 'ru', 'Буквы в числа (А=1)', 'Привет', '17 18 10 3 6 21', '', 'Позиционный с 1, русский алфавит, разделитель — пробел.', $now);

        $ex3 = $this->upsertExample($cipherId, 30, 'encrypt', 'space', $now);
        $this->upsertExampleTranslation($ex3, 'en', 'ASCII decimal mode', '72 101 108 108 111', 'Hello', '', 'ASCII decimal encoding: each number is a Unicode code point.', $now);
        $this->upsertExampleTranslation($ex3, 'ru', 'Режим ASCII десятичный', '72 101 108 108 111', 'Hello', '', 'ASCII десятичный: каждое число — кодовая точка Unicode.', $now);

        $ex4 = $this->upsertExample($cipherId, 40, 'encrypt', 'space', $now);
        $this->upsertExampleTranslation($ex4, 'en', 'ASCII binary mode', '01001000 01101001', 'Hi', '', 'ASCII binary: 8-bit zero-padded binary code for each character.', $now);
        $this->upsertExampleTranslation($ex4, 'ru', 'Режим ASCII двоичный', '01001000 01101001', 'Hi', '', 'ASCII двоичный: 8-битный двоичный код для каждого символа.', $now);

        // FAQ
        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now);
        $this->upsertFaqTranslation($faq1, 'en', 'What is the difference between A=1 and A=0 modes?', 'In A=1 (1-based) mode the first letter of the alphabet maps to 1, which matches the classic A1Z26 notation. In A=0 (0-based) mode the first letter maps to 0, which is more convenient in programming contexts.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'В чём разница между режимами А=1 и А=0?', 'В режиме А=1 (с единицы) первая буква алфавита соответствует числу 1, как в классической нотации A1Z26. В режиме А=0 (с нуля) первая буква соответствует нулю, что удобнее в программировании.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now);
        $this->upsertFaqTranslation($faq2, 'en', 'Which delimiter should I use?', 'For positional modes (A=1, A=0) use a space or dash to separate numbers within a word. The converter treats spaces as word boundaries. For ASCII/Hex/Binary modes a space is the standard delimiter.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Какой разделитель выбрать?', 'Для позиционных режимов (А=1, А=0) используйте пробел или тире, чтобы разделять числа внутри слова. Конвертер считает пробел границей слова. Для режимов ASCII/Hex/двоичный пробел является стандартным разделителем.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now);
        $this->upsertFaqTranslation($faq3, 'en', 'Does this tool work with non-Latin alphabets?', 'Yes. For positional modes you can switch to Russian, German, Spanish, French, Italian, Portuguese or Turkish. The "Auto" option detects the language from the input text. ASCII, hex and binary modes encode any Unicode character regardless of language.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Работает ли инструмент с нелатинскими алфавитами?', 'Да. В позиционных режимах можно переключиться на русский, немецкий, испанский, французский, итальянский, португальский или турецкий. Режим «Авто» определяет язык по входному тексту. Режимы ASCII, hex и двоичный кодируют любой символ Unicode вне зависимости от языка.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now);
        $this->upsertFaqTranslation($faq4, 'en', 'What is the difference between this and the A1Z26 cipher?', 'The A1Z26 cipher is a specific positional substitution (A=1). This converter extends the concept with A=0, ASCII, hex and binary modes, plus support for 8 language alphabets and configurable delimiters. If you only need A=1, both tools give the same result.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Чем этот инструмент отличается от шифра A1Z26?', 'Шифр A1Z26 — это конкретная позиционная замена (A=1). Данный конвертер расширяет концепцию: добавлены режимы А=0, ASCII, hex и двоичный, поддержка 8 языков и настраиваемые разделители. Если нужен только режим А=1, оба инструмента дают одинаковый результат.', $now);

        // Теги
        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Number to letter', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Числа в буквы', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'ASCII converter', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'ASCII конвертер', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Binary encoder', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Двоичный кодировщик', $now);

        $tag4 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 40, $now);
        $this->upsertTagTranslation($tag4, 'en', 'Multilingual alphabet', $now);
        $this->upsertTagTranslation($tag4, 'ru', 'Многоязычный алфавит', $now);
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
    private function upsertExample(int $cipherId, int $sortOrder, string $direction, string $delimiter, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET direction = ?, delimiter = ?, published = 1, updated_at = ? WHERE id = ?',
                [$direction, $delimiter, $now, (int) $row['id']]
            );
            return (int) $row['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, direction, delimiter, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $direction, $delimiter, $now, $now]
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
     * Возвращает переводы для всех языков.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'             => 'Numbers to Letters Converter',
                'name_short'       => 'Numbers ↔ Letters',
                'description'      => 'Convert numbers to letters and letters to numbers online. Supports positional (A=1, A=0), ASCII decimal, hex, and binary encoding for 8 language alphabets with auto-detection.',
                'description_stort' => 'Convert numbers to letters and letters to numbers with positional and ASCII modes.',
                'meta_title'       => 'Numbers to Letters Converter Online | Letters to Numbers',
                'meta_description' => 'Free online converter: turn numbers into letters or letters into numbers. Supports A=1, A=0, ASCII, hex and binary. Works with English, Russian, German and 5 more alphabets.',
            ],
            'ru' => [
                'name'             => 'Конвертер чисел в буквы',
                'name_short'       => 'Числа ↔ Буквы',
                'description'      => 'Переводите числа в буквы и буквы в числа онлайн. Поддерживаются позиционный режим (А=1, А=0), ASCII десятичный, шестнадцатеричный и двоичный — для 8 языков с автоопределением.',
                'description_stort' => 'Перевод чисел в буквы и букв в числа: позиционный режим и ASCII.',
                'meta_title'       => 'Конвертер чисел в буквы онлайн | Числа в текст',
                'meta_description' => 'Бесплатный онлайн-конвертер: числа в буквы и буквы в числа. Режимы А=1, А=0, ASCII, hex и двоичный. Поддержка русского, английского и 6 других алфавитов.',
            ],
            'de' => [
                'name'             => 'Zahlen-zu-Buchstaben-Konverter',
                'name_short'       => 'Zahlen ↔ Buchstaben',
                'description'      => 'Zahlen in Buchstaben und Buchstaben in Zahlen online umwandeln. Unterstützt positionsbasierte Modi (A=1, A=0), dezimales, hexadezimales und binäres ASCII — für 8 Sprachen mit automatischer Erkennung.',
                'description_stort' => 'Zahlen in Buchstaben und Buchstaben in Zahlen konvertieren.',
                'meta_title'       => 'Zahlen zu Buchstaben Konverter Online | Buchstaben zu Zahlen',
                'meta_description' => 'Kostenloser Online-Konverter: Zahlen in Buchstaben oder Buchstaben in Zahlen. Modi A=1, A=0, ASCII, Hex und Binär. Unterstützt Deutsch, Englisch und 6 weitere Alphabete.',
            ],
            'es' => [
                'name'             => 'Conversor de Números a Letras',
                'name_short'       => 'Números ↔ Letras',
                'description'      => 'Convierte números en letras y letras en números online. Compatible con modos posicionales (A=1, A=0), ASCII decimal, hexadecimal y binario para 8 idiomas con detección automática.',
                'description_stort' => 'Convierte números en letras y letras en números con modos posicional y ASCII.',
                'meta_title'       => 'Conversor de Números a Letras Online | Letras a Números',
                'meta_description' => 'Conversor online gratuito: números a letras o letras a números. Modos A=1, A=0, ASCII, hex y binario. Compatible con español, inglés y 6 alfabetos más.',
            ],
            'fr' => [
                'name'             => 'Convertisseur Chiffres-Lettres',
                'name_short'       => 'Chiffres ↔ Lettres',
                'description'      => 'Convertissez des chiffres en lettres et des lettres en chiffres en ligne. Modes positionnels (A=1, A=0), ASCII décimal, hexadécimal et binaire pour 8 langues avec détection automatique.',
                'description_stort' => 'Convertir des chiffres en lettres et des lettres en chiffres en ligne.',
                'meta_title'       => 'Convertisseur Chiffres en Lettres | Lettres en Chiffres',
                'meta_description' => 'Convertisseur en ligne gratuit : chiffres en lettres ou lettres en chiffres. Modes A=1, A=0, ASCII, hex et binaire. Compatible avec le français, l\'anglais et 6 autres alphabets.',
            ],
            'it' => [
                'name'             => 'Convertitore Numeri-Lettere',
                'name_short'       => 'Numeri ↔ Lettere',
                'description'      => 'Converti numeri in lettere e lettere in numeri online. Modalità posizionali (A=1, A=0), ASCII decimale, esadecimale e binario per 8 lingue con rilevamento automatico.',
                'description_stort' => 'Converti numeri in lettere e lettere in numeri online.',
                'meta_title'       => 'Convertitore Numeri in Lettere Online | Lettere in Numeri',
                'meta_description' => 'Convertitore online gratuito: numeri in lettere o lettere in numeri. Modalità A=1, A=0, ASCII, hex e binario. Supporta italiano, inglese e altri 6 alfabeti.',
            ],
            'pt' => [
                'name'             => 'Conversor de Números para Letras',
                'name_short'       => 'Números ↔ Letras',
                'description'      => 'Converta números em letras e letras em números online. Modos posicionais (A=1, A=0), ASCII decimal, hexadecimal e binário para 8 idiomas com detecção automática.',
                'description_stort' => 'Converta números em letras e letras em números online.',
                'meta_title'       => 'Conversor de Números para Letras Online | Letras para Números',
                'meta_description' => 'Conversor online gratuito: números em letras ou letras em números. Modos A=1, A=0, ASCII, hex e binário. Suporte para português, inglês e mais 6 alfabetos.',
            ],
            'tr' => [
                'name'             => 'Sayıdan Harfe Dönüştürücü',
                'name_short'       => 'Sayılar ↔ Harfler',
                'description'      => 'Sayıları harflere ve harfleri sayılara çevirin. Konumsal modlar (A=1, A=0), ASCII ondalık, onaltılık ve ikili — 8 dil alfabesi için otomatik algılama ile.',
                'description_stort' => 'Sayıları harflere ve harfleri sayılara çevirin.',
                'meta_title'       => 'Sayıdan Harfe Dönüştürücü Online | Harften Sayıya',
                'meta_description' => 'Ücretsiz online dönüştürücü: sayılardan harflere veya harflerden sayılara. A=1, A=0, ASCII, hex ve ikili modlar. Türkçe, İngilizce ve 6 alfabe daha destekleniyor.',
            ],
        ];
    }
}
