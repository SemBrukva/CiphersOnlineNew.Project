<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет клиентский инструмент Punycode (RFC 3492 / IDNA) в категорию encoding.
 * Базовый inline-контент (en/ru); подробный контент прорабатывается отдельно.
 */
class SeedPunycode extends Migration
{
    /**
     * Создаёт или обновляет инструмент, переводы карточки и базовый контент.
     */
    public function up(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['encoding']
        );

        if ($category === false) {
            return;
        }

        $categoryId = (int) $category['id'];
        $now = date('Y-m-d H:i:s');

        $cipherId = $this->upsertCipher($categoryId, 'punycode', 120, $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }

        $this->upsertContent($cipherId, $this->content(), $now);
    }

    /**
     * Удаляет добавленный инструмент.
     */
    public function down(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['encoding']
        );

        if ($category === false) {
            return;
        }

        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ?',
            [(int) $category['id'], 'punycode']
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
     * Создаёт базовый контент страницы: блок, два примера, два FAQ, два тега.
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
            $exampleId = $this->upsertExample($cipherId, $example['sort'], $example['direction'] ?? '', $example['settings'] ?? null, $now);
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
     * Создаёт или обновляет пример по сортировке. `$direction` — 'encrypt' / 'decrypt';
     * `$settings` — JSON настроек инструмента (выбранный вариант) либо null.
     */
    private function upsertExample(int $cipherId, int $sortOrder, string $direction, ?string $settings, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute('UPDATE ' . Tables::CIPHERS_EXAMPLES . ' SET published = 1, direction = ?, settings = ?, updated_at = ? WHERE id = ?', [$direction, $settings, $now, $id]);
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES . ' (app_id, sort_order, published, direction, settings, created_at, updated_at) VALUES (?, ?, 1, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $direction, $settings, $now, $now]
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
     * Возвращает переводы карточки инструмента на 8 языках.
     *
     * @return array<string, array<string, string>>
     */
    private function translations(): array
    {
        return [
            'en' => ['name' => 'Punycode Converter (IDN)', 'name_short' => 'Punycode', 'description' => 'Convert internationalized domain names to and from Punycode (RFC 3492) in your browser. Domain mode adds the xn-- prefix per label; raw mode shows the pure bootstring. Data is processed locally.', 'description_stort' => 'Convert IDN domains to and from Punycode (xn--), RFC 3492.', 'meta_title' => 'Punycode Converter (IDN to ASCII) | Ciphers Online', 'meta_description' => 'Convert internationalized domain names to Punycode and back online. IDN xn-- domain mode and raw RFC 3492 mode, UTF-8 support, all in your browser.'],
            'ru' => ['name' => 'Punycode: конвертер (IDN)', 'name_short' => 'Punycode', 'description' => 'Преобразование интернационализированных доменных имён в Punycode (RFC 3492) и обратно прямо в браузере. Режим домена добавляет префикс xn-- к каждой метке; сырой режим показывает чистый bootstring. Данные обрабатываются локально.', 'description_stort' => 'Преобразование IDN-доменов в Punycode (xn--) и обратно, RFC 3492.', 'meta_title' => 'Punycode: конвертер (IDN в ASCII) | Ciphers Online', 'meta_description' => 'Преобразуйте интернационализированные доменные имена в Punycode и обратно онлайн. Режим доменов xn-- и сырой режим RFC 3492, поддержка UTF-8, прямо в браузере.'],
            'de' => ['name' => 'Punycode-Konverter (IDN)', 'name_short' => 'Punycode', 'description' => 'Internationalisierte Domainnamen im Browser in Punycode (RFC 3492) und zurück umwandeln. Der Domain-Modus fügt pro Label das Präfix xn-- hinzu; der Roh-Modus zeigt den reinen Bootstring. Die Daten werden lokal verarbeitet.', 'description_stort' => 'IDN-Domains in Punycode (xn--) und zurück umwandeln, RFC 3492.', 'meta_title' => 'Punycode-Konverter (IDN zu ASCII) | Ciphers Online', 'meta_description' => 'Internationalisierte Domainnamen online in Punycode und zurück umwandeln. IDN-xn---Modus und roher RFC-3492-Modus, UTF-8, direkt im Browser.'],
            'es' => ['name' => 'Conversor Punycode (IDN)', 'name_short' => 'Punycode', 'description' => 'Convierte nombres de dominio internacionalizados a Punycode (RFC 3492) y viceversa en tu navegador. El modo dominio añade el prefijo xn-- por etiqueta; el modo sin procesar muestra el bootstring puro. Los datos se procesan localmente.', 'description_stort' => 'Convierte dominios IDN a Punycode (xn--) y viceversa, RFC 3492.', 'meta_title' => 'Conversor Punycode (IDN a ASCII) | Ciphers Online', 'meta_description' => 'Convierte nombres de dominio internacionalizados a Punycode y viceversa online. Modo dominio xn-- y modo sin procesar RFC 3492, soporte UTF-8, en tu navegador.'],
            'fr' => ['name' => 'Convertisseur Punycode (IDN)', 'name_short' => 'Punycode', 'description' => 'Convertissez les noms de domaine internationalisés en Punycode (RFC 3492) et inversement dans votre navigateur. Le mode domaine ajoute le préfixe xn-- par étiquette ; le mode brut affiche le bootstring pur. Les données sont traitées localement.', 'description_stort' => 'Convertissez les domaines IDN en Punycode (xn--) et inversement, RFC 3492.', 'meta_title' => 'Convertisseur Punycode (IDN vers ASCII) | Ciphers Online', 'meta_description' => 'Convertissez les noms de domaine internationalisés en Punycode et inversement en ligne. Mode domaine xn-- et mode brut RFC 3492, prise en charge UTF-8, dans votre navigateur.'],
            'it' => ['name' => 'Convertitore Punycode (IDN)', 'name_short' => 'Punycode', 'description' => 'Converti i nomi di dominio internazionalizzati in Punycode (RFC 3492) e viceversa nel browser. La modalità dominio aggiunge il prefisso xn-- per etichetta; la modalità grezza mostra il bootstring puro. I dati sono elaborati localmente.', 'description_stort' => 'Converti i domini IDN in Punycode (xn--) e viceversa, RFC 3492.', 'meta_title' => 'Convertitore Punycode (IDN in ASCII) | Ciphers Online', 'meta_description' => 'Converti i nomi di dominio internazionalizzati in Punycode e viceversa online. Modalità dominio xn-- e modalità grezza RFC 3492, supporto UTF-8, nel browser.'],
            'pt' => ['name' => 'Conversor Punycode (IDN)', 'name_short' => 'Punycode', 'description' => 'Converta nomes de domínio internacionalizados para Punycode (RFC 3492) e vice-versa no navegador. O modo domínio adiciona o prefixo xn-- por rótulo; o modo bruto mostra o bootstring puro. Os dados são processados localmente.', 'description_stort' => 'Converta domínios IDN para Punycode (xn--) e vice-versa, RFC 3492.', 'meta_title' => 'Conversor Punycode (IDN para ASCII) | Ciphers Online', 'meta_description' => 'Converta nomes de domínio internacionalizados para Punycode e vice-versa online. Modo domínio xn-- e modo bruto RFC 3492, suporte UTF-8, no navegador.'],
            'tr' => ['name' => 'Punycode Dönüştürücü (IDN)', 'name_short' => 'Punycode', 'description' => 'Uluslararasılaştırılmış alan adlarını tarayıcınızda Punycode’a (RFC 3492) ve tersine dönüştürün. Alan adı modu her etikete xn-- ön ekini ekler; ham mod saf bootstring’i gösterir. Veriler yerel olarak işlenir.', 'description_stort' => 'IDN alan adlarını Punycode’a (xn--) ve tersine dönüştürün, RFC 3492.', 'meta_title' => 'Punycode Dönüştürücü (IDN’den ASCII’ye) | Ciphers Online', 'meta_description' => 'Uluslararasılaştırılmış alan adlarını online olarak Punycode’a ve tersine dönüştürün. IDN xn-- alan adı modu ve ham RFC 3492 modu, UTF-8 desteği, tarayıcınızda.'],
        ];
    }

    /**
     * Возвращает базовый контент инструмента (en/ru).
     *
     * @return array<string, mixed>
     */
    private function content(): array
    {
        return [
            'block' => [
                'en' => ['title' => 'How Punycode works', 'text' => '<p>Punycode (RFC 3492) is a way to represent Unicode text using only the ASCII letters, digits and hyphen allowed in host names. It is the encoding behind Internationalized Domain Names (IDN): a label such as <code>münchen</code> becomes <code>xn--mnchen-3ya</code>.</p><p>The algorithm first copies all ASCII characters, then appends a compact, self-synchronising sequence that describes where the non-ASCII characters go and which code points they are. In <strong>domain</strong> mode this tool processes each dot-separated label separately and adds the <code>xn--</code> prefix only to labels that contain non-ASCII characters. <strong>Raw</strong> mode applies the bare RFC 3492 transform to the whole string, without the prefix or dot handling.</p>'],
                'ru' => ['title' => 'Как работает Punycode', 'text' => '<p>Punycode (RFC 3492) — способ представить текст Unicode, используя только ASCII-буквы, цифры и дефис, допустимые в именах хостов. Это кодировка интернационализированных доменных имён (IDN): метка вроде <code>münchen</code> превращается в <code>xn--mnchen-3ya</code>.</p><p>Алгоритм сначала копирует все ASCII-символы, а затем дописывает компактную самосинхронизирующуюся последовательность, которая описывает, куда вставляются не-ASCII символы и какие это code points. В режиме <strong>домена</strong> инструмент обрабатывает каждую метку между точками отдельно и добавляет префикс <code>xn--</code> только к меткам с не-ASCII символами. <strong>Сырой</strong> режим применяет чистое преобразование RFC 3492 ко всей строке — без префикса и разбивки по точкам.</p>'],
            ],
            'examples' => [
                ['sort' => 10, 'direction' => 'encrypt', 'settings' => '{"ciphers-base-variant":"domain"}', 'translations' => [
                    'en' => ['title' => 'Encode an IDN domain', 'input' => 'münchen.de', 'output' => 'xn--mnchen-3ya.de', 'description' => 'The non-ASCII label gets the xn-- prefix; the .de label stays as is.'],
                    'ru' => ['title' => 'Кодирование IDN-домена', 'input' => 'münchen.de', 'output' => 'xn--mnchen-3ya.de', 'description' => 'Не-ASCII метка получает префикс xn--; метка .de остаётся без изменений.'],
                ]],
                ['sort' => 20, 'direction' => 'decrypt', 'settings' => '{"ciphers-base-variant":"domain"}', 'translations' => [
                    'en' => ['title' => 'Decode a Punycode domain', 'input' => 'xn--mnchen-3ya.de', 'output' => 'münchen.de', 'description' => 'Decoding restores the original Unicode domain name.'],
                    'ru' => ['title' => 'Декодирование Punycode-домена', 'input' => 'xn--mnchen-3ya.de', 'output' => 'münchen.de', 'description' => 'Декодирование восстанавливает исходное Unicode-имя домена.'],
                ]],
            ],
            'faq' => [
                ['sort' => 10, 'translations' => [
                    'en' => ['question' => 'What is the xn-- prefix?', 'answer' => 'The prefix xn-- (the IDNA "ACE prefix") marks a domain label as Punycode-encoded. When a browser sees a label starting with xn--, it decodes the rest as Punycode to display the original Unicode name.'],
                    'ru' => ['question' => 'Что такое префикс xn--?', 'answer' => 'Префикс xn-- («ACE-префикс» IDNA) помечает метку домена как закодированную в Punycode. Увидев метку, начинающуюся с xn--, браузер декодирует остаток как Punycode и показывает исходное Unicode-имя.'],
                ]],
                ['sort' => 20, 'translations' => [
                    'en' => ['question' => 'Why is Punycode important for security?', 'answer' => 'Different Unicode characters can look identical (a Latin "a" versus a Cyrillic "а"). Converting a domain to Punycode reveals such homograph tricks, because a lookalike domain produces a different xn-- string than the genuine one.'],
                    'ru' => ['question' => 'Почему Punycode важен для безопасности?', 'answer' => 'Разные символы Unicode могут выглядеть одинаково (латинская «a» и кириллическая «а»). Преобразование домена в Punycode выявляет такие омографные уловки: похожий домен даёт другую строку xn--, чем настоящий.'],
                ]],
            ],
            'tags' => [
                ['sort' => 10, 'translations' => ['en' => 'IDN domains', 'ru' => 'IDN-домены']],
                ['sort' => 20, 'translations' => ['en' => 'RFC 3492', 'ru' => 'RFC 3492']],
            ],
        ];
    }
}
