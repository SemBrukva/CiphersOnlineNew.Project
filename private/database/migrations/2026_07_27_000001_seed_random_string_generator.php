<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет клиентский инструмент «Random String Generator» в категорию utilities.
 * Базовый inline-контент (en/ru); подробный контент прорабатывается отдельно.
 */
class SeedRandomStringGenerator extends Migration
{
    /**
     * Создаёт или обновляет инструмент, переводы карточки и базовый контент.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['utilities']
        );

        if ($category === false) {
            return;
        }

        $categoryId = (int) $category['id'];
        $now = date('Y-m-d H:i:s');

        $cipherId = $this->upsertCipher($categoryId, 'random-string', 30, $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }

        $this->upsertContent($cipherId, $this->content(), $now);
    }

    /**
     * Удаляет добавленный инструмент (контент уходит каскадом по FK).
     */
    public function down(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['utilities']
        );

        if ($category === false) {
            return;
        }

        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ?',
            [(int) $category['id'], 'random-string']
        );
    }

    /**
     * Создаёт или обновляет запись инструмента.
     */
    private function upsertCipher(int $categoryId, string $alias, int $sortOrder, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, $alias]
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, $alias, 'client', $sortOrder, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['client', $sortOrder, 1, $now, $cipherId]
        );

        return $cipherId;
    }

    /**
     * Создаёт или обновляет перевод карточки инструмента.
     *
     * @param array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string} $translation Данные перевода.
     */
    private function upsertTranslation(int $cipherId, string $language, array $translation, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS . ' WHERE app_id = ? AND language = ? LIMIT 1',
            [$cipherId, $language]
        );

        if ($existing === false) {
            $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS_TRANSLATIONS
                . ' (app_id, language, name, name_short, description, description_stort, meta_title, meta_description, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $cipherId,
                    $language,
                    $translation['name'],
                    $translation['name_short'],
                    $translation['description'],
                    $translation['description_stort'],
                    $translation['meta_title'],
                    $translation['meta_description'],
                    $now,
                    $now,
                ]
            );

            return;
        }

        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS_TRANSLATIONS
            . ' SET name = ?, name_short = ?, description = ?, description_stort = ?, meta_title = ?, meta_description = ?, updated_at = ? '
            . 'WHERE id = ?',
            [
                $translation['name'],
                $translation['name_short'],
                $translation['description'],
                $translation['description_stort'],
                $translation['meta_title'],
                $translation['meta_description'],
                $now,
                (int) $existing['id'],
            ]
        );
    }

    /**
     * Создаёт базовый контент страницы: блок, примеры-пресеты, FAQ, теги.
     *
     * @param array<string, mixed> $content Контент инструмента.
     */
    private function upsertContent(int $cipherId, array $content, string $now): void
    {
        $block = $this->upsertBlock($cipherId, 10, $now);
        foreach ($content['block'] as $language => $data) {
            $this->upsertBlockTranslation($block, $language, $data['title'], $data['text'], $now);
        }

        foreach ($content['examples'] as $example) {
            $exampleId = $this->upsertExample($cipherId, $example['sort'], $example['settings'], $now);
            foreach ($example['translations'] as $language => $data) {
                $this->upsertExampleTranslation($exampleId, $language, $data['title'], $data['input'], $data['output'], $data['description'], $now);
            }
        }

        foreach ($content['faq'] as $faq) {
            $faqId = $this->upsertFaq($cipherId, $faq['sort'], $now);
            foreach ($faq['translations'] as $language => $data) {
                $this->upsertFaqTranslation($faqId, $language, $data['question'], $data['answer'], $now);
            }
        }

        foreach ($content['tags'] as $tag) {
            $tagId = $this->upsertTag($cipherId, $tag['sort'], $now);
            foreach ($tag['translations'] as $language => $value) {
                $this->upsertTagTranslation($tagId, $language, $value, $now);
            }
        }
    }

    /**
     * Создаёт или обновляет блок контента по сортировке.
     */
    private function upsertBlock(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_BLOCKS . ' SET published = 1, updated_at = ? WHERE id = ?', [$now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод блока.
     */
    private function upsertBlockTranslation(int $blockId, string $language, string $title, string $text, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ? LIMIT 1',
            [$blockId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' SET title = ?, text = ?, updated_at = ? WHERE id = ?',
                [$title, $text, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' (block_id, language, title, text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$blockId, $language, $title, $text, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет пример-пресет по сортировке. `$settings` — JSON пресета
     * генератора (длина/наборы символов/произвольный алфавит).
     */
    private function upsertExample(int $cipherId, int $sortOrder, string $settings, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET published = 1, settings = ?, updated_at = ? WHERE id = ?', [$settings, $now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, settings, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?)',
            [$cipherId, $sortOrder, $settings, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(int $exampleId, string $language, string $title, string $input, string $output, string $description, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' SET title = ?, input = ?, output = ?, description = ?, updated_at = ? WHERE id = ?',
                [$title, $input, $output, $description, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' (example_id, language, title, input, output, description, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$exampleId, $language, $title, $input, $output, $description, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет FAQ по сортировке.
     */
    private function upsertFaq(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_FAQ . ' SET published = 1, show_in_category = 0, updated_at = ? WHERE id = ?', [$now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_FAQ . ' (app_id, sort_order, show_in_category, published, created_at, updated_at) VALUES (?, ?, 0, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод FAQ.
     */
    private function upsertFaqTranslation(int $faqId, string $language, string $question, string $answer, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' WHERE faq_id = ? AND language = ? LIMIT 1',
            [$faqId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' SET question = ?, answer = ?, updated_at = ? WHERE id = ?',
                [$question, $answer, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_FAQ_TRANSLATIONS . ' (faq_id, language, question, answer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$faqId, $language, $question, $answer, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет тег по сортировке.
     */
    private function upsertTag(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TAGS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_TAGS . ' SET published = 1, updated_at = ? WHERE id = ?', [$now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_TAGS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод тега.
     */
    private function upsertTagTranslation(int $tagId, string $language, string $tag, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' WHERE tag_id = ? AND language = ? LIMIT 1',
            [$tagId, $language]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' SET tag = ?, updated_at = ? WHERE id = ?',
                [$tag, $now, (int) $row['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_TAGS_TRANSLATIONS . ' (tag_id, language, tag, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$tagId, $language, $tag, $now, $now]
        );
    }

    /**
     * Возвращает переводы карточки инструмента на 8 языков.
     *
     * @return array<string, array<string, string>>
     */
    private function translations(): array
    {
        return [
            'en' => ['name' => 'Random String Generator', 'name_short' => 'Random String', 'description' => 'Generate random strings, tokens and IDs in your browser. Pick a length, combine lowercase, uppercase, digits and symbols, or supply your own custom alphabet (hex, Base58 and more). Generate in bulk, exclude look-alike characters and export the list one per line, comma- or space-separated. Everything runs locally — nothing is sent to a server.', 'description_stort' => 'Generate random strings, tokens and IDs online, in your browser.', 'meta_title' => 'Random String Generator — Tokens, IDs & Custom Alphabets | Ciphers Online', 'meta_description' => 'Free online random string generator. Create random strings, API tokens and IDs from any character set or custom alphabet, in bulk, locally in your browser. Nothing is sent to a server.'],
            'ru' => ['name' => 'Генератор случайных строк', 'name_short' => 'Случайная строка', 'description' => 'Создавайте случайные строки, токены и идентификаторы прямо в браузере. Задавайте длину, комбинируйте строчные, прописные, цифры и спецсимволы или укажите свой алфавит (hex, Base58 и другие). Генерация пачками, исключение похожих символов и экспорт списка по строке, через запятую или пробел. Всё работает локально — ничего не отправляется на сервер.', 'description_stort' => 'Создавайте случайные строки, токены и ID онлайн, в браузере.', 'meta_title' => 'Генератор случайных строк — токены, ID и свой алфавит | Ciphers Online', 'meta_description' => 'Бесплатный онлайн-генератор случайных строк. Создавайте случайные строки, API-токены и ID из любого набора символов или своего алфавита, пачками, локально в браузере. Ничего не отправляется на сервер.'],
            'de' => ['name' => 'Zufallsstring-Generator', 'name_short' => 'Zufallsstring', 'description' => 'Erzeugen Sie zufällige Zeichenketten, Tokens und IDs im Browser. Wählen Sie eine Länge, kombinieren Sie Klein- und Großbuchstaben, Ziffern und Symbole oder geben Sie ein eigenes Alphabet an (Hex, Base58 und mehr). Erzeugen Sie mehrere auf einmal, schließen Sie ähnlich aussehende Zeichen aus und exportieren Sie die Liste zeilenweise, komma- oder leerzeichengetrennt. Alles läuft lokal — nichts wird an einen Server gesendet.', 'description_stort' => 'Erzeugen Sie zufällige Zeichenketten, Tokens und IDs online im Browser.', 'meta_title' => 'Zufallsstring-Generator — Tokens, IDs & eigene Alphabete | Ciphers Online', 'meta_description' => 'Kostenloser Online-Zufallsstring-Generator. Erstellen Sie Zufallszeichenketten, API-Tokens und IDs aus jedem Zeichensatz oder eigenen Alphabet, in großer Zahl, lokal im Browser.'],
            'es' => ['name' => 'Generador de cadenas aleatorias', 'name_short' => 'Cadena aleatoria', 'description' => 'Genera cadenas aleatorias, tokens e ID en tu navegador. Elige una longitud, combina minúsculas, mayúsculas, dígitos y símbolos, o indica tu propio alfabeto (hex, Base58 y más). Genera en lote, excluye caracteres parecidos y exporta la lista una por línea, separada por comas o espacios. Todo se ejecuta localmente: nada se envía a un servidor.', 'description_stort' => 'Genera cadenas aleatorias, tokens e ID online, en tu navegador.', 'meta_title' => 'Generador de cadenas aleatorias — tokens, ID y alfabetos | Ciphers Online', 'meta_description' => 'Generador de cadenas aleatorias online gratis. Crea cadenas aleatorias, tokens de API e ID desde cualquier conjunto de caracteres o alfabeto propio, en lote, localmente en tu navegador.'],
            'fr' => ['name' => 'Générateur de chaînes aléatoires', 'name_short' => 'Chaîne aléatoire', 'description' => 'Générez des chaînes aléatoires, des jetons et des ID dans votre navigateur. Choisissez une longueur, combinez minuscules, majuscules, chiffres et symboles, ou fournissez votre propre alphabet (hex, Base58 et plus). Générez en lot, excluez les caractères ressemblants et exportez la liste une par ligne, séparée par des virgules ou des espaces. Tout s\'exécute localement — rien n\'est envoyé à un serveur.', 'description_stort' => 'Générez des chaînes aléatoires, des jetons et des ID en ligne, dans le navigateur.', 'meta_title' => 'Générateur de chaînes aléatoires — jetons, ID et alphabets | Ciphers Online', 'meta_description' => 'Générateur de chaînes aléatoires en ligne gratuit. Créez des chaînes aléatoires, des jetons d\'API et des ID à partir de n\'importe quel jeu de caractères ou alphabet personnalisé, en lot, localement dans votre navigateur.'],
            'it' => ['name' => 'Generatore di stringhe casuali', 'name_short' => 'Stringa casuale', 'description' => 'Genera stringhe casuali, token e ID nel browser. Scegli una lunghezza, combina minuscole, maiuscole, cifre e simboli oppure fornisci un tuo alfabeto personalizzato (hex, Base58 e altro). Genera in blocco, escludi i caratteri simili ed esporta l\'elenco uno per riga, separato da virgole o spazi. Tutto viene eseguito localmente: nulla viene inviato a un server.', 'description_stort' => 'Genera stringhe casuali, token e ID online, nel browser.', 'meta_title' => 'Generatore di stringhe casuali — token, ID e alfabeti | Ciphers Online', 'meta_description' => 'Generatore di stringhe casuali online gratuito. Crea stringhe casuali, token API e ID da qualsiasi set di caratteri o alfabeto personalizzato, in blocco, localmente nel browser.'],
            'pt' => ['name' => 'Gerador de strings aleatórias', 'name_short' => 'String aleatória', 'description' => 'Gere strings aleatórias, tokens e IDs no navegador. Escolha um comprimento, combine minúsculas, maiúsculas, dígitos e símbolos ou forneça o seu próprio alfabeto (hex, Base58 e mais). Gere em lote, exclua caracteres parecidos e exporte a lista uma por linha, separada por vírgulas ou espaços. Tudo roda localmente — nada é enviado a um servidor.', 'description_stort' => 'Gere strings aleatórias, tokens e IDs online, no navegador.', 'meta_title' => 'Gerador de strings aleatórias — tokens, IDs e alfabetos | Ciphers Online', 'meta_description' => 'Gerador de strings aleatórias online grátis. Crie strings aleatórias, tokens de API e IDs a partir de qualquer conjunto de caracteres ou alfabeto personalizado, em lote, localmente no navegador.'],
            'tr' => ['name' => 'Rastgele Dize Üreteci', 'name_short' => 'Rastgele Dize', 'description' => 'Tarayıcınızda rastgele dizeler, tokenlar ve kimlikler üretin. Bir uzunluk seçin; küçük harfleri, büyük harfleri, rakamları ve sembolleri birleştirin veya kendi özel alfabenizi girin (hex, Base58 ve daha fazlası). Toplu üretin, benzer görünen karakterleri hariç tutun ve listeyi her satırda bir, virgül veya boşlukla ayrılmış olarak dışa aktarın. Her şey yerel çalışır — hiçbir şey sunucuya gönderilmez.', 'description_stort' => 'Rastgele dizeleri, tokenları ve kimlikleri tarayıcıda çevrimiçi üretin.', 'meta_title' => 'Rastgele Dize Üreteci — tokenlar, kimlikler ve özel alfabeler | Ciphers Online', 'meta_description' => 'Ücretsiz çevrimiçi rastgele dize üreteci. Herhangi bir karakter kümesinden veya özel alfabeden rastgele dizeler, API tokenları ve kimlikler üretin, toplu ve yerel olarak tarayıcınızda.'],
        ];
    }

    /**
     * Возвращает базовый контент инструмента (en/ru): блок, примеры-пресеты, FAQ, теги.
     *
     * @return array<string, mixed>
     */
    private function content(): array
    {
        return [
            'examples' => [
                ['sort' => 10, 'settings' => '{"length":32,"lower":true,"upper":true,"digits":true,"symbols":false}', 'translations' => [
                    'en' => ['title' => 'Alphanumeric string (32)', 'input' => '', 'output' => '', 'description' => 'A 32-character mix of letters and digits — a safe default for tokens, slugs and identifiers.'],
                    'ru' => ['title' => 'Буквенно-цифровая строка (32)', 'input' => '', 'output' => '', 'description' => 'Смесь из 32 букв и цифр — надёжный вариант по умолчанию для токенов, слагов и идентификаторов.'],
                ]],
                ['sort' => 20, 'settings' => '{"length":32,"lower":false,"upper":false,"digits":false,"symbols":false,"custom":"0123456789abcdef"}', 'translations' => [
                    'en' => ['title' => 'Hex string (32)', 'input' => '', 'output' => '', 'description' => 'A 32-character lowercase hexadecimal string built from a custom alphabet — handy for keys and colours.'],
                    'ru' => ['title' => 'Hex-строка (32)', 'input' => '', 'output' => '', 'description' => 'Строка из 32 строчных шестнадцатеричных символов из своего алфавита — удобно для ключей и цветов.'],
                ]],
                ['sort' => 30, 'settings' => '{"length":20,"lower":true,"upper":true,"digits":true,"symbols":true}', 'translations' => [
                    'en' => ['title' => 'Strong 20 with symbols', 'input' => '', 'output' => '', 'description' => 'Twenty characters from every set including symbols — maximum entropy per character for secrets and salts.'],
                    'ru' => ['title' => 'Стойкая 20 со спецсимволами', 'input' => '', 'output' => '', 'description' => 'Двадцать символов из всех наборов, включая спецсимволы — максимум энтропии на символ для секретов и солей.'],
                ]],
            ],
            'block' => [
                'en' => ['title' => 'What is a random string generator?', 'text' => '<p>A random string generator produces unpredictable sequences of characters drawn from a set you choose. Unlike a password generator, it is not limited to human-friendly output: you can build strings from any alphabet — lowercase, uppercase, digits, symbols, hexadecimal, Base58 or a completely custom set of characters. That makes it the go-to utility for <strong>API keys, access tokens, session IDs, database salts, test fixtures and mock data</strong>.</p><p>Two properties determine how hard a random string is to guess: the <strong>length</strong> and the <strong>size of the character set</strong>. Together they define the <em>entropy</em> in bits — roughly <code>length × log₂(alphabet size)</code>. A 32-character hexadecimal string carries 128 bits of entropy, the same as a version-4 UUID. Every value here is produced with the browser\'s cryptographically secure random generator (<code>crypto.getRandomValues</code>) using rejection sampling to avoid bias, and never leaves your device.</p>'],
                'ru' => ['title' => 'Что такое генератор случайных строк?', 'text' => '<p>Генератор случайных строк создаёт непредсказуемые последовательности символов из выбранного вами набора. В отличие от генератора паролей, он не ограничен «человеко-читаемым» выводом: строку можно собрать из любого алфавита — строчные, прописные, цифры, спецсимволы, шестнадцатеричные, Base58 или полностью произвольный набор символов. Поэтому это основная утилита для <strong>API-ключей, токенов доступа, идентификаторов сессий, солей в БД, тестовых данных и заглушек</strong>.</p><p>Насколько трудно угадать случайную строку, определяют два свойства: <strong>длина</strong> и <strong>размер набора символов</strong>. Вместе они задают <em>энтропию</em> в битах — примерно <code>длина × log₂(размер алфавита)</code>. Строка из 32 шестнадцатеричных символов несёт 128 бит энтропии — столько же, сколько UUID версии 4. Каждое значение здесь создаётся криптографически стойким генератором браузера (<code>crypto.getRandomValues</code>) с rejection sampling, исключающим смещение, и никогда не покидает ваше устройство.</p>'],
            ],
            'faq' => [
                ['sort' => 10, 'translations' => [
                    'en' => ['question' => 'How is this different from the password generator?', 'answer' => 'The password generator is tuned for human-usable secrets and shows a strength meter and crack-time estimate. The random string generator is a developer utility: it supports any custom alphabet (hex, Base58, your own characters), bulk output and list formatting (one per line, comma- or space-separated), which is ideal for tokens, IDs and test data rather than login passwords.'],
                    'ru' => ['question' => 'Чем это отличается от генератора паролей?', 'answer' => 'Генератор паролей заточен под «человеческие» секреты и показывает индикатор надёжности и оценку времени взлома. Генератор случайных строк — это утилита для разработчиков: он поддерживает любой произвольный алфавит (hex, Base58, свои символы), пакетную генерацию и форматирование списка (по строке, через запятую или пробел), что идеально для токенов, ID и тестовых данных, а не для паролей входа.'],
                ]],
                ['sort' => 20, 'translations' => [
                    'en' => ['question' => 'Are the generated strings safe to use as secrets?', 'answer' => 'Yes. Every string is generated locally in your browser using the cryptographically secure Web Crypto API (crypto.getRandomValues) with rejection sampling to avoid modulo bias. Nothing is transmitted to a server, logged or stored. For secrets, prefer a length and character set that give at least 128 bits of entropy — for example a 32-character hexadecimal or a 22-character Base58 string.'],
                    'ru' => ['question' => 'Безопасно ли использовать эти строки как секреты?', 'answer' => 'Да. Каждая строка создаётся локально в браузере через криптографически стойкий Web Crypto API (crypto.getRandomValues) с rejection sampling, исключающим модуло-смещение. Ничего не передаётся на сервер, не логируется и не сохраняется. Для секретов выбирайте длину и набор символов, дающие минимум 128 бит энтропии — например, 32 шестнадцатеричных символа или строку Base58 из 22 символов.'],
                ]],
            ],
            'tags' => [
                ['sort' => 10, 'translations' => ['en' => 'random string', 'ru' => 'случайная строка']],
                ['sort' => 20, 'translations' => ['en' => 'token generator', 'ru' => 'генератор токенов']],
            ],
        ];
    }
}
