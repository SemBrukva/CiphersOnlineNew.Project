<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент SHA-1 (генератор хеша) в категорию «Хеширование».
 */
class SeedSha1Tool extends Migration
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
            ['sha1']
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
            ['sha1']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 20, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'sha1', 'client', 20, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is SHA-1?', '<p>SHA-1 (Secure Hash Algorithm 1) is a cryptographic hash function designed by the U.S. National Security Agency and published as a FIPS standard in 1995. It produces a 160-bit (20-byte) hash value, rendered as 40 hexadecimal characters.</p><p>Historically SHA-1 was widely used in TLS certificates, Git commit identifiers, and software signing. However, since 2017 (the SHAttered attack by Google and CWI) practical collision attacks are known: distinct inputs can be crafted to produce identical hashes. SHA-1 is therefore deprecated for security-sensitive uses.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое SHA-1?', '<p>SHA-1 (Secure Hash Algorithm 1) — криптографическая хеш-функция, разработанная Агентством национальной безопасности США и опубликованная как FIPS-стандарт в 1995 году. Выдаёт 160-битное (20-байтовое) значение в виде 40 шестнадцатеричных символов.</p><p>Исторически SHA-1 широко применялся в TLS-сертификатах, идентификаторах коммитов Git и подписании ПО. Однако с 2017 года (атака SHAttered от Google и CWI) известны практические атаки коллизий: можно подобрать два разных входа с одинаковым хешем. Поэтому SHA-1 не рекомендуется для безопасности.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Where SHA-1 is still used', '<p>Despite its weaknesses, SHA-1 remains in legacy systems and non-security contexts where collision resistance is not critical. Git uses SHA-1 for content addressing (object identifiers); recent versions of Git also support SHA-256 as an alternative.</p><p>Other lingering uses include older HMAC-SHA-1 token systems, legacy TLS certificates (mostly phased out), and integrity checks for non-adversarial scenarios. For any new security-sensitive design choose SHA-256 or stronger.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Где SHA-1 ещё используется', '<p>Несмотря на слабости, SHA-1 сохраняется в легаси-системах и неуязвимых к коллизиям контекстах. Git использует SHA-1 для контентной адресации (идентификаторы объектов); современные версии Git также поддерживают SHA-256 как альтернативу.</p><p>Остаточные применения: старые системы HMAC-SHA-1 токенов, легаси TLS-сертификаты (в основном выведены из эксплуатации), проверка целостности в не-противоречивых сценариях. Для нового кода с требованиями безопасности — берите SHA-256 или сильнее.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Empty string', '', 'da39a3ee5e6b4b0d3255bfef95601890afd80709', '', 'The SHA-1 hash of empty input is a well-known constant.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Пустая строка', '', 'da39a3ee5e6b4b0d3255bfef95601890afd80709', '', 'SHA-1 от пустой строки — известная константа.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Plain text', 'hello world', '2aae6c35c94fcfb415dbe95f408b9ce91ee846ed', '', 'Short input produces a fixed 40-character hex string.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Обычный текст', 'hello world', '2aae6c35c94fcfb415dbe95f408b9ce91ee846ed', '', 'Короткий ввод даёт фиксированную hex-строку из 40 символов.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Avalanche effect', 'Hello world', '7b502c3a1f48c8609ae212cdfb639dee39673f5e', '', 'A single bit flip (lowercase h → uppercase H) yields a completely different hash.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Лавинный эффект', 'Hello world', '7b502c3a1f48c8609ae212cdfb639dee39673f5e', '', 'Изменение одного бита (h → H) полностью меняет хеш.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'Is SHA-1 still safe to use?', 'For security-sensitive purposes (digital signatures, password hashing, certificate fingerprints) — no. Practical collisions against SHA-1 are publicly known since 2017. For non-adversarial contexts such as file deduplication or legacy compatibility — it is still useful, but always prefer SHA-256 or stronger for new systems.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Безопасно ли использовать SHA-1?', 'Для задач, чувствительных к безопасности (цифровые подписи, хеширование паролей, отпечатки сертификатов) — нет. С 2017 года известны практические коллизии SHA-1. Для неуязвимых контекстов (дедупликация файлов, легаси-совместимость) ещё подходит, но для нового кода всегда выбирайте SHA-256 или сильнее.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Why does Git still use SHA-1?', 'Git\'s use of SHA-1 is for content addressing, not for adversarial security. A successful collision attack against Git would require generating two distinct objects with identical content paths and identical hashes — an attack that is computationally expensive and not currently practical against arbitrary repositories. Newer Git versions support SHA-256 as an alternative, but SHA-1 remains the default for compatibility.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Почему Git до сих пор использует SHA-1?', 'Git использует SHA-1 для контентной адресации, а не для защиты от атак. Атака коллизий на Git потребовала бы сгенерировать два разных объекта с одинаковым контентным путём и одинаковым хешем — это вычислительно дорого и пока непрактично против произвольных репозиториев. Новые версии Git поддерживают SHA-256 как альтернативу, но SHA-1 остаётся по умолчанию для совместимости.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Can SHA-1 be reversed?', 'No. SHA-1 is one-way: there is no algorithm to recover the original input from a hash. The only approach is brute force — guessing inputs and comparing hashes. For short or predictable inputs (passwords, dictionary words) this is fast enough to be a real threat, which is why dedicated password hashing functions (Argon2, bcrypt) exist. For random or long inputs the search space is astronomically large.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Можно ли расшифровать SHA-1?', 'Нет. SHA-1 — односторонняя: нет алгоритма восстановления исходных данных по хешу. Единственный способ — перебор, угадывание и сравнение хешей. Для коротких или предсказуемых входов (пароли, словарные слова) это достаточно быстро, чтобы представлять реальную угрозу — поэтому существуют специализированные функции (Argon2, bcrypt). Для случайных или длинных входов пространство поиска астрономическое.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my input anywhere?', 'No. The SHA-1 hash is computed entirely in your browser using the built-in Web Crypto API (window.crypto.subtle.digest). Your input never leaves your device — no network requests, no logging, no server-side processing.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли этот инструмент мой ввод куда-либо?', 'Нет. SHA-1 хеш вычисляется полностью в браузере через встроенный Web Crypto API (window.crypto.subtle.digest). Введённые данные не покидают устройство — нет сетевых запросов, логирования и серверной обработки.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'SHA-1', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'SHA-1', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Hash', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Хеш', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Legacy', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Легаси', $now);
    }

    /**
     * Создаёт или обновляет родительскую запись.
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
     * Возвращает переводы инструмента SHA-1.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'SHA-1 Hash Generator',
                'name_short'        => 'SHA-1',
                'description'       => 'Compute the SHA-1 cryptographic hash of any text in your browser. SHA-1 produces a 160-bit (40 hex characters) fingerprint used in Git, legacy TLS certificates, and HMAC-SHA-1.',
                'description_stort' => 'Generate a SHA-1 hash of text in your browser.',
                'meta_title'        => 'SHA-1 Hash Generator Online | Ciphers Online',
                'meta_description'  => 'Free online SHA-1 hash generator. Compute SHA-1 fingerprints of any text in your browser — used in Git, legacy TLS, and HMAC. Deprecated for security, useful for legacy.',
            ],
            'ru' => [
                'name'              => 'Генератор SHA-1 хеша',
                'name_short'        => 'SHA-1',
                'description'       => 'Вычислите криптографический хеш SHA-1 любого текста в браузере. SHA-1 выдаёт 160-битный отпечаток (40 hex-символов), используется в Git, легаси TLS-сертификатах и HMAC-SHA-1.',
                'description_stort' => 'Вычислите SHA-1 хеш текста в браузере.',
                'meta_title'        => 'SHA-1 онлайн — генератор хеша | Ciphers Online',
                'meta_description'  => 'Бесплатный онлайн-генератор SHA-1. Вычисление отпечатков SHA-1 в браузере — используется в Git, легаси TLS и HMAC. Устарел для безопасности, актуален для совместимости.',
            ],
            'de' => [
                'name'              => 'SHA-1 Hash-Generator',
                'name_short'        => 'SHA-1',
                'description'       => 'Berechnen Sie den SHA-1-Hash beliebigen Textes direkt im Browser. SHA-1 erzeugt einen 160-Bit-Fingerabdruck (40 Hex-Zeichen), verwendet in Git, Legacy-TLS-Zertifikaten und HMAC-SHA-1.',
                'description_stort' => 'SHA-1-Hash von Text im Browser berechnen.',
                'meta_title'        => 'SHA-1 Hash-Generator Online | Ciphers Online',
                'meta_description'  => 'Kostenloser SHA-1-Hash-Generator online. Fingerabdrücke direkt im Browser — Git, Legacy-TLS, HMAC.',
            ],
            'es' => [
                'name'              => 'Generador de hash SHA-1',
                'name_short'        => 'SHA-1',
                'description'       => 'Calcula el hash SHA-1 de cualquier texto en tu navegador. SHA-1 produce una huella de 160 bits (40 caracteres hex) usada en Git, certificados TLS heredados y HMAC-SHA-1.',
                'description_stort' => 'Genera un hash SHA-1 de texto en el navegador.',
                'meta_title'        => 'Generador SHA-1 Online | Ciphers Online',
                'meta_description'  => 'Generador SHA-1 online gratis. Calcula huellas SHA-1 en el navegador — Git, TLS heredado, HMAC.',
            ],
            'fr' => [
                'name'              => 'Générateur de hachage SHA-1',
                'name_short'        => 'SHA-1',
                'description'       => 'Calculez l\'empreinte SHA-1 d\'un texte dans votre navigateur. SHA-1 produit une empreinte de 160 bits (40 caractères hex) utilisée dans Git, les anciens certificats TLS et HMAC-SHA-1.',
                'description_stort' => 'Générez une empreinte SHA-1 de texte dans le navigateur.',
                'meta_title'        => 'Générateur SHA-1 en ligne | Ciphers Online',
                'meta_description'  => 'Générateur SHA-1 gratuit en ligne. Calculez des empreintes SHA-1 dans votre navigateur — Git, TLS ancien, HMAC.',
            ],
            'it' => [
                'name'              => 'Generatore di hash SHA-1',
                'name_short'        => 'SHA-1',
                'description'       => 'Calcola l\'hash SHA-1 di qualsiasi testo nel browser. SHA-1 produce un\'impronta a 160 bit (40 caratteri hex) usata in Git, certificati TLS legacy e HMAC-SHA-1.',
                'description_stort' => 'Genera un hash SHA-1 di testo nel browser.',
                'meta_title'        => 'Generatore SHA-1 Online | Ciphers Online',
                'meta_description'  => 'Generatore SHA-1 gratuito online. Calcola impronte SHA-1 nel browser — Git, TLS legacy, HMAC.',
            ],
            'pt' => [
                'name'              => 'Gerador de hash SHA-1',
                'name_short'        => 'SHA-1',
                'description'       => 'Calcule o hash SHA-1 de qualquer texto no navegador. SHA-1 produz uma impressão de 160 bits (40 caracteres hex) usada em Git, certificados TLS legados e HMAC-SHA-1.',
                'description_stort' => 'Gere um hash SHA-1 de texto no navegador.',
                'meta_title'        => 'Gerador SHA-1 Online | Ciphers Online',
                'meta_description'  => 'Gerador SHA-1 grátis online. Calcule impressões SHA-1 no navegador — Git, TLS legado, HMAC.',
            ],
            'tr' => [
                'name'              => 'SHA-1 Hash Üretici',
                'name_short'        => 'SHA-1',
                'description'       => 'Herhangi bir metnin SHA-1 karma değerini doğrudan tarayıcınızda hesaplayın. SHA-1, 160 bit (40 hex karakter) parmak izi üretir; Git, eski TLS sertifikaları ve HMAC-SHA-1\'de kullanılır.',
                'description_stort' => 'Tarayıcıda metnin SHA-1 karmasını oluşturun.',
                'meta_title'        => 'SHA-1 Çevrimiçi Üretici | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi SHA-1 karma üretici. SHA-1 parmak izlerini doğrudan tarayıcıda hesaplayın — Git, eski TLS, HMAC.',
            ],
        ];
    }
}
