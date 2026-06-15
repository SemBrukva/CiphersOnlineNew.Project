<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет XOR-шифр в категорию классических шифров.
 */
class SeedXorCipher extends Migration
{
    /**
     * Создаёт или обновляет запись шифра, переводы и контент.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['classical-ciphers']
        );

        if ($category === false) {
            return;
        }

        $now      = date('Y-m-d H:i:s');
        $cipherId = $this->upsertCipher((int) $category['id'], $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertCipherTranslation($cipherId, $language, $translation, $now);
        }

        $this->seedContent($cipherId, $now);
    }

    /**
     * Удаляет запись XOR-шифра и связанные сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['xor-cipher']
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
     * Создаёт или обновляет запись XOR-шифра.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'xor-cipher']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'xor-cipher', 'api', 16, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 16, 1, $now, $cipherId]
        );

        return $cipherId;
    }

    /**
     * Создаёт или обновляет перевод шифра.
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
        $block = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How the XOR Cipher works', '<p>The XOR cipher (also called the exclusive-or cipher) is a symmetric encryption algorithm that operates on individual bytes. Each byte of the plaintext is combined with the corresponding byte of a repeating key using the XOR (exclusive or) bitwise operation. Because XOR is its own inverse, the same operation with the same key both encrypts and decrypts.</p><p>Encryption produces a sequence of bytes that is displayed as a hexadecimal string. To decrypt, paste the hex ciphertext into the input field and use the same key — the cipher will reconstruct the original text.</p><p>If the key is shorter than the message, it repeats cyclically. A longer key increases security; if the key is as long as the message and used only once, the result is a one-time pad (Vernam cipher), which is provably unbreakable.</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает XOR-шифр', '<p>XOR-шифр (шифр исключающего ИЛИ) — симметричный алгоритм шифрования, работающий на уровне отдельных байт. Каждый байт открытого текста объединяется с соответствующим байтом циклического ключа с помощью побитовой операции XOR. Так как XOR является инволюцией (самообратной операцией), та же операция с тем же ключом одновременно шифрует и дешифрует.</p><p>При шифровании получается последовательность байт, которая отображается в виде шестнадцатеричной строки. Для дешифрования вставьте hex-шифртекст в поле ввода и используйте тот же ключ — шифр восстановит исходный текст.</p><p>Если ключ короче сообщения, он повторяется циклически. Более длинный ключ повышает криптостойкость; если ключ равен длине сообщения и используется только один раз — это одноразовый блокнот (шифр Вернама), доказуемо невзламываемый.</p>', $now);

        // Примеры
        // HELLO XOR KEY: H(72)^K(75)=3, E(69)^E(69)=0, L(76)^Y(89)=21, L(76)^K(75)=7, O(79)^E(69)=10 → 030015070A
        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now);
        $this->upsertExampleTranslation($example1, 'en', 'Encrypt HELLO', 'HELLO', '030015070A', 'KEY', 'H(72)^K(75)=03, E(69)^E(69)=00, L(76)^Y(89)=15, L(76)^K(75)=07, O(79)^E(69)=0A.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Шифрование HELLO', 'HELLO', '030015070A', 'KEY', 'H(72)^K(75)=03, E(69)^E(69)=00, L(76)^Y(89)=15, L(76)^K(75)=07, O(79)^E(69)=0A.', $now);

        // ATTACK AT DAWN XOR SECRET
        // A(65)^S(83)=18=0x12, T(84)^E(69)=17=0x11, T(84)^C(67)=23=0x17, A(65)^R(82)=19=0x13,
        // C(67)^E(69)=6=0x06, K(75)^T(84)=31=0x1F, ' '(32)^S(83)=115=0x73, A(65)^E(69)=4=0x04,
        // T(84)^C(67)=23=0x17, ' '(32)^R(82)=114=0x72, D(68)^E(69)=1=0x01, A(65)^T(84)=21=0x15,
        // W(87)^S(83)=4=0x04, N(78)^E(69)=11=0x0B
        $example2 = $this->upsertExample($cipherId, 20, 'encrypt', $now);
        $this->upsertExampleTranslation($example2, 'en', 'Encrypt ATTACK AT DAWN', 'ATTACK AT DAWN', '12111713061F730417720115040B', 'SECRET', 'Each letter XOR-ed with the cycling key SECRET. The space character is included in the XOR operation.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Шифрование ATTACK AT DAWN', 'ATTACK AT DAWN', '12111713061F730417720115040B', 'SECRET', 'Каждая буква XOR-ится с циклическим ключом SECRET. Пробел тоже участвует в операции XOR.', $now);

        // Decrypt: 030015070A XOR KEY → HELLO
        $example3 = $this->upsertExample($cipherId, 30, 'decrypt', $now);
        $this->upsertExampleTranslation($example3, 'en', 'Decrypt hex ciphertext', '030015070A', 'HELLO', 'KEY', 'Paste the hex string and use the same key — XOR restores the original text.', $now);
        $this->upsertExampleTranslation($example3, 'ru', 'Дешифрование hex-шифртекста', '030015070A', 'HELLO', 'KEY', 'Вставьте hex-строку и используйте тот же ключ — XOR восстанавливает исходный текст.', $now);

        // FAQ
        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'Why does the XOR cipher output hex?', 'XOR operates on raw bytes, and the result can contain any byte value including non-printable characters and null bytes. Hexadecimal encoding provides a safe, readable representation of arbitrary binary data. When decrypting, paste the hex ciphertext — the tool converts it back to bytes, applies XOR, and returns the original text.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Почему XOR-шифр выводит результат в hex?', 'XOR работает с сырыми байтами, и результат может содержать любое байтовое значение, включая непечатаемые символы и нулевые байты. Шестнадцатеричное кодирование обеспечивает безопасное и читаемое представление произвольных двоичных данных. При дешифровании вставьте hex-шифртекст — инструмент преобразует его обратно в байты, применит XOR и вернёт исходный текст.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'What is the difference between XOR cipher and Vernam cipher?', 'Both ciphers apply XOR byte-by-byte with a key. The key difference is in key management: the Vernam cipher (one-time pad) requires a truly random key that is at least as long as the message and never reused — making it theoretically unbreakable. The XOR cipher typically uses a shorter, repeating key for convenience, which makes it vulnerable to statistical attacks when the key is short.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Чем XOR-шифр отличается от шифра Вернама?', 'Оба шифра применяют XOR побайтово с ключом. Ключевое различие — в управлении ключом: шифр Вернама (одноразовый блокнот) требует истинно случайного ключа не короче сообщения, который никогда не переиспользуется — это делает его теоретически невзламываемым. XOR-шифр, как правило, использует короткий повторяющийся ключ для удобства, что делает его уязвимым для статистических атак при коротком ключе.', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'How do I decrypt a message encrypted with the XOR cipher?', 'Because XOR is self-inverse (A XOR B XOR B = A), decryption is identical to encryption: select the Decode tab, paste the hex ciphertext into the input field, enter the same key, and click Run. The tool performs the same XOR operation on the hex-decoded bytes and returns the original plaintext.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Как дешифровать сообщение, зашифрованное XOR-шифром?', 'Поскольку XOR является инволюцией (A XOR B XOR B = A), дешифрование идентично шифрованию: перейдите на вкладку «Декодировать», вставьте hex-шифртекст в поле ввода, введите тот же ключ и нажмите «Выполнить». Инструмент применяет ту же операцию XOR к байтам, декодированным из hex, и возвращает исходный открытый текст.', $now);

        // Tags
        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Symmetric', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Симметричный', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Bitwise', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Побитовый', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'XOR', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'XOR', $now);
    }

    /**
     * Создаёт или обновляет родительскую запись контентной секции.
     *
     * @param array<string, int> $extra
     */
    private function upsertParent(string $table, string $foreignKey, int $cipherId, int $sortOrder, string $now, array $extra = []): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $assignments = ['published = 1', 'updated_at = ?'];
            $values      = [$now];
            foreach ($extra as $field => $value) {
                $assignments[] = $field . ' = ?';
                $values[]      = $value;
            }
            $values[] = (int) $row['id'];
            $this->db->execute('UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = ?', $values);

            return (int) $row['id'];
        }

        $columns      = [$foreignKey, 'sort_order', 'published', 'created_at', 'updated_at', ...array_keys($extra)];
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

        $columns      = array_map(static fn (string $field): string => '`' . $field . '`', [$foreignKey, 'language', ...array_keys($data), 'created_at', 'updated_at']);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            [$parentId, $language, ...array_values($data), $now, $now]
        );
    }

    /**
     * Возвращает переводы для XOR-шифра.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'             => 'XOR Cipher',
                'name_short'       => 'XOR',
                'description'      => 'Encrypt and decrypt text with the XOR (exclusive or) cipher. Enter any key — the cipher applies XOR byte-by-byte with the repeating key and outputs the result as hex.',
                'description_stort' => 'Symmetric byte-level cipher using XOR with a repeating key.',
                'meta_title'       => 'XOR Cipher Online | Ciphers Online',
                'meta_description' => 'Encrypt and decrypt text with the XOR cipher online. Enter your key, encode any message to hex, and decode it back with the same key.',
            ],
            'ru' => [
                'name'             => 'XOR-шифр',
                'name_short'       => 'XOR',
                'description'      => 'Зашифруйте и расшифруйте текст с помощью XOR-шифра (исключающее ИЛИ). Введите любой ключ — шифр применяет XOR побайтово с циклическим ключом и выводит результат в виде hex.',
                'description_stort' => 'Симметричный побайтовый шифр на основе XOR с циклическим ключом.',
                'meta_title'       => 'XOR-шифр онлайн | Ciphers Online',
                'meta_description' => 'Зашифруйте и расшифруйте текст XOR-шифром онлайн. Введите ключ, закодируйте сообщение в hex и декодируйте обратно тем же ключом.',
            ],
            'de' => [
                'name'             => 'XOR-Chiffre',
                'name_short'       => 'XOR',
                'description'      => 'Text mit der XOR-Chiffre (exklusives Oder) ver- und entschlüsseln. Schlüssel eingeben — die Chiffre wendet XOR byteweise mit dem zyklischen Schlüssel an und gibt das Ergebnis als Hex aus.',
                'description_stort' => 'Symmetrische byteweise Chiffre mit XOR und zyklischem Schlüssel.',
                'meta_title'       => 'XOR-Chiffre Online | Ciphers Online',
                'meta_description' => 'Text mit der XOR-Chiffre online ver- und entschlüsseln. Schlüssel eingeben, Nachricht als Hex kodieren und mit demselben Schlüssel dekodieren.',
            ],
            'es' => [
                'name'             => 'Cifrado XOR',
                'name_short'       => 'XOR',
                'description'      => 'Cifra y descifra texto con el cifrado XOR (o exclusivo). Introduce cualquier clave — el cifrado aplica XOR byte a byte con la clave repetida y muestra el resultado en hex.',
                'description_stort' => 'Cifrado simétrico byte a byte usando XOR con clave repetida.',
                'meta_title'       => 'Cifrado XOR Online | Ciphers Online',
                'meta_description' => 'Cifra y descifra texto con el cifrado XOR online. Introduce tu clave, codifica cualquier mensaje en hex y decodifícalo con la misma clave.',
            ],
            'fr' => [
                'name'             => 'Chiffre XOR',
                'name_short'       => 'XOR',
                'description'      => 'Chiffrez et déchiffrez du texte avec le chiffre XOR (ou exclusif). Saisissez n\'importe quelle clé — le chiffre applique XOR octet par octet avec la clé répétée et affiche le résultat en hexadécimal.',
                'description_stort' => 'Chiffre symétrique octet par octet utilisant XOR avec une clé répétée.',
                'meta_title'       => 'Chiffre XOR en ligne | Ciphers Online',
                'meta_description' => 'Chiffrez et déchiffrez du texte avec le chiffre XOR en ligne. Entrez votre clé, encodez n\'importe quel message en hex et décodez-le avec la même clé.',
            ],
            'it' => [
                'name'             => 'Cifrario XOR',
                'name_short'       => 'XOR',
                'description'      => 'Cifra e decifra testo con il cifrario XOR (or esclusivo). Inserisci qualsiasi chiave — il cifrario applica XOR byte per byte con la chiave ripetuta e restituisce il risultato in hex.',
                'description_stort' => 'Cifrario simmetrico byte per byte che usa XOR con chiave ripetuta.',
                'meta_title'       => 'Cifrario XOR Online | Ciphers Online',
                'meta_description' => 'Cifra e decifra testo con il cifrario XOR online. Inserisci la tua chiave, codifica qualsiasi messaggio in hex e decodificalo con la stessa chiave.',
            ],
            'pt' => [
                'name'             => 'Cifra XOR',
                'name_short'       => 'XOR',
                'description'      => 'Cifre e decifre texto com a cifra XOR (ou exclusivo). Insira qualquer chave — a cifra aplica XOR byte a byte com a chave repetida e exibe o resultado em hex.',
                'description_stort' => 'Cifra simétrica byte a byte usando XOR com chave repetida.',
                'meta_title'       => 'Cifra XOR Online | Ciphers Online',
                'meta_description' => 'Cifre e decifre texto com a cifra XOR online. Insira sua chave, codifique qualquer mensagem em hex e decodifique com a mesma chave.',
            ],
            'tr' => [
                'name'             => 'XOR Şifresi',
                'name_short'       => 'XOR',
                'description'      => 'XOR (özel veya) şifresiyle metin şifreleyin ve çözün. Herhangi bir anahtar girin — şifre, döngüsel anahtarla bayt bayt XOR uygular ve sonucu hex olarak gösterir.',
                'description_stort' => 'Döngüsel anahtarla XOR kullanan simetrik bayt düzeyi şifresi.',
                'meta_title'       => 'XOR Şifresi Çevrimiçi | Ciphers Online',
                'meta_description' => 'XOR şifresiyle metni çevrimiçi şifreleyin ve çözün. Anahtarınızı girin, herhangi bir mesajı hex olarak kodlayın ve aynı anahtarla çözün.',
            ],
        ];
    }
}
