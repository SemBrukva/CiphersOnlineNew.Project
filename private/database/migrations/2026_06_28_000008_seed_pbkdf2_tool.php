<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент PBKDF2 в категорию «Хеширование».
 */
class SeedPbkdf2Tool extends Migration
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
            ['pbkdf2']
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
            ['pbkdf2']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 80, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'pbkdf2', 'client', 80, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is PBKDF2?', '<p>PBKDF2 (Password-Based Key Derivation Function 2) is a key derivation function defined in RFC 2898 (2000) and standardized in NIST SP 800-132. It takes a password, a salt, and an iteration count, and derives a cryptographically strong key by applying a pseudo-random function (typically HMAC with SHA-256 or SHA-512) repeatedly.</p><p>PBKDF2 is intentionally slow: each iteration adds work. With enough iterations (OWASP 2024 recommends 600,000 for HMAC-SHA-256), the cost per password guess becomes high enough to defend against offline brute-force attacks. PBKDF2 is widely supported — it is part of Web Crypto, .NET, JVM, OpenSSL, and most operating systems.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое PBKDF2?', '<p>PBKDF2 (Password-Based Key Derivation Function 2) — функция вывода ключа из пароля, определённая в RFC 2898 (2000) и стандартизованная в NIST SP 800-132. Принимает пароль, соль и счётчик итераций; выводит криптографически стойкий ключ, многократно применяя псевдослучайную функцию (обычно HMAC с SHA-256 или SHA-512).</p><p>PBKDF2 намеренно медленный: каждая итерация добавляет работу. При достаточном количестве итераций (OWASP 2024 рекомендует 600 000 для HMAC-SHA-256) стоимость одной попытки подбора становится высокой, что защищает от офлайн брутфорса. PBKDF2 широко поддерживается — есть в Web Crypto, .NET, JVM, OpenSSL и большинстве ОС.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'PBKDF2 vs bcrypt vs Argon2', '<p>PBKDF2 is the most portable option — it works in every browser, every language, and many hardware tokens. Its weakness is that it is purely CPU-bound: attackers with GPUs and ASICs gain a large advantage over defenders running PBKDF2 on commodity servers.</p><p>bcrypt resists GPUs better thanks to its 4 KiB internal state. Argon2id is the modern choice (winner of the 2015 Password Hashing Competition, recommended by OWASP) — it is both memory-hard and parallel-tunable, making attacks expensive on GPUs and ASICs. If you can choose, prefer Argon2id; if you need maximum compatibility or work inside FIPS-validated systems, PBKDF2 is the safe answer.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'PBKDF2 против bcrypt и Argon2', '<p>PBKDF2 — самый портативный вариант: работает в любом браузере, любом языке и многих аппаратных токенах. Его слабость в том, что он чисто CPU-bound: атакующие с GPU и ASIC получают большое преимущество над защитниками, запускающими PBKDF2 на обычных серверах.</p><p>bcrypt лучше сопротивляется GPU благодаря 4 KiB внутреннего состояния. Argon2id — современный выбор (победитель Password Hashing Competition 2015, рекомендован OWASP) — он memory-hard и настраиваемо параллельный, что делает атаки на GPU и ASIC дорогими. Если есть выбор, предпочитайте Argon2id; если нужна максимальная совместимость или работа в FIPS-валидированных системах — PBKDF2 безопасный ответ.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Common password', 'password123', '', '', 'A typical password. Click Compute to derive a key with the current settings.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Типичный пароль', 'password123', '', '', 'Обычный пароль. Нажмите Вычислить, чтобы получить ключ с текущими настройками.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Passphrase', 'correct horse battery staple', '', '', 'A long passphrase has high entropy and is easy to remember.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Парольная фраза', 'correct horse battery staple', '', '', 'Длинная парольная фраза имеет высокую энтропию и легко запоминается.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'How many iterations should I use for PBKDF2?', 'OWASP 2024 recommends 600,000 iterations for PBKDF2-HMAC-SHA-256 and 210,000 for PBKDF2-HMAC-SHA-512. NIST SP 800-132 recommends at least 1,000 (a very low floor — modern systems should be far above this). The right number depends on your threat model and hardware: aim for hashing to take 100–500 ms on your server. Higher iterations slow attackers proportionally; lower iterations save user-facing latency.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Сколько итераций использовать для PBKDF2?', 'OWASP 2024 рекомендует 600 000 итераций для PBKDF2-HMAC-SHA-256 и 210 000 для PBKDF2-HMAC-SHA-512. NIST SP 800-132 требует не менее 1 000 (очень низкая планка — современные системы должны быть значительно выше). Правильное число зависит от модели угроз и железа: цельтесь, чтобы хеширование занимало 100–500 мс на вашем сервере. Больше итераций пропорционально замедляют атакующего; меньше — экономят user-facing latency.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Why do I need a salt?', 'A salt is a unique random value added to each password before hashing. Without a salt, identical passwords produce identical hashes — making rainbow-table attacks practical. With a unique salt per user, attackers must attack each hash separately. The salt does not need to be secret, only unique (16 random bytes is plenty). Store the salt alongside the hash.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Зачем нужна соль?', 'Соль — уникальное случайное значение, добавляемое к каждому паролю перед хешированием. Без соли одинаковые пароли дают одинаковые хеши, что делает атаки rainbow-table практичными. С уникальной солью на пользователя атакующему приходится атаковать каждый хеш отдельно. Соль не обязана быть секретной, только уникальной (16 случайных байт достаточно). Храните соль рядом с хешем.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'When should I prefer PBKDF2 over Argon2?', 'Choose PBKDF2 when you need broad platform support, FIPS validation, or interoperability with legacy systems. Argon2id is generally better for new systems where it is available. PBKDF2 has no memory cost — attackers can parallelize it cheaply on GPUs. For high-security applications, Argon2id is the recommended choice.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Когда выбирать PBKDF2 вместо Argon2?', 'Выбирайте PBKDF2, когда нужна широкая поддержка платформ, FIPS-валидация или совместимость с легаси-системами. Для новых систем, где Argon2id доступен, он обычно лучше. PBKDF2 не имеет memory-cost — атакующие дёшево параллелят его на GPU. Для высокого уровня безопасности рекомендуется Argon2id.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my password anywhere?', 'No. PBKDF2 is computed entirely in your browser via the Web Crypto API (window.crypto.subtle.deriveBits). Your password and salt never leave your device — no network requests, no logging, no server-side processing.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли инструмент мой пароль куда-либо?', 'Нет. PBKDF2 вычисляется полностью в браузере через Web Crypto API (window.crypto.subtle.deriveBits). Пароль и соль не покидают устройство — нет сетевых запросов, логирования и серверной обработки.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'PBKDF2', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'PBKDF2', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'KDF', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'KDF', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Password hashing', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Хеширование паролей', $now);
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
                'name'              => 'PBKDF2 Online — Key Derivation',
                'name_short'        => 'PBKDF2',
                'description'       => 'Derive a cryptographic key from a password using PBKDF2 (RFC 2898) directly in your browser. Configure hash function (SHA-256/SHA-512), iterations, key length, and salt. Includes a Verify mode to check a stored hash against a password.',
                'description_stort' => 'Derive a key from password using PBKDF2 in your browser.',
                'meta_title'        => 'PBKDF2 Online Calculator with Verify | Ciphers Online',
                'meta_description'  => 'Free online PBKDF2 calculator. Derive keys with SHA-256/SHA-512, configurable iterations and salt — and verify passwords against stored hashes. 100% in your browser.',
            ],
            'ru' => [
                'name'              => 'PBKDF2 онлайн — вывод ключа',
                'name_short'        => 'PBKDF2',
                'description'       => 'Получите криптографический ключ из пароля с помощью PBKDF2 (RFC 2898) прямо в браузере. Настраиваемая хеш-функция (SHA-256/SHA-512), число итераций, длина ключа и соль. Включает режим Verify для сравнения сохранённого хеша с паролем.',
                'description_stort' => 'Получите ключ из пароля через PBKDF2 в браузере.',
                'meta_title'        => 'PBKDF2 онлайн — калькулятор с Verify | Ciphers Online',
                'meta_description'  => 'Бесплатный онлайн-калькулятор PBKDF2. Вывод ключей с SHA-256/SHA-512, настраиваемыми итерациями и солью — плюс проверка паролей по сохранённым хешам. 100% в браузере.',
            ],
            'de' => [
                'name'              => 'PBKDF2 Online — Schlüsselableitung',
                'name_short'        => 'PBKDF2',
                'description'       => 'Leiten Sie einen kryptografischen Schlüssel aus einem Passwort mit PBKDF2 (RFC 2898) direkt im Browser ab. Konfigurierbare Hash-Funktion (SHA-256/SHA-512), Iterationen, Schlüssellänge und Salt. Enthält Verify-Modus zur Überprüfung gespeicherter Hashes.',
                'description_stort' => 'Schlüssel aus Passwort via PBKDF2 im Browser ableiten.',
                'meta_title'        => 'PBKDF2 Online Rechner mit Verify | Ciphers Online',
                'meta_description'  => 'Kostenloser PBKDF2-Rechner online. Schlüssel mit SHA-256/SHA-512, konfigurierbare Iterationen und Salt — plus Passwortverifikation. 100% im Browser.',
            ],
            'es' => [
                'name'              => 'PBKDF2 Online — derivación de clave',
                'name_short'        => 'PBKDF2',
                'description'       => 'Deriva una clave criptográfica a partir de una contraseña con PBKDF2 (RFC 2898) directamente en tu navegador. Configura hash (SHA-256/SHA-512), iteraciones, longitud y salt. Incluye modo Verify para comparar un hash almacenado con una contraseña.',
                'description_stort' => 'Deriva una clave de contraseña con PBKDF2 en el navegador.',
                'meta_title'        => 'Calculadora PBKDF2 Online con Verify | Ciphers Online',
                'meta_description'  => 'Calculadora PBKDF2 gratuita online. Deriva claves con SHA-256/SHA-512, iteraciones y salt configurables — más verificación de contraseñas. 100% en el navegador.',
            ],
            'fr' => [
                'name'              => 'PBKDF2 en ligne — dérivation de clé',
                'name_short'        => 'PBKDF2',
                'description'       => 'Dérivez une clé cryptographique d\'un mot de passe via PBKDF2 (RFC 2898) directement dans votre navigateur. Configurez la fonction de hachage (SHA-256/SHA-512), les itérations, la longueur de clé et le salt. Inclut un mode Vérifier pour comparer un hash stocké à un mot de passe.',
                'description_stort' => 'Dérivez une clé d\'un mot de passe via PBKDF2 dans le navigateur.',
                'meta_title'        => 'Calculateur PBKDF2 en ligne avec Verify | Ciphers Online',
                'meta_description'  => 'Calculateur PBKDF2 gratuit en ligne. Dérivez des clés avec SHA-256/SHA-512, itérations et salt configurables — plus vérification de mots de passe. 100% navigateur.',
            ],
            'it' => [
                'name'              => 'PBKDF2 Online — derivazione di chiave',
                'name_short'        => 'PBKDF2',
                'description'       => 'Deriva una chiave crittografica da una password con PBKDF2 (RFC 2898) direttamente nel browser. Configura hash (SHA-256/SHA-512), iterazioni, lunghezza chiave e salt. Include modalità Verify per confrontare un hash salvato con una password.',
                'description_stort' => 'Deriva una chiave da password con PBKDF2 nel browser.',
                'meta_title'        => 'Calcolatore PBKDF2 Online con Verify | Ciphers Online',
                'meta_description'  => 'Calcolatore PBKDF2 gratuito online. Deriva chiavi con SHA-256/SHA-512, iterazioni e salt configurabili — più verifica password. 100% nel browser.',
            ],
            'pt' => [
                'name'              => 'PBKDF2 Online — derivação de chave',
                'name_short'        => 'PBKDF2',
                'description'       => 'Derive uma chave criptográfica a partir de uma senha com PBKDF2 (RFC 2898) diretamente no navegador. Configure hash (SHA-256/SHA-512), iterações, comprimento e salt. Inclui modo Verify para comparar um hash armazenado com uma senha.',
                'description_stort' => 'Derive uma chave da senha com PBKDF2 no navegador.',
                'meta_title'        => 'Calculadora PBKDF2 Online com Verify | Ciphers Online',
                'meta_description'  => 'Calculadora PBKDF2 grátis online. Derive chaves com SHA-256/SHA-512, iterações e salt configuráveis — mais verificação de senhas. 100% no navegador.',
            ],
            'tr' => [
                'name'              => 'PBKDF2 Çevrimiçi — anahtar türetme',
                'name_short'        => 'PBKDF2',
                'description'       => 'PBKDF2 (RFC 2898) ile bir paroladan kriptografik anahtar türetin — doğrudan tarayıcınızda. Hash fonksiyonu (SHA-256/SHA-512), iterasyon, anahtar uzunluğu ve salt yapılandırılabilir. Doğrulama modu da var.',
                'description_stort' => 'Tarayıcıda PBKDF2 ile paroladan anahtar türetin.',
                'meta_title'        => 'PBKDF2 Çevrimiçi Hesaplayıcı (Verify) | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi PBKDF2 hesaplayıcı. SHA-256/SHA-512 ile anahtar türetimi, ayarlanabilir iterasyon ve salt — artı parola doğrulama. %100 tarayıcıda.',
            ],
        ];
    }
}
