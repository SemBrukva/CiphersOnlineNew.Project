<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент MD5 (генератор хеша) в категорию «Хеширование».
 */
class SeedMd5Tool extends Migration
{
    /**
     * Создаёт инструмент, переводы, блоки, примеры, FAQ и теги.
     */
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

    /**
     * Удаляет инструмент и связанные сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['md5']
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
            ['md5']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 40, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'md5', 'client', 40, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is MD5?', '<p>MD5 (Message Digest 5) is a cryptographic hash function designed by Ronald Rivest in 1991. It produces a 128-bit (16-byte) hash value, rendered as 32 hexadecimal characters. MD5 was widely deployed in the 1990s and 2000s for digital signatures, file integrity checks, and as a building block in various protocols.</p><p>Since 2004 MD5 is considered cryptographically broken: practical collision attacks can produce two distinct inputs with the same hash in seconds on commodity hardware. It must not be used where collision resistance is needed (signatures, certificates, password hashing).</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое MD5?', '<p>MD5 (Message Digest 5) — криптографическая хеш-функция, разработанная Рональдом Ривестом в 1991 году. Выдаёт 128-битное (16-байтовое) значение, представляемое как 32 шестнадцатеричных символа. MD5 широко использовался в 1990-х и 2000-х для цифровых подписей, проверки целостности файлов и как строительный блок различных протоколов.</p><p>С 2004 года MD5 считается криптографически сломанным: практические атаки коллизий генерируют два разных входа с одинаковым хешем за секунды на обычном железе. Использовать его там, где нужна стойкость к коллизиям (подписи, сертификаты, хеширование паролей), нельзя.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Where MD5 is still acceptable', '<p>Despite cryptographic weaknesses, MD5 remains common in non-adversarial contexts where a short fingerprint is convenient: file deduplication, cache keys, ETag generation, content-addressable identifiers, and checksums published alongside downloads (where the publisher and the downloader trust the channel).</p><p>For any context where an attacker could supply input — password storage, document signatures, certificate fingerprints — MD5 must not be used. Replace it with SHA-256 or a dedicated password-hashing function (Argon2id, bcrypt).</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Где MD5 ещё приемлем', '<p>Несмотря на криптографические слабости, MD5 остаётся обычным в неуязвимых к атакам контекстах, где удобен короткий отпечаток: дедупликация файлов, ключи кэша, генерация ETag, контентно-адресуемые идентификаторы, контрольные суммы рядом с публикуемыми загрузками (где издатель и пользователь доверяют каналу).</p><p>В любом контексте, где атакующий может подсунуть вход — хранение паролей, подписи документов, отпечатки сертификатов — MD5 использовать нельзя. Замените его на SHA-256 или специализированную функцию для паролей (Argon2id, bcrypt).</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Empty string', '', 'd41d8cd98f00b204e9800998ecf8427e', '', 'The MD5 hash of empty input is a well-known constant.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Пустая строка', '', 'd41d8cd98f00b204e9800998ecf8427e', '', 'MD5 от пустой строки — известная константа.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Plain text', 'hello world', '5eb63bbbe01eeed093cb22bb8f5acdc3', '', 'Short input produces a fixed 32-character hex string.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Обычный текст', 'hello world', '5eb63bbbe01eeed093cb22bb8f5acdc3', '', 'Короткий ввод даёт фиксированную hex-строку из 32 символов.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Avalanche effect', 'Hello world', '3e25960a79dbc69b674cd4ec67a72c62', '', 'Flipping a single bit (lowercase h → uppercase H) completely changes the hash.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Лавинный эффект', 'Hello world', '3e25960a79dbc69b674cd4ec67a72c62', '', 'Изменение одного бита (h → H) полностью меняет хеш.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'Is MD5 broken?', 'Yes, for security purposes. Practical collision attacks against MD5 have been known since 2004, and chosen-prefix collisions since 2007 — attackers can craft two different documents with the same MD5 hash in seconds. For digital signatures, certificate fingerprints, or any adversarial context use SHA-256 or stronger. For non-adversarial uses (cache keys, deduplication) MD5 is still adequate.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'MD5 сломан?', 'Да, для задач безопасности. Практические атаки коллизий против MD5 известны с 2004 года, атаки с выбранным префиксом — с 2007: атакующий может за секунды получить два разных документа с одинаковым MD5 хешем. Для цифровых подписей, отпечатков сертификатов или в любом «противоречивом» контексте используйте SHA-256 или сильнее. Для не-атакующих сценариев (ключи кэша, дедупликация) MD5 ещё годится.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Can I use MD5 for password storage?', 'Absolutely not. Raw MD5 is fast — billions of guesses per second on a modern GPU — and has known weaknesses. Many websites compromised over the past two decades stored passwords as raw MD5; the resulting leaks led to industry-wide credential stuffing attacks. For passwords use Argon2id (recommended), bcrypt, or scrypt with a unique salt per user.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Можно ли хранить пароли в MD5?', 'Однозначно нет. Простой MD5 быстр — миллиарды вариантов в секунду на современной GPU — и имеет известные слабости. Многие сайты, скомпрометированные за последние два десятилетия, хранили пароли как сырой MD5; утечки привели к индустриальной волне credential-stuffing атак. Для паролей используйте Argon2id (рекомендуется), bcrypt или scrypt с уникальной солью на пользователя.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Why is MD5 still everywhere?', 'MD5 is fast, ubiquitous (every standard library implements it), and produces a compact 32-character output. For non-security uses like cache keys, file deduplication, ETag generation, or quick fingerprinting of trusted content, those properties matter more than collision resistance. Replacing MD5 in such contexts often requires more code and yields no real benefit, so it persists.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Почему MD5 до сих пор повсюду?', 'MD5 быстр, повсеместен (есть в каждой стандартной библиотеке) и даёт компактный 32-символьный вывод. Для задач без безопасности — ключи кэша, дедупликация файлов, генерация ETag, быстрая снятие отпечатков доверенного контента — эти свойства важнее стойкости к коллизиям. Замена MD5 в таких контекстах обычно требует больше кода и не даёт реального выигрыша, поэтому он сохраняется.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my input anywhere?', 'No. MD5 is implemented as a pure JavaScript function that runs entirely in your browser — your input never leaves your device. No network requests, no logging, no server-side processing.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли этот инструмент мой ввод куда-либо?', 'Нет. MD5 реализован как чистая JavaScript-функция, работающая полностью в браузере — введённые данные не покидают устройство. Нет сетевых запросов, логирования и серверной обработки.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'MD5', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'MD5', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Hash', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Хеш', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Checksum', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Контрольная сумма', $now);
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
     * Возвращает переводы инструмента MD5.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'MD5 Hash Generator',
                'name_short'        => 'MD5',
                'description'       => 'Compute the MD5 hash of any text in your browser. MD5 produces a 128-bit (32 hex characters) fingerprint. Cryptographically broken since 2004 but still useful for non-security tasks like cache keys, ETags, and file deduplication.',
                'description_stort' => 'Generate an MD5 hash of text in your browser.',
                'meta_title'        => 'MD5 Hash Generator Online | Ciphers Online',
                'meta_description'  => 'Free online MD5 hash generator. Compute 32-character MD5 fingerprints of any text in your browser — useful for cache keys, ETags, and file deduplication. Do not use for security.',
            ],
            'ru' => [
                'name'              => 'Генератор MD5 хеша',
                'name_short'        => 'MD5',
                'description'       => 'Вычислите MD5 хеш любого текста в браузере. MD5 выдаёт 128-битный отпечаток (32 hex-символа). Криптографически сломан с 2004 года, но по-прежнему полезен для несвязанных с безопасностью задач — ключей кэша, ETag и дедупликации файлов.',
                'description_stort' => 'Вычислите MD5 хеш текста в браузере.',
                'meta_title'        => 'MD5 онлайн — генератор хеша | Ciphers Online',
                'meta_description'  => 'Бесплатный онлайн-генератор MD5. Вычисление 32-символьных MD5 отпечатков в браузере — для ключей кэша, ETag и дедупликации файлов. Не используйте для безопасности.',
            ],
            'de' => [
                'name'              => 'MD5 Hash-Generator',
                'name_short'        => 'MD5',
                'description'       => 'Berechnen Sie den MD5-Hash beliebigen Textes im Browser. MD5 erzeugt einen 128-Bit-Fingerabdruck (32 Hex-Zeichen). Kryptografisch gebrochen seit 2004, aber weiterhin nützlich für nicht-sicherheitsrelevante Aufgaben wie Cache-Keys, ETags und Datei-Deduplizierung.',
                'description_stort' => 'MD5-Hash von Text im Browser berechnen.',
                'meta_title'        => 'MD5 Hash-Generator Online | Ciphers Online',
                'meta_description'  => 'Kostenloser MD5-Hash-Generator online. 32-Zeichen-MD5-Fingerabdrücke direkt im Browser — Cache-Keys, ETags, Deduplizierung. Nicht für Sicherheit verwenden.',
            ],
            'es' => [
                'name'              => 'Generador de hash MD5',
                'name_short'        => 'MD5',
                'description'       => 'Calcula el hash MD5 de cualquier texto en tu navegador. MD5 produce una huella de 128 bits (32 caracteres hex). Roto criptográficamente desde 2004, pero útil para tareas no relacionadas con seguridad como claves de caché, ETags y deduplicación.',
                'description_stort' => 'Genera un hash MD5 de texto en el navegador.',
                'meta_title'        => 'Generador MD5 Online | Ciphers Online',
                'meta_description'  => 'Generador MD5 online gratis. Calcula huellas MD5 de 32 caracteres en el navegador — claves de caché, ETags, deduplicación. No usar para seguridad.',
            ],
            'fr' => [
                'name'              => 'Générateur de hachage MD5',
                'name_short'        => 'MD5',
                'description'       => 'Calculez l\'empreinte MD5 d\'un texte dans votre navigateur. MD5 produit une empreinte de 128 bits (32 caractères hex). Cryptographiquement cassé depuis 2004, mais utile pour des tâches non sécuritaires (clés de cache, ETags, déduplication).',
                'description_stort' => 'Générez une empreinte MD5 de texte dans le navigateur.',
                'meta_title'        => 'Générateur MD5 en ligne | Ciphers Online',
                'meta_description'  => 'Générateur MD5 gratuit en ligne. Empreintes MD5 de 32 caractères dans le navigateur — clés de cache, ETags, déduplication. Ne pas utiliser pour la sécurité.',
            ],
            'it' => [
                'name'              => 'Generatore di hash MD5',
                'name_short'        => 'MD5',
                'description'       => 'Calcola l\'hash MD5 di qualsiasi testo nel browser. MD5 produce un\'impronta a 128 bit (32 caratteri hex). Crittograficamente compromesso dal 2004, ma utile per attività non legate alla sicurezza come chiavi di cache, ETag e deduplicazione.',
                'description_stort' => 'Genera un hash MD5 di testo nel browser.',
                'meta_title'        => 'Generatore MD5 Online | Ciphers Online',
                'meta_description'  => 'Generatore MD5 gratuito online. Impronte MD5 a 32 caratteri nel browser — chiavi di cache, ETag, deduplicazione. Non usare per la sicurezza.',
            ],
            'pt' => [
                'name'              => 'Gerador de hash MD5',
                'name_short'        => 'MD5',
                'description'       => 'Calcule o hash MD5 de qualquer texto no navegador. MD5 produz uma impressão de 128 bits (32 caracteres hex). Quebrado criptograficamente desde 2004, mas útil para tarefas não relacionadas à segurança como chaves de cache, ETags e deduplicação.',
                'description_stort' => 'Gere um hash MD5 de texto no navegador.',
                'meta_title'        => 'Gerador MD5 Online | Ciphers Online',
                'meta_description'  => 'Gerador MD5 grátis online. Impressões MD5 de 32 caracteres no navegador — chaves de cache, ETags, deduplicação. Não use para segurança.',
            ],
            'tr' => [
                'name'              => 'MD5 Hash Üretici',
                'name_short'        => 'MD5',
                'description'       => 'Herhangi bir metnin MD5 karma değerini tarayıcınızda hesaplayın. MD5, 128 bit (32 hex karakter) parmak izi üretir. 2004\'ten beri kriptografik olarak kırılmış, ancak önbellek anahtarları, ETag ve dosya tekilleştirme gibi güvenlik dışı görevler için hâlâ yararlı.',
                'description_stort' => 'Tarayıcıda metnin MD5 karmasını oluşturun.',
                'meta_title'        => 'MD5 Çevrimiçi Üretici | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi MD5 karma üretici. 32 karakter MD5 parmak izleri tarayıcınızda — önbellek anahtarları, ETag, tekilleştirme. Güvenlik için kullanmayın.',
            ],
        ];
    }
}
