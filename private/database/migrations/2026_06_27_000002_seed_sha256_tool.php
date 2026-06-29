<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент SHA-256 (генератор хеша) в категорию «Хеширование».
 */
class SeedSha256Tool extends Migration
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
            ['sha256']
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
            ['sha256']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 10, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'sha256', 'client', 10, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is SHA-256?', '<p>SHA-256 is a cryptographic hash function from the SHA-2 family, designed by the U.S. National Security Agency and published by NIST in 2001. It produces a 256-bit (32-byte) hash value, typically rendered as a 64-character hexadecimal string.</p><p>The function is deterministic — the same input always produces the same hash — and one-way: given a hash, it is computationally infeasible to recover the original input or to find two different inputs producing the same output (collision resistance).</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое SHA-256?', '<p>SHA-256 — криптографическая хеш-функция из семейства SHA-2, разработанная Агентством национальной безопасности США и опубликованная NIST в 2001 году. На выходе формирует 256-битное (32-байтовое) значение, обычно представляемое в виде 64-символьной шестнадцатеричной строки.</p><p>Функция детерминирована — один и тот же вход всегда даёт один и тот же хеш — и односторонняя: по хешу вычислительно невозможно восстановить исходные данные или найти два разных входа с одинаковым хешем (стойкость к коллизиям).</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Where SHA-256 is used', '<p>SHA-256 underpins many security-critical systems: it is the hashing algorithm of Bitcoin and most modern blockchains, the default fingerprint for X.509 TLS certificates, and the building block of HMAC-SHA-256 used in JWT signatures, API authentication, and TLS.</p><p>It is also widely used for file integrity verification (alongside published checksums), digital signatures, software package signing, and as part of password hashing schemes such as PBKDF2-HMAC-SHA-256.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Где применяется SHA-256', '<p>SHA-256 лежит в основе многих критичных к безопасности систем: это алгоритм хеширования в Bitcoin и большинстве современных блокчейнов, стандартный отпечаток для TLS-сертификатов X.509, и базовый блок HMAC-SHA-256, используемого в подписи JWT, аутентификации API и TLS.</p><p>Также широко применяется для проверки целостности файлов (вместе с публикуемыми контрольными суммами), цифровых подписей, подписания пакетов и в составе схем хеширования паролей, например PBKDF2-HMAC-SHA-256.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Empty string', '', 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', '', 'The SHA-256 hash of an empty input is a well-known constant.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Пустая строка', '', 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', '', 'SHA-256 от пустой строки — хорошо известная константа.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Plain text', 'hello world', 'b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9', '', 'A short message produces a 64-character hex string of fixed length.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Обычный текст', 'hello world', 'b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9', '', 'Короткое сообщение даёт hex-строку фиксированной длины в 64 символа.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Avalanche effect', 'Hello world', '64ec88ca00b268e5ba1a35678a1b5316d212f4f366b2477232534a8aeca37f3c', '', 'Changing one bit (lowercase h → uppercase H) completely changes the hash — the avalanche effect.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Лавинный эффект', 'Hello world', '64ec88ca00b268e5ba1a35678a1b5316d212f4f366b2477232534a8aeca37f3c', '', 'Изменение одного бита (строчная h → заглавная H) полностью меняет хеш — лавинный эффект.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'Is SHA-256 reversible?', 'No. SHA-256 is a one-way function — given a hash, there is no practical algorithm to recover the original input. The only way to "reverse" it is to guess inputs and re-hash them, which works only for very small or predictable input spaces (e.g. dictionary words). For longer or random inputs the search space is astronomically large.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Можно ли расшифровать SHA-256?', 'Нет. SHA-256 — односторонняя функция: по хешу не существует практического алгоритма восстановления исходного значения. «Развернуть» хеш можно только подбором — пробуя разные входы и сравнивая результат. Это работает лишь для очень малых или предсказуемых пространств (например, словарных слов). Для длинных или случайных входов пространство поиска астрономическое.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'How is SHA-256 different from MD5 and SHA-1?', 'MD5 (128-bit) and SHA-1 (160-bit) are older hash functions with known collision attacks — different inputs can be crafted to produce the same hash. They should not be used for security-sensitive purposes. SHA-256 produces a 256-bit output and, as of 2026, has no practical collision or preimage attacks against it. It is the current standard for new systems.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Чем SHA-256 отличается от MD5 и SHA-1?', 'MD5 (128 бит) и SHA-1 (160 бит) — более старые хеш-функции с известными атаками коллизий: можно подобрать два разных входа, дающих одинаковый хеш. Для задач, критичных к безопасности, использовать их нельзя. SHA-256 выдаёт 256-битный результат и по состоянию на 2026 год не имеет практических атак коллизий или прообраза. Это текущий стандарт для новых систем.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Can I use SHA-256 to hash passwords?', 'A raw SHA-256 hash is too fast to be safe for password storage — attackers can compute billions of guesses per second on commodity hardware. For passwords, use a dedicated password hashing function such as Argon2, bcrypt, or scrypt, or at minimum PBKDF2-HMAC-SHA-256 with a high iteration count and a unique random salt per password.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Можно ли хешировать SHA-256 пароли?', 'Простой SHA-256 слишком быстр, чтобы быть безопасным для хранения паролей: на обычном железе можно перебирать миллиарды вариантов в секунду. Для паролей используйте специализированные функции — Argon2, bcrypt, scrypt — или, как минимум, PBKDF2-HMAC-SHA-256 с большим числом итераций и уникальной случайной солью для каждого пароля.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my input anywhere?', 'No. The hash is computed entirely in your browser using the built-in Web Crypto API (window.crypto.subtle.digest). Your input never leaves your device — there are no network requests, no logging of plaintext, and no server-side processing of the data you type.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли этот инструмент мой ввод куда-либо?', 'Нет. Хеш вычисляется полностью в вашем браузере с помощью встроенного Web Crypto API (window.crypto.subtle.digest). Введённые данные никогда не покидают ваше устройство — нет сетевых запросов, логирования открытого текста или серверной обработки.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'SHA-256', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'SHA-256', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Hash', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Хеш', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Cryptography', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Криптография', $now);
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

        $columns = array_map(static fn (string $field): string => '`' . $field . '`', [$foreignKey, 'language', ...array_keys($data), 'created_at', 'updated_at']);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            [$parentId, $language, ...array_values($data), $now, $now]
        );
    }

    /**
     * Возвращает переводы инструмента SHA-256.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'SHA-256 Hash Generator',
                'name_short'        => 'SHA-256',
                'description'       => 'Compute the SHA-256 cryptographic hash of any text in your browser. SHA-256 produces a 256-bit (64 hex characters) fingerprint used in Bitcoin, TLS certificates, JWT signatures, and file integrity checks.',
                'description_stort' => 'Generate a SHA-256 hash of text in your browser.',
                'meta_title'        => 'SHA-256 Hash Generator Online | Ciphers Online',
                'meta_description'  => 'Free online SHA-256 hash generator. Compute SHA-256 fingerprints of any text directly in your browser — no upload, no server. Used in Bitcoin, TLS, and JWT.',
            ],
            'ru' => [
                'name'              => 'Генератор SHA-256 хеша',
                'name_short'        => 'SHA-256',
                'description'       => 'Вычислите криптографический хеш SHA-256 любого текста прямо в браузере. SHA-256 выдаёт 256-битный отпечаток (64 шестнадцатеричных символа), используемый в Bitcoin, TLS-сертификатах, подписях JWT и для проверки целостности файлов.',
                'description_stort' => 'Вычислите SHA-256 хеш текста в браузере.',
                'meta_title'        => 'SHA-256 онлайн — генератор хеша | Ciphers Online',
                'meta_description'  => 'Бесплатный онлайн-генератор SHA-256. Вычисление отпечатков SHA-256 прямо в браузере, без загрузки на сервер. Используется в Bitcoin, TLS и JWT.',
            ],
            'de' => [
                'name'              => 'SHA-256 Hash-Generator',
                'name_short'        => 'SHA-256',
                'description'       => 'Berechnen Sie den kryptografischen SHA-256-Hash beliebigen Textes direkt im Browser. SHA-256 erzeugt einen 256-Bit-Fingerabdruck (64 Hex-Zeichen), der in Bitcoin, TLS-Zertifikaten, JWT-Signaturen und zur Integritätsprüfung verwendet wird.',
                'description_stort' => 'Berechnen Sie einen SHA-256-Hash von Text im Browser.',
                'meta_title'        => 'SHA-256 Hash-Generator Online | Ciphers Online',
                'meta_description'  => 'Kostenloser Online-SHA-256-Hash-Generator. Berechnen Sie SHA-256-Fingerabdrücke direkt im Browser — kein Upload, kein Server.',
            ],
            'es' => [
                'name'              => 'Generador de hash SHA-256',
                'name_short'        => 'SHA-256',
                'description'       => 'Calcula el hash criptográfico SHA-256 de cualquier texto en tu navegador. SHA-256 produce una huella de 256 bits (64 caracteres hex) usada en Bitcoin, certificados TLS, firmas JWT y verificación de integridad.',
                'description_stort' => 'Genera un hash SHA-256 de texto en el navegador.',
                'meta_title'        => 'Generador SHA-256 Online | Ciphers Online',
                'meta_description'  => 'Generador de hash SHA-256 gratis online. Calcula huellas SHA-256 directamente en tu navegador — sin cargas, sin servidor.',
            ],
            'fr' => [
                'name'              => 'Générateur de hachage SHA-256',
                'name_short'        => 'SHA-256',
                'description'       => 'Calculez l\'empreinte cryptographique SHA-256 d\'un texte directement dans votre navigateur. SHA-256 produit une empreinte de 256 bits (64 caractères hex) utilisée dans Bitcoin, les certificats TLS, les signatures JWT et la vérification d\'intégrité.',
                'description_stort' => 'Générez une empreinte SHA-256 de texte dans le navigateur.',
                'meta_title'        => 'Générateur SHA-256 en ligne | Ciphers Online',
                'meta_description'  => 'Générateur de hachage SHA-256 gratuit en ligne. Calculez des empreintes SHA-256 directement dans votre navigateur — pas de téléchargement.',
            ],
            'it' => [
                'name'              => 'Generatore di hash SHA-256',
                'name_short'        => 'SHA-256',
                'description'       => 'Calcola l\'hash crittografico SHA-256 di qualsiasi testo direttamente nel browser. SHA-256 produce un\'impronta a 256 bit (64 caratteri esadecimali) usata in Bitcoin, certificati TLS, firme JWT e verifica integrità.',
                'description_stort' => 'Genera un hash SHA-256 di testo nel browser.',
                'meta_title'        => 'Generatore SHA-256 Online | Ciphers Online',
                'meta_description'  => 'Generatore di hash SHA-256 gratuito online. Calcola impronte SHA-256 direttamente nel browser — nessun upload.',
            ],
            'pt' => [
                'name'              => 'Gerador de hash SHA-256',
                'name_short'        => 'SHA-256',
                'description'       => 'Calcule o hash criptográfico SHA-256 de qualquer texto diretamente no navegador. SHA-256 produz uma impressão de 256 bits (64 caracteres hex) usada em Bitcoin, certificados TLS, assinaturas JWT e verificação de integridade.',
                'description_stort' => 'Gere um hash SHA-256 de texto no navegador.',
                'meta_title'        => 'Gerador SHA-256 Online | Ciphers Online',
                'meta_description'  => 'Gerador de hash SHA-256 grátis online. Calcule impressões SHA-256 diretamente no navegador — sem upload, sem servidor.',
            ],
            'tr' => [
                'name'              => 'SHA-256 Hash Üretici',
                'name_short'        => 'SHA-256',
                'description'       => 'Herhangi bir metnin SHA-256 kriptografik karma değerini doğrudan tarayıcınızda hesaplayın. SHA-256, Bitcoin, TLS sertifikaları, JWT imzaları ve bütünlük doğrulamada kullanılan 256 bit (64 hex karakter) parmak izi üretir.',
                'description_stort' => 'Tarayıcıda metnin SHA-256 karmasını oluşturun.',
                'meta_title'        => 'SHA-256 Çevrimiçi Üretici | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi SHA-256 karma üretici. SHA-256 parmak izlerini doğrudan tarayıcınızda hesaplayın — yükleme yok, sunucu yok.',
            ],
        ];
    }
}
