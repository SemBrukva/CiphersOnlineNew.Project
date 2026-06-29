<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент Argon2 в категорию «Хеширование».
 */
class SeedArgon2Tool extends Migration
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
            ['argon2']
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
            ['argon2']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 100, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'argon2', 'client', 100, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is Argon2?', '<p>Argon2 is a password hashing function and key derivation function designed by Aumasson, Biryukov, and Khovratovich, and selected as the winner of the Password Hashing Competition in 2015. It is standardized in RFC 9106 (2021) and recommended by OWASP (2024) as the preferred algorithm for password hashing in new applications.</p><p>The Argon2 family has three variants: Argon2d (data-dependent memory access, fastest but vulnerable to side-channel attacks), Argon2i (data-independent, resistant to side-channel attacks but slightly slower), and Argon2id (a hybrid that combines both — recommended for general use). Argon2id is the right default.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое Argon2?', '<p>Argon2 — функция хеширования паролей и вывода ключей, разработанная Aumasson, Biryukov и Khovratovich, победитель Password Hashing Competition 2015 года. Стандартизована в RFC 9106 (2021) и рекомендована OWASP (2024) как предпочтительный алгоритм для хеширования паролей в новых приложениях.</p><p>Семейство Argon2 имеет три варианта: Argon2d (data-dependent доступ к памяти — самый быстрый, но уязвим к side-channel-атакам), Argon2i (data-independent, устойчив к side-channel, но чуть медленнее), и Argon2id (гибрид обоих — рекомендуется для общего использования). Argon2id — правильный дефолт.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Memory-hardness and tunable parameters', '<p>What makes Argon2 special is memory-hardness: each iteration requires a configurable block of memory (default 19 MiB per OWASP 2024). Attackers using GPUs and ASICs are no longer 10–100× faster than CPUs — they pay the full memory cost. This shifts the economic equation back in defenders\' favour.</p><p>Argon2 has three tunable parameters: <strong>memory</strong> (KiB, default 19,456 = 19 MiB), <strong>iterations</strong> (time cost, default 2), and <strong>parallelism</strong> (lanes, default 1). OWASP 2024 recommendations: 19 MiB / 2 iterations / 1 lane, or 47 MiB / 1 / 1, or 9 MiB / 4 / 1. Pick the combination that fits your CPU and latency budget.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Memory-hardness и настраиваемые параметры', '<p>Главная особенность Argon2 — memory-hardness: каждая итерация требует настраиваемого блока памяти (по умолчанию 19 MiB по OWASP 2024). Атакующие на GPU и ASIC больше не в 10–100× быстрее CPU — они платят полную стоимость памяти. Это возвращает экономическое уравнение в пользу защитника.</p><p>У Argon2 три настраиваемых параметра: <strong>memory</strong> (KiB, дефолт 19 456 = 19 MiB), <strong>iterations</strong> (time cost, дефолт 2) и <strong>parallelism</strong> (lanes, дефолт 1). Рекомендации OWASP 2024: 19 MiB / 2 итерации / 1 lane, или 47 MiB / 1 / 1, или 9 MiB / 4 / 1. Выбирайте комбинацию под ваш CPU и latency-бюджет.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Common password', 'password123', '', '', 'A typical password. Click Compute to generate an Argon2id hash with OWASP-recommended defaults.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Типичный пароль', 'password123', '', '', 'Обычный пароль. Нажмите Вычислить, чтобы получить Argon2id-хеш с рекомендуемыми OWASP параметрами.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Passphrase', 'correct horse battery staple', '', '', 'A long passphrase with high entropy.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Парольная фраза', 'correct horse battery staple', '', '', 'Длинная парольная фраза с высокой энтропией.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'Which variant should I use?', 'Use Argon2id. It combines data-dependent and data-independent memory access in two passes, gaining the speed of Argon2d and the side-channel resistance of Argon2i. RFC 9106 and OWASP 2024 both recommend Argon2id as the default. Use Argon2i only when side-channel attacks are a primary concern; use Argon2d only when side-channels are guaranteed irrelevant (e.g. cryptocurrencies running on dedicated hardware).', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Какой вариант выбрать?', 'Используйте Argon2id. Он комбинирует data-dependent и data-independent доступ к памяти в двух проходах, получая скорость Argon2d и устойчивость к side-channel-атакам от Argon2i. RFC 9106 и OWASP 2024 рекомендуют Argon2id как дефолт. Используйте Argon2i только если side-channel-атаки — главная угроза; Argon2d — только когда side-channel гарантированно неактуален (например, криптовалюты на выделенном железе).', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'How do I choose memory, iterations, and parallelism?', 'OWASP 2024 lists several acceptable combinations: 19 MiB / 2 / 1, 47 MiB / 1 / 1, or 9 MiB / 4 / 1. Pick the one that gives 100–500 ms on your server — that is slow enough to deter brute-force but fast enough not to ruin login UX. If memory budget is tight, raise iterations; if CPU is tight, raise memory. Parallelism rarely needs to be > 1 outside specific multi-core scenarios.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Как выбрать memory, iterations и parallelism?', 'OWASP 2024 предлагает несколько приемлемых комбинаций: 19 MiB / 2 / 1, 47 MiB / 1 / 1 или 9 MiB / 4 / 1. Выбирайте ту, что даёт 100–500 мс на вашем сервере — это достаточно медленно для отпугивания брутфорса и достаточно быстро для логин-UX. Если памяти мало, поднимайте итерации; если CPU мало — поднимайте память. Parallelism > 1 редко нужен вне специфичных multi-core сценариев.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Why is Argon2 better than bcrypt or PBKDF2?', 'Argon2 is memory-hard: attackers using GPUs or ASICs cannot reduce their cost by parallelism since each guess still requires the configured amount of memory. bcrypt has only 4 KiB of internal state, and PBKDF2 has effectively none. With 19 MiB per guess (Argon2 default), a GPU with 24 GiB can only run ~1,200 guesses in parallel vs millions for PBKDF2 — a thousandfold defender\'s advantage.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Чем Argon2 лучше bcrypt и PBKDF2?', 'Argon2 memory-hard: атакующие на GPU и ASIC не могут снизить стоимость распараллеливанием, так как каждая попытка всё равно требует настроенный объём памяти. У bcrypt всего 4 KiB внутреннего состояния, а у PBKDF2 — практически нет. С 19 MiB на попытку (дефолт Argon2) GPU с 24 GiB может выполнять параллельно лишь ~1 200 попыток против миллионов для PBKDF2 — тысячекратное преимущество защитника.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my password anywhere?', 'No. Argon2 runs entirely in your browser using the hash-wasm library (WebAssembly) — your password never leaves your device. No network requests, no logging, no server-side processing. Still: do not use this tool for production hashing of real user accounts — use a server-side library.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли инструмент мой пароль куда-либо?', 'Нет. Argon2 работает полностью в браузере через библиотеку hash-wasm (WebAssembly) — пароль не покидает устройство. Нет сетевых запросов, логирования и серверной обработки. Тем не менее: не используйте этот инструмент для продакшен-хеширования реальных аккаунтов — берите серверную библиотеку.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Argon2', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Argon2', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Argon2id', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Argon2id', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'OWASP recommended', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'OWASP рекомендован', $now);
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
                'name'              => 'Argon2 Online — Password Hashing',
                'name_short'        => 'Argon2',
                'description'       => 'Generate or verify Argon2id password hashes in your browser. Argon2 is the winner of the Password Hashing Competition (2015), RFC 9106, OWASP-recommended. Memory-hard, resistant to GPU/ASIC attacks. Configure memory, iterations, parallelism, variant.',
                'description_stort' => 'Generate or verify Argon2 password hashes in your browser.',
                'meta_title'        => 'Argon2 Online Generator and Verifier | Ciphers Online',
                'meta_description'  => 'Free online Argon2id generator and password verifier. OWASP-recommended defaults, configurable memory/iterations/parallelism, PHC encoded output. 100% in browser via WebAssembly.',
            ],
            'ru' => [
                'name'              => 'Argon2 онлайн — хеширование паролей',
                'name_short'        => 'Argon2',
                'description'       => 'Сгенерируйте или проверьте Argon2id-хеши пароля в браузере. Argon2 — победитель Password Hashing Competition (2015), RFC 9106, рекомендован OWASP. Memory-hard, устойчив к GPU/ASIC-атакам. Настраиваемые память, итерации, параллелизм, вариант.',
                'description_stort' => 'Сгенерируйте или проверьте Argon2-хеши пароля в браузере.',
                'meta_title'        => 'Argon2 онлайн — генератор и верификатор | Ciphers Online',
                'meta_description'  => 'Бесплатный Argon2id-генератор и верификатор паролей онлайн. Рекомендованные OWASP дефолты, настраиваемые память/итерации/параллелизм, PHC-формат. 100% в браузере через WebAssembly.',
            ],
            'de' => [
                'name'              => 'Argon2 Online — Passwort-Hashing',
                'name_short'        => 'Argon2',
                'description'       => 'Erzeugen oder verifizieren Sie Argon2id-Passwort-Hashes im Browser. Argon2 — Gewinner der Password Hashing Competition (2015), RFC 9106, von OWASP empfohlen. Memory-hard, widerstandsfähig gegen GPU/ASIC-Angriffe. Konfigurierbar.',
                'description_stort' => 'Argon2-Passwort-Hashes im Browser erzeugen oder verifizieren.',
                'meta_title'        => 'Argon2 Online Generator und Verifizierer | Ciphers Online',
                'meta_description'  => 'Kostenloser Argon2id-Generator und Passwort-Verifizierer online. OWASP-empfohlene Defaults, konfigurierbar. 100% im Browser via WebAssembly.',
            ],
            'es' => [
                'name'              => 'Argon2 Online — hashing de contraseñas',
                'name_short'        => 'Argon2',
                'description'       => 'Genera o verifica hashes Argon2id de contraseñas en tu navegador. Argon2 es el ganador de la Password Hashing Competition (2015), RFC 9106, recomendado por OWASP. Memory-hard, resistente a ataques GPU/ASIC. Configurable.',
                'description_stort' => 'Genera o verifica hashes Argon2 en el navegador.',
                'meta_title'        => 'Generador y verificador Argon2 Online | Ciphers Online',
                'meta_description'  => 'Generador Argon2id online gratis con verificación. Valores predeterminados recomendados por OWASP, configurable. 100% en el navegador vía WebAssembly.',
            ],
            'fr' => [
                'name'              => 'Argon2 en ligne — hachage de mots de passe',
                'name_short'        => 'Argon2',
                'description'       => 'Générez ou vérifiez des empreintes Argon2id de mots de passe dans votre navigateur. Argon2 — vainqueur de la Password Hashing Competition (2015), RFC 9106, recommandé par OWASP. Memory-hard, résistant aux attaques GPU/ASIC. Configurable.',
                'description_stort' => 'Générez ou vérifiez des empreintes Argon2 dans le navigateur.',
                'meta_title'        => 'Générateur et vérificateur Argon2 en ligne | Ciphers Online',
                'meta_description'  => 'Générateur Argon2id gratuit en ligne avec vérification. Valeurs par défaut recommandées par OWASP, configurable. 100% navigateur via WebAssembly.',
            ],
            'it' => [
                'name'              => 'Argon2 Online — hashing di password',
                'name_short'        => 'Argon2',
                'description'       => 'Genera o verifica hash Argon2id di password nel browser. Argon2 è il vincitore della Password Hashing Competition (2015), RFC 9106, raccomandato da OWASP. Memory-hard, resistente ad attacchi GPU/ASIC. Configurabile.',
                'description_stort' => 'Genera o verifica hash Argon2 nel browser.',
                'meta_title'        => 'Generatore e verificatore Argon2 Online | Ciphers Online',
                'meta_description'  => 'Generatore Argon2id gratuito online con verifica. Valori predefiniti raccomandati da OWASP, configurabile. 100% nel browser via WebAssembly.',
            ],
            'pt' => [
                'name'              => 'Argon2 Online — hashing de senhas',
                'name_short'        => 'Argon2',
                'description'       => 'Gere ou verifique hashes Argon2id de senhas no navegador. Argon2 é o vencedor da Password Hashing Competition (2015), RFC 9106, recomendado pela OWASP. Memory-hard, resistente a ataques GPU/ASIC. Configurável.',
                'description_stort' => 'Gere ou verifique hashes Argon2 no navegador.',
                'meta_title'        => 'Gerador e verificador Argon2 Online | Ciphers Online',
                'meta_description'  => 'Gerador Argon2id grátis online com verificação. Padrões recomendados pela OWASP, configurável. 100% no navegador via WebAssembly.',
            ],
            'tr' => [
                'name'              => 'Argon2 Çevrimiçi — parola karması',
                'name_short'        => 'Argon2',
                'description'       => 'Tarayıcınızda Argon2id parola karmaları üretin veya doğrulayın. Argon2 — Password Hashing Competition (2015) galibi, RFC 9106, OWASP tarafından önerilir. Memory-hard, GPU/ASIC saldırılarına dayanıklı. Yapılandırılabilir.',
                'description_stort' => 'Tarayıcıda Argon2 parola karmaları üretin veya doğrulayın.',
                'meta_title'        => 'Argon2 Çevrimiçi Üretici ve Doğrulayıcı | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi Argon2id üretici ve doğrulayıcı. OWASP\'nin önerdiği varsayılanlar, yapılandırılabilir. %100 tarayıcıda WebAssembly ile.',
            ],
        ];
    }
}
