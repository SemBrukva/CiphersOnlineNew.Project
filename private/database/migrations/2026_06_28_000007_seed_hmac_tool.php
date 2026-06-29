<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент HMAC в категорию «Хеширование».
 */
class SeedHmacTool extends Migration
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
            ['hmac']
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
            ['hmac']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 15, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'hmac', 'client', 15, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is HMAC?', '<p>HMAC (Hash-based Message Authentication Code) is a construction for authenticating messages using a cryptographic hash function and a secret key. Defined in RFC 2104 (1997), HMAC turns any hash function (SHA-256, SHA-512, …) into a message authentication code: it takes a message and a secret key and produces a fixed-length tag.</p><p>Only parties who know the key can compute or verify the tag. If an attacker modifies the message without knowing the key, the tag won\'t match — proving both integrity and authenticity. HMAC is the workhorse of API authentication (AWS request signing, JWT HS256, webhook signatures), TLS, IPsec, and countless protocols.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое HMAC?', '<p>HMAC (Hash-based Message Authentication Code) — конструкция для аутентификации сообщений с помощью криптографической хеш-функции и секретного ключа. Определён в RFC 2104 (1997). HMAC превращает любую хеш-функцию (SHA-256, SHA-512, …) в код аутентификации сообщения: принимает сообщение и секретный ключ, выдаёт тег фиксированной длины.</p><p>Только стороны, знающие ключ, могут вычислить или проверить тег. Если атакующий модифицирует сообщение, не зная ключа, тег не совпадёт — это доказывает и целостность, и подлинность. HMAC — рабочая лошадка аутентификации API (подписи запросов AWS, JWT HS256, подписи webhook), TLS, IPsec и множества других протоколов.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'How HMAC works', '<p>HMAC computes <code>H(K\' ⊕ opad || H(K\' ⊕ ipad || message))</code>, where K\' is the key (padded or hashed to block size), opad/ipad are constants, and H is the underlying hash. The double hashing structure is what gives HMAC its security: it remains secure even when the underlying hash has weaknesses like length-extension attacks.</p><p>Common variants are HMAC-SHA-256 (most popular for API authentication and JWT), HMAC-SHA-1 (legacy systems), HMAC-SHA-512 (when longer tags are desired). The output length equals the hash output: 32 bytes for SHA-256, 64 for SHA-512, etc.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Как работает HMAC', '<p>HMAC вычисляет <code>H(K\' ⊕ opad || H(K\' ⊕ ipad || message))</code>, где K\' — ключ (дополненный или хешированный до размера блока), opad/ipad — константы, H — лежащая в основе хеш-функция. Двойное хеширование даёт HMAC его безопасность: он остаётся стойким, даже когда лежащий в основе хеш имеет уязвимости вроде length-extension.</p><p>Распространённые варианты: HMAC-SHA-256 (самый популярный для аутентификации API и JWT), HMAC-SHA-1 (легаси-системы), HMAC-SHA-512 (когда нужен более длинный тег). Длина вывода равна длине хеша: 32 байта для SHA-256, 64 для SHA-512 и т.д.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Empty input', '', 'b613679a0814d9ec772f95d778c35fc5ff1697c493715653c6c712144292c5ad', '', 'HMAC-SHA-256 of empty message with empty key. The key is a required input — keep it secret in production.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Пустой ввод', '', 'b613679a0814d9ec772f95d778c35fc5ff1697c493715653c6c712144292c5ad', '', 'HMAC-SHA-256 от пустого сообщения с пустым ключом. Ключ — обязательный вход; в продакшене держите его в секрете.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Classic example', 'hello world', '734cc62f32841568f45715aeb9f4d7891324e6d948e4c6c60c0621cdac48623a', 'secret', 'HMAC-SHA-256 of "hello world" with key "secret".', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Классический пример', 'hello world', '734cc62f32841568f45715aeb9f4d7891324e6d948e4c6c60c0621cdac48623a', 'secret', 'HMAC-SHA-256 от "hello world" с ключом "secret".', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'RFC test vector', 'hello', '9307b3b915efb5171ff14d8cb55fbcc798c6c0ef1456d66ded1a6aa723a58b7b', 'key', 'HMAC-SHA-256 of "hello" with key "key" — a common test vector for HMAC implementations.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Тестовый вектор', 'hello', '9307b3b915efb5171ff14d8cb55fbcc798c6c0ef1456d66ded1a6aa723a58b7b', 'key', 'HMAC-SHA-256 от "hello" с ключом "key" — распространённый тестовый вектор для реализаций HMAC.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'When should I use HMAC vs a plain hash?', 'Use HMAC when you need to authenticate a message — confirm both that it has not been modified and that it was created by someone with the secret key. A plain hash (SHA-256) only protects integrity against accidental corruption: anyone can compute or recompute the hash. HMAC ties the hash to a secret, making forgery infeasible without the key.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Когда использовать HMAC, а когда обычный хеш?', 'Используйте HMAC, когда нужно аутентифицировать сообщение — подтвердить, что оно не было изменено и создано кем-то, владеющим секретным ключом. Обычный хеш (SHA-256) защищает только от случайной порчи: любой может вычислить или пересчитать хеш. HMAC привязывает хеш к секрету, делая подделку невозможной без ключа.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'What should the HMAC key look like?', 'A good HMAC key is a random sequence of bytes with at least 256 bits (32 bytes) of entropy for HMAC-SHA-256. Generate it with a cryptographic RNG (crypto.getRandomValues, /dev/urandom) and encode it as hex or base64 for storage. Never use passwords or guessable strings as HMAC keys — they have far less entropy than the key length implies.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Каким должен быть HMAC-ключ?', 'Хороший HMAC-ключ — случайная последовательность байт с энтропией не менее 256 бит (32 байта) для HMAC-SHA-256. Генерируйте его криптографическим RNG (crypto.getRandomValues, /dev/urandom) и сохраняйте в hex или base64. Никогда не используйте пароли или угадываемые строки как HMAC-ключи — у них гораздо меньше энтропии, чем длина ключа подразумевает.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Why does this tool support hex and base64 key formats?', 'In production, HMAC keys are random bytes — not text. They are usually stored as hex or base64 strings (e.g. in environment variables or config files). If you copy a real key from your system, choose the format it was encoded in. The "text" option treats the key as UTF-8 bytes directly, which is convenient for ad-hoc experimentation but not for production keys.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Почему инструмент поддерживает hex и base64 форматы ключа?', 'В продакшене HMAC-ключи — это случайные байты, а не текст. Их обычно хранят как hex или base64 строки (например, в переменных окружения или конфигах). Если копируете реальный ключ из системы, выбирайте формат, в котором он был закодирован. Опция «текст» трактует ключ как UTF-8 байты напрямую — это удобно для экспериментов, но не для продакшен-ключей.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my message or key anywhere?', 'No. HMAC is computed entirely in your browser via the Web Crypto API (window.crypto.subtle.sign). Your message and secret key never leave your device — no network requests, no logging, no server-side processing. Still: do not paste production keys into any web tool you do not fully trust.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли этот инструмент моё сообщение или ключ куда-либо?', 'Нет. HMAC вычисляется полностью в браузере через Web Crypto API (window.crypto.subtle.sign). Сообщение и секретный ключ не покидают устройство — нет сетевых запросов, логирования и серверной обработки. Тем не менее: не вставляйте продакшен-ключи в веб-инструменты, которым не полностью доверяете.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'HMAC', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'HMAC', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'MAC', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'MAC', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'API auth', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'API auth', $now);
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
                'name'              => 'HMAC Generator',
                'name_short'        => 'HMAC',
                'description'       => 'Generate an HMAC (Hash-based Message Authentication Code) of any message with a secret key. Supports HMAC-SHA-1, HMAC-SHA-256, HMAC-SHA-384, HMAC-SHA-512. Used in API signing (AWS), JWT HS256, webhook verification, and TLS.',
                'description_stort' => 'Generate an HMAC of a message with a secret key.',
                'meta_title'        => 'HMAC Generator Online (SHA-256, SHA-512, …) | Ciphers Online',
                'meta_description'  => 'Free online HMAC generator. Compute HMAC-SHA-256, HMAC-SHA-1, HMAC-SHA-384, HMAC-SHA-512 with a secret key — for API signing, JWT, and webhook verification. All in your browser.',
            ],
            'ru' => [
                'name'              => 'Генератор HMAC',
                'name_short'        => 'HMAC',
                'description'       => 'Вычислите HMAC (Hash-based Message Authentication Code) любого сообщения с секретным ключом. Поддерживает HMAC-SHA-1, HMAC-SHA-256, HMAC-SHA-384, HMAC-SHA-512. Используется в подписи API (AWS), JWT HS256, верификации webhook и TLS.',
                'description_stort' => 'Вычислите HMAC сообщения с секретным ключом.',
                'meta_title'        => 'HMAC онлайн — генератор (SHA-256, SHA-512, …) | Ciphers Online',
                'meta_description'  => 'Бесплатный онлайн-генератор HMAC. Вычисление HMAC-SHA-256, HMAC-SHA-1, HMAC-SHA-384, HMAC-SHA-512 с секретным ключом — для подписи API, JWT и верификации webhook. Всё в браузере.',
            ],
            'de' => [
                'name'              => 'HMAC Generator',
                'name_short'        => 'HMAC',
                'description'       => 'HMAC (Hash-based Message Authentication Code) für jede Nachricht mit einem geheimen Schlüssel erzeugen. Unterstützt HMAC-SHA-1, HMAC-SHA-256, HMAC-SHA-384, HMAC-SHA-512. Verwendet bei API-Signierung (AWS), JWT HS256 und Webhook-Verifikation.',
                'description_stort' => 'HMAC einer Nachricht mit geheimem Schlüssel erzeugen.',
                'meta_title'        => 'HMAC Generator Online (SHA-256, SHA-512, …) | Ciphers Online',
                'meta_description'  => 'Kostenloser HMAC-Generator online. HMAC-SHA-256, HMAC-SHA-1, HMAC-SHA-384, HMAC-SHA-512 mit geheimem Schlüssel — für API-Signaturen, JWT, Webhook-Verifikation.',
            ],
            'es' => [
                'name'              => 'Generador HMAC',
                'name_short'        => 'HMAC',
                'description'       => 'Genera un HMAC (Hash-based Message Authentication Code) de cualquier mensaje con una clave secreta. Soporta HMAC-SHA-1, HMAC-SHA-256, HMAC-SHA-384, HMAC-SHA-512. Usado en firma de API (AWS), JWT HS256 y verificación de webhooks.',
                'description_stort' => 'Genera un HMAC de un mensaje con una clave secreta.',
                'meta_title'        => 'Generador HMAC Online (SHA-256, SHA-512, …) | Ciphers Online',
                'meta_description'  => 'Generador HMAC online gratis. Calcula HMAC-SHA-256, HMAC-SHA-1, HMAC-SHA-384, HMAC-SHA-512 con clave secreta — para firma de API, JWT y verificación de webhooks.',
            ],
            'fr' => [
                'name'              => 'Générateur HMAC',
                'name_short'        => 'HMAC',
                'description'       => 'Calculez un HMAC (Hash-based Message Authentication Code) d\'un message avec une clé secrète. Prend en charge HMAC-SHA-1, HMAC-SHA-256, HMAC-SHA-384, HMAC-SHA-512. Utilisé pour la signature d\'API (AWS), JWT HS256 et la vérification de webhooks.',
                'description_stort' => 'Calculez un HMAC d\'un message avec une clé secrète.',
                'meta_title'        => 'Générateur HMAC en ligne (SHA-256, SHA-512, …) | Ciphers Online',
                'meta_description'  => 'Générateur HMAC gratuit en ligne. Calculez HMAC-SHA-256, HMAC-SHA-1, HMAC-SHA-384, HMAC-SHA-512 avec une clé secrète — signatures d\'API, JWT, webhooks.',
            ],
            'it' => [
                'name'              => 'Generatore HMAC',
                'name_short'        => 'HMAC',
                'description'       => 'Genera un HMAC (Hash-based Message Authentication Code) di qualsiasi messaggio con una chiave segreta. Supporta HMAC-SHA-1, HMAC-SHA-256, HMAC-SHA-384, HMAC-SHA-512. Usato nella firma di API (AWS), JWT HS256 e verifica di webhook.',
                'description_stort' => 'Genera un HMAC di un messaggio con una chiave segreta.',
                'meta_title'        => 'Generatore HMAC Online (SHA-256, SHA-512, …) | Ciphers Online',
                'meta_description'  => 'Generatore HMAC gratuito online. Calcola HMAC-SHA-256, HMAC-SHA-1, HMAC-SHA-384, HMAC-SHA-512 con chiave segreta — firma API, JWT, verifica webhook.',
            ],
            'pt' => [
                'name'              => 'Gerador HMAC',
                'name_short'        => 'HMAC',
                'description'       => 'Gere um HMAC (Hash-based Message Authentication Code) de qualquer mensagem com uma chave secreta. Suporta HMAC-SHA-1, HMAC-SHA-256, HMAC-SHA-384, HMAC-SHA-512. Usado em assinatura de API (AWS), JWT HS256 e verificação de webhooks.',
                'description_stort' => 'Gere um HMAC de uma mensagem com uma chave secreta.',
                'meta_title'        => 'Gerador HMAC Online (SHA-256, SHA-512, …) | Ciphers Online',
                'meta_description'  => 'Gerador HMAC grátis online. Calcule HMAC-SHA-256, HMAC-SHA-1, HMAC-SHA-384, HMAC-SHA-512 com chave secreta — assinatura de API, JWT, webhooks.',
            ],
            'tr' => [
                'name'              => 'HMAC Üretici',
                'name_short'        => 'HMAC',
                'description'       => 'Herhangi bir mesajın gizli anahtarla HMAC (Hash-based Message Authentication Code) değerini hesaplayın. HMAC-SHA-1, HMAC-SHA-256, HMAC-SHA-384, HMAC-SHA-512 destekler. API imzalama (AWS), JWT HS256 ve webhook doğrulamada kullanılır.',
                'description_stort' => 'Bir mesajın gizli anahtarla HMAC değerini hesaplayın.',
                'meta_title'        => 'HMAC Çevrimiçi Üretici (SHA-256, SHA-512, …) | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi HMAC üretici. Gizli anahtarla HMAC-SHA-256, HMAC-SHA-1, HMAC-SHA-384, HMAC-SHA-512 hesaplayın — API imzalama, JWT, webhook doğrulama.',
            ],
        ];
    }
}
