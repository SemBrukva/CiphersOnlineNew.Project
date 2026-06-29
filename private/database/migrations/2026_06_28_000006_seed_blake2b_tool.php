<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент BLAKE2b в категорию «Хеширование».
 */
class SeedBlake2bTool extends Migration
{
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['hashing']
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

    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['blake2b']
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

    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['blake2b']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 70, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'blake2b', 'client', 70, 1, $now, $now]
        );
    }

    /**
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

    private function seedContent(int $cipherId, string $now): void
    {
        $block1 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $this->upsertBlockTranslation($block1, 'en', 'What is BLAKE2b?', '<p>BLAKE2b is a cryptographic hash function published by Aumasson, Neves, Wilcox-O\'Hearn, and Winnerlein in 2012 (RFC 7693). It is a refinement of BLAKE — a SHA-3 finalist — optimized for 64-bit platforms. BLAKE2b produces up to 512-bit (64-byte) hash values, rendered as up to 128 hex characters.</p><p>BLAKE2b is faster than MD5, SHA-1, SHA-2, and SHA-3 in pure software while providing security comparable to SHA-3. It is the default hash function in Argon2 (the winner of the Password Hashing Competition), and widely used in cryptocurrencies (Zcash, Polkadot), modern Git alternatives, and high-performance integrity checking.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое BLAKE2b?', '<p>BLAKE2b — криптографическая хеш-функция, опубликованная Aumasson, Neves, Wilcox-O\'Hearn и Winnerlein в 2012 году (RFC 7693). Это улучшение BLAKE — финалиста SHA-3 — оптимизированное под 64-битные платформы. BLAKE2b выдаёт до 512-битных (64-байтовых) хешей, представляемых как до 128 hex-символов.</p><p>BLAKE2b быстрее MD5, SHA-1, SHA-2 и SHA-3 в чистом софте, при этом даёт безопасность, сравнимую с SHA-3. Это хеш-функция по умолчанию в Argon2 (победитель Password Hashing Competition), широко используется в криптовалютах (Zcash, Polkadot), современных альтернативах Git и высокопроизводительной проверке целостности.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'BLAKE2 family and parameters', '<p>The BLAKE2 family has two main variants: BLAKE2b (optimized for 64-bit, up to 64-byte output) and BLAKE2s (optimized for 32-bit, up to 32-byte output). Both support optional features built into the function itself: keyed hashing (a built-in MAC, no need for HMAC), salt, personalization, and tree hashing for parallel processing.</p><p>The default output for BLAKE2b is 64 bytes (128 hex). When you see BLAKE2b-256 or BLAKE2b-384, those are the same function configured to output 32 or 48 bytes. This tool produces the default 64-byte output.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Семейство BLAKE2 и параметры', '<p>Семейство BLAKE2 включает два основных варианта: BLAKE2b (оптимизирован под 64-битные платформы, вывод до 64 байт) и BLAKE2s (оптимизирован под 32-битные, вывод до 32 байт). Оба поддерживают встроенные опции: keyed hashing (встроенный MAC — HMAC не нужен), соль, персонализация, древовидное хеширование для параллельной обработки.</p><p>По умолчанию BLAKE2b выдаёт 64 байта (128 hex). Когда вы видите BLAKE2b-256 или BLAKE2b-384 — это та же функция, настроенная на 32 или 48 байт вывода. Этот инструмент выдаёт стандартные 64 байта.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Empty string', '', '786a02f742015903c6c6fd852552d272912f4740e15847618a86e217f71f5419d25e1031afee585313896444934eb04b903a685b1448b755d56f701afe9be2ce', '', 'The BLAKE2b hash of empty input — the default 64-byte output.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Пустая строка', '', '786a02f742015903c6c6fd852552d272912f4740e15847618a86e217f71f5419d25e1031afee585313896444934eb04b903a685b1448b755d56f701afe9be2ce', '', 'BLAKE2b от пустой строки — стандартный 64-байтовый вывод.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Plain text', 'hello world', '021ced8799296ceca557832ab941a50b4a11f83478cf141f51f933f653ab9fbcc05a037cddbed06e309bf334942c4e58cdf1a46e237911ccd7fcf9787cbc7fd0', '', 'A short message produces the full 128-character hex output.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Обычный текст', 'hello world', '021ced8799296ceca557832ab941a50b4a11f83478cf141f51f933f653ab9fbcc05a037cddbed06e309bf334942c4e58cdf1a46e237911ccd7fcf9787cbc7fd0', '', 'Короткое сообщение даёт полные 128 hex-символов вывода.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Avalanche effect', 'Hello world', '6ff843ba685842aa82031d3f53c48b66326df7639a63d128974c5c14f31a0f33343a8c65551134ed1ae0f2b0dd2bb495dc81039e3eeb0aa1bb0388bbeac29183', '', 'One bit flipped (h → H) — completely different output.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Лавинный эффект', 'Hello world', '6ff843ba685842aa82031d3f53c48b66326df7639a63d128974c5c14f31a0f33343a8c65551134ed1ae0f2b0dd2bb495dc81039e3eeb0aa1bb0388bbeac29183', '', 'Один бит изменён (h → H) — совершенно другой вывод.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'When should I prefer BLAKE2b over SHA-256?', 'BLAKE2b is the right choice when raw speed matters and you have control of both the hashing and verifying side (no protocol constraint). It is faster than SHA-256 in pure software on 64-bit platforms. Use SHA-256 when interoperability or platform support is the priority (it has hardware acceleration on most modern CPUs).', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Когда выбирать BLAKE2b вместо SHA-256?', 'BLAKE2b — правильный выбор, когда важна скорость, и обе стороны (хеширования и проверки) под вашим контролем (нет ограничений протокола). Он быстрее SHA-256 в чистом софте на 64-битных платформах. Используйте SHA-256, если важна совместимость или поддержка платформ (он имеет аппаратное ускорение на большинстве современных CPU).', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'How does BLAKE2b differ from BLAKE2s?', 'BLAKE2b is optimized for 64-bit platforms with up to 64-byte output and a 128-byte block size. BLAKE2s is optimized for 8/16/32-bit platforms with up to 32-byte output and a 64-byte block size. On 64-bit hardware BLAKE2b is faster; on small embedded systems BLAKE2s uses less memory and may be faster. For desktop and server use BLAKE2b is the default.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Чем BLAKE2b отличается от BLAKE2s?', 'BLAKE2b оптимизирован под 64-битные платформы с выводом до 64 байт и блоком 128 байт. BLAKE2s — под 8/16/32-битные платформы с выводом до 32 байт и блоком 64 байта. На 64-битном железе BLAKE2b быстрее; на маленьких встраиваемых системах BLAKE2s использует меньше памяти и может быть быстрее. Для десктопа и сервера по умолчанию выбирают BLAKE2b.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Is BLAKE2b used in Argon2?', 'Yes. Argon2 (the winner of the 2015 Password Hashing Competition and recommended by OWASP for password storage) uses BLAKE2b as its core hash function. Argon2 builds on BLAKE2b with memory-hardness, parallelism, and iteration parameters that make password cracking slow and expensive even with GPUs and ASICs.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Используется ли BLAKE2b в Argon2?', 'Да. Argon2 (победитель Password Hashing Competition 2015 года, рекомендованный OWASP для хранения паролей) использует BLAKE2b как основную хеш-функцию. Argon2 добавляет к BLAKE2b memory-hardness, параллелизм и параметры итераций, делающие подбор паролей медленным и дорогим даже на GPU и ASIC.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my input anywhere?', 'No. BLAKE2b runs entirely in your browser as pure JavaScript via the blakejs library — your input never leaves your device. No network requests, no logging, no server-side processing.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли этот инструмент мой ввод куда-либо?', 'Нет. BLAKE2b работает полностью в браузере на чистом JavaScript через библиотеку blakejs — введённые данные не покидают устройство. Нет сетевых запросов, логирования и серверной обработки.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'BLAKE2b', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'BLAKE2b', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Fast', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Быстрый', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Argon2', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Argon2', $now);
    }

    /**
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

    private function upsertBlockTranslation(int $blockId, string $language, string $title, string $text, string $now): void
    {
        $this->upsertTranslation(Tables::CIPHERS_BLOCKS_TRANSLATIONS, 'block_id', $blockId, $language, ['title' => $title, 'text' => $text], $now);
    }

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

    private function upsertFaqTranslation(int $faqId, string $language, string $question, string $answer, string $now): void
    {
        $this->upsertTranslation(Tables::CIPHERS_FAQ_TRANSLATIONS, 'faq_id', $faqId, $language, ['question' => $question, 'answer' => $answer], $now);
    }

    private function upsertTagTranslation(int $tagId, string $language, string $tag, string $now): void
    {
        $this->upsertTranslation(Tables::CIPHERS_TAGS_TRANSLATIONS, 'tag_id', $tagId, $language, ['tag' => $tag], $now);
    }

    /**
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
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'BLAKE2b Hash Generator',
                'name_short'        => 'BLAKE2b',
                'description'       => 'Compute the BLAKE2b hash of any text in your browser. BLAKE2b is a fast cryptographic hash function (RFC 7693), faster than MD5/SHA-1/SHA-2/SHA-3 in software, with up to 64-byte (128 hex) output. Used in Argon2, Zcash, and Polkadot.',
                'description_stort' => 'Generate a BLAKE2b hash of text in your browser.',
                'meta_title'        => 'BLAKE2b Hash Generator Online | Ciphers Online',
                'meta_description'  => 'Free online BLAKE2b hash generator. Compute BLAKE2b fingerprints in your browser — faster than SHA-2/SHA-3, used in Argon2 password hashing and modern cryptocurrencies.',
            ],
            'ru' => [
                'name'              => 'Генератор BLAKE2b хеша',
                'name_short'        => 'BLAKE2b',
                'description'       => 'Вычислите BLAKE2b хеш любого текста в браузере. BLAKE2b — быстрая криптографическая хеш-функция (RFC 7693), быстрее MD5/SHA-1/SHA-2/SHA-3 в софте, с выводом до 64 байт (128 hex). Используется в Argon2, Zcash и Polkadot.',
                'description_stort' => 'Вычислите BLAKE2b хеш текста в браузере.',
                'meta_title'        => 'BLAKE2b онлайн — генератор хеша | Ciphers Online',
                'meta_description'  => 'Бесплатный онлайн-генератор BLAKE2b. Вычисление BLAKE2b отпечатков в браузере — быстрее SHA-2/SHA-3, используется в хешировании паролей Argon2 и современных криптовалютах.',
            ],
            'de' => [
                'name'              => 'BLAKE2b Hash-Generator',
                'name_short'        => 'BLAKE2b',
                'description'       => 'Berechnen Sie den BLAKE2b-Hash beliebigen Textes im Browser. BLAKE2b ist eine schnelle kryptografische Hash-Funktion (RFC 7693), schneller als MD5/SHA-1/SHA-2/SHA-3 in Software, mit bis zu 64-Byte-Ausgabe (128 Hex). Verwendet in Argon2, Zcash und Polkadot.',
                'description_stort' => 'BLAKE2b-Hash von Text im Browser berechnen.',
                'meta_title'        => 'BLAKE2b Hash-Generator Online | Ciphers Online',
                'meta_description'  => 'Kostenloser BLAKE2b-Hash-Generator online. BLAKE2b-Fingerabdrücke im Browser — schneller als SHA-2/SHA-3, verwendet in Argon2.',
            ],
            'es' => [
                'name'              => 'Generador de hash BLAKE2b',
                'name_short'        => 'BLAKE2b',
                'description'       => 'Calcula el hash BLAKE2b de cualquier texto en tu navegador. BLAKE2b es una función hash rápida (RFC 7693), más rápida que MD5/SHA-1/SHA-2/SHA-3 en software, con hasta 64 bytes de salida (128 hex). Usado en Argon2, Zcash y Polkadot.',
                'description_stort' => 'Genera un hash BLAKE2b de texto en el navegador.',
                'meta_title'        => 'Generador BLAKE2b Online | Ciphers Online',
                'meta_description'  => 'Generador BLAKE2b online gratis. Calcula huellas BLAKE2b en el navegador — más rápido que SHA-2/SHA-3, usado en Argon2.',
            ],
            'fr' => [
                'name'              => 'Générateur de hachage BLAKE2b',
                'name_short'        => 'BLAKE2b',
                'description'       => 'Calculez l\'empreinte BLAKE2b d\'un texte dans votre navigateur. BLAKE2b est une fonction de hachage rapide (RFC 7693), plus rapide que MD5/SHA-1/SHA-2/SHA-3 en logiciel, avec jusqu\'à 64 octets de sortie (128 hex). Utilisé dans Argon2, Zcash et Polkadot.',
                'description_stort' => 'Générez une empreinte BLAKE2b de texte dans le navigateur.',
                'meta_title'        => 'Générateur BLAKE2b en ligne | Ciphers Online',
                'meta_description'  => 'Générateur BLAKE2b gratuit en ligne. Empreintes BLAKE2b dans le navigateur — plus rapide que SHA-2/SHA-3, utilisé dans Argon2.',
            ],
            'it' => [
                'name'              => 'Generatore di hash BLAKE2b',
                'name_short'        => 'BLAKE2b',
                'description'       => 'Calcola l\'hash BLAKE2b di qualsiasi testo nel browser. BLAKE2b è una funzione hash crittografica veloce (RFC 7693), più veloce di MD5/SHA-1/SHA-2/SHA-3 in software, con fino a 64 byte di output (128 hex). Usato in Argon2, Zcash e Polkadot.',
                'description_stort' => 'Genera un hash BLAKE2b di testo nel browser.',
                'meta_title'        => 'Generatore BLAKE2b Online | Ciphers Online',
                'meta_description'  => 'Generatore BLAKE2b gratuito online. Impronte BLAKE2b nel browser — più veloce di SHA-2/SHA-3, usato in Argon2.',
            ],
            'pt' => [
                'name'              => 'Gerador de hash BLAKE2b',
                'name_short'        => 'BLAKE2b',
                'description'       => 'Calcule o hash BLAKE2b de qualquer texto no navegador. BLAKE2b é uma função hash criptográfica rápida (RFC 7693), mais rápida que MD5/SHA-1/SHA-2/SHA-3 em software, com até 64 bytes de saída (128 hex). Usado em Argon2, Zcash e Polkadot.',
                'description_stort' => 'Gere um hash BLAKE2b de texto no navegador.',
                'meta_title'        => 'Gerador BLAKE2b Online | Ciphers Online',
                'meta_description'  => 'Gerador BLAKE2b grátis online. Impressões BLAKE2b no navegador — mais rápido que SHA-2/SHA-3, usado em Argon2.',
            ],
            'tr' => [
                'name'              => 'BLAKE2b Hash Üretici',
                'name_short'        => 'BLAKE2b',
                'description'       => 'Herhangi bir metnin BLAKE2b karma değerini tarayıcınızda hesaplayın. BLAKE2b, hızlı bir kriptografik karma fonksiyonudur (RFC 7693), yazılımda MD5/SHA-1/SHA-2/SHA-3\'ten daha hızlıdır, 64 bayta kadar çıktı (128 hex) üretir. Argon2, Zcash ve Polkadot\'ta kullanılır.',
                'description_stort' => 'Tarayıcıda metnin BLAKE2b karmasını oluşturun.',
                'meta_title'        => 'BLAKE2b Çevrimiçi Üretici | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi BLAKE2b karma üretici. BLAKE2b parmak izlerini tarayıcınızda hesaplayın — SHA-2/SHA-3\'ten hızlı, Argon2\'de kullanılır.',
            ],
        ];
    }
}
