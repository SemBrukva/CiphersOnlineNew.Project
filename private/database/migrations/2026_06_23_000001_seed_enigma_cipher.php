<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет симулятор шифровальной машины Enigma и базовый контент страницы.
 */
class SeedEnigmaCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр Enigma, блоки, примеры и FAQ.
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
     * Удаляет шифр Enigma вместе с контентом.
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
            [(int) $category['id'], 'enigma']
        );
    }

    /**
     * Создаёт или обновляет запись шифра Enigma.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'enigma']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'enigma', 'api', 60, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 60, 1, $now, $cipherId]
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
                'name'              => 'Enigma Machine Simulator',
                'name_short'        => 'Enigma',
                'description'       => 'Historically accurate online Enigma I (Wehrmacht, M3) simulator: choose rotors I–V, reflector UKW-B/C, ring settings, starting positions and plugboard.',
                'description_stort' => 'Online Enigma I simulator with rotors, reflector and plugboard.',
                'meta_title'        => 'Enigma Machine Simulator Online | Ciphers Online',
                'meta_description'  => 'Online simulator of the German Enigma I cipher machine: configure rotors, reflector, ring settings, positions and plugboard to encrypt or decrypt text.',
            ],
            'ru' => [
                'name'              => 'Симулятор Энигмы',
                'name_short'        => 'Энигма',
                'description'       => 'Исторически точный онлайн-симулятор машины Энигма I (Вермахт, M3): выбирайте роторы I–V, рефлектор UKW-B/C, кольца, начальные позиции и plugboard.',
                'description_stort' => 'Онлайн-симулятор Энигмы I с роторами, рефлектором и plugboard.',
                'meta_title'        => 'Симулятор машины Энигма онлайн | Ciphers Online',
                'meta_description'  => 'Онлайн-симулятор немецкой шифровальной машины Энигма I: настройте роторы, рефлектор, кольца, позиции и plugboard для шифрования и расшифровки.',
            ],
            'de' => [
                'name'              => 'Enigma-Maschinen-Simulator',
                'name_short'        => 'Enigma',
                'description'       => 'Historisch genauer Online-Simulator der Enigma I (Wehrmacht, M3): Walzen I–V, Reflektor UKW-B/C, Ringstellung, Grundstellung und Steckerbrett.',
                'description_stort' => 'Online-Simulator der Enigma I mit Walzen, Reflektor und Steckerbrett.',
                'meta_title'        => 'Enigma-Maschinen-Simulator Online | Ciphers Online',
                'meta_description'  => 'Online-Simulator der deutschen Chiffriermaschine Enigma I: Walzen, Reflektor, Ringstellung, Position und Steckerbrett konfigurieren.',
            ],
            'es' => [
                'name'              => 'Simulador de la máquina Enigma',
                'name_short'        => 'Enigma',
                'description'       => 'Simulador online históricamente preciso de la Enigma I (Wehrmacht, M3): rotores I–V, reflector UKW-B/C, anillos, posiciones iniciales y plugboard.',
                'description_stort' => 'Simulador online de la Enigma I con rotores, reflector y plugboard.',
                'meta_title'        => 'Simulador de Enigma online | Ciphers Online',
                'meta_description'  => 'Simulador online de la máquina de cifrado alemana Enigma I: configura rotores, reflector, anillos, posiciones y plugboard.',
            ],
            'fr' => [
                'name'              => 'Simulateur Enigma',
                'name_short'        => 'Enigma',
                'description'       => 'Simulateur en ligne historiquement exact de l\'Enigma I (Wehrmacht, M3) : rotors I–V, réflecteur UKW-B/C, anneaux, positions de départ et tableau de connexions.',
                'description_stort' => 'Simulateur en ligne d\'Enigma I avec rotors, réflecteur et plugboard.',
                'meta_title'        => 'Simulateur de la machine Enigma en ligne | Ciphers Online',
                'meta_description'  => 'Simulateur en ligne de la machine à chiffrer allemande Enigma I : configurez rotors, réflecteur, anneaux, positions et tableau de connexions.',
            ],
            'it' => [
                'name'              => 'Simulatore della macchina Enigma',
                'name_short'        => 'Enigma',
                'description'       => 'Simulatore online storicamente accurato dell\'Enigma I (Wehrmacht, M3): rotori I–V, riflettore UKW-B/C, anelli, posizioni iniziali e plugboard.',
                'description_stort' => 'Simulatore online dell\'Enigma I con rotori, riflettore e plugboard.',
                'meta_title'        => 'Simulatore Enigma Online | Ciphers Online',
                'meta_description'  => 'Simulatore online della macchina cifrante tedesca Enigma I: configura rotori, riflettore, anelli, posizioni e plugboard.',
            ],
            'pt' => [
                'name'              => 'Simulador da máquina Enigma',
                'name_short'        => 'Enigma',
                'description'       => 'Simulador online historicamente preciso da Enigma I (Wehrmacht, M3): rotores I–V, refletor UKW-B/C, anéis, posições iniciais e plugboard.',
                'description_stort' => 'Simulador online da Enigma I com rotores, refletor e plugboard.',
                'meta_title'        => 'Simulador da máquina Enigma online | Ciphers Online',
                'meta_description'  => 'Simulador online da máquina de cifra alemã Enigma I: configure rotores, refletor, anéis, posições e plugboard.',
            ],
            'tr' => [
                'name'              => 'Enigma Makinesi Simülatörü',
                'name_short'        => 'Enigma',
                'description'       => 'Tarihi doğrulukta çevrimiçi Enigma I (Wehrmacht, M3) simülatörü: rotorlar I–V, reflektör UKW-B/C, halkalar, başlangıç konumları ve plugboard.',
                'description_stort' => 'Rotorlar, reflektör ve plugboard ile çevrimiçi Enigma I simülatörü.',
                'meta_title'        => 'Enigma Makinesi Simülatörü Online | Ciphers Online',
                'meta_description'  => 'Alman şifre makinesi Enigma I\'in çevrimiçi simülatörü: rotorları, reflektörü, halkaları, konumları ve plugboard\'u yapılandırın.',
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
                    'title' => 'How the Enigma machine works',
                    'text'  => '<p>The Enigma machine is an electromechanical rotor cipher used by Nazi Germany before and during World War II. Each key press routes the electrical signal through a plugboard, three rotating rotors, a reflector and back through the rotors and plugboard, lighting a different letter on the lampboard.</p><p>Because of the reflector, no letter is ever encrypted to itself, and the same machine configuration both encrypts and decrypts. The rotors advance with each key press — sometimes two at once due to the famous «double-stepping» anomaly — so the substitution alphabet changes with every letter.</p>',
                ],
                'ru' => [
                    'title' => 'Как работает машина Энигма',
                    'text'  => '<p>Энигма — электромеханическая роторная шифровальная машина, использовавшаяся нацистской Германией перед Второй мировой войной и во время неё. При каждом нажатии клавиши электрический сигнал проходит через коммутационную панель, три вращающихся ротора, рефлектор и обратно через роторы и панель, зажигая другую букву на ламповой доске.</p><p>Из-за рефлектора буква никогда не шифруется в саму себя, а одинаковые настройки машины и шифруют, и расшифровывают сообщение. После каждого нажатия роторы продвигаются — иногда сразу два из-за знаменитой аномалии «двойного шага» — поэтому алфавит замены меняется с каждой буквой.</p>',
                ],
                'de' => [
                    'title' => 'Wie die Enigma-Maschine funktioniert',
                    'text'  => '<p>Die Enigma ist eine elektromechanische Rotor-Chiffriermaschine, die von Nazi-Deutschland vor und während des Zweiten Weltkriegs verwendet wurde. Bei jedem Tastendruck läuft das elektrische Signal durch das Steckerbrett, drei rotierende Walzen, einen Reflektor und zurück durch Walzen und Steckerbrett.</p><p>Durch den Reflektor wird kein Buchstabe auf sich selbst abgebildet, und dieselbe Maschineneinstellung ver- und entschlüsselt. Die Walzen drehen sich bei jedem Tastendruck weiter — wegen der bekannten «Doppelschritt»-Anomalie manchmal zwei gleichzeitig.</p>',
                ],
                'es' => [
                    'title' => 'Cómo funciona la máquina Enigma',
                    'text'  => '<p>La Enigma es una máquina de cifrado de rotores electromecánica utilizada por la Alemania nazi antes y durante la Segunda Guerra Mundial. Cada pulsación enruta la señal eléctrica a través del plugboard, tres rotores giratorios, un reflector y de vuelta por los rotores y el plugboard.</p><p>Gracias al reflector ninguna letra se cifra a sí misma y la misma configuración cifra y descifra. Los rotores avanzan con cada pulsación — a veces dos a la vez por la famosa anomalía del «doble paso».</p>',
                ],
                'fr' => [
                    'title' => 'Fonctionnement de la machine Enigma',
                    'text'  => '<p>L\'Enigma est une machine à chiffrer à rotors électromécanique utilisée par l\'Allemagne nazie avant et pendant la Seconde Guerre mondiale. Chaque frappe envoie le signal électrique à travers le tableau de connexions, trois rotors rotatifs, un réflecteur, puis retour par les rotors et le tableau.</p><p>Grâce au réflecteur, aucune lettre ne se chiffre en elle-même et la même configuration permet à la fois de chiffrer et de déchiffrer. Les rotors avancent à chaque frappe — parfois deux à la fois, à cause de la célèbre anomalie du «double pas».</p>',
                ],
                'it' => [
                    'title' => 'Come funziona la macchina Enigma',
                    'text'  => '<p>L\'Enigma è una macchina cifrante a rotori elettromeccanica usata dalla Germania nazista prima e durante la Seconda guerra mondiale. Ogni pressione di tasto invia il segnale elettrico attraverso il plugboard, tre rotori rotanti, un riflettore e di nuovo attraverso rotori e plugboard.</p><p>Grazie al riflettore nessuna lettera viene cifrata in sé stessa e la stessa configurazione cifra e decifra. I rotori avanzano a ogni pressione — talvolta due insieme per la famosa anomalia del «doppio scatto».</p>',
                ],
                'pt' => [
                    'title' => 'Como funciona a máquina Enigma',
                    'text'  => '<p>A Enigma é uma máquina de cifra de rotores eletromecânica utilizada pela Alemanha nazista antes e durante a Segunda Guerra Mundial. Cada tecla pressionada faz o sinal elétrico passar pelo plugboard, três rotores rotativos, um refletor e novamente pelos rotores e plugboard.</p><p>Devido ao refletor, nenhuma letra é cifrada nela mesma e a mesma configuração cifra e decifra. Os rotores avançam a cada tecla — às vezes dois ao mesmo tempo, devido à conhecida anomalia do «duplo passo».</p>',
                ],
                'tr' => [
                    'title' => 'Enigma makinesi nasıl çalışır',
                    'text'  => '<p>Enigma, Nazi Almanyası tarafından İkinci Dünya Savaşı öncesi ve sırasında kullanılan elektromekanik bir rotor şifre makinesidir. Her tuşa basıldığında elektrik sinyali plugboard\'dan, üç döner rotordan, bir reflektörden geçer ve aynı yoldan geri döner.</p><p>Reflektör sayesinde hiçbir harf kendisine şifrelenmez ve aynı ayarlar hem şifreler hem çözer. Her tuşa basıldığında rotorlar ilerler — meşhur «çift adım» anomalisi nedeniyle bazen iki rotor aynı anda döner.</p>',
                ],
            ],
            20 => [
                'en' => [
                    'title' => 'Configuring the simulator',
                    'text'  => '<p>This simulator models the standard <strong>Enigma I</strong> machine with five available rotors (<strong>I, II, III, IV, V</strong>) and two reflectors (<strong>UKW-B</strong> and <strong>UKW-C</strong>). For each of the three rotor slots (left, middle, right) you select a rotor, a <em>ring setting</em> (Ringstellung) and a <em>starting position</em> (Grundstellung).</p><p>The optional <strong>plugboard</strong> (Steckerbrett) accepts pairs of letters separated by spaces, e.g. <code>AB CD EF</code>. A historical operator typically used 10 pairs. Each letter can appear in at most one pair and cannot be paired with itself.</p>',
                ],
                'ru' => [
                    'title' => 'Настройка симулятора',
                    'text'  => '<p>Симулятор моделирует стандартную машину <strong>Enigma I</strong> с пятью доступными роторами (<strong>I, II, III, IV, V</strong>) и двумя рефлекторами (<strong>UKW-B</strong> и <strong>UKW-C</strong>). Для каждого из трёх слотов (левый, средний, правый) выбирается ротор, <em>кольцевая установка</em> (Ringstellung) и <em>начальная позиция</em> (Grundstellung).</p><p>Необязательная <strong>коммутационная панель</strong> (Steckerbrett) принимает пары букв через пробел, например <code>AB CD EF</code>. Исторически использовалось около 10 пар. Каждая буква может входить максимум в одну пару и не может быть соединена с самой собой.</p>',
                ],
                'de' => [
                    'title' => 'Den Simulator konfigurieren',
                    'text'  => '<p>Dieser Simulator modelliert die Standardmaschine <strong>Enigma I</strong> mit fünf verfügbaren Walzen (<strong>I, II, III, IV, V</strong>) und zwei Reflektoren (<strong>UKW-B</strong> und <strong>UKW-C</strong>). Für jeden der drei Walzenplätze (links, Mitte, rechts) wählen Sie eine Walze, eine <em>Ringstellung</em> und eine <em>Grundstellung</em>.</p><p>Das optionale <strong>Steckerbrett</strong> akzeptiert Buchstabenpaare durch Leerzeichen getrennt, z.B. <code>AB CD EF</code>. Historisch wurden meist 10 Paare verwendet.</p>',
                ],
                'es' => [
                    'title' => 'Configurar el simulador',
                    'text'  => '<p>Este simulador reproduce la máquina estándar <strong>Enigma I</strong> con cinco rotores disponibles (<strong>I, II, III, IV, V</strong>) y dos reflectores (<strong>UKW-B</strong> y <strong>UKW-C</strong>). Para cada una de las tres ranuras eliges un rotor, un <em>ajuste de anillo</em> (Ringstellung) y una <em>posición inicial</em> (Grundstellung).</p><p>El <strong>plugboard</strong> opcional acepta pares de letras separados por espacios, p. ej. <code>AB CD EF</code>. Históricamente se usaban 10 pares.</p>',
                ],
                'fr' => [
                    'title' => 'Configurer le simulateur',
                    'text'  => '<p>Ce simulateur reproduit la machine standard <strong>Enigma I</strong> avec cinq rotors disponibles (<strong>I, II, III, IV, V</strong>) et deux réflecteurs (<strong>UKW-B</strong> et <strong>UKW-C</strong>). Pour chacun des trois emplacements de rotor, vous choisissez un rotor, un <em>réglage d\'anneau</em> (Ringstellung) et une <em>position initiale</em> (Grundstellung).</p><p>Le <strong>tableau de connexions</strong> facultatif accepte des paires de lettres séparées par des espaces, ex. <code>AB CD EF</code>. Historiquement, on utilisait environ 10 paires.</p>',
                ],
                'it' => [
                    'title' => 'Configurare il simulatore',
                    'text'  => '<p>Questo simulatore riproduce la macchina standard <strong>Enigma I</strong> con cinque rotori disponibili (<strong>I, II, III, IV, V</strong>) e due riflettori (<strong>UKW-B</strong> e <strong>UKW-C</strong>). Per ciascuna delle tre posizioni si sceglie un rotore, un <em>anello</em> (Ringstellung) e una <em>posizione iniziale</em> (Grundstellung).</p><p>Il <strong>plugboard</strong> opzionale accetta coppie di lettere separate da spazi, ad es. <code>AB CD EF</code>. Storicamente venivano usate 10 coppie.</p>',
                ],
                'pt' => [
                    'title' => 'Configurando o simulador',
                    'text'  => '<p>Este simulador reproduz a máquina padrão <strong>Enigma I</strong> com cinco rotores disponíveis (<strong>I, II, III, IV, V</strong>) e dois refletores (<strong>UKW-B</strong> e <strong>UKW-C</strong>). Para cada um dos três slots você escolhe um rotor, um <em>ajuste de anel</em> (Ringstellung) e uma <em>posição inicial</em> (Grundstellung).</p><p>O <strong>plugboard</strong> opcional aceita pares de letras separados por espaços, ex.: <code>AB CD EF</code>. Historicamente eram usados 10 pares.</p>',
                ],
                'tr' => [
                    'title' => 'Simülatörü yapılandırma',
                    'text'  => '<p>Bu simülatör, beş rotor (<strong>I, II, III, IV, V</strong>) ve iki reflektör (<strong>UKW-B</strong> ve <strong>UKW-C</strong>) ile standart <strong>Enigma I</strong> makinesini modeller. Üç rotor yuvasının her biri için bir rotor, <em>halka ayarı</em> (Ringstellung) ve <em>başlangıç konumu</em> (Grundstellung) seçersiniz.</p><p>İsteğe bağlı <strong>plugboard</strong>, boşlukla ayrılmış harf çiftlerini kabul eder; örn. <code>AB CD EF</code>. Tarihsel olarak yaklaşık 10 çift kullanılırdı.</p>',
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
                'translations' => $this->translatedExample(
                    'HELLO WORLD',
                    'ILBDA AMTAZ',
                    'I,II,III|A,A,A|A,A,A|B|',
                    'Default settings',
                    'Rotors I-II-III, reflector UKW-B, rings A-A-A, positions A-A-A, no plugboard.'
                ),
            ],
            20 => [
                'direction'    => 'encrypt',
                'translations' => $this->translatedExample(
                    'ATTACK AT DAWN',
                    'VXLLMV EL MTDA',
                    'I,II,III|A,A,A|M,C,K|B|AB CD EF',
                    'With plugboard',
                    'Rotors I-II-III, positions M-C-K, plugboard AB CD EF (3 pairs).'
                ),
            ],
            30 => [
                'direction'    => 'decrypt',
                'translations' => $this->translatedExample(
                    'ILBDA AMTAZ',
                    'HELLO WORLD',
                    'I,II,III|A,A,A|A,A,A|B|',
                    'Decrypt example',
                    'Same settings as example 1 — Enigma is reciprocal.'
                ),
            ],
        ];
    }

    /**
     * Возвращает переводы одного примера на все языки.
     *
     * @return array<string, array{title: string, input: string, output: string, key: string, description: string}>
     */
    private function translatedExample(string $input, string $output, string $key, string $title, string $description): array
    {
        return array_fill_keys(['en', 'ru', 'de', 'es', 'fr', 'it', 'pt', 'tr'], [
            'title'       => $title,
            'input'       => $input,
            'output'      => $output,
            'key'         => $key,
            'description' => $description,
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
                'Which Enigma model is simulated?',
                'This is the standard Enigma I (M3) used by the Wehrmacht. It has three rotor slots and ships with rotors I–V and reflectors UKW-B and UKW-C. The naval M4 with a fourth (thin) rotor is not included.'
            ),
            20 => $this->translatedFaq(
                'Why does encryption equal decryption?',
                'The reflector at the end of the signal path sends the current back through the rotors along a different route. This makes every wiring symmetric: if A encrypts to D, then D encrypts back to A with the same settings. As a consequence, no letter is ever encrypted to itself.'
            ),
            30 => $this->translatedFaq(
                'What is the «double-stepping» anomaly?',
                'Normally the right rotor steps with every key press and triggers the middle rotor when it passes its notch. But the middle rotor itself also steps when its position is at the notch, taking the left rotor along — even if the right rotor would not have triggered it. This historical mechanical quirk is faithfully reproduced.'
            ),
            40 => $this->translatedFaq(
                'How do I configure the plugboard?',
                'Enter pairs of letters separated by spaces, dashes or commas, e.g. AB CD EF. Each letter may appear in only one pair and cannot be paired with itself. Up to 13 pairs are allowed. Leave the field empty to disable the plugboard.'
            ),
            50 => $this->translatedFaq(
                'Is the Enigma secure today?',
                'No. Modern computers brute-force any historic Enigma key in seconds. Even in WWII the cipher was broken by Polish and British cryptographers (Marian Rejewski, Alan Turing and others) using captured material, message indicators and statistical attacks at Bletchley Park. The simulator is intended for education and exploration.'
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
