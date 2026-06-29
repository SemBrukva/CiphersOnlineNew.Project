<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент SHA-512 (генератор хеша) в категорию «Хеширование».
 */
class SeedSha512Tool extends Migration
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
            ['sha512']
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
            ['sha512']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 30, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'sha512', 'client', 30, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is SHA-512?', '<p>SHA-512 is the 512-bit variant of the SHA-2 family of cryptographic hash functions, designed by the U.S. National Security Agency and published by NIST in 2001. It produces a 512-bit (64-byte) hash value, rendered as a 128-character hexadecimal string.</p><p>SHA-512 operates on 1024-bit blocks with 80 rounds of 64-bit word operations. Internally it uses larger word size than SHA-256, which can be faster on 64-bit hardware for large inputs. The output is twice the length of SHA-256.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое SHA-512?', '<p>SHA-512 — 512-битный вариант криптографических хеш-функций семейства SHA-2, разработан Агентством национальной безопасности США и опубликован NIST в 2001 году. Выдаёт 512-битное (64-байтовое) значение, представляемое в виде 128-символьной шестнадцатеричной строки.</p><p>SHA-512 работает с 1024-битными блоками и 80 раундами операций над 64-битными словами. Внутренний размер слова больше, чем у SHA-256, что может давать прирост скорости на 64-битном железе для больших входов. Длина вывода вдвое больше, чем у SHA-256.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'When to choose SHA-512 over SHA-256', '<p>SHA-512 offers a larger output (512 vs 256 bits) and stronger theoretical resistance against future cryptanalytic advances. For applications with very long horizons (decades) or where output size is not a constraint, SHA-512 is a defensible default.</p><p>On 64-bit hardware SHA-512 can be faster than SHA-256 for bulk hashing because it processes 1024-bit blocks at a time. In space-constrained contexts (databases, URLs, tokens) SHA-256 is usually preferred because its output is half the size.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Когда выбирать SHA-512 вместо SHA-256', '<p>SHA-512 даёт больший вывод (512 vs 256 бит) и теоретически сильнее против будущих криптоаналитических прорывов. Для приложений с очень длинным горизонтом (десятилетия) или где размер вывода не критичен, SHA-512 — обоснованный выбор по умолчанию.</p><p>На 64-битном железе SHA-512 может быть быстрее SHA-256 при массовом хешировании, так как обрабатывает 1024-битные блоки за итерацию. В местах с ограничениями по размеру (БД, URL, токены) обычно предпочитают SHA-256 — он вдвое короче.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Empty string', '', 'cf83e1357eefb8bdf1542850d66d8007d620e4050b5715dc83f4a921d36ce9ce47d0d13c5d85f2b0ff8318d2877eec2f63b931bd47417a81a538327af927da3e', '', 'The SHA-512 hash of an empty input is a well-known constant.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Пустая строка', '', 'cf83e1357eefb8bdf1542850d66d8007d620e4050b5715dc83f4a921d36ce9ce47d0d13c5d85f2b0ff8318d2877eec2f63b931bd47417a81a538327af927da3e', '', 'SHA-512 от пустой строки — известная константа.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Plain text', 'hello world', '309ecc489c12d6eb4cc40f50c902f2b4d0ed77ee511a7c7a9bcd3ca86d4cd86f989dd35bc5ff499670da34255b45b0cfd830e81f605dcf7dc5542e93ae9cd76f', '', 'Short input produces a fixed 128-character hex string.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Обычный текст', 'hello world', '309ecc489c12d6eb4cc40f50c902f2b4d0ed77ee511a7c7a9bcd3ca86d4cd86f989dd35bc5ff499670da34255b45b0cfd830e81f605dcf7dc5542e93ae9cd76f', '', 'Короткий ввод даёт фиксированную hex-строку из 128 символов.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Avalanche effect', 'Hello world', 'b7f783baed8297f0db917462184ff4f08e69c2d5e5f79a942600f9725f58ce1f29c18139bf80b06c0fff2bdd34738452ecf40c488c22a7e3d80cdf6f9c1c0d47', '', 'A single bit change (lowercase h → uppercase H) completely transforms the output.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Лавинный эффект', 'Hello world', 'b7f783baed8297f0db917462184ff4f08e69c2d5e5f79a942600f9725f58ce1f29c18139bf80b06c0fff2bdd34738452ecf40c488c22a7e3d80cdf6f9c1c0d47', '', 'Изменение одного бита (h → H) полностью меняет вывод.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'Is SHA-512 more secure than SHA-256?', 'In practice, both SHA-256 and SHA-512 are considered secure for current and foreseeable use. SHA-512 has a larger output (512 bits vs 256), which provides a wider security margin against future attacks. There are no known practical attacks against either function. For most applications SHA-256 is sufficient; choose SHA-512 when you specifically need the larger output size or have 64-bit hardware where it can be faster.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'SHA-512 безопаснее SHA-256?', 'На практике и SHA-256, и SHA-512 считаются безопасными для текущего и обозримого использования. SHA-512 имеет больший вывод (512 бит vs 256), что даёт больший запас прочности против будущих атак. Практических атак против обеих функций не известно. Для большинства приложений достаточно SHA-256; выбирайте SHA-512, когда нужен больший вывод или используете 64-битное железо, где он быстрее.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'When is SHA-512 actually faster than SHA-256?', 'On 64-bit CPUs SHA-512 can outperform SHA-256 for large inputs because it operates on 64-bit words and processes 1024-bit blocks per iteration (vs 32-bit words and 512-bit blocks in SHA-256). For small inputs (tokens, IDs) the difference is negligible and SHA-256 is usually faster due to lower setup cost. Benchmark on your target hardware if performance matters.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Когда SHA-512 действительно быстрее SHA-256?', 'На 64-битных процессорах SHA-512 может опережать SHA-256 на больших входах, поскольку работает с 64-битными словами и обрабатывает 1024-битные блоки за итерацию (vs 32-битные слова и 512-битные блоки в SHA-256). Для коротких входов (токены, ID) разница незначительна и SHA-256 обычно быстрее из-за меньших накладных расходов. Бенчмарк на целевом железе — лучший способ определиться.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Can I use SHA-512 for password hashing?', 'Like all fast cryptographic hashes, raw SHA-512 is too quick to safely hash passwords — attackers can compute billions of guesses per second on commodity GPUs. For passwords use Argon2id (recommended), bcrypt, or scrypt, or PBKDF2-HMAC-SHA-512 with a high iteration count and a unique random salt per password.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Можно ли использовать SHA-512 для хеширования паролей?', 'Как и все быстрые криптографические хеши, простой SHA-512 слишком быстр для безопасного хранения паролей — на обычных GPU атакующий перебирает миллиарды вариантов в секунду. Для паролей используйте Argon2id (рекомендуется), bcrypt или scrypt; либо PBKDF2-HMAC-SHA-512 с большим числом итераций и уникальной случайной солью.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my input anywhere?', 'No. The SHA-512 hash is computed entirely in your browser via the Web Crypto API (window.crypto.subtle.digest). Your input never leaves your device — no network requests, no logging, no server-side processing.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли этот инструмент мой ввод куда-либо?', 'Нет. SHA-512 хеш вычисляется полностью в браузере через Web Crypto API (window.crypto.subtle.digest). Введённые данные не покидают устройство — нет сетевых запросов, логирования и серверной обработки.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'SHA-512', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'SHA-512', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Hash', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Хеш', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'SHA-2', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'SHA-2', $now);
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
     * Возвращает переводы инструмента SHA-512.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'SHA-512 Hash Generator',
                'name_short'        => 'SHA-512',
                'description'       => 'Compute the SHA-512 cryptographic hash of any text in your browser. SHA-512 is the 512-bit variant of the SHA-2 family, producing a 128-character hex fingerprint used in PBKDF2-HMAC-SHA-512, digital signatures, and integrity verification.',
                'description_stort' => 'Generate a SHA-512 hash of text in your browser.',
                'meta_title'        => 'SHA-512 Hash Generator Online | Ciphers Online',
                'meta_description'  => 'Free online SHA-512 hash generator. Compute SHA-512 fingerprints in your browser — used in PBKDF2, signatures, and integrity checks. Stronger output than SHA-256.',
            ],
            'ru' => [
                'name'              => 'Генератор SHA-512 хеша',
                'name_short'        => 'SHA-512',
                'description'       => 'Вычислите криптографический хеш SHA-512 любого текста в браузере. SHA-512 — 512-битный вариант семейства SHA-2, выдаёт 128-символьный hex-отпечаток для PBKDF2-HMAC-SHA-512, цифровых подписей и проверки целостности.',
                'description_stort' => 'Вычислите SHA-512 хеш текста в браузере.',
                'meta_title'        => 'SHA-512 онлайн — генератор хеша | Ciphers Online',
                'meta_description'  => 'Бесплатный онлайн-генератор SHA-512. Вычисление отпечатков SHA-512 в браузере — для PBKDF2, подписей и проверки целостности. Сильнее SHA-256.',
            ],
            'de' => [
                'name'              => 'SHA-512 Hash-Generator',
                'name_short'        => 'SHA-512',
                'description'       => 'Berechnen Sie den SHA-512-Hash beliebigen Textes im Browser. SHA-512 ist die 512-Bit-Variante der SHA-2-Familie und erzeugt einen 128-Zeichen-Hex-Fingerabdruck für PBKDF2-HMAC-SHA-512, digitale Signaturen und Integritätsprüfung.',
                'description_stort' => 'SHA-512-Hash von Text im Browser berechnen.',
                'meta_title'        => 'SHA-512 Hash-Generator Online | Ciphers Online',
                'meta_description'  => 'Kostenloser SHA-512-Hash-Generator online. Fingerabdrücke direkt im Browser — für PBKDF2, Signaturen, Integritätsprüfung.',
            ],
            'es' => [
                'name'              => 'Generador de hash SHA-512',
                'name_short'        => 'SHA-512',
                'description'       => 'Calcula el hash SHA-512 de cualquier texto en tu navegador. SHA-512 es la variante de 512 bits de SHA-2, produce una huella hex de 128 caracteres usada en PBKDF2-HMAC-SHA-512, firmas digitales y verificación de integridad.',
                'description_stort' => 'Genera un hash SHA-512 de texto en el navegador.',
                'meta_title'        => 'Generador SHA-512 Online | Ciphers Online',
                'meta_description'  => 'Generador SHA-512 online gratis. Calcula huellas SHA-512 en el navegador — PBKDF2, firmas, integridad.',
            ],
            'fr' => [
                'name'              => 'Générateur de hachage SHA-512',
                'name_short'        => 'SHA-512',
                'description'       => 'Calculez l\'empreinte SHA-512 d\'un texte dans votre navigateur. SHA-512 est la variante 512 bits de la famille SHA-2, produit une empreinte hex de 128 caractères pour PBKDF2-HMAC-SHA-512, signatures et intégrité.',
                'description_stort' => 'Générez une empreinte SHA-512 de texte dans le navigateur.',
                'meta_title'        => 'Générateur SHA-512 en ligne | Ciphers Online',
                'meta_description'  => 'Générateur SHA-512 gratuit en ligne. Calculez des empreintes SHA-512 dans votre navigateur — PBKDF2, signatures, intégrité.',
            ],
            'it' => [
                'name'              => 'Generatore di hash SHA-512',
                'name_short'        => 'SHA-512',
                'description'       => 'Calcola l\'hash SHA-512 di qualsiasi testo nel browser. SHA-512 è la variante a 512 bit della famiglia SHA-2, produce un\'impronta hex di 128 caratteri per PBKDF2-HMAC-SHA-512, firme e integrità.',
                'description_stort' => 'Genera un hash SHA-512 di testo nel browser.',
                'meta_title'        => 'Generatore SHA-512 Online | Ciphers Online',
                'meta_description'  => 'Generatore SHA-512 gratuito online. Calcola impronte SHA-512 nel browser — PBKDF2, firme, integrità.',
            ],
            'pt' => [
                'name'              => 'Gerador de hash SHA-512',
                'name_short'        => 'SHA-512',
                'description'       => 'Calcule o hash SHA-512 de qualquer texto no navegador. SHA-512 é a variante de 512 bits da família SHA-2, produz uma impressão hex de 128 caracteres para PBKDF2-HMAC-SHA-512, assinaturas e integridade.',
                'description_stort' => 'Gere um hash SHA-512 de texto no navegador.',
                'meta_title'        => 'Gerador SHA-512 Online | Ciphers Online',
                'meta_description'  => 'Gerador SHA-512 grátis online. Calcule impressões SHA-512 no navegador — PBKDF2, assinaturas, integridade.',
            ],
            'tr' => [
                'name'              => 'SHA-512 Hash Üretici',
                'name_short'        => 'SHA-512',
                'description'       => 'Herhangi bir metnin SHA-512 karma değerini doğrudan tarayıcınızda hesaplayın. SHA-512, SHA-2 ailesinin 512 bit varyantıdır; PBKDF2-HMAC-SHA-512, dijital imzalar ve bütünlük doğrulama için 128 karakterlik hex parmak izi üretir.',
                'description_stort' => 'Tarayıcıda metnin SHA-512 karmasını oluşturun.',
                'meta_title'        => 'SHA-512 Çevrimiçi Üretici | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi SHA-512 karma üretici. SHA-512 parmak izlerini doğrudan tarayıcıda hesaplayın — PBKDF2, imzalar, bütünlük.',
            ],
        ];
    }
}
