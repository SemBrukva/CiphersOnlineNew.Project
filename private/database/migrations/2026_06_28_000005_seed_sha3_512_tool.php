<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент SHA3-512 в категорию «Хеширование».
 */
class SeedSha3512Tool extends Migration
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
            ['sha3-512']
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
            ['sha3-512']
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
            [$categoryId, 'sha3-512', 'client', 60, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is SHA3-512?', '<p>SHA3-512 is the 512-bit variant of the SHA-3 family, standardized in NIST FIPS 202 (2015). Like its smaller sibling SHA3-256, it is built on the Keccak sponge construction — a fundamentally different design from the SHA-2 family. The output is a 512-bit (128 hex characters) fingerprint.</p><p>SHA3-512 offers the largest single-call output in the SHA-3 family and is suitable for applications requiring extra security margin or wider hash outputs, such as long-term archival integrity, future-proof signatures, or as a building block in PBKDF2/HKDF constructions.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое SHA3-512?', '<p>SHA3-512 — 512-битный вариант семейства SHA-3, стандартизованный в NIST FIPS 202 (2015). Как и младший брат SHA3-256, построен на sponge-конструкции Keccak — принципиально иной по дизайну, чем семейство SHA-2. Вывод — 512-битный отпечаток (128 hex-символов).</p><p>SHA3-512 даёт наибольший вывод за один вызов в семействе SHA-3 и подходит для приложений с повышенным запасом прочности или большим размером отпечатка: долгосрочная целостность архивов, подписи с заделом на будущее, основа для PBKDF2/HKDF.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'SHA3-512 vs SHA-512', '<p>Both produce 512-bit outputs, but their internal designs differ completely. SHA-512 uses the Merkle–Damgård construction with 80 rounds of 64-bit operations; SHA3-512 uses the Keccak sponge with 24 rounds of permutation on a 1600-bit state.</p><p>In software SHA-512 is typically faster (especially with native CPU support for SHA-512 on modern processors). SHA3-512 is competitive in hardware and offers a different security profile — using both functions in a system provides defence in depth if one ever falls to cryptanalysis.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'SHA3-512 против SHA-512', '<p>Оба выдают 512-битный вывод, но внутренние конструкции полностью разные. SHA-512 использует Merkle–Damgård с 80 раундами 64-битных операций; SHA3-512 — sponge Keccak с 24 раундами перестановки над 1600-битным состоянием.</p><p>В софте SHA-512 обычно быстрее (особенно при нативной поддержке SHA-512 у современных CPU). SHA3-512 конкурентен в аппаратных реализациях и даёт другой профиль безопасности — использование обеих функций в одной системе даёт защиту-в-глубину, если одна из них падёт.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Empty string', '', 'a69f73cca23a9ac5c8b567dc185a756e97c982164fe25859e0d1dcc1475c80a615b2123af1f5f94c11e3e9402c3ac558f500199d95b6d3e301758586281dcd26', '', 'The SHA3-512 hash of empty input — a well-defined constant from FIPS 202.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Пустая строка', '', 'a69f73cca23a9ac5c8b567dc185a756e97c982164fe25859e0d1dcc1475c80a615b2123af1f5f94c11e3e9402c3ac558f500199d95b6d3e301758586281dcd26', '', 'SHA3-512 от пустой строки — определённая константа из FIPS 202.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Plain text', 'hello world', '840006653e9ac9e95117a15c915caab81662918e925de9e004f774ff82d7079a40d4d27b1b372657c61d46d470304c88c788b3a4527ad074d1dccbee5dbaa99a', '', 'Short input produces a fixed 128-character hex string.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Обычный текст', 'hello world', '840006653e9ac9e95117a15c915caab81662918e925de9e004f774ff82d7079a40d4d27b1b372657c61d46d470304c88c788b3a4527ad074d1dccbee5dbaa99a', '', 'Короткий ввод даёт фиксированную hex-строку из 128 символов.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Avalanche effect', 'Hello world', 'e2e1c9e522efb2495a178434c8bb8f11000ca23f1fd679058b7d7e141f0cf3433f94fc427ec0b9bebb12f327a3240021053db6091196576d5e6d9bd8fac71c0c', '', 'A single bit change yields an unrelated output.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Лавинный эффект', 'Hello world', 'e2e1c9e522efb2495a178434c8bb8f11000ca23f1fd679058b7d7e141f0cf3433f94fc427ec0b9bebb12f327a3240021053db6091196576d5e6d9bd8fac71c0c', '', 'Изменение одного бита даёт несвязанный вывод.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'When should I prefer SHA3-512 over SHA-512?', 'Pick SHA3-512 when you specifically want a hash with a different internal design than SHA-2 — for example, defence in depth (using both families in one system) or compliance requirements that mandate FIPS 202. For pure performance, SHA-512 is usually faster in software. For new general-purpose systems either is acceptable; SHA-512 has wider library and hardware support.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Когда стоит выбрать SHA3-512 вместо SHA-512?', 'Выбирайте SHA3-512, когда нужен именно хеш с другой внутренней структурой, отличной от SHA-2 — например, для защиты-в-глубину (обе семьи в одной системе) или из-за требований соответствия FIPS 202. По чистой производительности SHA-512 обычно быстрее в софте. Для новых систем общего назначения подходит и тот и другой; у SHA-512 шире поддержка в библиотеках и железе.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Is SHA3-512 quantum-resistant?', 'SHA3-512 offers 256 bits of pre-image security and 128 bits of collision security against quantum attackers (assuming Grover\'s algorithm). For comparison, SHA-256 offers 128 bits pre-image and 64 bits collision against quantum. SHA3-512 is suitable for applications requiring long-term post-quantum security in hash-based primitives.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Устойчив ли SHA3-512 к квантовым атакам?', 'SHA3-512 даёт 256 бит prepossess безопасности и 128 бит collision-безопасности против квантового атакующего (с учётом алгоритма Гровера). Для сравнения, SHA-256 даёт 128 бит pre-image и 64 бита collision против квантов. SHA3-512 подходит для приложений, требующих долгосрочной пост-квантовой безопасности в хеш-примитивах.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Can I use SHA3-512 for password storage?', 'No, like all raw cryptographic hashes SHA3-512 is too fast to safely hash passwords. Attackers can compute many billions of guesses per second. Use Argon2id (recommended), bcrypt, or scrypt for passwords; alternatively PBKDF2-HMAC-SHA3-512 with a high iteration count and a unique random salt per password.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Можно ли использовать SHA3-512 для хранения паролей?', 'Нет, как и все сырые криптографические хеши, SHA3-512 слишком быстр для безопасного хранения паролей. Атакующий считает миллиарды вариантов в секунду. Для паролей используйте Argon2id (рекомендуется), bcrypt или scrypt; либо PBKDF2-HMAC-SHA3-512 с большим числом итераций и уникальной случайной солью.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my input anywhere?', 'No. SHA3-512 runs entirely in your browser as pure JavaScript — your input never leaves your device. No network requests, no logging, no server-side processing.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли этот инструмент мой ввод куда-либо?', 'Нет. SHA3-512 работает полностью в браузере на чистом JavaScript — введённые данные не покидают устройство. Нет сетевых запросов, логирования и серверной обработки.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'SHA3-512', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'SHA3-512', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Keccak', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Keccak', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'FIPS 202', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'FIPS 202', $now);
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
                'name'              => 'SHA3-512 Hash Generator',
                'name_short'        => 'SHA3-512',
                'description'       => 'Compute the SHA3-512 hash of any text in your browser. The 512-bit variant of the SHA-3 family, based on Keccak. Suitable for archival integrity, long-term signatures, and applications requiring extra security margin.',
                'description_stort' => 'Generate a SHA3-512 hash of text in your browser.',
                'meta_title'        => 'SHA3-512 Hash Generator Online | Ciphers Online',
                'meta_description'  => 'Free online SHA3-512 hash generator. Compute 128-character SHA3-512 fingerprints in your browser using the Keccak sponge construction. Different internal design from SHA-512.',
            ],
            'ru' => [
                'name'              => 'Генератор SHA3-512 хеша',
                'name_short'        => 'SHA3-512',
                'description'       => 'Вычислите SHA3-512 хеш любого текста в браузере. 512-битный вариант семейства SHA-3 на основе Keccak. Подходит для архивной целостности, долгосрочных подписей и приложений, требующих повышенного запаса безопасности.',
                'description_stort' => 'Вычислите SHA3-512 хеш текста в браузере.',
                'meta_title'        => 'SHA3-512 онлайн — генератор хеша | Ciphers Online',
                'meta_description'  => 'Бесплатный онлайн-генератор SHA3-512. Вычисление 128-символьных SHA3-512 отпечатков в браузере с использованием sponge-конструкции Keccak. Другая внутренняя структура, чем у SHA-512.',
            ],
            'de' => [
                'name'              => 'SHA3-512 Hash-Generator',
                'name_short'        => 'SHA3-512',
                'description'       => 'Berechnen Sie den SHA3-512-Hash beliebigen Textes im Browser. Die 512-Bit-Variante der SHA-3-Familie auf Keccak-Basis. Geeignet für Archivintegrität, Langzeitsignaturen und Anwendungen mit erhöhtem Sicherheitsbedarf.',
                'description_stort' => 'SHA3-512-Hash von Text im Browser berechnen.',
                'meta_title'        => 'SHA3-512 Hash-Generator Online | Ciphers Online',
                'meta_description'  => 'Kostenloser SHA3-512-Hash-Generator online. 128-Zeichen-SHA3-512-Fingerabdrücke im Browser über die Keccak-Sponge-Konstruktion.',
            ],
            'es' => [
                'name'              => 'Generador de hash SHA3-512',
                'name_short'        => 'SHA3-512',
                'description'       => 'Calcula el hash SHA3-512 de cualquier texto en tu navegador. La variante de 512 bits de la familia SHA-3, basada en Keccak. Adecuado para integridad de archivos, firmas a largo plazo y aplicaciones con margen de seguridad adicional.',
                'description_stort' => 'Genera un hash SHA3-512 de texto en el navegador.',
                'meta_title'        => 'Generador SHA3-512 Online | Ciphers Online',
                'meta_description'  => 'Generador SHA3-512 online gratis. Calcula huellas SHA3-512 de 128 caracteres en el navegador usando la construcción Keccak.',
            ],
            'fr' => [
                'name'              => 'Générateur de hachage SHA3-512',
                'name_short'        => 'SHA3-512',
                'description'       => 'Calculez l\'empreinte SHA3-512 d\'un texte dans votre navigateur. La variante 512 bits de la famille SHA-3, basée sur Keccak. Adaptée à l\'intégrité d\'archives, signatures à long terme et applications nécessitant une marge de sécurité étendue.',
                'description_stort' => 'Générez une empreinte SHA3-512 de texte dans le navigateur.',
                'meta_title'        => 'Générateur SHA3-512 en ligne | Ciphers Online',
                'meta_description'  => 'Générateur SHA3-512 gratuit en ligne. Empreintes SHA3-512 de 128 caractères dans votre navigateur via la construction Keccak.',
            ],
            'it' => [
                'name'              => 'Generatore di hash SHA3-512',
                'name_short'        => 'SHA3-512',
                'description'       => 'Calcola l\'hash SHA3-512 di qualsiasi testo nel browser. La variante a 512 bit della famiglia SHA-3, basata su Keccak. Adatto per integrità d\'archivio, firme a lungo termine e applicazioni con margine di sicurezza esteso.',
                'description_stort' => 'Genera un hash SHA3-512 di testo nel browser.',
                'meta_title'        => 'Generatore SHA3-512 Online | Ciphers Online',
                'meta_description'  => 'Generatore SHA3-512 gratuito online. Impronte SHA3-512 a 128 caratteri nel browser tramite la costruzione Keccak.',
            ],
            'pt' => [
                'name'              => 'Gerador de hash SHA3-512',
                'name_short'        => 'SHA3-512',
                'description'       => 'Calcule o hash SHA3-512 de qualquer texto no navegador. A variante de 512 bits da família SHA-3, baseada em Keccak. Adequado para integridade de arquivos, assinaturas de longo prazo e aplicações com margem de segurança extra.',
                'description_stort' => 'Gere um hash SHA3-512 de texto no navegador.',
                'meta_title'        => 'Gerador SHA3-512 Online | Ciphers Online',
                'meta_description'  => 'Gerador SHA3-512 grátis online. Impressões SHA3-512 de 128 caracteres no navegador via construção Keccak.',
            ],
            'tr' => [
                'name'              => 'SHA3-512 Hash Üretici',
                'name_short'        => 'SHA3-512',
                'description'       => 'Herhangi bir metnin SHA3-512 karma değerini tarayıcınızda hesaplayın. Keccak tabanlı SHA-3 ailesinin 512 bit varyantı. Arşiv bütünlüğü, uzun ömürlü imzalar ve ekstra güvenlik payı gerektiren uygulamalar için uygundur.',
                'description_stort' => 'Tarayıcıda metnin SHA3-512 karmasını oluşturun.',
                'meta_title'        => 'SHA3-512 Çevrimiçi Üretici | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi SHA3-512 karma üretici. 128 karakter SHA3-512 parmak izlerini Keccak sponge ile tarayıcınızda hesaplayın.',
            ],
        ];
    }
}
