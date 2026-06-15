<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет инструмент Timestamp Converter в категорию «Кодирование».
 */
class SeedTimestampConverterTool extends Migration
{
    /**
     * Создаёт инструмент и переводы.
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

        $now      = date('Y-m-d H:i:s');
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
            ['timestamp-converter']
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
     * Создаёт или обновляет запись инструмента.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['timestamp-converter']
        );

        if ($cipher !== false) {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET category_id = ?, calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                [$categoryId, 'client', 70, 1, $now, $cipherId]
            );
            return $cipherId;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS
            . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, 'timestamp-converter', 'client', 70, 1, $now, $now]
        );
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
     * Заполняет блоки, FAQ и теги.
     */
    private function seedContent(int $cipherId, string $now): void
    {
        $block1 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $this->upsertBlockTranslation($block1, 'en', 'What is a Unix timestamp?', '<p>A Unix timestamp (also known as Unix time or POSIX time) is the number of seconds that have elapsed since 00:00:00 UTC on 1 January 1970, known as the Unix epoch. It is a point-in-time representation that is timezone-independent and widely used in databases, APIs, and log files.</p><p>Millisecond timestamps follow the same convention but count milliseconds instead of seconds, making them 1000 times larger. Many modern languages and APIs (JavaScript\'s <code>Date.now()</code>, Java\'s <code>System.currentTimeMillis()</code>) return millisecond timestamps by default.</p>', $now);
        $this->upsertBlockTranslation($block1, 'ru', 'Что такое Unix-метка времени?', '<p>Unix-метка времени (Unix time, POSIX time) — количество секунд, прошедших с 00:00:00 UTC 1 января 1970 года (Unix-эпоха). Это независимое от часового пояса представление момента времени, широко используемое в базах данных, API и лог-файлах.</p><p>Миллисекундные метки следуют той же логике, но считают миллисекунды вместо секунд, поэтому они в 1000 раз больше. Многие современные языки и API (JavaScript <code>Date.now()</code>, Java <code>System.currentTimeMillis()</code>) возвращают миллисекундные метки по умолчанию.</p>', $now);

        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);
        $this->upsertBlockTranslation($block2, 'en', 'Seconds vs milliseconds', '<p>The tool automatically detects whether your timestamp is in seconds or milliseconds. Numbers above 100 billion (10¹¹) are treated as milliseconds; smaller numbers as seconds. Use the <strong>Input unit</strong> setting to override auto-detection if needed.</p><p>A quick rule of thumb: a 10-digit number is in seconds, a 13-digit number is in milliseconds. The current Unix time in seconds is typically around 1.7 billion (10 digits).</p>', $now);
        $this->upsertBlockTranslation($block2, 'ru', 'Секунды vs миллисекунды', '<p>Инструмент автоматически определяет, задана ли метка в секундах или в миллисекундах. Числа больше 100 миллиардов (10¹¹) считаются миллисекундами; меньшие — секундами. Используйте настройку <strong>Единица ввода</strong>, чтобы переопределить автоопределение.</p><p>Практическое правило: 10-значное число — в секундах, 13-значное — в миллисекундах. Текущее Unix-время в секундах составляет около 1,7 миллиарда (10 цифр).</p>', $now);

        $faq1 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 10, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq1, 'en', 'How do I get the current Unix timestamp?', 'Click the <strong>Now</strong> button to instantly fill the input with the current Unix timestamp in seconds. You can also use terminal commands: <code>date +%s</code> on Linux/macOS, or <code>Get-Date -UFormat %s</code> in PowerShell. In JavaScript, use <code>Math.floor(Date.now() / 1000)</code> for seconds or <code>Date.now()</code> for milliseconds.', $now);
        $this->upsertFaqTranslation($faq1, 'ru', 'Как получить текущую Unix-метку?', 'Нажмите кнопку <strong>Сейчас</strong>, чтобы мгновенно заполнить поле ввода текущей Unix-меткой в секундах. Также можно использовать команды терминала: <code>date +%s</code> в Linux/macOS или <code>Get-Date -UFormat %s</code> в PowerShell. В JavaScript используйте <code>Math.floor(Date.now() / 1000)</code> для секунд или <code>Date.now()</code> для миллисекунд.', $now);

        $faq2 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 20, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq2, 'en', 'What date formats can I convert to a timestamp?', 'The converter accepts any date string that the browser\'s JavaScript engine can parse, including ISO 8601 (<code>2024-01-15T12:00:00Z</code>), simple dates (<code>2024-01-15</code>), date-time without timezone (<code>2024-01-15 12:00:00</code>), and many locale-specific formats. For best compatibility, use ISO 8601 format with an explicit timezone (e.g. <code>2024-01-15T12:00:00+03:00</code>).', $now);
        $this->upsertFaqTranslation($faq2, 'ru', 'Какие форматы дат можно конвертировать в метку?', 'Конвертер принимает любую строку даты, которую может разобрать браузерный движок JavaScript, включая ISO 8601 (<code>2024-01-15T12:00:00Z</code>), простые даты (<code>2024-01-15</code>), дату-время без временной зоны (<code>2024-01-15 12:00:00</code>) и многие локальные форматы. Для максимальной совместимости используйте формат ISO 8601 с явным указанием часового пояса (например, <code>2024-01-15T12:00:00+03:00</code>).', $now);

        $faq3 = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, 30, $now, ['show_in_category' => 0]);
        $this->upsertFaqTranslation($faq3, 'en', 'What is the Year 2038 problem?', 'The Year 2038 problem (Y2K38) affects systems that store Unix timestamps as a 32-bit signed integer. Such systems can only represent times up to 03:14:07 UTC on 19 January 2038 (timestamp 2147483647). After that the value overflows and wraps to a large negative number, representing a date in 1901. Modern 64-bit systems are not affected, as they can represent dates billions of years into the future.', $now);
        $this->upsertFaqTranslation($faq3, 'ru', 'Что такое проблема 2038 года?', 'Проблема 2038 года (Y2K38) касается систем, хранящих Unix-метки как 32-битное знаковое целое число. Такие системы могут представлять время только до 03:14:07 UTC 19 января 2038 года (метка 2147483647). После этого значение переполняется и становится большим отрицательным числом, соответствующим дате в 1901 году. Современные 64-битные системы не подвержены этой проблеме, так как могут представлять даты на миллиарды лет вперёд.', $now);

        $tag1 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 10, $now);
        $this->upsertTagTranslation($tag1, 'en', 'Unix timestamp', $now);
        $this->upsertTagTranslation($tag1, 'ru', 'Unix-метка', $now);

        $tag2 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 20, $now);
        $this->upsertTagTranslation($tag2, 'en', 'epoch converter', $now);
        $this->upsertTagTranslation($tag2, 'ru', 'конвертер эпохи', $now);

        $tag3 = $this->upsertParent(Tables::CIPHERS_TAGS, 'app_id', $cipherId, 30, $now);
        $this->upsertTagTranslation($tag3, 'en', 'date to timestamp', $now);
        $this->upsertTagTranslation($tag3, 'ru', 'дата в метку', $now);
    }

    /**
     * Создаёт или обновляет родительскую запись (блок, FAQ, тег).
     *
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
     * Создаёт или обновляет перевод блока.
     */
    private function upsertBlockTranslation(int $blockId, string $language, string $title, string $text, string $now): void
    {
        $this->upsertTranslation(Tables::CIPHERS_BLOCKS_TRANSLATIONS, 'block_id', $blockId, $language, ['title' => $title, 'text' => $text], $now);
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
     * Возвращает переводы инструмента Timestamp Converter.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Timestamp Converter',
                'name_short'        => 'Timestamp',
                'description'       => 'Convert Unix timestamps to human-readable dates and back. Paste a Unix timestamp (seconds or milliseconds) to see it in UTC, local time, ISO 8601 and more. Switch to Date → Timestamp mode to get the Unix time for any date string.',
                'description_stort' => 'Convert Unix timestamps to dates and dates to timestamps.',
                'meta_title'        => 'Timestamp Converter Online | Unix Time to Date',
                'meta_description'  => 'Convert Unix timestamps to readable dates and vice versa. Supports seconds and milliseconds, shows UTC, local time, ISO 8601, relative time and day of week. Free online tool, works entirely in your browser.',
            ],
            'ru' => [
                'name'              => 'Конвертер временны́х меток',
                'name_short'        => 'Timestamp',
                'description'       => 'Конвертируйте Unix-метки в читаемые даты и обратно. Вставьте Unix-метку (в секундах или миллисекундах), чтобы увидеть её в формате UTC, местного времени, ISO 8601 и других. Переключитесь в режим «Дата → Метка», чтобы получить Unix-время для любой строки даты.',
                'description_stort' => 'Конвертация Unix-меток в даты и дат в метки.',
                'meta_title'        => 'Конвертер временны́х меток онлайн | Unix Time в дату',
                'meta_description'  => 'Конвертируйте Unix-метки в читаемые даты и наоборот. Поддерживает секунды и миллисекунды, показывает UTC, местное время, ISO 8601, относительное время и день недели. Бесплатный инструмент, работает в браузере.',
            ],
            'de' => [
                'name'              => 'Zeitstempel-Konverter',
                'name_short'        => 'Timestamp',
                'description'       => 'Konvertieren Sie Unix-Zeitstempel in lesbare Datumsangaben und umgekehrt. Fügen Sie einen Unix-Zeitstempel (Sekunden oder Millisekunden) ein, um ihn in UTC, Ortszeit, ISO 8601 und mehr anzuzeigen.',
                'description_stort' => 'Unix-Zeitstempel in Datum und Datum in Zeitstempel konvertieren.',
                'meta_title'        => 'Zeitstempel-Konverter Online | Unix-Zeit in Datum',
                'meta_description'  => 'Unix-Zeitstempel in lesbare Datumsangaben und zurück konvertieren. Unterstützt Sekunden und Millisekunden. Kostenloses Online-Tool.',
            ],
            'es' => [
                'name'              => 'Conversor de timestamp',
                'name_short'        => 'Timestamp',
                'description'       => 'Convierte timestamps Unix en fechas legibles y viceversa. Pega un timestamp Unix (segundos o milisegundos) para verlo en UTC, hora local, ISO 8601 y más.',
                'description_stort' => 'Convierte timestamps Unix en fechas y fechas en timestamps.',
                'meta_title'        => 'Conversor de Timestamp Online | Unix Time a Fecha',
                'meta_description'  => 'Convierte timestamps Unix en fechas legibles y al revés. Compatible con segundos y milisegundos. Herramienta gratuita en línea.',
            ],
            'fr' => [
                'name'              => 'Convertisseur de timestamp',
                'name_short'        => 'Timestamp',
                'description'       => 'Convertissez des timestamps Unix en dates lisibles et vice-versa. Collez un timestamp Unix (secondes ou millisecondes) pour le voir en UTC, heure locale, ISO 8601 et plus.',
                'description_stort' => 'Convertit les timestamps Unix en dates et les dates en timestamps.',
                'meta_title'        => 'Convertisseur de Timestamp en ligne | Unix Time en Date',
                'meta_description'  => 'Convertissez des timestamps Unix en dates lisibles et inversement. Prend en charge les secondes et les millisecondes. Outil gratuit en ligne.',
            ],
            'it' => [
                'name'              => 'Convertitore di timestamp',
                'name_short'        => 'Timestamp',
                'description'       => 'Converti timestamp Unix in date leggibili e viceversa. Incolla un timestamp Unix (secondi o millisecondi) per vederlo in UTC, ora locale, ISO 8601 e altro.',
                'description_stort' => 'Converte timestamp Unix in date e date in timestamp.',
                'meta_title'        => 'Convertitore Timestamp Online | Unix Time in Data',
                'meta_description'  => 'Converti timestamp Unix in date leggibili e viceversa. Supporta secondi e millisecondi. Strumento gratuito online.',
            ],
            'pt' => [
                'name'              => 'Conversor de timestamp',
                'name_short'        => 'Timestamp',
                'description'       => 'Converta timestamps Unix em datas legíveis e vice-versa. Cole um timestamp Unix (segundos ou milissegundos) para vê-lo em UTC, hora local, ISO 8601 e mais.',
                'description_stort' => 'Converte timestamps Unix em datas e datas em timestamps.',
                'meta_title'        => 'Conversor de Timestamp Online | Unix Time para Data',
                'meta_description'  => 'Converta timestamps Unix em datas legíveis e vice-versa. Suporta segundos e milissegundos. Ferramenta gratuita online.',
            ],
            'tr' => [
                'name'              => 'Zaman Damgası Dönüştürücü',
                'name_short'        => 'Timestamp',
                'description'       => 'Unix zaman damgalarını okunabilir tarihlere ve tersine dönüştürün. Bir Unix zaman damgası (saniye veya milisaniye) yapıştırarak UTC, yerel saat, ISO 8601 ve daha fazlasında görüntüleyin.',
                'description_stort' => 'Unix zaman damgalarını tarihlere ve tarihleri zaman damgalarına dönüştürür.',
                'meta_title'        => 'Zaman Damgası Dönüştürücü Online | Unix Zamanını Tarihe Çevir',
                'meta_description'  => 'Unix zaman damgalarını okunabilir tarihlere ve tersine dönüştürün. Saniye ve milisaniyeyi destekler. Ücretsiz çevrimiçi araç.',
            ],
        ];
    }
}
