<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент SHA3-256 в категорию «Хеширование».
 */
class SeedSha3256Tool extends Migration
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
            ['sha3-256']
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
            ['sha3-256']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 50, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'sha3-256', 'client', 50, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is SHA3-256?', '<p>SHA3-256 is the 256-bit variant of the SHA-3 family, standardized by NIST in FIPS 202 (2015). SHA-3 is based on the Keccak permutation, fundamentally different in design from SHA-2: it uses a sponge construction rather than a Merkle–Damgård structure. The output is a 256-bit (64 hex characters) fingerprint.</p><p>SHA-3 was selected as the winner of NIST\'s 2007–2012 hash function competition, intended as a backup to SHA-2 with a completely different internal design. There are no known practical attacks against SHA3-256.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое SHA3-256?', '<p>SHA3-256 — 256-битный вариант семейства SHA-3, стандартизованный NIST в FIPS 202 (2015). SHA-3 основан на перестановке Keccak, принципиально иной по дизайну, чем SHA-2: использует конструкцию sponge вместо Merkle–Damgård. Вывод — 256-битный отпечаток (64 hex-символа).</p><p>SHA-3 был победителем конкурса хеш-функций NIST 2007–2012 годов и задумывался как резервная альтернатива SHA-2 с полностью другой внутренней структурой. Практических атак против SHA3-256 не известно.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'SHA3-256 vs SHA-256', '<p>Both produce 256-bit outputs and are considered secure for current and foreseeable use. The key difference is architectural: SHA-256 uses Merkle–Damgård with the Davies–Meyer compression function, while SHA3-256 uses Keccak\'s sponge construction. This means an attack breaking SHA-256\'s structure would not automatically affect SHA-3.</p><p>SHA-256 is faster in pure software on most hardware (especially with hardware acceleration like Intel SHA extensions). SHA3-256 is competitive in hardware implementations and is sometimes chosen for defence-in-depth — pairing different hash families in a system.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'SHA3-256 против SHA-256', '<p>Оба выдают 256-битный вывод и считаются безопасными для текущего и обозримого использования. Главное отличие — архитектура: SHA-256 использует Merkle–Damgård с компрессией Davies–Meyer, а SHA3-256 — sponge-конструкцию Keccak. Это значит, что атака на структуру SHA-256 автоматически не затронет SHA-3.</p><p>SHA-256 быстрее в чистом ПО на большинстве платформ (особенно с аппаратным ускорением Intel SHA extensions). SHA3-256 конкурентен в аппаратных реализациях, и часто выбирается для defense-in-depth — комбинации хеш-функций разных семейств в одной системе.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Empty string', '', 'a7ffc6f8bf1ed76651c14756a061d662f580ff4de43b49fa82d80a4b80f8434a', '', 'The SHA3-256 hash of empty input differs from SHA-256\'s due to the different padding rules.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Пустая строка', '', 'a7ffc6f8bf1ed76651c14756a061d662f580ff4de43b49fa82d80a4b80f8434a', '', 'SHA3-256 от пустой строки отличается от SHA-256 из-за разных правил паддинга.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Plain text', 'hello world', '644bcc7e564373040999aac89e7622f3ca71fba1d972fd94a31c3bfbf24e3938', '', 'Same input as SHA-256 but completely different output — different internal design.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Обычный текст', 'hello world', '644bcc7e564373040999aac89e7622f3ca71fba1d972fd94a31c3bfbf24e3938', '', 'Тот же ввод, что и у SHA-256, но совершенно другой вывод — другая внутренняя структура.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Avalanche effect', 'Hello world', '369183d3786773cef4e56c7b849e7ef5f742867510b676d6b38f8e38a222d8a2', '', 'A single bit change yields an unrelated output, just like SHA-2.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Лавинный эффект', 'Hello world', '369183d3786773cef4e56c7b849e7ef5f742867510b676d6b38f8e38a222d8a2', '', 'Изменение одного бита даёт несвязанный вывод, как и у SHA-2.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'Should I migrate from SHA-256 to SHA3-256?', 'Not urgently. SHA-256 has no known practical attacks and remains a sound choice for most applications. SHA3-256 is useful as a backup design in case future cryptanalysis affects SHA-2, but switching mid-project is rarely necessary. For new systems either is acceptable; SHA-256 is more widely supported in protocols and hardware.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Стоит ли мигрировать с SHA-256 на SHA3-256?', 'Срочно — нет. У SHA-256 нет известных практических атак, и он остаётся подходящим выбором для большинства приложений. SHA3-256 полезен как резервный дизайн на случай будущих атак против SHA-2, но переключаться в середине проекта обычно ни к чему. Для новых систем подходит и тот и другой; SHA-256 шире поддержан в протоколах и железе.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'What is the sponge construction?', 'A sponge function absorbs input by XOR-ing it into a fixed-size internal state and applies a permutation between absorption steps. To produce output (squeeze phase) it reads bytes from the state, applying the permutation between reads. This design allows arbitrary input length, arbitrary output length, and provides a clean separation between the rate (absorbed bytes per round) and the capacity (security parameter).', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Что такое sponge-конструкция?', 'Sponge-функция «впитывает» вход, XOR-я его с внутренним состоянием фиксированного размера, и применяет перестановку между шагами впитывания. На выходе («сжатие») считывает байты из состояния, применяя перестановку между чтениями. Эта конструкция позволяет произвольную длину входа, произвольную длину вывода и чёткое разделение между rate (байтами на раунд) и capacity (параметром безопасности).', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Is SHA3-256 used in Ethereum?', 'Ethereum uses Keccak-256, which is similar to SHA3-256 but with a slightly different padding rule (the padding was changed between Keccak\'s submission and SHA-3\'s standardization). The two functions produce different outputs for the same input. Tools labeled "Keccak-256" and "SHA3-256" are not interchangeable for blockchain use.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Используется ли SHA3-256 в Ethereum?', 'Ethereum использует Keccak-256, который похож на SHA3-256, но имеет немного другие правила паддинга (паддинг изменили между подачей Keccak и стандартизацией SHA-3). Две функции дают разный вывод для одного и того же входа. Инструменты с маркировкой «Keccak-256» и «SHA3-256» взаимозаменяемы не являются.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my input anywhere?', 'No. SHA3-256 is implemented as pure JavaScript that runs entirely in your browser — your input never leaves your device. No network requests, no logging, no server-side processing.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли этот инструмент мой ввод куда-либо?', 'Нет. SHA3-256 реализован на чистом JavaScript и работает полностью в браузере — введённые данные не покидают устройство. Нет сетевых запросов, логирования и серверной обработки.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'SHA3-256', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'SHA3-256', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Keccak', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Keccak', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'Sponge', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'Sponge', $now);
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
                'name'              => 'SHA3-256 Hash Generator',
                'name_short'        => 'SHA3-256',
                'description'       => 'Compute the SHA3-256 hash of any text in your browser. SHA3-256 is the 256-bit variant of the SHA-3 family, based on the Keccak permutation. Standardized in FIPS 202 (2015) as a backup design to SHA-2.',
                'description_stort' => 'Generate a SHA3-256 hash of text in your browser.',
                'meta_title'        => 'SHA3-256 Hash Generator Online | Ciphers Online',
                'meta_description'  => 'Free online SHA3-256 hash generator. Compute SHA3-256 fingerprints in your browser using the Keccak-based SHA-3 family. Different from SHA-256 by design.',
            ],
            'ru' => [
                'name'              => 'Генератор SHA3-256 хеша',
                'name_short'        => 'SHA3-256',
                'description'       => 'Вычислите SHA3-256 хеш любого текста в браузере. SHA3-256 — 256-битный вариант семейства SHA-3, основанный на перестановке Keccak. Стандартизован в FIPS 202 (2015) как резервный дизайн к SHA-2.',
                'description_stort' => 'Вычислите SHA3-256 хеш текста в браузере.',
                'meta_title'        => 'SHA3-256 онлайн — генератор хеша | Ciphers Online',
                'meta_description'  => 'Бесплатный онлайн-генератор SHA3-256. Вычисление SHA3-256 отпечатков в браузере с использованием семейства SHA-3 на основе Keccak. Отличается от SHA-256 по дизайну.',
            ],
            'de' => [
                'name'              => 'SHA3-256 Hash-Generator',
                'name_short'        => 'SHA3-256',
                'description'       => 'Berechnen Sie den SHA3-256-Hash beliebigen Textes im Browser. SHA3-256 ist die 256-Bit-Variante der SHA-3-Familie auf Basis der Keccak-Permutation. Standardisiert in FIPS 202 (2015) als Backup-Design zu SHA-2.',
                'description_stort' => 'SHA3-256-Hash von Text im Browser berechnen.',
                'meta_title'        => 'SHA3-256 Hash-Generator Online | Ciphers Online',
                'meta_description'  => 'Kostenloser SHA3-256-Hash-Generator online. SHA3-256-Fingerabdrücke im Browser mit der Keccak-basierten SHA-3-Familie.',
            ],
            'es' => [
                'name'              => 'Generador de hash SHA3-256',
                'name_short'        => 'SHA3-256',
                'description'       => 'Calcula el hash SHA3-256 de cualquier texto en tu navegador. SHA3-256 es la variante de 256 bits de la familia SHA-3, basada en la permutación Keccak. Estandarizada en FIPS 202 (2015) como diseño de respaldo de SHA-2.',
                'description_stort' => 'Genera un hash SHA3-256 de texto en el navegador.',
                'meta_title'        => 'Generador SHA3-256 Online | Ciphers Online',
                'meta_description'  => 'Generador SHA3-256 online gratis. Calcula huellas SHA3-256 en tu navegador usando la familia SHA-3 basada en Keccak.',
            ],
            'fr' => [
                'name'              => 'Générateur de hachage SHA3-256',
                'name_short'        => 'SHA3-256',
                'description'       => 'Calculez l\'empreinte SHA3-256 d\'un texte dans votre navigateur. SHA3-256 est la variante 256 bits de la famille SHA-3, basée sur la permutation Keccak. Standardisée dans FIPS 202 (2015) comme design de secours pour SHA-2.',
                'description_stort' => 'Générez une empreinte SHA3-256 de texte dans le navigateur.',
                'meta_title'        => 'Générateur SHA3-256 en ligne | Ciphers Online',
                'meta_description'  => 'Générateur SHA3-256 gratuit en ligne. Calculez des empreintes SHA3-256 dans votre navigateur via la famille SHA-3 basée sur Keccak.',
            ],
            'it' => [
                'name'              => 'Generatore di hash SHA3-256',
                'name_short'        => 'SHA3-256',
                'description'       => 'Calcola l\'hash SHA3-256 di qualsiasi testo nel browser. SHA3-256 è la variante a 256 bit della famiglia SHA-3, basata sulla permutazione Keccak. Standardizzata in FIPS 202 (2015) come design di backup per SHA-2.',
                'description_stort' => 'Genera un hash SHA3-256 di testo nel browser.',
                'meta_title'        => 'Generatore SHA3-256 Online | Ciphers Online',
                'meta_description'  => 'Generatore SHA3-256 gratuito online. Calcola impronte SHA3-256 nel browser tramite la famiglia SHA-3 basata su Keccak.',
            ],
            'pt' => [
                'name'              => 'Gerador de hash SHA3-256',
                'name_short'        => 'SHA3-256',
                'description'       => 'Calcule o hash SHA3-256 de qualquer texto no navegador. SHA3-256 é a variante de 256 bits da família SHA-3, baseada na permutação Keccak. Padronizada em FIPS 202 (2015) como design de backup para SHA-2.',
                'description_stort' => 'Gere um hash SHA3-256 de texto no navegador.',
                'meta_title'        => 'Gerador SHA3-256 Online | Ciphers Online',
                'meta_description'  => 'Gerador SHA3-256 grátis online. Calcule impressões SHA3-256 no navegador usando a família SHA-3 baseada em Keccak.',
            ],
            'tr' => [
                'name'              => 'SHA3-256 Hash Üretici',
                'name_short'        => 'SHA3-256',
                'description'       => 'Herhangi bir metnin SHA3-256 karma değerini tarayıcınızda hesaplayın. SHA3-256, Keccak permütasyonuna dayanan SHA-3 ailesinin 256 bit varyantıdır. FIPS 202 (2015) standardında SHA-2 için yedek tasarım olarak belirlendi.',
                'description_stort' => 'Tarayıcıda metnin SHA3-256 karmasını oluşturun.',
                'meta_title'        => 'SHA3-256 Çevrimiçi Üretici | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi SHA3-256 karma üretici. SHA3-256 parmak izlerini Keccak tabanlı SHA-3 ailesi ile tarayıcınızda hesaplayın.',
            ],
        ];
    }
}
