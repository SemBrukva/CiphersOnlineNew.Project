<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент «Affine Brute Force» в категорию «Анализ текста и криптоанализ».
 */
class SeedAffineBruteForceTool extends Migration
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
            ['affine-brute-force']
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
            ['affine-brute-force']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'api', 25, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'affine-brute-force', 'api', 25, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'How affine brute force works', '<p>The affine cipher encrypts each letter using the formula E(x) = (a·x + b) mod m, where m is the alphabet size, a is the multiplier, and b is the shift. The key requirement is that gcd(a, m) = 1 — that is, a and m must be coprime.</p><p>For the English alphabet (m = 26), there are 12 valid values of a and 26 values of b, yielding 312 possible key pairs. This tool automatically decrypts the input with every valid (a, b) pair and ranks the results by statistical similarity to natural language text.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Как работает перебор ключей аффинного шифра', '<p>Аффинный шифр шифрует каждую букву по формуле E(x) = (a·x + b) mod m, где m — размер алфавита, a — множитель, b — сдвиг. Обязательное условие: НОД(a, m) = 1, то есть a и m должны быть взаимно простыми.</p><p>Для английского алфавита (m = 26) существует 12 допустимых значений a и 26 значений b, что даёт 312 возможных пар ключей. Этот инструмент автоматически дешифрует введённый текст всеми допустимыми парами (a, b) и ранжирует результаты по статистическому сходству с естественным текстом.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'When to use affine brute force', '<p>Use this tool when you suspect a text has been encrypted with an affine cipher but you do not know the key values a and b. Paste the ciphertext, select the appropriate language, and scan the ranked results for the decryption that produces recognisable words.</p><p>The tool assigns a fitness score to each result based on letter frequency analysis: the higher the score, the more likely that decryption is the correct plaintext.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Когда использовать перебор аффинного шифра', '<p>Используйте этот инструмент, если вы подозреваете, что текст зашифрован аффинным шифром, но не знаете значения ключей a и b. Вставьте зашифрованный текст, выберите язык и найдите среди ранжированных результатов вариант с читаемыми словами.</p><p>Инструмент присваивает каждому результату оценку пригодности на основе частотного анализа букв: чем выше оценка, тем более вероятно, что данная дешифровка является правильным открытым текстом.</p>', $now);

        $example1Cipher = 'Ihhwvc swfrcpu cvspyfz cisr lczzcp owzr zoa veqcpws gcyu ivx pcjcil livmeimc fizzcpvu evxcp ivilyuwu';
        $example1Plain  = 'Affine ciphers encrypt each letter with two numeric keys and reveal language patterns under analysis.';
        $example1       = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'English (a=5, b=8)', $example1Cipher, '', '', 'Decoded with a=5, b=8: ' . $example1Plain, $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Английский (a=5, b=8)', $example1Cipher, '', '', 'Дешифруется при a=5, b=8: ' . $example1Plain, $now);

        $example2Cipher = 'Ksngf mxsrf dggdrvz gsp fufsp udchy vfp edhs nqghc gaf ecdhq gfig fjfstfz hq sfdydkcf mxsj mxs dqdcpzhz';
        $example2Plain  = 'Brute force attacks try every valid key pair until the plain text emerges in readable form for analysis.';
        $example2       = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'English (a=7, b=3)', $example2Cipher, '', '', 'Decoded with a=7, b=3: ' . $example2Plain, $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Английский (a=7, b=3)', $example2Cipher, '', '', 'Дешифруется при a=7, b=3: ' . $example2Plain, $now);

        $example3Cipher = 'Бунъишда у ъзябунърспсзшда даэяд зннуллпж бунърж я ысижг ьуяврспжу эвюьзжу';
        $example3Plain  = 'Шифруйте и расшифровывайте текст аффинным шифром с двумя числовыми ключами.';
        $example3       = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Russian (a=5, b=8)', $example3Cipher, '', '', 'Decoded with a=5, b=8 (Russian alphabet): ' . $example3Plain, $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Русский (a=5, b=8)', $example3Cipher, '', '', 'Дешифруется при a=5, b=8 (русский алфавит): ' . $example3Plain, $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'How many key combinations does the affine cipher have?', 'The number of valid key pairs (a, b) depends on the alphabet size m. For the multiplier a, only values coprime with m are valid. For the English alphabet (m = 26), there are 12 valid values of a and 26 values of b, giving 312 key pairs. For Russian (m = 32), there are 16 valid values of a and 32 values of b, giving 512 key pairs.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Сколько комбинаций ключей у аффинного шифра?', 'Количество допустимых пар ключей (a, b) зависит от размера алфавита m. Для множителя a допустимы только значения, взаимно простые с m. Для английского алфавита (m = 26) существует 12 допустимых значений a и 26 значений b, что даёт 312 пар ключей. Для русского (m = 32) — 16 допустимых значений a и 32 значения b, итого 512 пар.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Why must the multiplier a be coprime with the alphabet size?', 'The coprimality condition gcd(a, m) = 1 ensures that the encryption function is a bijection — every plaintext letter maps to a unique ciphertext letter. If gcd(a, m) > 1, several plaintext letters would map to the same ciphertext letter, making decryption impossible. This is why not all values of a are valid multipliers.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Почему множитель a должен быть взаимно простым с размером алфавита?', 'Условие взаимной простоты НОД(a, m) = 1 гарантирует, что функция шифрования является биекцией — каждой букве открытого текста соответствует уникальная буква шифртекста. Если НОД(a, m) > 1, несколько букв открытого текста будут отображаться в одну и ту же букву шифртекста, что делает дешифровку невозможной. Именно поэтому не все значения a являются допустимыми множителями.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Is the affine cipher secure?', 'No. Despite having more key pairs than the Caesar cipher, the affine cipher is still trivially breakable. With a small key space (312 pairs for English), brute force is instant. It is also vulnerable to frequency analysis and known-plaintext attacks. The affine cipher should never be used to protect sensitive information.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Надёжен ли аффинный шифр?', 'Нет. Несмотря на то что у него больше пар ключей, чем у шифра Цезаря, аффинный шифр по-прежнему тривиально взламывается. При небольшом пространстве ключей (312 пар для английского) перебор мгновенен. Шифр также уязвим к частотному анализу и атакам с известным открытым текстом. Аффинный шифр никогда не следует использовать для защиты конфиденциальных данных.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Affine cipher', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Аффинный шифр', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Brute force', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Перебор', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Cryptanalysis', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Криптоанализ', $now);
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
     * Возвращает переводы инструмента перебора ключей аффинного шифра.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Affine Brute Force',
                'name_short'        => 'Affine Brute Force',
                'description'       => 'Automatically try all valid affine cipher key combinations and display every decryption result at once. Identify the correct plaintext in seconds without knowing the key pair (a, b).',
                'description_stort' => 'Try all valid affine cipher key pairs to find the correct decryption instantly.',
                'meta_title'        => 'Affine Cipher Brute Force | Try All Keys Online',
                'meta_description'  => 'Automatically try all valid affine cipher key pairs online. Instantly find the correct decryption without knowing a and b — perfect for cryptanalysis and CTF challenges.',
            ],
            'ru' => [
                'name'              => 'Перебор ключей аффинного шифра',
                'name_short'        => 'Перебор аффинного',
                'description'       => 'Автоматически перебирайте все допустимые комбинации ключей аффинного шифра и просматривайте все варианты дешифровки сразу. Определите правильный открытый текст за секунды, не зная пары ключей (a, b).',
                'description_stort' => 'Переберите все допустимые пары ключей аффинного шифра для мгновенной дешифровки.',
                'meta_title'        => 'Перебор аффинного шифра онлайн | Все ключи',
                'meta_description'  => 'Автоматически перебирайте все допустимые пары ключей аффинного шифра онлайн. Мгновенно найдите правильную дешифровку без знания a и b — для криптоанализа и CTF-задач.',
            ],
            'de' => [
                'name'              => 'Affine Brute Force',
                'name_short'        => 'Affine Brute Force',
                'description'       => 'Alle gültigen affinen Schlüsselkombinationen automatisch ausprobieren und alle Entschlüsselungsergebnisse auf einmal anzeigen. Den korrekten Klartext in Sekunden identifizieren, ohne das Schlüsselpaar (a, b) zu kennen.',
                'description_stort' => 'Alle gültigen affinen Schlüsselpaare ausprobieren, um die korrekte Entschlüsselung sofort zu finden.',
                'meta_title'        => 'Affine Brute Force | Alle Schlüssel online',
                'meta_description'  => 'Alle gültigen affinen Schlüsselpaare online automatisch ausprobieren. Den korrekten Klartext sofort ohne Kenntnis von a und b finden – für Kryptoanalyse und CTF.',
            ],
            'es' => [
                'name'              => 'Fuerza bruta afín',
                'name_short'        => 'Fuerza bruta afín',
                'description'       => 'Prueba automáticamente todas las combinaciones de claves válidas del cifrado afín y muestra todos los resultados de descifrado a la vez. Identifica el texto original en segundos sin conocer el par de claves (a, b).',
                'description_stort' => 'Prueba todos los pares de claves afines válidos para encontrar el descifrado correcto al instante.',
                'meta_title'        => 'Afín Fuerza Bruta | Todas las claves online',
                'meta_description'  => 'Prueba automáticamente todos los pares de claves afines válidos online. Encuentra el descifrado correcto sin conocer a y b — para criptoanálisis y CTF.',
            ],
            'fr' => [
                'name'              => 'Affine force brute',
                'name_short'        => 'Affine force brute',
                'description'       => 'Essayez automatiquement toutes les combinaisons de clés affines valides et affichez tous les résultats de déchiffrement en même temps. Identifiez le texte clair correct en quelques secondes sans connaître la paire de clés (a, b).',
                'description_stort' => 'Essayez toutes les paires de clés affines valides pour trouver le déchiffrement correct instantanément.',
                'meta_title'        => 'Affine force brute | Toutes les clés en ligne',
                'meta_description'  => 'Essayez automatiquement toutes les paires de clés affines valides en ligne. Trouvez instantanément le bon déchiffrement sans connaître a et b — pour la cryptanalyse et les CTF.',
            ],
            'it' => [
                'name'              => 'Affine forza bruta',
                'name_short'        => 'Affine forza bruta',
                'description'       => 'Prova automaticamente tutte le combinazioni di chiavi affini valide e visualizza tutti i risultati di decifratura contemporaneamente. Identifica il testo in chiaro corretto in pochi secondi senza conoscere la coppia di chiavi (a, b).',
                'description_stort' => 'Prova tutte le coppie di chiavi affini valide per trovare la decifratura corretta all\'istante.',
                'meta_title'        => 'Affine forza bruta | Tutte le chiavi online',
                'meta_description'  => 'Prova automaticamente tutte le coppie di chiavi affini valide online. Trova immediatamente la decifratura corretta senza conoscere a e b — per la crittoanalisi e le sfide CTF.',
            ],
            'pt' => [
                'name'              => 'Afim força bruta',
                'name_short'        => 'Afim força bruta',
                'description'       => 'Tente automaticamente todas as combinações de chaves afins válidas e exiba todos os resultados de decifração de uma vez. Identifique o texto simples correto em segundos sem conhecer o par de chaves (a, b).',
                'description_stort' => 'Tente todos os pares de chaves afins válidos para encontrar a decifração correta instantaneamente.',
                'meta_title'        => 'Afim Força Bruta | Todas as chaves online',
                'meta_description'  => 'Tente automaticamente todos os pares de chaves afins válidos online. Encontre instantaneamente a decifração correta sem conhecer a e b — para criptoanálise e CTF.',
            ],
            'tr' => [
                'name'              => 'Afin kaba kuvvet',
                'name_short'        => 'Afin kaba kuvvet',
                'description'       => 'Tüm geçerli afin şifre anahtar kombinasyonlarını otomatik olarak deneyin ve tüm şifre çözme sonuçlarını aynı anda görüntüleyin. Anahtar çiftini (a, b) bilmeden saniyeler içinde doğru düz metni belirleyin.',
                'description_stort' => 'Anahtarı bilmeden doğru şifre çözümünü anında bulmak için tüm geçerli afin anahtar çiftlerini deneyin.',
                'meta_title'        => 'Afin Kaba Kuvvet | Tüm anahtarlar çevrimiçi',
                'meta_description'  => 'Tüm geçerli afin şifre anahtar çiftlerini çevrimiçi otomatik deneyin. a ve b\'yi bilmeden doğru şifre çözümünü anında bulun — kriptoanalizis ve CTF için.',
            ],
        ];
    }
}
