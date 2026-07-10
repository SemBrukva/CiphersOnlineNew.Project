<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет книжный шифр (Book cipher) в категорию classical-ciphers
 * с базовым многоязычным содержимым (финальный контент прорабатывается отдельно).
 */
class SeedBookCipher extends Migration
{
    /**
     * Создаёт или обновляет запись инструмента, переводы и базовый контент.
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

        $now = date('Y-m-d H:i:s');
        $cipherId = $this->upsertCipher((int) $category['id'], $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertCipherTranslation($cipherId, $language, $translation, $now);
        }

        $this->seedContent($cipherId, $now);
    }

    /**
     * Удаляет запись инструмента и связанные сущности.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['book-cipher']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS . ' WHERE id = ?', [$cipherId]);
    }

    /**
     * Создаёт или обновляет запись инструмента.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['book-cipher']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'book-cipher', 'client', 110, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            [$categoryId, 'client', 110, 1, $now, $cipherId]
        );

        return $cipherId;
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
     * Заполняет блоки, примеры, FAQ и теги базовым содержимым (en + ru).
     */
    private function seedContent(int $cipherId, string $now): void
    {
        $book = 'the quick brown fox jumps over the lazy dog';

        $block = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $this->upsertBlockTranslation($block, 'en', 'How the Book Cipher works', '<p>A book cipher uses an agreed reference text — the "book" — as the key. Instead of a short keyword, both sides share a whole passage and replace each part of the message with a pointer to a location inside it.</p><p>This tool supports four addressing schemes: <strong>Word index</strong> (a number is the position of a word in the book), <strong>Beale</strong> (a number points to a word whose first letter spells the message), <strong>Line · word</strong> (coordinates such as 2.5) and <strong>Character index</strong> (a number is the position of a single character). To decode, paste the exact same reference text and the numbers.</p>', $now);
        $this->upsertBlockTranslation($block, 'ru', 'Как работает книжный шифр', '<p>Книжный шифр использует согласованный референсный текст — «книгу» — в качестве ключа. Вместо короткого кодового слова обе стороны делят целый отрывок и заменяют каждую часть сообщения ссылкой на позицию внутри него.</p><p>Инструмент поддерживает четыре схемы адресации: <strong>индекс слова</strong> (число — позиция слова в книге), <strong>схема Билла</strong> (число указывает на слово, чья первая буква складывает сообщение), <strong>строка · слово</strong> (координаты вида 2.5) и <strong>индекс символа</strong> (число — позиция отдельного символа). Для расшифровки вставьте тот же самый референсный текст и числа.</p>', $now);

        $schemeSettings = json_encode(['ciphers-book-scheme' => 'word-index'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $example1 = $this->upsertExample($cipherId, 10, 'encrypt', $now, $schemeSettings);
        $this->upsertExampleTranslation($example1, 'en', 'Encode with word index', 'quick fox', '', $book, 'Word-index scheme: each word becomes its position in the reference text.', $now);
        $this->upsertExampleTranslation($example1, 'ru', 'Зашифровать по индексу слова', 'quick fox', '', $book, 'Схема «индекс слова»: каждое слово превращается в свою позицию в референсном тексте.', $now);

        $example2 = $this->upsertExample($cipherId, 20, 'decrypt', $now, $schemeSettings);
        $this->upsertExampleTranslation($example2, 'en', 'Decode numbers to words', '2 4', '', $book, 'Reverse the word-index scheme: numbers map back to words 2 and 4 of the book.', $now);
        $this->upsertExampleTranslation($example2, 'ru', 'Расшифровать числа в слова', '2 4', '', $book, 'Обратная схема «индекс слова»: числа возвращаются в слова 2 и 4 книги.', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'What do I use as the key?', 'Any text can be the key, as long as both sides use the exact same copy — a book chapter, a poem, a song\'s lyrics or any pasted passage. The security comes from keeping the reference text secret; the numbers alone are useless without it.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Что использовать в качестве ключа?', 'Ключом может быть любой текст, если обе стороны используют его точную копию — глава книги, стихотворение, слова песни или любой вставленный отрывок. Стойкость обеспечивается секретностью референсного текста; сами по себе числа без него бесполезны.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'Why does encoding sometimes fail?', 'In the word-based schemes every word of your message must appear somewhere in the reference text. If a word is missing, the tool lists the uncovered words so you can extend the book or switch to the character-index scheme, which only needs the individual letters and spaces.', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Почему шифрование иногда не проходит?', 'В схемах по словам каждое слово сообщения должно встречаться в референсном тексте. Если слова нет, инструмент покажет список непокрытых слов — можно расширить книгу или переключиться на схему «индекс символа», которой нужны лишь отдельные буквы и пробелы.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Book cipher', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Книжный шифр', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'Beale cipher', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'Шифр Билла', $now);
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
    private function upsertExample(int $cipherId, int $sortOrder, string $direction, string $now, ?string $settings = null): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET direction = ?, delimiter = ?, settings = ?, published = 1, updated_at = ? WHERE id = ?',
                [$direction, '', $settings, $now, (int) $row['id']]
            );
            return (int) $row['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, direction, delimiter, settings, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $direction, '', $settings, $now, $now]
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
            'title' => $title,
            'input' => $input,
            'output' => $output,
            'key' => $key,
            'shift' => 0,
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
     * Возвращает переводы инструмента для всех поддерживаемых языков.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Book Cipher',
                'name_short'        => 'Book Cipher',
                'description'       => 'Encode and decode messages with a book cipher — use any reference text as the key. Supports word-index, Beale, line·word and character-index schemes.',
                'description_stort' => 'Book cipher with a reference text as the key and four addressing schemes.',
                'meta_title'        => 'Book Cipher Online | Ciphers Online',
                'meta_description'  => 'Encode and decode a book cipher online using any reference text as the key. Word index, Beale, line·word and character index schemes. Runs entirely in your browser.',
            ],
            'ru' => [
                'name'              => 'Книжный шифр',
                'name_short'        => 'Книжный шифр',
                'description'       => 'Шифруйте и расшифровывайте сообщения книжным шифром — ключом служит любой референсный текст. Поддерживаются схемы индекс слова, Билла, строка·слово и индекс символа.',
                'description_stort' => 'Книжный шифр с референсным текстом-ключом и четырьмя схемами адресации.',
                'meta_title'        => 'Книжный шифр онлайн | Ciphers Online',
                'meta_description'  => 'Шифруйте и расшифровывайте книжный шифр онлайн, используя любой референсный текст как ключ. Схемы индекс слова, Билла, строка·слово и индекс символа. Работает прямо в браузере.',
            ],
            'de' => [
                'name'              => 'Buchchiffre',
                'name_short'        => 'Buchchiffre',
                'description'       => 'Kodieren und dekodieren Sie Nachrichten mit einer Buchchiffre — jeder Referenztext dient als Schlüssel. Unterstützt Wortindex-, Beale-, Zeile·Wort- und Zeichenindex-Schemata.',
                'description_stort' => 'Buchchiffre mit einem Referenztext als Schlüssel und vier Adressierungsschemata.',
                'meta_title'        => 'Buchchiffre Online | Ciphers Online',
                'meta_description'  => 'Buchchiffre online kodieren und dekodieren, mit beliebigem Referenztext als Schlüssel. Wortindex-, Beale-, Zeile·Wort- und Zeichenindex-Schemata. Läuft vollständig im Browser.',
            ],
            'es' => [
                'name'              => 'Cifrado de libro',
                'name_short'        => 'Cifrado de libro',
                'description'       => 'Cifra y descifra mensajes con un cifrado de libro: usa cualquier texto de referencia como clave. Admite los esquemas de índice de palabra, Beale, línea·palabra e índice de carácter.',
                'description_stort' => 'Cifrado de libro con un texto de referencia como clave y cuatro esquemas de direccionamiento.',
                'meta_title'        => 'Cifrado de Libro Online | Ciphers Online',
                'meta_description'  => 'Cifra y descifra un cifrado de libro online usando cualquier texto de referencia como clave. Esquemas de índice de palabra, Beale, línea·palabra e índice de carácter. Funciona por completo en tu navegador.',
            ],
            'fr' => [
                'name'              => 'Chiffre du livre',
                'name_short'        => 'Chiffre du livre',
                'description'       => 'Chiffrez et déchiffrez des messages avec un chiffre du livre — n\'importe quel texte de référence sert de clé. Prend en charge les schémas index de mot, Beale, ligne·mot et index de caractère.',
                'description_stort' => 'Chiffre du livre avec un texte de référence comme clé et quatre schémas d\'adressage.',
                'meta_title'        => 'Chiffre du Livre en Ligne | Ciphers Online',
                'meta_description'  => 'Chiffrez et déchiffrez un chiffre du livre en ligne avec n\'importe quel texte de référence comme clé. Schémas index de mot, Beale, ligne·mot et index de caractère. Fonctionne entièrement dans votre navigateur.',
            ],
            'it' => [
                'name'              => 'Cifrario del libro',
                'name_short'        => 'Cifrario del libro',
                'description'       => 'Cifra e decifra messaggi con un cifrario del libro: usa qualsiasi testo di riferimento come chiave. Supporta gli schemi indice di parola, Beale, riga·parola e indice di carattere.',
                'description_stort' => 'Cifrario del libro con un testo di riferimento come chiave e quattro schemi di indirizzamento.',
                'meta_title'        => 'Cifrario del Libro Online | Ciphers Online',
                'meta_description'  => 'Cifra e decifra un cifrario del libro online usando qualsiasi testo di riferimento come chiave. Schemi indice di parola, Beale, riga·parola e indice di carattere. Funziona interamente nel tuo browser.',
            ],
            'pt' => [
                'name'              => 'Cifra do livro',
                'name_short'        => 'Cifra do livro',
                'description'       => 'Cifre e decifre mensagens com uma cifra do livro — use qualquer texto de referência como chave. Suporta os esquemas de índice de palavra, Beale, linha·palavra e índice de caractere.',
                'description_stort' => 'Cifra do livro com um texto de referência como chave e quatro esquemas de endereçamento.',
                'meta_title'        => 'Cifra do Livro Online | Ciphers Online',
                'meta_description'  => 'Cifre e decifre uma cifra do livro online usando qualquer texto de referência como chave. Esquemas de índice de palavra, Beale, linha·palavra e índice de caractere. Funciona inteiramente no seu navegador.',
            ],
            'tr' => [
                'name'              => 'Kitap Şifresi',
                'name_short'        => 'Kitap Şifresi',
                'description'       => 'Kitap şifresiyle mesajları şifreleyin ve çözün — herhangi bir referans metni anahtar olarak kullanın. Sözcük dizini, Beale, satır·sözcük ve karakter dizini şemalarını destekler.',
                'description_stort' => 'Referans metni anahtar olan ve dört adresleme şeması bulunan kitap şifresi.',
                'meta_title'        => 'Kitap Şifresi Çevrimiçi | Ciphers Online',
                'meta_description'  => 'Herhangi bir referans metni anahtar olarak kullanarak kitap şifresini çevrimiçi şifreleyin ve çözün. Sözcük dizini, Beale, satır·sözcük ve karakter dizini şemaları. Tamamen tarayıcınızda çalışır.',
            ],
        ];
    }
}
