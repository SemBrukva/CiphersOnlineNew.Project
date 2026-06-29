<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент bcrypt в категорию «Хеширование».
 */
class SeedBcryptTool extends Migration
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
            ['bcrypt']
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
            ['bcrypt']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 90, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'bcrypt', 'client', 90, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is bcrypt?', '<p>bcrypt is a password hashing function designed by Niels Provos and David Mazières (USENIX 1999). It is based on the Blowfish cipher and was specifically built to resist brute-force attacks by being intentionally slow and configurable in cost. The output is a single self-contained string in the format <code>$2b$cost$22-char-salt+31-char-hash</code> — the salt, cost factor, and hash are all stored together.</p><p>bcrypt has been the workhorse of password storage for two decades. It is the default in Ruby on Rails, Django\'s legacy hasher, OpenBSD passwords, and countless web frameworks. Despite its age, it remains a solid choice when Argon2id is not available.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое bcrypt?', '<p>bcrypt — функция хеширования паролей, разработанная Niels Provos и David Mazières (USENIX 1999). Основана на шифре Blowfish и специально создана для защиты от брутфорса: намеренно медленная и настраиваемая по cost. Вывод — самодостаточная строка формата <code>$2b$cost$22-симв.-соль+31-симв.-хеш</code> — соль, cost factor и хеш хранятся вместе.</p><p>bcrypt был рабочей лошадкой хранения паролей два десятилетия. Это дефолт в Ruby on Rails, легаси Django, паролях OpenBSD и множестве веб-фреймворков. Несмотря на возраст, остаётся надёжным выбором, когда Argon2id недоступен.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Cost factor explained', '<p>bcrypt\'s cost factor is an exponent: cost=10 means 2¹⁰ = 1024 iterations. Each increase by 1 doubles the work. OWASP 2024 recommends a cost of at least 10, with 12 being common for new applications. Cost=12 takes roughly 200–400 ms on a typical server in 2026 — slow enough to make brute-force costly, fast enough not to ruin login UX.</p><p>The cost factor is stored in the hash output, so increasing it later does not break existing hashes (existing hashes are verified with their original cost; new hashes can use the higher cost). Plan to bump the cost every 2–3 years as hardware improves.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Cost factor: как работает', '<p>Cost factor bcrypt — это показатель степени: cost=10 означает 2¹⁰ = 1024 итерации. Каждое увеличение на 1 удваивает работу. OWASP 2024 рекомендует cost не менее 10, а 12 — обычный выбор для новых приложений. Cost=12 занимает примерно 200–400 мс на типичном сервере 2026 года — достаточно медленно, чтобы сделать брутфорс дорогим, но не настолько, чтобы ухудшить UX логина.</p><p>Cost factor хранится в выводе хеша, поэтому его увеличение позже не ломает существующие хеши (они проверяются со своим исходным cost; новые хеши могут использовать больший). Планируйте поднимать cost каждые 2–3 года по мере роста производительности железа.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Common password', 'password123', '', '', 'A typical password. Click Compute to generate a bcrypt hash with the configured cost.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Типичный пароль', 'password123', '', '', 'Обычный пароль. Нажмите Вычислить, чтобы получить bcrypt-хеш с указанным cost.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Passphrase', 'correct horse battery staple', '', '', 'A long passphrase. bcrypt only uses the first 72 bytes of input.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Парольная фраза', 'correct horse battery staple', '', '', 'Длинная парольная фраза. bcrypt использует только первые 72 байта входа.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'What is the 72-byte limit?', 'bcrypt internally truncates input passwords to 72 bytes. Longer passwords have their tail silently ignored — anything beyond byte 72 contributes nothing to security. A common workaround is to first hash the password with SHA-256 (giving 32 bytes) and feed the hash into bcrypt; alternatively, switch to Argon2id which has no such limit.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Что за лимит 72 байта?', 'bcrypt внутри обрезает входной пароль до 72 байт. У более длинных паролей хвост молча игнорируется — всё за 72-м байтом не влияет на безопасность. Распространённый обход — сначала захешировать пароль SHA-256 (32 байта) и передать хеш в bcrypt; альтернативно — переходите на Argon2id, у которого такого ограничения нет.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Should I use bcrypt or Argon2id?', 'For new systems, Argon2id is recommended by OWASP (2024). It is memory-hard, which makes GPU-based attacks much more expensive than against bcrypt. Choose bcrypt when you need broad library support, very stable behaviour, or compliance with platforms that require it. bcrypt remains acceptable when configured with cost ≥ 10.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'bcrypt или Argon2id?', 'Для новых систем OWASP (2024) рекомендует Argon2id. Он memory-hard, что делает GPU-атаки значительно дороже, чем против bcrypt. Выбирайте bcrypt, когда нужна широкая поддержка библиотек, стабильное поведение или соответствие платформам, требующим его. bcrypt остаётся приемлемым с cost ≥ 10.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'How do $2a$, $2b$, $2y$ differ?', 'These are versions of the bcrypt encoded format. $2a$ is the original; $2y$ was introduced in PHP to mark fixed implementations after a 2011 sign-extension bug; $2b$ is the current standard format (used by OpenBSD and the canonical OpenBSD-style bcrypt libraries). For new hashes you will see $2b$. Verification of older $2a$ and $2y$ hashes still works in all modern libraries.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Чем отличаются $2a$, $2b$, $2y$?', 'Это версии формата bcrypt. $2a$ — оригинал; $2y$ был введён в PHP для маркировки исправленных реализаций после бага sign-extension 2011 года; $2b$ — текущий стандартный формат (OpenBSD и каноничные OpenBSD-style библиотеки). Для новых хешей вы увидите $2b$. Проверка старых $2a$ и $2y$ хешей работает во всех современных библиотеках.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my password anywhere?', 'No. bcrypt runs entirely in your browser using the bcryptjs library — your password never leaves your device. No network requests, no logging, no server-side processing. Still: do not use this tool for production password hashing of real user accounts — use a server-side library with proper salt management.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли инструмент мой пароль куда-либо?', 'Нет. bcrypt работает полностью в браузере через библиотеку bcryptjs — пароль не покидает устройство. Нет сетевых запросов, логирования и серверной обработки. Тем не менее: не используйте этот инструмент для продакшен-хеширования реальных пользовательских паролей — берите серверную библиотеку с правильным управлением солью.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'bcrypt', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'bcrypt', $now);

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
                'name'              => 'bcrypt Online — Password Hashing',
                'name_short'        => 'bcrypt',
                'description'       => 'Generate or verify bcrypt password hashes in your browser. bcrypt is a slow, salted password hashing function based on Blowfish (1999), widely used in Rails, Django, OpenBSD, and many web frameworks. Configurable cost factor.',
                'description_stort' => 'Generate or verify bcrypt password hashes in your browser.',
                'meta_title'        => 'bcrypt Online Generator and Verifier | Ciphers Online',
                'meta_description'  => 'Free online bcrypt generator and password verifier. Configurable cost factor, automatic salt, $2b$ output format. 100% in your browser — no upload, no server.',
            ],
            'ru' => [
                'name'              => 'bcrypt онлайн — хеширование паролей',
                'name_short'        => 'bcrypt',
                'description'       => 'Сгенерируйте или проверьте bcrypt-хеши пароля в браузере. bcrypt — медленная функция хеширования паролей на основе Blowfish (1999), широко используется в Rails, Django, OpenBSD и многих веб-фреймворках. Настраиваемый cost factor.',
                'description_stort' => 'Сгенерируйте или проверьте bcrypt-хеши пароля в браузере.',
                'meta_title'        => 'bcrypt онлайн — генератор и верификатор | Ciphers Online',
                'meta_description'  => 'Бесплатный bcrypt-генератор и верификатор паролей онлайн. Настраиваемый cost factor, автогенерация соли, формат $2b$. 100% в браузере, без сервера.',
            ],
            'de' => [
                'name'              => 'bcrypt Online — Passwort-Hashing',
                'name_short'        => 'bcrypt',
                'description'       => 'Erzeugen oder verifizieren Sie bcrypt-Passwort-Hashes im Browser. bcrypt ist eine langsame, gesalzene Passwort-Hash-Funktion auf Blowfish-Basis (1999), weit verbreitet in Rails, Django, OpenBSD und vielen Web-Frameworks. Konfigurierbarer Cost-Faktor.',
                'description_stort' => 'bcrypt-Passwort-Hashes im Browser erzeugen oder verifizieren.',
                'meta_title'        => 'bcrypt Online Generator und Verifizierer | Ciphers Online',
                'meta_description'  => 'Kostenloser bcrypt-Generator und Passwort-Verifizierer online. Konfigurierbarer Cost-Faktor, automatischer Salt, $2b$-Format. 100% im Browser.',
            ],
            'es' => [
                'name'              => 'bcrypt Online — hashing de contraseñas',
                'name_short'        => 'bcrypt',
                'description'       => 'Genera o verifica hashes bcrypt de contraseñas en tu navegador. bcrypt es una función de hashing lenta y con salt basada en Blowfish (1999), usada en Rails, Django, OpenBSD y muchos frameworks web. Factor de coste configurable.',
                'description_stort' => 'Genera o verifica hashes bcrypt en el navegador.',
                'meta_title'        => 'Generador y verificador bcrypt Online | Ciphers Online',
                'meta_description'  => 'Generador bcrypt online gratis con verificación. Factor de coste configurable, salt automático, formato $2b$. 100% en el navegador.',
            ],
            'fr' => [
                'name'              => 'bcrypt en ligne — hachage de mots de passe',
                'name_short'        => 'bcrypt',
                'description'       => 'Générez ou vérifiez des empreintes bcrypt de mots de passe dans votre navigateur. bcrypt est une fonction de hachage lente et salée basée sur Blowfish (1999), utilisée dans Rails, Django, OpenBSD et nombreux frameworks. Facteur de coût configurable.',
                'description_stort' => 'Générez ou vérifiez des empreintes bcrypt dans le navigateur.',
                'meta_title'        => 'Générateur et vérificateur bcrypt en ligne | Ciphers Online',
                'meta_description'  => 'Générateur bcrypt gratuit en ligne avec vérification. Facteur de coût configurable, salt automatique, format $2b$. 100% navigateur.',
            ],
            'it' => [
                'name'              => 'bcrypt Online — hashing di password',
                'name_short'        => 'bcrypt',
                'description'       => 'Genera o verifica hash bcrypt di password nel browser. bcrypt è una funzione di hashing lenta con salt basata su Blowfish (1999), usata in Rails, Django, OpenBSD e molti framework. Cost factor configurabile.',
                'description_stort' => 'Genera o verifica hash bcrypt nel browser.',
                'meta_title'        => 'Generatore e verificatore bcrypt Online | Ciphers Online',
                'meta_description'  => 'Generatore bcrypt gratuito online con verifica. Cost factor configurabile, salt automatico, formato $2b$. 100% nel browser.',
            ],
            'pt' => [
                'name'              => 'bcrypt Online — hashing de senhas',
                'name_short'        => 'bcrypt',
                'description'       => 'Gere ou verifique hashes bcrypt de senhas no navegador. bcrypt é uma função de hashing lenta com salt baseada em Blowfish (1999), usada em Rails, Django, OpenBSD e muitos frameworks. Cost factor configurável.',
                'description_stort' => 'Gere ou verifique hashes bcrypt no navegador.',
                'meta_title'        => 'Gerador e verificador bcrypt Online | Ciphers Online',
                'meta_description'  => 'Gerador bcrypt grátis online com verificação. Cost factor configurável, salt automático, formato $2b$. 100% no navegador.',
            ],
            'tr' => [
                'name'              => 'bcrypt Çevrimiçi — parola karması',
                'name_short'        => 'bcrypt',
                'description'       => 'Tarayıcınızda bcrypt parola karmaları üretin veya doğrulayın. bcrypt, Blowfish tabanlı (1999) yavaş ve salt\'lı bir parola karma fonksiyonudur. Rails, Django, OpenBSD ve birçok framework\'te kullanılır. Cost factor yapılandırılabilir.',
                'description_stort' => 'Tarayıcıda bcrypt parola karmaları üretin veya doğrulayın.',
                'meta_title'        => 'bcrypt Çevrimiçi Üretici ve Doğrulayıcı | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi bcrypt üretici ve doğrulayıcı. Cost factor ayarlanabilir, otomatik salt, $2b$ formatı. %100 tarayıcıda.',
            ],
        ];
    }
}
