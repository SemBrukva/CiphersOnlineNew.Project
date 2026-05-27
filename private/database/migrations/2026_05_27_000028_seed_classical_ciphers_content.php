<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Заполняет контентные сущности для классических шифров: Цезарь, Плейфер, Бофор, Гронсфельд и Виженер.
 */
class SeedClassicalCiphersContent extends Migration
{
    /**
     * Добавляет или обновляет блоки, примеры, FAQ и теги для заданных шифров.
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        foreach ($this->contentMap() as $alias => $content) {
            $cipher = $this->db->fetch(
                'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
                [$alias]
            );

            if ($cipher === false) {
                continue;
            }

            $cipherId = (int) $cipher['id'];
            $this->seedCipherContent($cipherId, $content, $now);
        }
    }

    /**
     * Удаляет сгенерированный контент для целевых шифров.
     */
    public function down(): void
    {
        foreach (array_keys($this->contentMap()) as $alias) {
            $cipher = $this->db->fetch(
                'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
                [$alias]
            );

            if ($cipher === false) {
                continue;
            }

            $cipherId = (int) $cipher['id'];
            $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ?', [$cipherId]);
            $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ?', [$cipherId]);
            $this->db->execute('DELETE FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ?', [$cipherId]);
            $this->db->execute('DELETE FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ?', [$cipherId]);
        }
    }

    /**
     * Заполняет контентные сущности для одного шифра.
     *
     * @param array{
     *   block: array{en: array{title: string, text: string}, ru: array{title: string, text: string}},
     *   examples: array<int, array{sort: int, en: array{title: string, input: string, output: string, description: string}, ru: array{title: string, input: string, output: string, description: string}}>,
     *   faq: array<int, array{sort: int, en: array{question: string, answer: string}, ru: array{question: string, answer: string}}>,
     *   tags: array<int, array{sort: int, en: string, ru: string}>
     * } $content
     */
    private function seedCipherContent(int $cipherId, array $content, string $now): void
    {
        $block = $this->upsertBlock($cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', $content['block']['en']['title'], $content['block']['en']['text'], $now);
        $this->upsertBlockTranslation($block, 'ru', $content['block']['ru']['title'], $content['block']['ru']['text'], $now);

        foreach ($content['examples'] as $example) {
            $exampleId = $this->upsertExample($cipherId, (int) $example['sort'], $now);
            $this->upsertExampleTranslation(
                $exampleId,
                'en',
                $example['en']['title'],
                $example['en']['input'],
                $example['en']['output'],
                $example['en']['description'],
                $now
            );
            $this->upsertExampleTranslation(
                $exampleId,
                'ru',
                $example['ru']['title'],
                $example['ru']['input'],
                $example['ru']['output'],
                $example['ru']['description'],
                $now
            );
        }

        foreach ($content['faq'] as $faq) {
            $faqId = $this->upsertFaq($cipherId, (int) $faq['sort'], $now);
            $this->upsertFaqTranslation($faqId, 'en', $faq['en']['question'], $faq['en']['answer'], $now);
            $this->upsertFaqTranslation($faqId, 'ru', $faq['ru']['question'], $faq['ru']['answer'], $now);
        }

        foreach ($content['tags'] as $tag) {
            $tagId = $this->upsertTag($cipherId, (int) $tag['sort'], $now);
            $this->upsertTagTranslation($tagId, 'en', $tag['en'], $now);
            $this->upsertTagTranslation($tagId, 'ru', $tag['ru'], $now);
        }
    }

    /**
     * Возвращает карту контента по alias шифра.
     *
     * @return array<string, array{
     *   block: array{en: array{title: string, text: string}, ru: array{title: string, text: string}},
     *   examples: array<int, array{sort: int, en: array{title: string, input: string, output: string, description: string}, ru: array{title: string, input: string, output: string, description: string}}>,
     *   faq: array<int, array{sort: int, en: array{question: string, answer: string}, ru: array{question: string, answer: string}}>,
     *   tags: array<int, array{sort: int, en: string, ru: string}>
     * }>
     */
    private function contentMap(): array
    {
        return [
            'caesar' => [
                'block' => [
                    'en' => [
                        'title' => 'How Caesar cipher works',
                        'text' => 'Each letter is shifted by a fixed number of positions in the selected alphabet. Encryption applies positive shift, decryption applies negative shift.',
                    ],
                    'ru' => [
                        'title' => 'Как работает шифр Цезаря',
                        'text' => 'Каждая буква сдвигается на фиксированное число позиций в выбранном алфавите. При шифровании сдвиг положительный, при расшифровке — обратный.',
                    ],
                ],
                'examples' => [
                    [
                        'sort' => 10,
                        'en' => ['title' => 'Shift by 3', 'input' => 'HELLO WORLD', 'output' => 'KHOOR ZRUOG', 'description' => 'Alphabet: English, shift: 3, mode: encrypt.'],
                        'ru' => ['title' => 'Сдвиг на 3', 'input' => 'HELLO WORLD', 'output' => 'KHOOR ZRUOG', 'description' => 'Алфавит: English, сдвиг: 3, режим: шифрование.'],
                    ],
                    [
                        'sort' => 20,
                        'en' => ['title' => 'Reverse shift', 'input' => 'KHOOR ZRUOG', 'output' => 'HELLO WORLD', 'description' => 'Alphabet: English, shift: 3, mode: decrypt.'],
                        'ru' => ['title' => 'Обратный сдвиг', 'input' => 'KHOOR ZRUOG', 'output' => 'HELLO WORLD', 'description' => 'Алфавит: English, сдвиг: 3, режим: расшифровка.'],
                    ],
                ],
                'faq' => [
                    [
                        'sort' => 10,
                        'en' => ['question' => 'Is Caesar cipher secure?', 'answer' => 'No. It is easy to brute-force and mainly used for education.'],
                        'ru' => ['question' => 'Надёжен ли шифр Цезаря?', 'answer' => 'Нет. Он легко подбирается перебором и используется в учебных целях.'],
                    ],
                    [
                        'sort' => 20,
                        'en' => ['question' => 'Can I use non-Latin text?', 'answer' => 'Yes. Choose the matching alphabet in settings before processing text.'],
                        'ru' => ['question' => 'Можно ли использовать не латиницу?', 'answer' => 'Да. Перед обработкой выберите подходящий алфавит в настройках.'],
                    ],
                ],
                'tags' => [
                    ['sort' => 10, 'en' => 'Shift cipher', 'ru' => 'Шифр сдвига'],
                    ['sort' => 20, 'en' => 'Classical cryptography', 'ru' => 'Классическая криптография'],
                    ['sort' => 30, 'en' => 'Educational', 'ru' => 'Учебный'],
                ],
            ],
            'playfair' => [
                'block' => [
                    'en' => [
                        'title' => 'How Playfair cipher works',
                        'text' => 'Playfair encrypts pairs of letters using a 5x5 matrix built from the key. Rules depend on same row, same column or rectangle positions.',
                    ],
                    'ru' => [
                        'title' => 'Как работает шифр Плейфера',
                        'text' => 'Плейфер шифрует пары букв по матрице 5x5, построенной из ключа. Применяются правила для одной строки, одного столбца или прямоугольника.',
                    ],
                ],
                'examples' => [
                    [
                        'sort' => 10,
                        'en' => ['title' => 'Encrypt with MONARCHY', 'input' => 'HELLO WORLD', 'output' => 'CFSUPM VNMTBZ', 'description' => 'Keyword: MONARCHY, mode: encrypt.'],
                        'ru' => ['title' => 'Шифрование с ключом MONARCHY', 'input' => 'HELLO WORLD', 'output' => 'CFSUPM VNMTBZ', 'description' => 'Ключ: MONARCHY, режим: шифрование.'],
                    ],
                    [
                        'sort' => 20,
                        'en' => ['title' => 'Decrypt pair text', 'input' => 'CFSUPM VNMTBZ', 'output' => 'HELXLO WORLDX', 'description' => 'Keyword: MONARCHY, mode: decrypt. Padding X may appear.'],
                        'ru' => ['title' => 'Расшифровка биграмм', 'input' => 'CFSUPM VNMTBZ', 'output' => 'HELXLO WORLDX', 'description' => 'Ключ: MONARCHY, режим: расшифровка. Возможны служебные X.'],
                    ],
                ],
                'faq' => [
                    [
                        'sort' => 10,
                        'en' => ['question' => 'Why are extra letters added?', 'answer' => 'Playfair requires even-length digraphs, so filler letters can be inserted.'],
                        'ru' => ['question' => 'Почему добавляются лишние буквы?', 'answer' => 'Плейфер работает с чётным числом символов в биграммах, поэтому вставляются заполнители.'],
                    ],
                    [
                        'sort' => 20,
                        'en' => ['question' => 'Does key quality matter?', 'answer' => 'Yes. Better key diversity produces less predictable matrices.'],
                        'ru' => ['question' => 'Важен ли выбор ключа?', 'answer' => 'Да. Более разнообразный ключ даёт менее предсказуемую матрицу.'],
                    ],
                ],
                'tags' => [
                    ['sort' => 10, 'en' => 'Digraph cipher', 'ru' => 'Биграммный шифр'],
                    ['sort' => 20, 'en' => 'Key matrix', 'ru' => 'Ключевая матрица'],
                    ['sort' => 30, 'en' => 'Classical cryptography', 'ru' => 'Классическая криптография'],
                ],
            ],
            'beaufort' => [
                'block' => [
                    'en' => [
                        'title' => 'How Beaufort cipher works',
                        'text' => 'Beaufort is a polyalphabetic substitution where each output symbol is computed from key position minus text position in alphabet.',
                    ],
                    'ru' => [
                        'title' => 'Как работает шифр Бофора',
                        'text' => 'Бофор — полиалфавитная подстановка, где каждый выходной символ вычисляется как позиция ключа минус позиция текста в алфавите.',
                    ],
                ],
                'examples' => [
                    [
                        'sort' => 10,
                        'en' => ['title' => 'Encrypt with FORT', 'input' => 'DEFEND THE EAST WALL', 'output' => 'CKMPSL YMB KRBM SRIU', 'description' => 'Keyword: FORT, mode: process.'],
                        'ru' => ['title' => 'Шифрование с ключом FORT', 'input' => 'DEFEND THE EAST WALL', 'output' => 'CKMPSL YMB KRBM SRIU', 'description' => 'Ключ: FORT, режим: обработка.'],
                    ],
                    [
                        'sort' => 20,
                        'en' => ['title' => 'Reciprocal decode', 'input' => 'CKMPSL YMB KRBM SRIU', 'output' => 'DEFEND THE EAST WALL', 'description' => 'Applying Beaufort again with same key restores text.'],
                        'ru' => ['title' => 'Обратимость', 'input' => 'CKMPSL YMB KRBM SRIU', 'output' => 'DEFEND THE EAST WALL', 'description' => 'Повторное применение Бофора с тем же ключом восстанавливает текст.'],
                    ],
                ],
                'faq' => [
                    [
                        'sort' => 10,
                        'en' => ['question' => 'Is Beaufort symmetric?', 'answer' => 'Yes. The same transformation can be used for both encryption and decryption.'],
                        'ru' => ['question' => 'Симметричен ли Бофор?', 'answer' => 'Да. Одна и та же операция используется и для шифрования, и для расшифровки.'],
                    ],
                    [
                        'sort' => 20,
                        'en' => ['question' => 'Can key include spaces?', 'answer' => 'Only alphabet symbols participate in transformation; other characters are ignored in key stream.'],
                        'ru' => ['question' => 'Можно ли использовать пробелы в ключе?', 'answer' => 'В преобразовании участвуют только символы алфавита; остальные символы в ключевом потоке игнорируются.'],
                    ],
                ],
                'tags' => [
                    ['sort' => 10, 'en' => 'Polyalphabetic', 'ru' => 'Полиалфавитный'],
                    ['sort' => 20, 'en' => 'Reciprocal cipher', 'ru' => 'Взаимообратимый шифр'],
                    ['sort' => 30, 'en' => 'Keyword cipher', 'ru' => 'Шифр по ключевому слову'],
                ],
            ],
            'gronsfeld' => [
                'block' => [
                    'en' => [
                        'title' => 'How Gronsfeld cipher works',
                        'text' => 'Gronsfeld is a Caesar-like polyalphabetic cipher that uses a numeric key. Each digit defines shift for corresponding text symbol.',
                    ],
                    'ru' => [
                        'title' => 'Как работает шифр Гронсфельда',
                        'text' => 'Гронсфельд — полиалфавитный вариант Цезаря с числовым ключом. Каждая цифра задаёт сдвиг для соответствующего символа текста.',
                    ],
                ],
                'examples' => [
                    [
                        'sort' => 10,
                        'en' => ['title' => 'Encrypt with 314159', 'input' => 'HELLO WORLD', 'output' => 'KFPMT ZPVMI', 'description' => 'Numeric key: 314159, mode: encrypt.'],
                        'ru' => ['title' => 'Шифрование с ключом 314159', 'input' => 'HELLO WORLD', 'output' => 'KFPMT ZPVMI', 'description' => 'Числовой ключ: 314159, режим: шифрование.'],
                    ],
                    [
                        'sort' => 20,
                        'en' => ['title' => 'Decrypt with 314159', 'input' => 'KFPMT ZPVMI', 'output' => 'HELLO WORLD', 'description' => 'Numeric key: 314159, mode: decrypt.'],
                        'ru' => ['title' => 'Расшифровка с ключом 314159', 'input' => 'KFPMT ZPVMI', 'output' => 'HELLO WORLD', 'description' => 'Числовой ключ: 314159, режим: расшифровка.'],
                    ],
                ],
                'faq' => [
                    [
                        'sort' => 10,
                        'en' => ['question' => 'What key format is supported?', 'answer' => 'Only digits are accepted in Gronsfeld key.'],
                        'ru' => ['question' => 'Какой формат ключа поддерживается?', 'answer' => 'Для ключа Гронсфельда допускаются только цифры.'],
                    ],
                    [
                        'sort' => 20,
                        'en' => ['question' => 'How long can key be?', 'answer' => 'Longer keys provide more variation; practical UI limit is applied by the tool validator.'],
                        'ru' => ['question' => 'Какой длины может быть ключ?', 'answer' => 'Более длинный ключ даёт больше вариативности; практический лимит длины контролируется валидатором инструмента.'],
                    ],
                ],
                'tags' => [
                    ['sort' => 10, 'en' => 'Numeric key', 'ru' => 'Числовой ключ'],
                    ['sort' => 20, 'en' => 'Polyalphabetic', 'ru' => 'Полиалфавитный'],
                    ['sort' => 30, 'en' => 'Classical cryptography', 'ru' => 'Классическая криптография'],
                ],
            ],
            'vigenere' => [
                'block' => [
                    'en' => [
                        'title' => 'How Vigenere cipher works',
                        'text' => 'Vigenere uses a repeated keyword and shifts each text letter by the position of corresponding key letter in selected alphabet.',
                    ],
                    'ru' => [
                        'title' => 'Как работает шифр Виженера',
                        'text' => 'Виженер использует повторяющееся ключевое слово и сдвигает каждую букву текста на позицию соответствующей буквы ключа в выбранном алфавите.',
                    ],
                ],
                'examples' => [
                    [
                        'sort' => 10,
                        'en' => ['title' => 'Encrypt ATTACK AT DAWN', 'input' => 'ATTACK AT DAWN', 'output' => 'LXFOPV EF RNHR', 'description' => 'Keyword: LEMON, mode: encrypt.'],
                        'ru' => ['title' => 'Шифрование ATTACK AT DAWN', 'input' => 'ATTACK AT DAWN', 'output' => 'LXFOPV EF RNHR', 'description' => 'Ключевое слово: LEMON, режим: шифрование.'],
                    ],
                    [
                        'sort' => 20,
                        'en' => ['title' => 'Decrypt LXFOPV EF RNHR', 'input' => 'LXFOPV EF RNHR', 'output' => 'ATTACK AT DAWN', 'description' => 'Keyword: LEMON, mode: decrypt.'],
                        'ru' => ['title' => 'Расшифровка LXFOPV EF RNHR', 'input' => 'LXFOPV EF RNHR', 'output' => 'ATTACK AT DAWN', 'description' => 'Ключевое слово: LEMON, режим: расшифровка.'],
                    ],
                ],
                'faq' => [
                    [
                        'sort' => 10,
                        'en' => ['question' => 'Can key be longer than text?', 'answer' => 'In this tool, key length should not exceed input text length.'],
                        'ru' => ['question' => 'Может ли ключ быть длиннее текста?', 'answer' => 'В этом инструменте длина ключа не должна превышать длину входного текста.'],
                    ],
                    [
                        'sort' => 20,
                        'en' => ['question' => 'Is Vigenere stronger than Caesar?', 'answer' => 'Yes, because it changes shift per symbol using key stream, not a single constant shift.'],
                        'ru' => ['question' => 'Надёжнее ли Виженер, чем Цезарь?', 'answer' => 'Да, потому что сдвиг меняется для каждого символа по ключевому потоку, а не остаётся постоянным.'],
                    ],
                ],
                'tags' => [
                    ['sort' => 10, 'en' => 'Keyword cipher', 'ru' => 'Шифр по ключевому слову'],
                    ['sort' => 20, 'en' => 'Polyalphabetic', 'ru' => 'Полиалфавитный'],
                    ['sort' => 30, 'en' => 'Classical cryptography', 'ru' => 'Классическая криптография'],
                ],
            ],
        ];
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
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_BLOCKS . ' SET published = 1, updated_at = ? WHERE id = ?',
                [$now, $id]
            );

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
     * Создаёт или обновляет пример по сортировке.
     */
    private function upsertExample(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET published = 1, updated_at = ? WHERE id = ?',
                [$now, $id]
            );

            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(
        int $exampleId,
        string $language,
        string $title,
        string $input,
        string $output,
        string $description,
        string $now
    ): void {
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
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_FAQ . ' SET published = 1, show_in_category = 0, updated_at = ? WHERE id = ?',
                [$now, $id]
            );

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
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_TAGS . ' SET published = 1, updated_at = ? WHERE id = ?',
                [$now, $id]
            );

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
}
