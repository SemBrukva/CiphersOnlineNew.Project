<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет клиентские инструменты кодирования Base32, Base58, Base85 и Base45
 * в категорию encoding. Базовый inline-контент (en/ru); подробный контент
 * прорабатывается по каждому инструменту отдельно.
 */
class SeedBaseEncodings extends Migration
{
    /**
     * Создаёт или обновляет инструменты, переводы карточек и базовый контент.
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

        foreach ($this->tools() as $alias => $tool) {
            $cipherId = $this->upsertCipher($categoryId, $alias, (int) $tool['sort_order'], $now);

            foreach ($tool['translations'] as $language => $translation) {
                $this->upsertTranslation($cipherId, $language, $translation, $now);
            }

            // base32 использует авторитетный content JSON (storage/content/encoding/base32) —
            // inline-контент для него не сидируем, только структурную строку и переводы карточки.
            if ($tool['content'] !== []) {
                $this->upsertContent($cipherId, $tool['content'], $now);
            }
        }
    }

    /**
     * Удаляет добавленные инструменты.
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

        $categoryId = (int) $category['id'];

        foreach (array_keys($this->tools()) as $alias) {
            $this->db->execute(
                'DELETE FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ?',
                [$categoryId, $alias]
            );
        }
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
     * @param array{block: array<string, array{title: string, text: string}>, examples: array<int, array{sort: int, translations: array<string, array{title: string, input: string, output: string, description: string}>}>, faq: array<int, array{sort: int, translations: array<string, array{question: string, answer: string}>}>, tags: array<int, array{sort: int, translations: array<string, string>}>} $content Контент инструмента.
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
     * Создаёт или обновляет пример по сортировке. `$direction` — 'encrypt' / 'decrypt' (задаёт
     * направление, которое выставится по клику «Use example»); `$settings` — JSON настроек
     * инструмента (например, выбранный вариант кодировки) либо null.
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
     * Возвращает конфигурацию всех четырёх инструментов.
     *
     * @return array<string, array{sort_order: int, translations: array<string, array<string, string>>, content: array<string, mixed>}>
     */
    private function tools(): array
    {
        return [
            'base32' => [
                'sort_order' => 80,
                'translations' => $this->base32Translations(),
                // Контент base32 живёт в авторитетном content JSON (импорт), не в миграции.
                'content' => [],
            ],
            'base58' => [
                'sort_order' => 90,
                'translations' => $this->base58Translations(),
                'content' => $this->base58Content(),
            ],
            'base85' => [
                'sort_order' => 100,
                'translations' => $this->base85Translations(),
                'content' => $this->base85Content(),
            ],
            'base45' => [
                'sort_order' => 110,
                'translations' => $this->base45Translations(),
                'content' => $this->base45Content(),
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function base32Translations(): array
    {
        return [
            'en' => ['name' => 'Base32 Encoder & Decoder', 'name_short' => 'Base32', 'description' => 'Encode and decode Base32 in your browser. Supports RFC 4648, base32hex and Crockford variants. Data is processed locally and never sent to the server.', 'description_stort' => 'Encode and decode Base32 with RFC 4648, base32hex and Crockford variants.', 'meta_title' => 'Base32 Encoder & Decoder | Ciphers Online', 'meta_description' => 'Encode and decode Base32 online. RFC 4648, base32hex and Crockford variants, UTF-8 support, all in your browser.'],
            'ru' => ['name' => 'Base32: кодировщик и декодировщик', 'name_short' => 'Base32', 'description' => 'Кодирование и декодирование Base32 в браузере. Поддержка вариантов RFC 4648, base32hex и Crockford. Данные обрабатываются локально и не отправляются на сервер.', 'description_stort' => 'Кодирование и декодирование Base32: RFC 4648, base32hex и Crockford.', 'meta_title' => 'Base32: кодировщик и декодировщик | Ciphers Online', 'meta_description' => 'Кодируйте и декодируйте Base32 онлайн. Варианты RFC 4648, base32hex и Crockford, поддержка UTF-8, прямо в браузере.'],
            'de' => ['name' => 'Base32-Encoder & Decoder', 'name_short' => 'Base32', 'description' => 'Base32 im Browser kodieren und dekodieren. Unterstützt RFC 4648, base32hex und Crockford. Die Daten werden lokal verarbeitet und nie an den Server gesendet.', 'description_stort' => 'Base32 kodieren und dekodieren: RFC 4648, base32hex und Crockford.', 'meta_title' => 'Base32-Encoder & Decoder | Ciphers Online', 'meta_description' => 'Base32 online kodieren und dekodieren. Varianten RFC 4648, base32hex und Crockford, UTF-8, direkt im Browser.'],
            'es' => ['name' => 'Codificador y decodificador Base32', 'name_short' => 'Base32', 'description' => 'Codifica y decodifica Base32 en tu navegador. Admite RFC 4648, base32hex y Crockford. Los datos se procesan localmente y nunca se envían al servidor.', 'description_stort' => 'Codifica y decodifica Base32: RFC 4648, base32hex y Crockford.', 'meta_title' => 'Codificador y decodificador Base32 | Ciphers Online', 'meta_description' => 'Codifica y decodifica Base32 online. Variantes RFC 4648, base32hex y Crockford, soporte UTF-8, en tu navegador.'],
            'fr' => ['name' => 'Encodeur et décodeur Base32', 'name_short' => 'Base32', 'description' => 'Encodez et décodez du Base32 dans votre navigateur. Prend en charge RFC 4648, base32hex et Crockford. Les données sont traitées localement et jamais envoyées au serveur.', 'description_stort' => 'Encodez et décodez du Base32 : RFC 4648, base32hex et Crockford.', 'meta_title' => 'Encodeur et décodeur Base32 | Ciphers Online', 'meta_description' => 'Encodez et décodez du Base32 en ligne. Variantes RFC 4648, base32hex et Crockford, prise en charge UTF-8, dans votre navigateur.'],
            'it' => ['name' => 'Codificatore e decodificatore Base32', 'name_short' => 'Base32', 'description' => 'Codifica e decodifica Base32 nel browser. Supporta RFC 4648, base32hex e Crockford. I dati sono elaborati localmente e mai inviati al server.', 'description_stort' => 'Codifica e decodifica Base32: RFC 4648, base32hex e Crockford.', 'meta_title' => 'Codificatore e decodificatore Base32 | Ciphers Online', 'meta_description' => 'Codifica e decodifica Base32 online. Varianti RFC 4648, base32hex e Crockford, supporto UTF-8, nel browser.'],
            'pt' => ['name' => 'Codificador e decodificador Base32', 'name_short' => 'Base32', 'description' => 'Codifique e decodifique Base32 no navegador. Suporta RFC 4648, base32hex e Crockford. Os dados são processados localmente e nunca enviados ao servidor.', 'description_stort' => 'Codifique e decodifique Base32: RFC 4648, base32hex e Crockford.', 'meta_title' => 'Codificador e decodificador Base32 | Ciphers Online', 'meta_description' => 'Codifique e decodifique Base32 online. Variantes RFC 4648, base32hex e Crockford, suporte UTF-8, no navegador.'],
            'tr' => ['name' => 'Base32 Kodlayıcı ve Çözücü', 'name_short' => 'Base32', 'description' => 'Base32’yi tarayıcınızda kodlayın ve çözün. RFC 4648, base32hex ve Crockford desteklenir. Veriler yerel olarak işlenir ve sunucuya gönderilmez.', 'description_stort' => 'Base32 kodlama ve çözme: RFC 4648, base32hex ve Crockford.', 'meta_title' => 'Base32 Kodlayıcı ve Çözücü | Ciphers Online', 'meta_description' => 'Base32’yi online kodlayın ve çözün. RFC 4648, base32hex ve Crockford varyantları, UTF-8 desteği, tarayıcınızda.'],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function base58Translations(): array
    {
        return [
            'en' => ['name' => 'Base58 Encoder & Decoder', 'name_short' => 'Base58', 'description' => 'Encode and decode Base58 in your browser. Supports raw Base58 and Base58Check with checksum, the format used by cryptocurrency addresses. Data is processed locally.', 'description_stort' => 'Encode and decode Base58 and Base58Check (crypto addresses).', 'meta_title' => 'Base58 Encoder & Decoder | Ciphers Online', 'meta_description' => 'Encode and decode Base58 online. Raw Base58 and Base58Check with checksum, the encoding used by Bitcoin and other crypto addresses.'],
            'ru' => ['name' => 'Base58: кодировщик и декодировщик', 'name_short' => 'Base58', 'description' => 'Кодирование и декодирование Base58 в браузере. Поддержка чистого Base58 и Base58Check с контрольной суммой — формата криптовалютных адресов. Данные обрабатываются локально.', 'description_stort' => 'Кодирование и декодирование Base58 и Base58Check (крипто-адреса).', 'meta_title' => 'Base58: кодировщик и декодировщик | Ciphers Online', 'meta_description' => 'Кодируйте и декодируйте Base58 онлайн. Чистый Base58 и Base58Check с контрольной суммой — кодировка адресов Bitcoin и других криптовалют.'],
            'de' => ['name' => 'Base58-Encoder & Decoder', 'name_short' => 'Base58', 'description' => 'Base58 im Browser kodieren und dekodieren. Unterstützt rohes Base58 und Base58Check mit Prüfsumme, das Format von Kryptowährungs-Adressen. Die Daten werden lokal verarbeitet.', 'description_stort' => 'Base58 und Base58Check (Krypto-Adressen) kodieren und dekodieren.', 'meta_title' => 'Base58-Encoder & Decoder | Ciphers Online', 'meta_description' => 'Base58 online kodieren und dekodieren. Rohes Base58 und Base58Check mit Prüfsumme, die Kodierung von Bitcoin- und anderen Krypto-Adressen.'],
            'es' => ['name' => 'Codificador y decodificador Base58', 'name_short' => 'Base58', 'description' => 'Codifica y decodifica Base58 en tu navegador. Admite Base58 sin procesar y Base58Check con suma de verificación, el formato de las direcciones de criptomonedas. Los datos se procesan localmente.', 'description_stort' => 'Codifica y decodifica Base58 y Base58Check (direcciones cripto).', 'meta_title' => 'Codificador y decodificador Base58 | Ciphers Online', 'meta_description' => 'Codifica y decodifica Base58 online. Base58 sin procesar y Base58Check con checksum, la codificación de las direcciones de Bitcoin y otras criptomonedas.'],
            'fr' => ['name' => 'Encodeur et décodeur Base58', 'name_short' => 'Base58', 'description' => 'Encodez et décodez du Base58 dans votre navigateur. Prend en charge Base58 brut et Base58Check avec somme de contrôle, le format des adresses de cryptomonnaies. Les données sont traitées localement.', 'description_stort' => 'Encodez et décodez Base58 et Base58Check (adresses crypto).', 'meta_title' => 'Encodeur et décodeur Base58 | Ciphers Online', 'meta_description' => 'Encodez et décodez du Base58 en ligne. Base58 brut et Base58Check avec somme de contrôle, l’encodage des adresses Bitcoin et autres cryptomonnaies.'],
            'it' => ['name' => 'Codificatore e decodificatore Base58', 'name_short' => 'Base58', 'description' => 'Codifica e decodifica Base58 nel browser. Supporta Base58 grezzo e Base58Check con checksum, il formato degli indirizzi di criptovaluta. I dati sono elaborati localmente.', 'description_stort' => 'Codifica e decodifica Base58 e Base58Check (indirizzi cripto).', 'meta_title' => 'Codificatore e decodificatore Base58 | Ciphers Online', 'meta_description' => 'Codifica e decodifica Base58 online. Base58 grezzo e Base58Check con checksum, la codifica degli indirizzi Bitcoin e di altre criptovalute.'],
            'pt' => ['name' => 'Codificador e decodificador Base58', 'name_short' => 'Base58', 'description' => 'Codifique e decodifique Base58 no navegador. Suporta Base58 puro e Base58Check com checksum, o formato dos endereços de criptomoedas. Os dados são processados localmente.', 'description_stort' => 'Codifique e decodifique Base58 e Base58Check (endereços cripto).', 'meta_title' => 'Codificador e decodificador Base58 | Ciphers Online', 'meta_description' => 'Codifique e decodifique Base58 online. Base58 puro e Base58Check com checksum, a codificação dos endereços de Bitcoin e outras criptomoedas.'],
            'tr' => ['name' => 'Base58 Kodlayıcı ve Çözücü', 'name_short' => 'Base58', 'description' => 'Base58’i tarayıcınızda kodlayın ve çözün. Ham Base58 ve sağlama toplamlı Base58Check — kripto para adreslerinin biçimi — desteklenir. Veriler yerel olarak işlenir.', 'description_stort' => 'Base58 ve Base58Check (kripto adresleri) kodlama ve çözme.', 'meta_title' => 'Base58 Kodlayıcı ve Çözücü | Ciphers Online', 'meta_description' => 'Base58’i online kodlayın ve çözün. Ham Base58 ve sağlama toplamlı Base58Check — Bitcoin ve diğer kripto adreslerinin kodlaması.'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function base58Content(): array
    {
        return [
            'block' => [
                'en' => ['title' => 'How Base58 works', 'text' => '<p>Base58 encodes data as a large base-58 number using an alphabet that omits the visually ambiguous characters <code>0</code>, <code>O</code>, <code>I</code> and <code>l</code>. Unlike Base32/Base64 it is not a simple bit-grouping; the whole byte string is treated as one big integer.</p><p>Base58Check adds a version byte and a 4-byte checksum (double SHA-256), so a single typo in an address is detected. This is the encoding behind Bitcoin and many other cryptocurrency addresses and keys.</p>'],
                'ru' => ['title' => 'Как работает Base58', 'text' => '<p>Base58 кодирует данные как большое число в системе счисления по основанию 58, используя алфавит без визуально неоднозначных символов <code>0</code>, <code>O</code>, <code>I</code> и <code>l</code>. В отличие от Base32/Base64 это не простая группировка бит — вся строка байт трактуется как одно большое целое число.</p><p>Base58Check добавляет version-байт и 4-байтовую контрольную сумму (двойной SHA-256), поэтому опечатка в адресе обнаруживается. Это кодировка адресов и ключей Bitcoin и многих других криптовалют.</p>'],
            ],
            'examples' => [
                ['sort' => 10, 'direction' => 'encrypt', 'settings' => '{"ciphers-base-variant":"base58"}', 'translations' => [
                    'en' => ['title' => 'Encode text to Base58', 'input' => 'Hello, World!', 'output' => '72k1xXWG59fYdzSNoA', 'description' => 'Raw Base58 of the UTF-8 bytes.'],
                    'ru' => ['title' => 'Кодирование текста в Base58', 'input' => 'Hello, World!', 'output' => '72k1xXWG59fYdzSNoA', 'description' => 'Чистый Base58 от UTF-8-байтов.'],
                ]],
                ['sort' => 20, 'direction' => 'decrypt', 'settings' => '{"ciphers-base-variant":"base58"}', 'translations' => [
                    'en' => ['title' => 'Decode Base58', 'input' => '72k1xXWG59fYdzSNoA', 'output' => 'Hello, World!', 'description' => 'Decoding restores the original text.'],
                    'ru' => ['title' => 'Декодирование Base58', 'input' => '72k1xXWG59fYdzSNoA', 'output' => 'Hello, World!', 'description' => 'Декодирование восстанавливает исходный текст.'],
                ]],
            ],
            'faq' => [
                ['sort' => 10, 'translations' => [
                    'en' => ['question' => 'What is Base58Check?', 'answer' => 'Base58Check prepends a version byte and appends a 4-byte checksum (double SHA-256) before Base58 encoding. The checksum lets software reject mistyped addresses. It is used by Bitcoin addresses and WIF private keys.'],
                    'ru' => ['question' => 'Что такое Base58Check?', 'answer' => 'Base58Check добавляет version-байт в начало и 4-байтовую контрольную сумму (двойной SHA-256) в конец перед кодированием в Base58. Контрольная сумма позволяет отклонять адреса с опечатками. Используется в адресах Bitcoin и приватных ключах WIF.'],
                ]],
                ['sort' => 20, 'translations' => [
                    'en' => ['question' => 'Why does Base58 skip some characters?', 'answer' => 'The digits 0, capital O, capital I and lowercase l are omitted because they are easily confused in many fonts, which reduces transcription errors when copying addresses by hand.'],
                    'ru' => ['question' => 'Почему в Base58 пропущены некоторые символы?', 'answer' => 'Цифра 0, заглавная O, заглавная I и строчная l исключены, потому что во многих шрифтах их легко перепутать — это уменьшает ошибки при ручном переписывании адресов.'],
                ]],
            ],
            'tags' => [
                ['sort' => 10, 'translations' => ['en' => 'Crypto addresses', 'ru' => 'Крипто-адреса']],
                ['sort' => 20, 'translations' => ['en' => 'Base58Check', 'ru' => 'Base58Check']],
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function base85Translations(): array
    {
        return [
            'en' => ['name' => 'Base85 / Ascii85 Encoder & Decoder', 'name_short' => 'Base85', 'description' => 'Encode and decode Base85 in your browser. Supports Adobe Ascii85 and ZeroMQ Z85 variants. Data is processed locally and never sent to the server.', 'description_stort' => 'Encode and decode Base85 with Ascii85 and Z85 variants.', 'meta_title' => 'Base85 / Ascii85 Encoder & Decoder | Ciphers Online', 'meta_description' => 'Encode and decode Base85 online. Adobe Ascii85 and ZeroMQ Z85 variants, used in PDF and Git binary diffs, all in your browser.'],
            'ru' => ['name' => 'Base85 / Ascii85: кодировщик и декодировщик', 'name_short' => 'Base85', 'description' => 'Кодирование и декодирование Base85 в браузере. Поддержка вариантов Adobe Ascii85 и ZeroMQ Z85. Данные обрабатываются локально и не отправляются на сервер.', 'description_stort' => 'Кодирование и декодирование Base85: Ascii85 и Z85.', 'meta_title' => 'Base85 / Ascii85: кодировщик и декодировщик | Ciphers Online', 'meta_description' => 'Кодируйте и декодируйте Base85 онлайн. Варианты Adobe Ascii85 и ZeroMQ Z85, применяемые в PDF и бинарных диффах Git, прямо в браузере.'],
            'de' => ['name' => 'Base85 / Ascii85 Encoder & Decoder', 'name_short' => 'Base85', 'description' => 'Base85 im Browser kodieren und dekodieren. Unterstützt Adobe Ascii85 und ZeroMQ Z85. Die Daten werden lokal verarbeitet und nie an den Server gesendet.', 'description_stort' => 'Base85 kodieren und dekodieren: Ascii85 und Z85.', 'meta_title' => 'Base85 / Ascii85 Encoder & Decoder | Ciphers Online', 'meta_description' => 'Base85 online kodieren und dekodieren. Adobe Ascii85 und ZeroMQ Z85, verwendet in PDF und Git-Binärdiffs, direkt im Browser.'],
            'es' => ['name' => 'Codificador y decodificador Base85 / Ascii85', 'name_short' => 'Base85', 'description' => 'Codifica y decodifica Base85 en tu navegador. Admite Adobe Ascii85 y ZeroMQ Z85. Los datos se procesan localmente y nunca se envían al servidor.', 'description_stort' => 'Codifica y decodifica Base85: Ascii85 y Z85.', 'meta_title' => 'Codificador y decodificador Base85 / Ascii85 | Ciphers Online', 'meta_description' => 'Codifica y decodifica Base85 online. Adobe Ascii85 y ZeroMQ Z85, usados en PDF y diffs binarios de Git, en tu navegador.'],
            'fr' => ['name' => 'Encodeur et décodeur Base85 / Ascii85', 'name_short' => 'Base85', 'description' => 'Encodez et décodez du Base85 dans votre navigateur. Prend en charge Adobe Ascii85 et ZeroMQ Z85. Les données sont traitées localement et jamais envoyées au serveur.', 'description_stort' => 'Encodez et décodez du Base85 : Ascii85 et Z85.', 'meta_title' => 'Encodeur et décodeur Base85 / Ascii85 | Ciphers Online', 'meta_description' => 'Encodez et décodez du Base85 en ligne. Adobe Ascii85 et ZeroMQ Z85, utilisés dans les PDF et les diffs binaires Git, dans votre navigateur.'],
            'it' => ['name' => 'Codificatore e decodificatore Base85 / Ascii85', 'name_short' => 'Base85', 'description' => 'Codifica e decodifica Base85 nel browser. Supporta Adobe Ascii85 e ZeroMQ Z85. I dati sono elaborati localmente e mai inviati al server.', 'description_stort' => 'Codifica e decodifica Base85: Ascii85 e Z85.', 'meta_title' => 'Codificatore e decodificatore Base85 / Ascii85 | Ciphers Online', 'meta_description' => 'Codifica e decodifica Base85 online. Adobe Ascii85 e ZeroMQ Z85, usati in PDF e diff binari di Git, nel browser.'],
            'pt' => ['name' => 'Codificador e decodificador Base85 / Ascii85', 'name_short' => 'Base85', 'description' => 'Codifique e decodifique Base85 no navegador. Suporta Adobe Ascii85 e ZeroMQ Z85. Os dados são processados localmente e nunca enviados ao servidor.', 'description_stort' => 'Codifique e decodifique Base85: Ascii85 e Z85.', 'meta_title' => 'Codificador e decodificador Base85 / Ascii85 | Ciphers Online', 'meta_description' => 'Codifique e decodifique Base85 online. Adobe Ascii85 e ZeroMQ Z85, usados em PDF e diffs binários do Git, no navegador.'],
            'tr' => ['name' => 'Base85 / Ascii85 Kodlayıcı ve Çözücü', 'name_short' => 'Base85', 'description' => 'Base85’i tarayıcınızda kodlayın ve çözün. Adobe Ascii85 ve ZeroMQ Z85 desteklenir. Veriler yerel olarak işlenir ve sunucuya gönderilmez.', 'description_stort' => 'Base85 kodlama ve çözme: Ascii85 ve Z85.', 'meta_title' => 'Base85 / Ascii85 Kodlayıcı ve Çözücü | Ciphers Online', 'meta_description' => 'Base85’i online kodlayın ve çözün. PDF ve Git ikili farklarında kullanılan Adobe Ascii85 ve ZeroMQ Z85, tarayıcınızda.'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function base85Content(): array
    {
        return [
            'block' => [
                'en' => ['title' => 'How Base85 works', 'text' => '<p>Base85 packs 4 bytes into 5 printable ASCII characters, so it is about 25% more compact than Base64 (which needs 4 characters per 3 bytes). It works by treating each 4-byte group as a 32-bit number written in base 85.</p><p>The Adobe Ascii85 variant wraps output in <code>&lt;~ ~&gt;</code> and uses <code>z</code> as shorthand for four zero bytes. The ZeroMQ Z85 variant uses a different, more shell-safe alphabet and requires the input length to be a multiple of four bytes.</p>'],
                'ru' => ['title' => 'Как работает Base85', 'text' => '<p>Base85 упаковывает 4 байта в 5 печатных ASCII-символов, поэтому он примерно на 25% компактнее Base64 (которому нужно 4 символа на 3 байта). Каждая группа из 4 байт трактуется как 32-битное число, записанное в системе по основанию 85.</p><p>Вариант Adobe Ascii85 оборачивает вывод в <code>&lt;~ ~&gt;</code> и использует <code>z</code> как сокращение для четырёх нулевых байт. Вариант ZeroMQ Z85 использует другой, более безопасный для shell алфавит и требует, чтобы длина входа была кратна четырём байтам.</p>'],
            ],
            'examples' => [
                ['sort' => 10, 'direction' => 'encrypt', 'settings' => '{"ciphers-base-variant":"ascii85"}', 'translations' => [
                    'en' => ['title' => 'Encode text to Ascii85', 'input' => 'Hello, World!', 'output' => '<~87cURD_*#4DfTZ)+T~>', 'description' => 'Adobe Ascii85 with <~ ~> delimiters.'],
                    'ru' => ['title' => 'Кодирование текста в Ascii85', 'input' => 'Hello, World!', 'output' => '<~87cURD_*#4DfTZ)+T~>', 'description' => 'Adobe Ascii85 с рамками <~ ~>.'],
                ]],
                ['sort' => 20, 'direction' => 'decrypt', 'settings' => '{"ciphers-base-variant":"ascii85"}', 'translations' => [
                    'en' => ['title' => 'Decode Ascii85', 'input' => '<~87cURD_*#4DfTZ)+T~>', 'output' => 'Hello, World!', 'description' => 'Decoding restores the original text.'],
                    'ru' => ['title' => 'Декодирование Ascii85', 'input' => '<~87cURD_*#4DfTZ)+T~>', 'output' => 'Hello, World!', 'description' => 'Декодирование восстанавливает исходный текст.'],
                ]],
            ],
            'faq' => [
                ['sort' => 10, 'translations' => [
                    'en' => ['question' => 'Where is Base85 used?', 'answer' => 'Adobe Ascii85 appears in PostScript and PDF files, and a Base85 variant is used by Git for binary patches. ZeroMQ uses Z85 to encode keys and frames in a shell-safe way.'],
                    'ru' => ['question' => 'Где применяется Base85?', 'answer' => 'Adobe Ascii85 встречается в файлах PostScript и PDF, а вариант Base85 используется Git для бинарных патчей. ZeroMQ применяет Z85 для кодирования ключей и кадров в безопасном для shell виде.'],
                ]],
                ['sort' => 20, 'translations' => [
                    'en' => ['question' => 'What is the difference between Ascii85 and Z85?', 'answer' => 'They use different 85-character alphabets. Ascii85 supports partial groups, <~ ~> framing and the z shortcut; Z85 has a fixed alphabet chosen to be safe in source code and requires the byte length to be a multiple of four.'],
                    'ru' => ['question' => 'В чём разница между Ascii85 и Z85?', 'answer' => 'Они используют разные алфавиты из 85 символов. Ascii85 поддерживает неполные группы, рамки <~ ~> и сокращение z; у Z85 фиксированный алфавит, безопасный в исходном коде, и требуется длина в байтах, кратная четырём.'],
                ]],
            ],
            'tags' => [
                ['sort' => 10, 'translations' => ['en' => 'Ascii85', 'ru' => 'Ascii85']],
                ['sort' => 20, 'translations' => ['en' => 'Z85', 'ru' => 'Z85']],
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function base45Translations(): array
    {
        return [
            'en' => ['name' => 'Base45 Encoder & Decoder', 'name_short' => 'Base45', 'description' => 'Encode and decode Base45 (RFC 9285) in your browser. Base45 is optimised for QR codes and is used by EU Digital COVID Certificates. Data is processed locally.', 'description_stort' => 'Encode and decode Base45 (RFC 9285) for QR codes.', 'meta_title' => 'Base45 Encoder & Decoder | Ciphers Online', 'meta_description' => 'Encode and decode Base45 online. RFC 9285 encoding optimised for QR codes and used by EU Digital COVID Certificates, all in your browser.'],
            'ru' => ['name' => 'Base45: кодировщик и декодировщик', 'name_short' => 'Base45', 'description' => 'Кодирование и декодирование Base45 (RFC 9285) в браузере. Base45 оптимизирован для QR-кодов и применяется в цифровых COVID-сертификатах ЕС. Данные обрабатываются локально.', 'description_stort' => 'Кодирование и декодирование Base45 (RFC 9285) для QR-кодов.', 'meta_title' => 'Base45: кодировщик и декодировщик | Ciphers Online', 'meta_description' => 'Кодируйте и декодируйте Base45 онлайн. Кодировка RFC 9285, оптимизированная для QR-кодов и применяемая в цифровых COVID-сертификатах ЕС, прямо в браузере.'],
            'de' => ['name' => 'Base45-Encoder & Decoder', 'name_short' => 'Base45', 'description' => 'Base45 (RFC 9285) im Browser kodieren und dekodieren. Base45 ist für QR-Codes optimiert und wird von EU-COVID-Zertifikaten verwendet. Die Daten werden lokal verarbeitet.', 'description_stort' => 'Base45 (RFC 9285) für QR-Codes kodieren und dekodieren.', 'meta_title' => 'Base45-Encoder & Decoder | Ciphers Online', 'meta_description' => 'Base45 online kodieren und dekodieren. RFC-9285-Kodierung für QR-Codes, verwendet von EU-COVID-Zertifikaten, direkt im Browser.'],
            'es' => ['name' => 'Codificador y decodificador Base45', 'name_short' => 'Base45', 'description' => 'Codifica y decodifica Base45 (RFC 9285) en tu navegador. Base45 está optimizado para códigos QR y lo usan los Certificados COVID Digitales de la UE. Los datos se procesan localmente.', 'description_stort' => 'Codifica y decodifica Base45 (RFC 9285) para códigos QR.', 'meta_title' => 'Codificador y decodificador Base45 | Ciphers Online', 'meta_description' => 'Codifica y decodifica Base45 online. Codificación RFC 9285 optimizada para códigos QR y usada por los Certificados COVID Digitales de la UE.'],
            'fr' => ['name' => 'Encodeur et décodeur Base45', 'name_short' => 'Base45', 'description' => 'Encodez et décodez du Base45 (RFC 9285) dans votre navigateur. Base45 est optimisé pour les codes QR et utilisé par les certificats COVID numériques de l’UE. Les données sont traitées localement.', 'description_stort' => 'Encodez et décodez du Base45 (RFC 9285) pour les codes QR.', 'meta_title' => 'Encodeur et décodeur Base45 | Ciphers Online', 'meta_description' => 'Encodez et décodez du Base45 en ligne. Encodage RFC 9285 optimisé pour les codes QR et utilisé par les certificats COVID numériques de l’UE.'],
            'it' => ['name' => 'Codificatore e decodificatore Base45', 'name_short' => 'Base45', 'description' => 'Codifica e decodifica Base45 (RFC 9285) nel browser. Base45 è ottimizzato per i codici QR ed è usato dai certificati COVID digitali dell’UE. I dati sono elaborati localmente.', 'description_stort' => 'Codifica e decodifica Base45 (RFC 9285) per i codici QR.', 'meta_title' => 'Codificatore e decodificatore Base45 | Ciphers Online', 'meta_description' => 'Codifica e decodifica Base45 online. Codifica RFC 9285 ottimizzata per i codici QR e usata dai certificati COVID digitali dell’UE.'],
            'pt' => ['name' => 'Codificador e decodificador Base45', 'name_short' => 'Base45', 'description' => 'Codifique e decodifique Base45 (RFC 9285) no navegador. Base45 é otimizado para códigos QR e usado pelos Certificados COVID Digitais da UE. Os dados são processados localmente.', 'description_stort' => 'Codifique e decodifique Base45 (RFC 9285) para códigos QR.', 'meta_title' => 'Codificador e decodificador Base45 | Ciphers Online', 'meta_description' => 'Codifique e decodifique Base45 online. Codificação RFC 9285 otimizada para códigos QR e usada pelos Certificados COVID Digitais da UE.'],
            'tr' => ['name' => 'Base45 Kodlayıcı ve Çözücü', 'name_short' => 'Base45', 'description' => 'Base45’i (RFC 9285) tarayıcınızda kodlayın ve çözün. Base45, QR kodları için optimize edilmiştir ve AB Dijital COVID Sertifikalarında kullanılır. Veriler yerel olarak işlenir.', 'description_stort' => 'QR kodları için Base45 (RFC 9285) kodlama ve çözme.', 'meta_title' => 'Base45 Kodlayıcı ve Çözücü | Ciphers Online', 'meta_description' => 'Base45’i online kodlayın ve çözün. QR kodları için optimize edilmiş ve AB Dijital COVID Sertifikalarında kullanılan RFC 9285 kodlaması.'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function base45Content(): array
    {
        return [
            'block' => [
                'en' => ['title' => 'How Base45 works', 'text' => '<p>Base45 (RFC 9285) converts each pair of bytes into a 16-bit number and writes it as three characters from a 45-symbol alphabet (0–9, A–Z and a few punctuation marks). A trailing single byte becomes two characters.</p><p>The 45-character alphabet was chosen to fit the QR code alphanumeric mode, which encodes such data more densely than binary mode. This is why Base45 is used inside EU Digital COVID Certificates and similar QR payloads.</p>'],
                'ru' => ['title' => 'Как работает Base45', 'text' => '<p>Base45 (RFC 9285) преобразует каждую пару байт в 16-битное число и записывает его тремя символами из алфавита в 45 символов (0–9, A–Z и несколько знаков пунктуации). Одиночный завершающий байт превращается в два символа.</p><p>Алфавит из 45 символов подобран под буквенно-цифровой режим QR-кода, который кодирует такие данные плотнее, чем бинарный режим. Поэтому Base45 применяется в цифровых COVID-сертификатах ЕС и похожих QR-нагрузках.</p>'],
            ],
            'examples' => [
                ['sort' => 10, 'direction' => 'encrypt', 'translations' => [
                    'en' => ['title' => 'Encode text to Base45', 'input' => 'Hello!!', 'output' => '%69 VD92EX0', 'description' => 'Base45 per RFC 9285; note the space is part of the alphabet.'],
                    'ru' => ['title' => 'Кодирование текста в Base45', 'input' => 'Hello!!', 'output' => '%69 VD92EX0', 'description' => 'Base45 по RFC 9285; пробел входит в алфавит.'],
                ]],
                ['sort' => 20, 'direction' => 'decrypt', 'translations' => [
                    'en' => ['title' => 'Decode Base45', 'input' => '%69 VD92EX0', 'output' => 'Hello!!', 'description' => 'Decoding restores the original text.'],
                    'ru' => ['title' => 'Декодирование Base45', 'input' => '%69 VD92EX0', 'output' => 'Hello!!', 'description' => 'Декодирование восстанавливает исходный текст.'],
                ]],
            ],
            'faq' => [
                ['sort' => 10, 'translations' => [
                    'en' => ['question' => 'Why does Base45 exist when Base64 already does?', 'answer' => 'Base45 maps onto the QR code alphanumeric character set, so its output fits QR codes more efficiently than Base64. It is a trade-off: larger strings, but denser QR codes.'],
                    'ru' => ['question' => 'Зачем нужен Base45, если есть Base64?', 'answer' => 'Base45 отображается на буквенно-цифровой набор символов QR-кода, поэтому его вывод помещается в QR-код эффективнее, чем Base64. Это компромисс: строки длиннее, зато QR-код плотнее.'],
                ]],
                ['sort' => 20, 'translations' => [
                    'en' => ['question' => 'Is Base45 the same as the COVID certificate?', 'answer' => 'No. Base45 is only the outer text encoding of the QR payload. The certificate itself is a CBOR/COSE structure that is compressed and then Base45-encoded for the QR code.'],
                    'ru' => ['question' => 'Base45 — это и есть COVID-сертификат?', 'answer' => 'Нет. Base45 — лишь внешняя текстовая кодировка QR-нагрузки. Сам сертификат — это структура CBOR/COSE, которая сжимается и затем кодируется в Base45 для QR-кода.'],
                ]],
            ],
            'tags' => [
                ['sort' => 10, 'translations' => ['en' => 'RFC 9285', 'ru' => 'RFC 9285']],
                ['sort' => 20, 'translations' => ['en' => 'QR codes', 'ru' => 'QR-коды']],
            ],
        ];
    }
}
