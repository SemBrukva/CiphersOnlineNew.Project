<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Альберти и базовый контент страницы.
 */
class SeedAlbertiCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр Альберти, блоки, примеры и FAQ.
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

        $categoryId = (int) $category['id'];
        $now        = date('Y-m-d H:i:s');
        $cipherId   = $this->upsertCipher($categoryId, $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertCipherTranslation($cipherId, $language, $translation, $now);
        }

        $this->seedBlocks($cipherId, $now);
        $this->seedExamples($cipherId, $now);
        $this->seedFaq($cipherId, $now);
    }

    /**
     * Удаляет шифр Альберти вместе с контентом.
     */
    public function down(): void
    {
        $category = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHER_CATEGORIES . ' WHERE alias = ? LIMIT 1',
            ['classical-ciphers']
        );

        if ($category === false) {
            return;
        }

        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ?',
            [(int) $category['id'], 'alberti']
        );
    }

    /**
     * Создаёт или обновляет запись шифра Альберти.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'alberti']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'alberti', 'api', 59, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 59, 1, $now, $cipherId]
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
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS
            . ' WHERE app_id = ? AND language = ? LIMIT 1',
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
                . ' SET name = ?, name_short = ?, description = ?, description_stort = ?, meta_title = ?, meta_description = ?, updated_at = ? '
                . 'WHERE id = ?',
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

    /** Добавляет базовые блоки страницы. */
    private function seedBlocks(int $cipherId, string $now): void
    {
        foreach ($this->blockTranslations() as $sortOrder => $translations) {
            $blockId = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, $sortOrder, $now);
            foreach ($translations as $language => $translation) {
                $this->upsertTranslation(
                    Tables::CIPHERS_BLOCKS_TRANSLATIONS,
                    'block_id',
                    $blockId,
                    $language,
                    ['title' => $translation['title'], 'text' => $translation['text']],
                    $now
                );
            }
        }
    }

    /** Добавляет базовые примеры. */
    private function seedExamples(int $cipherId, string $now): void
    {
        foreach ($this->examples() as $sortOrder => $example) {
            $exampleId = $this->upsertExample($cipherId, $sortOrder, $example['direction'], $now);
            foreach ($example['translations'] as $language => $translation) {
                $this->upsertTranslation(
                    Tables::CIPHERS_EXAMPLES_TRANSLATIONS,
                    'example_id',
                    $exampleId,
                    $language,
                    [
                        'title'       => $translation['title'],
                        'input'       => $translation['input'],
                        'output'      => $translation['output'],
                        'description' => $translation['description'],
                        'key'         => $translation['key'],
                    ],
                    $now
                );
            }
        }
    }

    /** Добавляет базовый FAQ. */
    private function seedFaq(int $cipherId, string $now): void
    {
        foreach ($this->faqs() as $sortOrder => $translations) {
            $faqId = $this->upsertParent(
                Tables::CIPHERS_FAQ,
                'app_id',
                $cipherId,
                $sortOrder,
                $now,
                ['show_in_category' => 0]
            );
            foreach ($translations as $language => $translation) {
                $this->upsertTranslation(
                    Tables::CIPHERS_FAQ_TRANSLATIONS,
                    'faq_id',
                    $faqId,
                    $language,
                    ['question' => $translation['question'], 'answer' => $translation['answer']],
                    $now
                );
            }
        }
    }

    /**
     * Создаёт или обновляет родительскую запись контента.
     *
     * @param array<string, int|string> $extra
     */
    private function upsertParent(string $table, string $foreignKey, int $ownerId, int $sortOrder, string $now, array $extra = []): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND sort_order = ? LIMIT 1',
            [$ownerId, $sortOrder]
        );

        if ($row !== false) {
            $id          = (int) $row['id'];
            $assignments = ['published = 1', 'updated_at = ?'];
            $params      = [$now];
            foreach ($extra as $column => $value) {
                $assignments[] = $column . ' = ?';
                $params[]      = $value;
            }
            $params[] = $id;
            $this->db->execute(
                'UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = ?',
                $params
            );
            return $id;
        }

        $columns = [$foreignKey, 'sort_order', 'published'];
        $values  = [$ownerId, $sortOrder, 1];
        foreach ($extra as $column => $value) {
            $columns[] = $column;
            $values[]  = $value;
        }
        $columns[] = 'created_at';
        $columns[] = 'updated_at';
        $values[]  = $now;
        $values[]  = $now;

        return (int) $this->db->insert(
            'INSERT INTO ' . $table
            . ' (' . implode(', ', $columns) . ') '
            . 'VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
            $values
        );
    }

    /** Создаёт или обновляет пример. */
    private function upsertExample(int $cipherId, int $sortOrder, string $direction, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $id = (int) $row['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_EXAMPLES
                . ' SET direction = ?, delimiter = ?, key_format = ?, published = 1, updated_at = ? WHERE id = ?',
                [$direction, '', '', $now, $id]
            );
            return $id;
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES
            . ' (app_id, sort_order, published, direction, delimiter, key_format, created_at, updated_at) '
            . 'VALUES (?, ?, 1, ?, ?, ?, ?, ?)',
            [$cipherId, $sortOrder, $direction, '', '', $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод родительской записи.
     *
     * @param array<string, int|string> $columns
     */
    private function upsertTranslation(string $table, string $foreignKey, int $parentId, string $language, array $columns, string $now): void
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . $table . ' WHERE ' . $foreignKey . ' = ? AND language = ? LIMIT 1',
            [$parentId, $language]
        );

        if ($row !== false) {
            $this->updateById($table, (int) $row['id'], $columns, $now);
            return;
        }

        $insertColumns = [$foreignKey, 'language'];
        $values        = [$parentId, $language];
        foreach ($columns as $column => $value) {
            $insertColumns[] = $column;
            $values[]        = $value;
        }
        $insertColumns[] = 'created_at';
        $insertColumns[] = 'updated_at';
        $values[]        = $now;
        $values[]        = $now;

        $this->db->insert(
            'INSERT INTO ' . $table
            . ' (' . implode(', ', $insertColumns) . ') '
            . 'VALUES (' . implode(', ', array_fill(0, count($insertColumns), '?')) . ')',
            $values
        );
    }

    /**
     * Обновляет строку по id набором колонок.
     *
     * @param array<string, int|string> $columns
     */
    private function updateById(string $table, int $id, array $columns, string $now): void
    {
        $assignments = [];
        $values      = [];
        foreach ($columns as $column => $value) {
            $assignments[] = $column . ' = ?';
            $values[]      = $value;
        }
        $assignments[] = 'updated_at = ?';
        $values[]      = $now;
        $values[]      = $id;

        $this->db->execute(
            'UPDATE ' . $table . ' SET ' . implode(', ', $assignments) . ' WHERE id = ?',
            $values
        );
    }

    /**
     * Возвращает переводы карточки шифра.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Alberti Cipher',
                'name_short'        => 'Alberti',
                'description'       => 'Encrypt and decrypt Latin text with the Alberti cipher disk — a rotating two-ring device and one of the first polyalphabetic ciphers.',
                'description_stort' => 'Alberti cipher disk: rotate, encrypt and decrypt.',
                'meta_title'        => 'Alberti Cipher Online | Ciphers Online',
                'meta_description'  => 'Use the Alberti cipher disk online: enter a keyword, set the starting index, and encrypt or decrypt Latin text with the rotating disk cipher.',
            ],
            'ru' => [
                'name'              => 'Шифр Альберти',
                'name_short'        => 'Альберти',
                'description'       => 'Онлайн-инструмент для шифрования и расшифровки латинского текста диском Альберти — вращающимся двухкольцевым устройством, одним из первых многоалфавитных шифров.',
                'description_stort' => 'Диск Альберти: вращайте, шифруйте и расшифровывайте.',
                'meta_title'        => 'Шифр Альберти Онлайн | Ciphers Online',
                'meta_description'  => 'Используйте диск Альберти онлайн: задайте ключевое слово, выберите начальный индекс и зашифруйте или расшифруйте латинский текст.',
            ],
            'de' => [
                'name'              => 'Alberti-Chiffre',
                'name_short'        => 'Alberti',
                'description'       => 'Lateinischen Text mit der Alberti-Chiffrierscheibe ver- und entschlüsseln — einem rotierenden Doppelring-Gerät und einer der ersten polyalphabetischen Chiffren.',
                'description_stort' => 'Alberti-Scheibe: drehen, verschlüsseln, entschlüsseln.',
                'meta_title'        => 'Alberti-Chiffre Online | Ciphers Online',
                'meta_description'  => 'Nutzen Sie die Alberti-Chiffrierscheibe online: Schlüsselwort eingeben, Startindex wählen und lateinischen Text sofort ver- oder entschlüsseln.',
            ],
            'es' => [
                'name'              => 'Cifrado Alberti',
                'name_short'        => 'Alberti',
                'description'       => 'Cifra y descifra texto latino con el disco de cifrado de Alberti — un dispositivo rotatorio de dos anillos y uno de los primeros cifrados polialfabéticos.',
                'description_stort' => 'Disco Alberti: gira, cifra y descifra.',
                'meta_title'        => 'Cifrado Alberti Online | Ciphers Online',
                'meta_description'  => 'Usa el disco de Alberti online: introduce una palabra clave, elige el índice inicial y cifra o descifra texto latino.',
            ],
            'fr' => [
                'name'              => 'Chiffre d\'Alberti',
                'name_short'        => 'Alberti',
                'description'       => 'Chiffrez et déchiffrez du texte latin avec le disque d\'Alberti — un dispositif rotatif à deux anneaux et l\'un des premiers chiffres polyalphabétiques.',
                'description_stort' => 'Disque d\'Alberti : faites tourner, chiffrez et déchiffrez.',
                'meta_title'        => 'Chiffre d\'Alberti en ligne | Ciphers Online',
                'meta_description'  => 'Utilisez le disque d\'Alberti en ligne : saisissez un mot-clé, choisissez l\'index de départ et chiffrez ou déchiffrez du texte latin.',
            ],
            'it' => [
                'name'              => 'Cifrario Alberti',
                'name_short'        => 'Alberti',
                'description'       => 'Cifra e decifra testo latino con il disco cifrante di Alberti — un dispositivo rotante a due anelli e uno dei primi cifrari polialfabetici.',
                'description_stort' => 'Disco di Alberti: ruota, cifra e decifra.',
                'meta_title'        => 'Cifrario Alberti Online | Ciphers Online',
                'meta_description'  => 'Usa il disco di Alberti online: inserisci una parola chiave, scegli l\'indice iniziale e cifra o decifra testo latino.',
            ],
            'pt' => [
                'name'              => 'Cifra de Alberti',
                'name_short'        => 'Alberti',
                'description'       => 'Cifre e decifre texto latino com o disco de Alberti — um dispositivo rotativo de dois anéis e uma das primeiras cifras polialfabéticas.',
                'description_stort' => 'Disco de Alberti: gire, cifre e decifre.',
                'meta_title'        => 'Cifra de Alberti Online | Ciphers Online',
                'meta_description'  => 'Use o disco de Alberti online: informe uma palavra-chave, escolha o índice inicial e cifre ou decifre texto latino.',
            ],
            'tr' => [
                'name'              => 'Alberti Şifresi',
                'name_short'        => 'Alberti',
                'description'       => 'Alberti şifre diski ile Latin metnini şifreleyin veya çözün — ilk polialfabetik şifrelerden biri olan dönen çift halkalı bir cihaz.',
                'description_stort' => 'Alberti diski: döndür, şifrele ve çöz.',
                'meta_title'        => 'Alberti Şifresi Online | Ciphers Online',
                'meta_description'  => 'Alberti şifre diskini online kullanın: anahtar kelime girin, başlangıç indeksini seçin ve Latin metnini şifreleyin veya çözün.',
            ],
        ];
    }

    /**
     * Возвращает переводы блоков.
     *
     * @return array<int, array<string, array{title: string, text: string}>>
     */
    private function blockTranslations(): array
    {
        return [
            10 => [
                'en' => [
                    'title' => 'How the Alberti Cipher Disk works',
                    'text'  => '<p>Invented by Leon Battista Alberti around 1467, the cipher disk consists of two concentric rings. The outer ring holds the plaintext alphabet (A–Z) in fixed order. The inner ring holds a keyword-mixed alphabet and can rotate to different starting positions, called the index.</p><p>To encrypt, you find each plaintext letter on the outer ring and read the corresponding letter on the inner ring at the current alignment. The disk\'s ability to rotate to multiple positions makes it one of the earliest known polyalphabetic ciphers.</p>',
                ],
                'ru' => [
                    'title' => 'Как работает диск Альберти',
                    'text'  => '<p>Изобретённый Леоном Баттистой Альберти около 1467 года, диск состоит из двух концентрических колец. Внешнее кольцо содержит алфавит открытого текста (A–Z) в фиксированном порядке. Внутреннее кольцо содержит перемешанный по ключевому слову алфавит и может вращаться в разные начальные позиции — индексы.</p><p>Для шифрования вы находите каждую букву открытого текста на внешнем кольце и считываете соответствующую букву на внутреннем кольце при текущем выравнивании. Возможность вращения диска делает его одним из первых известных полиалфавитных шифров.</p>',
                ],
                'de' => [
                    'title' => 'Wie die Alberti-Chiffrierscheibe funktioniert',
                    'text'  => '<p>Erfunden von Leon Battista Alberti um 1467, besteht die Scheibe aus zwei konzentrischen Ringen. Der äußere Ring trägt das Klartext-Alphabet (A–Z) in fester Reihenfolge. Der innere Ring enthält ein durch ein Schlüsselwort gemischtes Alphabet und kann auf verschiedene Startpositionen gedreht werden, den sogenannten Index.</p><p>Zur Verschlüsselung suchen Sie jeden Klartextbuchstaben im äußeren Ring und lesen den entsprechenden Buchstaben im inneren Ring bei der aktuellen Ausrichtung ab.</p>',
                ],
                'es' => [
                    'title' => 'Cómo funciona el disco de cifrado de Alberti',
                    'text'  => '<p>Inventado por Leon Battista Alberti alrededor de 1467, el disco consta de dos anillos concéntricos. El anillo exterior contiene el alfabeto del texto plano (A–Z) en orden fijo. El anillo interior contiene un alfabeto mezclado con una palabra clave y puede girarse a diferentes posiciones iniciales, llamadas índice.</p><p>Para cifrar, se encuentra cada letra del texto plano en el anillo exterior y se lee la letra correspondiente en el anillo interior en la alineación actual.</p>',
                ],
                'fr' => [
                    'title' => 'Fonctionnement du disque d\'Alberti',
                    'text'  => '<p>Inventé par Leon Battista Alberti vers 1467, le disque est composé de deux anneaux concentriques. L\'anneau extérieur porte l\'alphabet du texte clair (A–Z) dans un ordre fixe. L\'anneau intérieur contient un alphabet mélangé avec un mot-clé et peut tourner vers différentes positions de départ, appelées index.</p><p>Pour chiffrer, on trouve chaque lettre du texte clair sur l\'anneau extérieur et on lit la lettre correspondante sur l\'anneau intérieur à l\'alignement actuel.</p>',
                ],
                'it' => [
                    'title' => 'Come funziona il disco cifrante di Alberti',
                    'text'  => '<p>Inventato da Leon Battista Alberti intorno al 1467, il disco è composto da due anelli concentrici. L\'anello esterno porta l\'alfabeto del testo in chiaro (A–Z) in ordine fisso. L\'anello interno contiene un alfabeto mescolato con una parola chiave e può ruotare su diverse posizioni iniziali, chiamate indice.</p><p>Per cifrare, si trova ogni lettera del testo in chiaro nell\'anello esterno e si legge la lettera corrispondente nell\'anello interno all\'allineamento attuale.</p>',
                ],
                'pt' => [
                    'title' => 'Como funciona o disco de Alberti',
                    'text'  => '<p>Inventado por Leon Battista Alberti por volta de 1467, o disco é composto por dois anéis concêntricos. O anel externo contém o alfabeto do texto simples (A–Z) em ordem fixa. O anel interno contém um alfabeto misturado com uma palavra-chave e pode girar para diferentes posições iniciais, chamadas de índice.</p><p>Para cifrar, encontra-se cada letra do texto simples no anel externo e lê-se a letra correspondente no anel interno no alinhamento atual.</p>',
                ],
                'tr' => [
                    'title' => 'Alberti şifre diski nasıl çalışır',
                    'text'  => '<p>Leon Battista Alberti tarafından yaklaşık 1467\'de icat edilen disk, iki eş merkezli halkadan oluşur. Dış halka, sabit sırada düz metin alfabesini (A–Z) taşır. İç halka, anahtar kelimeyle karıştırılmış bir alfabeye sahiptir ve indeks olarak adlandırılan farklı başlangıç konumlarına döndürülebilir.</p><p>Şifrelemek için dış halkada her düz metin harfini bulur ve mevcut hizalamada iç halkadaki karşılık gelen harfi okursunuz.</p>',
                ],
            ],
            20 => [
                'en' => [
                    'title' => 'When to use this tool',
                    'text'  => '<p>Use this page to explore the historical Alberti cipher, test different keyword–index combinations, and understand the transition from monoalphabetic to polyalphabetic ciphers. The interactive cipher disk updates in real time as you change the keyword or starting index.</p>',
                ],
                'ru' => [
                    'title' => 'Когда использовать инструмент',
                    'text'  => '<p>Используйте эту страницу для изучения исторического шифра Альберти, проверки различных комбинаций ключ–индекс и понимания перехода от моноалфавитных к многоалфавитным шифрам. Интерактивный диск обновляется в реальном времени при изменении ключевого слова или начального индекса.</p>',
                ],
                'de' => [
                    'title' => 'Wann dieses Werkzeug nützlich ist',
                    'text'  => '<p>Verwenden Sie diese Seite, um die historische Alberti-Chiffre zu erkunden, verschiedene Schlüsselwort-Index-Kombinationen zu testen und den Übergang von mono- zu polyalphabetischen Chiffren zu verstehen. Die interaktive Scheibe aktualisiert sich in Echtzeit, wenn Sie Schlüsselwort oder Startindex ändern.</p>',
                ],
                'es' => [
                    'title' => 'Cuándo usar esta herramienta',
                    'text'  => '<p>Usa esta página para explorar el cifrado histórico de Alberti, probar diferentes combinaciones de clave e índice y comprender la transición de cifrados monoalfabéticos a polialfabéticos. El disco interactivo se actualiza en tiempo real al cambiar la clave o el índice inicial.</p>',
                ],
                'fr' => [
                    'title' => 'Quand utiliser cet outil',
                    'text'  => '<p>Utilisez cette page pour explorer le chiffre historique d\'Alberti, tester différentes combinaisons mot-clé/index et comprendre la transition des chiffres monoalphabétiques vers les polyalphabétiques. Le disque interactif se met à jour en temps réel lorsque vous modifiez le mot-clé ou l\'index de départ.</p>',
                ],
                'it' => [
                    'title' => 'Quando usare questo strumento',
                    'text'  => '<p>Usa questa pagina per esplorare il cifrario storico di Alberti, testare diverse combinazioni parola chiave-indice e comprendere la transizione dai cifrari monoalfabetici a quelli polialfabetici. Il disco interattivo si aggiorna in tempo reale quando si cambia la parola chiave o l\'indice iniziale.</p>',
                ],
                'pt' => [
                    'title' => 'Quando usar esta ferramenta',
                    'text'  => '<p>Use esta página para explorar a cifra histórica de Alberti, testar diferentes combinações de palavra-chave e índice, e entender a transição das cifras monoalfabéticas para as polialfabéticas. O disco interativo atualiza em tempo real ao alterar a palavra-chave ou o índice inicial.</p>',
                ],
                'tr' => [
                    'title' => 'Bu araç ne zaman kullanılır',
                    'text'  => '<p>Tarihi Alberti şifresini keşfetmek, farklı anahtar kelime-indeks kombinasyonlarını denemek ve tek alfabeli şifrelerden çok alfabeli şifrelere geçişi anlamak için bu sayfayı kullanın. İnteraktif disk, anahtar kelimeyi veya başlangıç indeksini değiştirdiğinizde gerçek zamanlı olarak güncellenir.</p>',
                ],
            ],
        ];
    }

    /**
     * Возвращает примеры.
     *
     * @return array<int, array{direction: string, translations: array<string, array{title: string, input: string, output: string, key: string, description: string}>}>
     */
    private function examples(): array
    {
        return [
            10 => [
                'direction'    => 'encrypt',
                'translations' => $this->translatedExample('HELLO WORLD', 'CRHHM WMPHE', 'ALBERTI', 'Alberti example'),
            ],
            20 => [
                'direction'    => 'encrypt',
                'translations' => $this->translatedExample('ATTACK AT DAWN', 'ZQQZBH ZQ RZVK', 'ZEBRAS', 'Zebras key'),
            ],
            30 => [
                'direction'    => 'decrypt',
                'translations' => $this->translatedExample('CRHHM WMPHE', 'HELLO WORLD', 'ALBERTI', 'Decode example'),
            ],
        ];
    }

    /**
     * Возвращает переводы одного примера на все языки.
     *
     * @return array<string, array{title: string, input: string, output: string, key: string, description: string}>
     */
    private function translatedExample(string $input, string $output, string $key, string $title): array
    {
        return array_fill_keys(['en', 'ru', 'de', 'es', 'fr', 'it', 'pt', 'tr'], [
            'title'       => $title,
            'input'       => $input,
            'output'      => $output,
            'key'         => $key,
            'description' => 'Alberti cipher disk example (index A).',
        ]);
    }

    /**
     * Возвращает FAQ.
     *
     * @return array<int, array<string, array{question: string, answer: string}>>
     */
    private function faqs(): array
    {
        return [
            10 => $this->translatedFaq(
                'What alphabet does the Alberti cipher use?',
                'This implementation uses the Latin alphabet A–Z. The outer ring is fixed; the inner ring is a keyword-mixed permutation of the same 26 letters. Non-Latin characters pass through unchanged.'
            ),
            20 => $this->translatedFaq(
                'What is the role of the keyword?',
                'The keyword generates the inner ring alphabet by placing its unique letters first, then appending the remaining letters of A–Z in order. An empty keyword results in a standard A–Z inner ring, making the cipher equivalent to a Caesar cipher at the chosen index.'
            ),
            30 => $this->translatedFaq(
                'What is the starting index?',
                'The starting index (A–Z) determines which outer-ring letter aligns with position 0 of the inner ring. Changing the index rotates the disk, producing a completely different substitution from the same keyword.'
            ),
            40 => $this->translatedFaq(
                'Is the Alberti cipher secure?',
                'No. As a historical single-substitution cipher it is easily broken by frequency analysis. It was, however, a significant advancement over monoalphabetic ciphers of its era because the disk can be rotated mid-message to change the substitution alphabet.'
            ),
        ];
    }

    /**
     * Возвращает переводы одного FAQ на все языки.
     *
     * @return array<string, array{question: string, answer: string}>
     */
    private function translatedFaq(string $question, string $answer): array
    {
        return array_fill_keys(['en', 'ru', 'de', 'es', 'fr', 'it', 'pt', 'tr'], [
            'question' => $question,
            'answer'   => $answer,
        ]);
    }
}
