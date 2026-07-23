<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет клиентский инструмент «UUID / GUID Generator» в категорию utilities.
 * Базовый inline-контент (en/ru); подробный контент прорабатывается отдельно.
 */
class SeedUuidGenerator extends Migration
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

        $cipherId = $this->upsertCipher($categoryId, 'uuid-generator', 10, $now);

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
            [(int) $category['id'], 'uuid-generator']
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
     * Создаёт базовый контент страницы: блок, FAQ, теги.
     * Примеры не создаются: генератор не имеет пары «вход → выход».
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
     * генератора (версия/количество/имя/пространство имён/формат).
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
            'en' => ['name' => 'UUID / GUID Generator', 'name_short' => 'UUID Generator', 'description' => 'Generate UUIDs and GUIDs in your browser — versions 1, 3, 4, 5 and 7, plus the nil and max UUIDs. Create up to 100 at once, pick uppercase, braces or urn:uuid formatting, then copy or download the results. Everything runs locally.', 'description_stort' => 'Generate v1/v3/v4/v5/v7 UUIDs (GUIDs) online, in bulk, in your browser.', 'meta_title' => 'UUID Generator (v1, v3, v4, v5, v7 / GUID) | Ciphers Online', 'meta_description' => 'Free online UUID/GUID generator. Create random v4, time-ordered v7, name-based v3/v5 and time-based v1 UUIDs in bulk, locally in your browser.'],
            'ru' => ['name' => 'Генератор UUID / GUID', 'name_short' => 'Генератор UUID', 'description' => 'Генерация UUID и GUID прямо в браузере — версии 1, 3, 4, 5 и 7, а также нулевой и максимальный UUID. До 100 значений за раз, форматы uppercase, в фигурных скобках или с префиксом urn:uuid, копирование и скачивание. Всё работает локально.', 'description_stort' => 'Генерация UUID (GUID) версий 1/3/4/5/7 онлайн, пакетно, в браузере.', 'meta_title' => 'Генератор UUID (v1, v3, v4, v5, v7 / GUID) | Ciphers Online', 'meta_description' => 'Бесплатный онлайн-генератор UUID/GUID. Создавайте случайные v4, упорядоченные по времени v7, именные v3/v5 и временные v1 UUID пакетно, локально в браузере.'],
            'de' => ['name' => 'UUID- / GUID-Generator', 'name_short' => 'UUID-Generator', 'description' => 'Erzeugen Sie UUIDs und GUIDs im Browser — Versionen 1, 3, 4, 5 und 7 sowie die Nil- und Max-UUID. Bis zu 100 auf einmal, Formate in Großbuchstaben, mit geschweiften Klammern oder urn:uuid, Kopieren und Herunterladen. Alles läuft lokal.', 'description_stort' => 'UUIDs (GUIDs) der Versionen 1/3/4/5/7 online und im Stapel im Browser erzeugen.', 'meta_title' => 'UUID-Generator (v1, v3, v4, v5, v7 / GUID) | Ciphers Online', 'meta_description' => 'Kostenloser Online-UUID/GUID-Generator. Erzeugen Sie zufällige v4-, zeitlich sortierte v7-, namensbasierte v3/v5- und zeitbasierte v1-UUIDs im Stapel, lokal im Browser.'],
            'es' => ['name' => 'Generador de UUID / GUID', 'name_short' => 'Generador UUID', 'description' => 'Genera UUID y GUID en tu navegador: versiones 1, 3, 4, 5 y 7, además del UUID nil y max. Hasta 100 a la vez, formatos en mayúsculas, con llaves o con prefijo urn:uuid, copiar y descargar. Todo se ejecuta localmente.', 'description_stort' => 'Genera UUID (GUID) de las versiones 1/3/4/5/7 online, en lote, en el navegador.', 'meta_title' => 'Generador de UUID (v1, v3, v4, v5, v7 / GUID) | Ciphers Online', 'meta_description' => 'Generador de UUID/GUID online gratis. Crea UUID aleatorios v4, ordenados por tiempo v7, basados en nombre v3/v5 y basados en tiempo v1 en lote, localmente en tu navegador.'],
            'fr' => ['name' => 'Générateur d\'UUID / GUID', 'name_short' => 'Générateur UUID', 'description' => 'Générez des UUID et GUID dans votre navigateur : versions 1, 3, 4, 5 et 7, ainsi que les UUID nil et max. Jusqu\'à 100 à la fois, formats en majuscules, entre accolades ou avec préfixe urn:uuid, copie et téléchargement. Tout s\'exécute localement.', 'description_stort' => 'Générez des UUID (GUID) des versions 1/3/4/5/7 en ligne, par lots, dans le navigateur.', 'meta_title' => 'Générateur d\'UUID (v1, v3, v4, v5, v7 / GUID) | Ciphers Online', 'meta_description' => 'Générateur d\'UUID/GUID en ligne gratuit. Créez des UUID aléatoires v4, triés par temps v7, basés sur un nom v3/v5 et basés sur le temps v1 par lots, localement dans votre navigateur.'],
            'it' => ['name' => 'Generatore di UUID / GUID', 'name_short' => 'Generatore UUID', 'description' => 'Genera UUID e GUID nel browser: versioni 1, 3, 4, 5 e 7, più gli UUID nil e max. Fino a 100 alla volta, formati in maiuscolo, tra parentesi graffe o con prefisso urn:uuid, copia e download. Tutto viene eseguito localmente.', 'description_stort' => 'Genera UUID (GUID) delle versioni 1/3/4/5/7 online, in blocco, nel browser.', 'meta_title' => 'Generatore di UUID (v1, v3, v4, v5, v7 / GUID) | Ciphers Online', 'meta_description' => 'Generatore di UUID/GUID online gratuito. Crea UUID casuali v4, ordinati per tempo v7, basati su nome v3/v5 e basati su tempo v1 in blocco, localmente nel browser.'],
            'pt' => ['name' => 'Gerador de UUID / GUID', 'name_short' => 'Gerador UUID', 'description' => 'Gere UUIDs e GUIDs no navegador — versões 1, 3, 4, 5 e 7, além dos UUIDs nil e max. Até 100 de uma vez, formatos em maiúsculas, entre chaves ou com prefixo urn:uuid, copiar e baixar. Tudo roda localmente.', 'description_stort' => 'Gere UUIDs (GUIDs) das versões 1/3/4/5/7 online, em lote, no navegador.', 'meta_title' => 'Gerador de UUID (v1, v3, v4, v5, v7 / GUID) | Ciphers Online', 'meta_description' => 'Gerador de UUID/GUID online grátis. Crie UUIDs aleatórios v4, ordenados por tempo v7, baseados em nome v3/v5 e baseados em tempo v1 em lote, localmente no navegador.'],
            'tr' => ['name' => 'UUID / GUID Üreteci', 'name_short' => 'UUID Üreteci', 'description' => 'Tarayıcınızda UUID ve GUID üretin — sürüm 1, 3, 4, 5 ve 7\'nin yanı sıra nil ve max UUID\'ler. Bir kerede 100\'e kadar, büyük harf, süslü parantez veya urn:uuid biçimleri, kopyalama ve indirme. Her şey yerel olarak çalışır.', 'description_stort' => 'Sürüm 1/3/4/5/7 UUID\'lerini (GUID) çevrimiçi, toplu olarak tarayıcıda üretin.', 'meta_title' => 'UUID Üreteci (v1, v3, v4, v5, v7 / GUID) | Ciphers Online', 'meta_description' => 'Ücretsiz çevrimiçi UUID/GUID üreteci. Rastgele v4, zamana göre sıralı v7, ada dayalı v3/v5 ve zamana dayalı v1 UUID\'leri toplu olarak tarayıcınızda yerel olarak oluşturun.'],
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
                ['sort' => 10, 'settings' => '{"version":"v4","count":5,"name":"","namespace":"dns","uppercase":false,"hyphens":true,"braces":false,"urn":false}', 'translations' => [
                    'en' => ['title' => 'Bulk random v4', 'input' => '', 'output' => '', 'description' => 'Generate five random version-4 UUIDs at once — the default for most apps and databases.'],
                    'ru' => ['title' => 'Пакет случайных v4', 'input' => '', 'output' => '', 'description' => 'Сгенерировать сразу пять случайных UUID версии 4 — вариант по умолчанию для большинства приложений и БД.'],
                ]],
                ['sort' => 20, 'settings' => '{"version":"v4","count":1,"name":"","namespace":"dns","uppercase":true,"hyphens":false,"braces":false,"urn":false}', 'translations' => [
                    'en' => ['title' => 'Compact GUID', 'input' => '', 'output' => '', 'description' => 'Uppercase without hyphens — the bare 32-character GUID form often used in code and registry keys.'],
                    'ru' => ['title' => 'Компактный GUID', 'input' => '', 'output' => '', 'description' => 'Верхний регистр без дефисов — «голая» 32-символьная форма GUID, часто используемая в коде и ключах реестра.'],
                ]],
                ['sort' => 30, 'settings' => '{"version":"v5","count":1,"name":"example.com","namespace":"dns","uppercase":false,"hyphens":true,"braces":false,"urn":false}', 'translations' => [
                    'en' => ['title' => 'Namespace v5 (DNS)', 'input' => '', 'output' => 'cfbff0d1-9375-5685-968c-48ce8b15ae17', 'description' => 'Deterministic UUID for the name example.com in the DNS namespace — the same input always yields this value.'],
                    'ru' => ['title' => 'Именной v5 (DNS)', 'input' => '', 'output' => 'cfbff0d1-9375-5685-968c-48ce8b15ae17', 'description' => 'Детерминированный UUID для имени example.com в пространстве имён DNS — тот же ввод всегда даёт это значение.'],
                ]],
            ],
            'block' => [
                'en' => ['title' => 'What is a UUID?', 'text' => '<p>A UUID (Universally Unique Identifier), also called a GUID on Microsoft platforms, is a 128-bit value used to label information without a central authority. Written as 32 hexadecimal digits in five hyphen-separated groups (<code>8-4-4-4-12</code>), a typical UUID looks like <code>f47ac10b-58cc-4372-a567-0e02b2c3d479</code>.</p><p>Several versions exist, defined by RFC 4122 and RFC 9562. <strong>Version 4</strong> is fully random and by far the most common. <strong>Version 7</strong> embeds a Unix timestamp so the values sort chronologically — a great fit for database keys. <strong>Version 1</strong> is time-based, while <strong>versions 3 and 5</strong> are derived deterministically from a namespace and a name using MD5 (v3) or SHA-1 (v5). The <em>nil</em> UUID is all zeros and the <em>max</em> UUID is all ones.</p>'],
                'ru' => ['title' => 'Что такое UUID?', 'text' => '<p>UUID (универсальный уникальный идентификатор), на платформах Microsoft также называемый GUID, — это 128-битное значение для маркировки данных без центрального органа. Записывается как 32 шестнадцатеричные цифры в пяти группах через дефис (<code>8-4-4-4-12</code>); типичный UUID выглядит так: <code>f47ac10b-58cc-4372-a567-0e02b2c3d479</code>.</p><p>Существует несколько версий, определённых в RFC 4122 и RFC 9562. <strong>Версия 4</strong> полностью случайна и встречается чаще всего. <strong>Версия 7</strong> содержит метку времени Unix, поэтому значения сортируются хронологически — удобно для ключей БД. <strong>Версия 1</strong> основана на времени, а <strong>версии 3 и 5</strong> детерминированно выводятся из пространства имён и имени с помощью MD5 (v3) или SHA-1 (v5). <em>Нулевой</em> UUID состоит из одних нулей, а <em>максимальный</em> — из единиц.</p>'],
            ],
            'faq' => [
                ['sort' => 10, 'translations' => [
                    'en' => ['question' => 'Which UUID version should I use?', 'answer' => 'For most cases pick version 4 (random) — it needs no input and has a negligible collision chance. If you store the values as database keys and want them to sort by creation time, prefer version 7. Use versions 3 or 5 only when you need the same input to always produce the same UUID.'],
                    'ru' => ['question' => 'Какую версию UUID выбрать?', 'answer' => 'В большинстве случаев берите версию 4 (случайную) — она не требует ввода и имеет ничтожную вероятность коллизии. Если значения хранятся как ключи БД и должны сортироваться по времени создания, выбирайте версию 7. Версии 3 или 5 нужны только когда один и тот же ввод должен всегда давать один и тот же UUID.'],
                ]],
                ['sort' => 20, 'translations' => [
                    'en' => ['question' => 'Are these UUIDs generated securely?', 'answer' => 'Yes. Random UUIDs (v4) and the random parts of v7 use the browser\'s cryptographically secure Web Crypto API (crypto.getRandomValues). Nothing is sent to a server — every value is generated locally in your browser.'],
                    'ru' => ['question' => 'Насколько безопасно генерируются эти UUID?', 'answer' => 'Безопасно. Случайные UUID (v4) и случайные части v7 используют криптографически стойкий Web Crypto API браузера (crypto.getRandomValues). Ничего не отправляется на сервер — каждое значение создаётся локально в вашем браузере.'],
                ]],
            ],
            'tags' => [
                ['sort' => 10, 'translations' => ['en' => 'UUID', 'ru' => 'UUID']],
                ['sort' => 20, 'translations' => ['en' => 'GUID', 'ru' => 'GUID']],
            ],
        ];
    }
}
