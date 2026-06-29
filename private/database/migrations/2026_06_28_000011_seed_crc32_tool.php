<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент CRC32 в категорию «Хеширование».
 */
class SeedCrc32Tool extends Migration
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
            ['crc32']
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
            ['crc32']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 110, 1, $now, $cipherId]
            );

            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'crc32', 'client', 110, 1, $now, $now]
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
        $this->upsertBlockTranslation($block1, 'en', 'What is CRC32?', '<p>CRC32 (Cyclic Redundancy Check, 32-bit) is a checksum function based on polynomial division over GF(2). The IEEE 802.3 variant — used in Ethernet, ZIP archives, PNG image chunks, and gzip — uses the polynomial 0xEDB88320 (reversed representation of 0x04C11DB7). It produces an 8-character hexadecimal fingerprint.</p><p>CRC32 is fast, simple to implement, and excellent at detecting accidental errors during transmission or storage: any single-bit flip, any double-bit error within a 32-bit window, and any odd-bit error pattern are guaranteed to be detected. It is the standard integrity check for many file formats and network protocols.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое CRC32?', '<p>CRC32 (Cyclic Redundancy Check, 32-bit) — функция контрольной суммы, основанная на полиномиальном делении над GF(2). Вариант IEEE 802.3 — используется в Ethernet, ZIP-архивах, PNG-чанках и gzip — задаётся полиномом 0xEDB88320 (отражённое представление 0x04C11DB7). Выдаёт 8-символьный шестнадцатеричный отпечаток.</p><p>CRC32 быстрый, прост в реализации и отлично обнаруживает случайные ошибки при передаче или хранении: любой однобитный сбой, любая двухбитная ошибка в 32-битном окне и любой нечётный по числу битов паттерн гарантированно обнаруживаются. Это стандартная проверка целостности для многих файловых форматов и сетевых протоколов.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'CRC32 vs cryptographic hashes', '<p>CRC32 is NOT a cryptographic hash function. It is designed for accidental error detection — not for resisting deliberate attacks. Forging a message with a target CRC32 is trivial: the function is linear and an attacker can solve for the required input bytes in milliseconds.</p><p>Use CRC32 when you need a fast integrity check against transmission noise, disk errors, or memory corruption. Use SHA-256 or stronger when you need to detect deliberate tampering. Never use CRC32 for password hashing, signatures, or any security-critical purpose.</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'CRC32 против криптографических хешей', '<p>CRC32 — НЕ криптографическая хеш-функция. Она создана для обнаружения случайных ошибок, а не для защиты от целенаправленных атак. Подделать сообщение с заданным CRC32 тривиально: функция линейная, и атакующий находит нужные байты за миллисекунды.</p><p>Используйте CRC32, когда нужна быстрая проверка целостности против шума передачи, сбоев диска или повреждения памяти. Используйте SHA-256 и сильнее, когда нужно обнаруживать намеренные изменения. Никогда не применяйте CRC32 для хеширования паролей, подписей или других задач безопасности.</p>', $now);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Empty string', '', '00000000', '', 'The CRC32 of empty input is all zeros.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Пустая строка', '', '00000000', '', 'CRC32 от пустой строки — все нули.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Plain text', 'hello world', '0d4a1185', '', 'A short message produces an 8-character hex string of fixed length.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Обычный текст', 'hello world', '0d4a1185', '', 'Короткое сообщение даёт фиксированную hex-строку из 8 символов.', $now);

        $example3 = $this->upsertExample($cipherId, 30, 'encrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Pangram', 'The quick brown fox jumps over the lazy dog', '414fa339', '', 'A classic test pangram used in CRC32 and other checksum tests.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Панграмма', 'The quick brown fox jumps over the lazy dog', '414fa339', '', 'Классическая тестовая панграмма, используемая в проверках CRC32 и контрольных сумм.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'Why does my ZIP/PNG file use CRC32?', 'CRC32 catches the vast majority of accidental corruption while being extremely fast to compute (hundreds of MB/s on modern CPUs, billions of bytes/s with hardware acceleration). For container formats that store many independent items (ZIP entries, PNG chunks), CRC32 is fast enough to be computed on every read, making integrity checks essentially free. Cryptographic hashes would be overkill — and more expensive.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Почему мой ZIP/PNG файл использует CRC32?', 'CRC32 ловит подавляющее большинство случайных повреждений и при этом очень быстро считается (сотни MB/s на современных CPU, миллиарды байт/с с аппаратным ускорением). Для контейнерных форматов с множеством независимых элементов (ZIP-записи, PNG-чанки) CRC32 настолько быстр, что вычисляется при каждом чтении — проверка целостности обходится бесплатно. Криптографические хеши были бы избыточны и дороже.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Is CRC32 the same as CRC32C?', 'No. CRC32 (IEEE 802.3, polynomial 0xEDB88320) and CRC32C (Castagnoli, polynomial 0x82F63B78) use different polynomials and produce different outputs for the same input. CRC32 is used in Ethernet, ZIP, PNG, gzip. CRC32C is used in iSCSI, SCTP, Btrfs, ext4 metadata, and is hardware-accelerated by the SSE 4.2 CRC32 instruction. Most general-purpose tools (including this one) mean CRC32 IEEE when they say "CRC32".', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'CRC32 и CRC32C — одно и то же?', 'Нет. CRC32 (IEEE 802.3, полином 0xEDB88320) и CRC32C (Castagnoli, полином 0x82F63B78) используют разные полиномы и дают разный вывод для одного и того же входа. CRC32 применяется в Ethernet, ZIP, PNG, gzip. CRC32C — в iSCSI, SCTP, Btrfs, метаданных ext4, имеет аппаратное ускорение в SSE 4.2. Большинство универсальных инструментов (включая этот) под «CRC32» понимают IEEE-вариант.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'Can I use CRC32 to detect deliberate tampering?', 'No. CRC32 is mathematically linear: given a target checksum and any starting message, an attacker can append (or modify) bytes to make the CRC32 match the target. This takes microseconds. For any threat model where an adversary could modify your data, use an HMAC (HMAC-SHA-256) or a digital signature instead.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Можно ли CRC32 использовать для обнаружения намеренных изменений?', 'Нет. CRC32 математически линеен: имея целевую контрольную сумму и любое начальное сообщение, атакующий может дописать (или изменить) байты так, чтобы CRC32 совпал с целевым. Это занимает микросекунды. Для любой модели угроз, где злоумышленник может модифицировать данные, используйте HMAC (HMAC-SHA-256) или цифровую подпись.', $now);

        $faq4 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 40, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq4, 'en', 'Does this tool send my input anywhere?', 'No. CRC32 is implemented as pure JavaScript using a lookup table — your input never leaves your device. No network requests, no logging, no server-side processing. The computation runs in your browser at hundreds of megabytes per second.', $now);
        $this->upsertFaqTranslation($faq4, 'ru', 'Отправляет ли этот инструмент мой ввод куда-либо?', 'Нет. CRC32 реализован на чистом JavaScript через таблицу подстановки — введённые данные не покидают устройство. Нет сетевых запросов, логирования и серверной обработки. Вычисление работает в браузере со скоростью сотен мегабайт в секунду.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'CRC32', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'CRC32', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Checksum', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Контрольная сумма', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'IEEE 802.3', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'IEEE 802.3', $now);
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
                'name'              => 'CRC32 Online Calculator',
                'name_short'        => 'CRC32',
                'description'       => 'Compute the CRC32 (IEEE 802.3) checksum of any text in your browser. CRC32 is a non-cryptographic 32-bit checksum used in Ethernet, ZIP, PNG, gzip, and many file formats for fast integrity verification.',
                'description_stort' => 'Compute the CRC32 checksum of text in your browser.',
                'meta_title'        => 'CRC32 Online Calculator (IEEE 802.3) | Ciphers Online',
                'meta_description'  => 'Free online CRC32 calculator. Compute 8-character CRC32 (IEEE 802.3) checksums in your browser — used in Ethernet, ZIP, PNG, and gzip. Fast, simple, no upload.',
            ],
            'ru' => [
                'name'              => 'CRC32 онлайн — калькулятор',
                'name_short'        => 'CRC32',
                'description'       => 'Вычислите CRC32 (IEEE 802.3) контрольную сумму любого текста в браузере. CRC32 — некриптографическая 32-битная контрольная сумма, используется в Ethernet, ZIP, PNG, gzip и многих форматах для быстрой проверки целостности.',
                'description_stort' => 'Вычислите CRC32 контрольную сумму текста в браузере.',
                'meta_title'        => 'CRC32 онлайн — калькулятор (IEEE 802.3) | Ciphers Online',
                'meta_description'  => 'Бесплатный онлайн-калькулятор CRC32. Вычисление 8-символьных CRC32 (IEEE 802.3) контрольных сумм в браузере — Ethernet, ZIP, PNG, gzip. Быстро, просто, без загрузки.',
            ],
            'de' => [
                'name'              => 'CRC32 Online-Rechner',
                'name_short'        => 'CRC32',
                'description'       => 'Berechnen Sie die CRC32-Prüfsumme (IEEE 802.3) beliebigen Textes im Browser. CRC32 ist eine nicht-kryptografische 32-Bit-Prüfsumme, verwendet in Ethernet, ZIP, PNG, gzip und vielen Dateiformaten zur schnellen Integritätsprüfung.',
                'description_stort' => 'CRC32-Prüfsumme von Text im Browser berechnen.',
                'meta_title'        => 'CRC32 Online-Rechner (IEEE 802.3) | Ciphers Online',
                'meta_description'  => 'Kostenloser CRC32-Rechner online. 8-Zeichen-CRC32-Prüfsummen (IEEE 802.3) im Browser — Ethernet, ZIP, PNG, gzip.',
            ],
            'es' => [
                'name'              => 'Calculadora CRC32 Online',
                'name_short'        => 'CRC32',
                'description'       => 'Calcula la suma de comprobación CRC32 (IEEE 802.3) de cualquier texto en tu navegador. CRC32 es una suma no criptográfica de 32 bits, usada en Ethernet, ZIP, PNG, gzip y muchos formatos.',
                'description_stort' => 'Calcula la suma de comprobación CRC32 de texto en el navegador.',
                'meta_title'        => 'Calculadora CRC32 Online (IEEE 802.3) | Ciphers Online',
                'meta_description'  => 'Calculadora CRC32 online gratis. Sumas CRC32 (IEEE 802.3) de 8 caracteres en el navegador — Ethernet, ZIP, PNG, gzip.',
            ],
            'fr' => [
                'name'              => 'Calculateur CRC32 en ligne',
                'name_short'        => 'CRC32',
                'description'       => 'Calculez la somme de contrôle CRC32 (IEEE 802.3) d\'un texte dans votre navigateur. CRC32 est une somme de contrôle non cryptographique de 32 bits, utilisée dans Ethernet, ZIP, PNG, gzip et de nombreux formats.',
                'description_stort' => 'Calculez la somme de contrôle CRC32 de texte dans le navigateur.',
                'meta_title'        => 'Calculateur CRC32 en ligne (IEEE 802.3) | Ciphers Online',
                'meta_description'  => 'Calculateur CRC32 gratuit en ligne. Sommes CRC32 (IEEE 802.3) de 8 caractères dans le navigateur — Ethernet, ZIP, PNG, gzip.',
            ],
            'it' => [
                'name'              => 'Calcolatore CRC32 Online',
                'name_short'        => 'CRC32',
                'description'       => 'Calcola il checksum CRC32 (IEEE 802.3) di qualsiasi testo nel browser. CRC32 è un checksum non crittografico a 32 bit, usato in Ethernet, ZIP, PNG, gzip e molti formati.',
                'description_stort' => 'Calcola il checksum CRC32 di testo nel browser.',
                'meta_title'        => 'Calcolatore CRC32 Online (IEEE 802.3) | Ciphers Online',
                'meta_description'  => 'Calcolatore CRC32 gratuito online. Checksum CRC32 (IEEE 802.3) a 8 caratteri nel browser — Ethernet, ZIP, PNG, gzip.',
            ],
            'pt' => [
                'name'              => 'Calculadora CRC32 Online',
                'name_short'        => 'CRC32',
                'description'       => 'Calcule a soma de verificação CRC32 (IEEE 802.3) de qualquer texto no navegador. CRC32 é uma soma não criptográfica de 32 bits, usada em Ethernet, ZIP, PNG, gzip e muitos formatos.',
                'description_stort' => 'Calcule a soma de verificação CRC32 de texto no navegador.',
                'meta_title'        => 'Calculadora CRC32 Online (IEEE 802.3) | Ciphers Online',
                'meta_description'  => 'Calculadora CRC32 grátis online. Somas CRC32 (IEEE 802.3) de 8 caracteres no navegador — Ethernet, ZIP, PNG, gzip.',
            ],
            'tr' => [
                'name'              => 'CRC32 Çevrimiçi Hesaplayıcı',
                'name_short'        => 'CRC32',
                'description'       => 'Herhangi bir metnin CRC32 (IEEE 802.3) sağlama toplamını tarayıcınızda hesaplayın. CRC32, Ethernet, ZIP, PNG, gzip ve birçok dosya formatında kullanılan kriptografik olmayan 32 bit sağlama toplamıdır.',
                'description_stort' => 'Tarayıcıda metnin CRC32 sağlama toplamını hesaplayın.',
                'meta_title'        => 'CRC32 Çevrimiçi Hesaplayıcı (IEEE 802.3) | Ciphers Online',
                'meta_description'  => 'Ücretsiz çevrimiçi CRC32 hesaplayıcı. 8 karakter CRC32 (IEEE 802.3) toplamları tarayıcıda — Ethernet, ZIP, PNG, gzip.',
            ],
        ];
    }
}
