<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет базовые блоки, примеры и FAQ для шифра Трифид.
 */
class SeedTrifidCipherBasicContent extends Migration
{
    /**
     * Создаёт или обновляет базовый контент страницы Trifid.
     */
    public function up(): void
    {
        $cipher = $this->findCipher();

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $now      = date('Y-m-d H:i:s');

        $block1 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 10, $now);
        $block2 = $this->upsertParent(Tables::CIPHERS_BLOCKS, 'app_id', $cipherId, 20, $now);

        foreach ($this->blockTranslations() as $language => $blocks) {
            $this->upsertBlockTranslation($block1, $language, $blocks[10]['title'], $blocks[10]['text'], $now);
            $this->upsertBlockTranslation($block2, $language, $blocks[20]['title'], $blocks[20]['text'], $now);
        }

        foreach ($this->examples() as $sortOrder => $example) {
            $exampleId = $this->upsertExample($cipherId, $sortOrder, $example['direction'], $now);

            foreach ($example['translations'] as $language => $translation) {
                $this->upsertExampleTranslation(
                    $exampleId,
                    $language,
                    $translation['title'],
                    $translation['input'],
                    $translation['output'],
                    $translation['key'],
                    $translation['alphabet'],
                    $translation['description'],
                    $now
                );
            }
        }

        foreach ($this->faqs() as $sortOrder => $faq) {
            $faqId = $this->upsertParent(Tables::CIPHERS_FAQ, 'app_id', $cipherId, $sortOrder, $now, ['show_in_category' => 0]);

            foreach ($faq as $language => $translation) {
                $this->upsertFaqTranslation($faqId, $language, $translation['question'], $translation['answer'], $now);
            }
        }
    }

    /**
     * Удаляет базовый контент страницы Trifid.
     */
    public function down(): void
    {
        $cipher = $this->findCipher();

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];

        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_EXAMPLES . ' WHERE app_id = ?', [$cipherId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_FAQ . ' WHERE app_id = ?', [$cipherId]);
    }

    /**
     * Находит запись шифра Trifid.
     *
     * @return array<string, mixed>|false
     */
    private function findCipher(): array|false
    {
        return $this->db->fetch(
            'SELECT c.id FROM ' . Tables::CIPHERS . ' c '
            . 'JOIN ' . Tables::CIPHER_CATEGORIES . ' cc ON cc.id = c.category_id '
            . 'WHERE cc.alias = ? AND c.alias = ? LIMIT 1',
            ['classical-ciphers', 'trifid']
        );
    }

    /**
     * Создаёт или обновляет родительскую контентную запись.
     *
     * @param array<string, int|string> $extra Дополнительные поля.
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

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        return (int) $this->db->insert(
            'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')',
            $values
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
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS
            . ' (block_id, language, title, text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$blockId, $language, $title, $text, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод примера.
     */
    private function upsertExampleTranslation(
        int $exampleId,
        string $language,
        string $title,
        string $input,
        string $output,
        string $key,
        string $alphabet,
        string $description,
        string $now
    ): void {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS . ' WHERE example_id = ? AND language = ? LIMIT 1',
            [$exampleId, $language]
        );

        if ($row !== false) {
            $this->updateById(Tables::CIPHERS_EXAMPLES_TRANSLATIONS, (int) $row['id'], [
                'title'       => $title,
                'input'       => $input,
                'output'      => $output,
                'description' => $description,
                'key'         => $key,
            ], $now);

            return;
        }

        $columns = ['example_id', 'language', 'title', 'input', 'output', 'description', 'key', 'created_at', 'updated_at'];
        $values  = [$exampleId, $language, $title, $input, $output, $description, $key, $now, $now];

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_EXAMPLES_TRANSLATIONS
            . ' (' . implode(', ', $columns) . ') '
            . 'VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')',
            $values
        );
    }

    /**
     * Обновляет строку по id набором колонок.
     *
     * @param array<string, int|string> $columns Значения колонок.
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
            'INSERT INTO ' . Tables::CIPHERS_FAQ_TRANSLATIONS
            . ' (faq_id, language, question, answer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$faqId, $language, $question, $answer, $now, $now]
        );
    }

    /**
     * Возвращает базовые переводы блоков.
     *
     * @return array<string, array<int, array{title: string, text: string}>>
     */
    private function blockTranslations(): array
    {
        return [
            'en' => [
                10 => [
                    'title' => 'How the Trifid Cipher works',
                    'text'  => '<p>The Trifid Cipher is a classical fractionating cipher invented by Félix Delastelle, the same cryptographer who created the Bifid cipher. Instead of a 2D Polybius square, it uses a 3×3×3 Polybius cube: the 27 cells hold the alphabet letters, and each letter is identified by three coordinates — layer, row, and column. During encryption the three coordinate streams are concatenated and then regrouped into new triples, which are looked up in the cube to produce the ciphertext. This three-dimensional fractionation makes the cipher stronger than Bifid.</p>',
                ],
                20 => [
                    'title' => 'When to use this tool',
                    'text'  => '<p>Use this page to encrypt and decrypt text with the Trifid cipher, experiment with different keywords, and explore how 3D coordinate fractionation increases diffusion compared to the Bifid cipher. Supported alphabets: English (25 letters, J→I, two numeric pads), Italian (same grid), and Spanish (27 letters including ñ, no padding needed).</p>',
                ],
            ],
            'ru' => [
                10 => [
                    'title' => 'Как работает шифр Трифид',
                    'text'  => '<p>Шифр Трифид — классический фракционирующий шифр, изобретённый Феликсом Делестелем, тем же криптографом, который создал шифр Бифид. Вместо двумерного квадрата Полибия используется куб Полибия 3×3×3: 27 ячеек куба заполняются буквами алфавита, и каждая буква идентифицируется тремя координатами — слой, строка и столбец. При шифровании три потока координат конкатенируются, затем разбиваются на новые тройки, по которым снова производится поиск в кубе для получения шифротекста. Трёхмерное фракционирование делает шифр надёжнее, чем Бифид.</p>',
                ],
                20 => [
                    'title' => 'Когда использовать этот инструмент',
                    'text'  => '<p>Используйте эту страницу для шифрования и расшифровки текста шифром Трифид, экспериментируйте с различными ключевыми словами и изучайте, как трёхмерное фракционирование координат увеличивает диффузию по сравнению с шифром Бифид. Поддерживаемые алфавиты: английский (25 букв, J→I, два цифровых заполнителя), итальянский (та же сетка) и испанский (27 букв, включая ñ, без заполнителей).</p>',
                ],
            ],
            'de' => [
                10 => [
                    'title' => 'Wie die Trifid-Chiffre funktioniert',
                    'text'  => '<p>Die Trifid-Chiffre ist eine klassische fraktionierende Chiffre, erfunden von Félix Delastelle, demselben Kryptografen, der auch die Bifid-Chiffre entwickelte. Statt eines 2D-Polybios-Quadrats wird ein 3×3×3-Polybios-Würfel verwendet: Die 27 Zellen enthalten die Buchstaben des Alphabets, und jeder Buchstabe wird durch drei Koordinaten — Ebene, Zeile und Spalte — identifiziert. Beim Verschlüsseln werden die drei Koordinatenströme zusammengefasst und in neue Tripel aufgeteilt, die im Würfel nachgeschlagen werden. Diese dreidimensionale Fraktionierung macht die Chiffre stärker als Bifid.</p>',
                ],
                20 => [
                    'title' => 'Wann dieses Tool nützlich ist',
                    'text'  => '<p>Nutzen Sie diese Seite zum Ver- und Entschlüsseln von Texten mit der Trifid-Chiffre, zum Ausprobieren verschiedener Schlüsselwörter und zum Erkunden, wie die 3D-Koordinatenfraktionierung die Diffusion gegenüber Bifid erhöht. Unterstützte Alphabete: Englisch (25 Buchstaben, J→I, zwei Füllziffern), Italienisch (gleiche Raster) und Spanisch (27 Buchstaben einschließlich ñ, kein Auffüllen nötig).</p>',
                ],
            ],
            'es' => [
                10 => [
                    'title' => 'Cómo funciona el cifrado Trifid',
                    'text'  => '<p>El cifrado Trifid es un cifrado de transposición fraccionada clásico inventado por Félix Delastelle, el mismo criptógrafo que creó el cifrado Bifid. En lugar de un cuadrado de Polibio 2D, utiliza un cubo de Polibio 3×3×3: las 27 celdas contienen las letras del alfabeto, y cada letra se identifica mediante tres coordenadas — capa, fila y columna. Durante el cifrado, los tres flujos de coordenadas se concatenan y se reagrupan en nuevas tripletas que se buscan en el cubo para producir el texto cifrado. Esta fraccionación tridimensional hace el cifrado más fuerte que Bifid.</p>',
                ],
                20 => [
                    'title' => 'Cuándo usar esta herramienta',
                    'text'  => '<p>Usa esta página para cifrar y descifrar texto con Trifid, experimentar con distintas palabras clave y explorar cómo la fraccionación de coordenadas 3D aumenta la difusión respecto a Bifid. Alfabetos admitidos: inglés (25 letras, J→I, dos dígitos de relleno), italiano (misma cuadrícula) y español (27 letras incluyendo ñ, sin relleno).</p>',
                ],
            ],
            'fr' => [
                10 => [
                    'title' => 'Fonctionnement du chiffre de Trifid',
                    'text'  => "<p>Le chiffre de Trifid est un chiffre de transposition fractionnelle classique inventé par Félix Delastelle, le même cryptographe qui a créé le chiffre de Bifid. Au lieu d'un carré de Polybe 2D, il utilise un cube de Polybe 3×3×3 : les 27 cellules contiennent les lettres de l'alphabet, et chaque lettre est identifiée par trois coordonnées — couche, ligne et colonne. Lors du chiffrement, les trois flux de coordonnées sont concaténés et regroupés en nouveaux triplets qui sont recherchés dans le cube. Ce fractionnement tridimensionnel rend le chiffre plus fort que Bifid.</p>",
                ],
                20 => [
                    'title' => 'Quand utiliser cet outil',
                    'text'  => "<p>Utilisez cette page pour chiffrer et déchiffrer du texte avec Trifid, tester différents mots-clés et explorer comment le fractionnement de coordonnées 3D augmente la diffusion par rapport à Bifid. Alphabets pris en charge : anglais (25 lettres, J→I, deux chiffres de rembourrage), italien (même grille) et espagnol (27 lettres dont ñ, sans rembourrage).</p>",
                ],
            ],
            'it' => [
                10 => [
                    'title' => 'Come funziona il cifrario Trifid',
                    'text'  => "<p>Il cifrario Trifid è un cifrario di trasposizione frazionante classico inventato da Félix Delastelle, lo stesso crittografo che creò il cifrario Bifid. Invece di un quadrato di Polibio 2D, utilizza un cubo di Polibio 3×3×3: le 27 celle contengono le lettere dell'alfabeto, e ogni lettera è identificata da tre coordinate — strato, riga e colonna. Durante la cifratura, i tre flussi di coordinate vengono concatenati e raggruppati in nuove triplette che vengono cercate nel cubo. Questa frazionazione tridimensionale rende il cifrario più robusto di Bifid.</p>",
                ],
                20 => [
                    'title' => 'Quando usare questo strumento',
                    'text'  => "<p>Usa questa pagina per cifrare e decifrare testo con Trifid, sperimentare con diverse parole chiave ed esplorare come la frazionazione delle coordinate 3D aumenta la diffusione rispetto a Bifid. Alfabeti supportati: inglese (25 lettere, J→I, due cifre di riempimento), italiano (stessa griglia) e spagnolo (27 lettere inclusa ñ, senza riempimento).</p>",
                ],
            ],
            'pt' => [
                10 => [
                    'title' => 'Como a cifra Trifid funciona',
                    'text'  => '<p>A cifra Trifid é uma cifra de transposição fracionada clássica inventada por Félix Delastelle, o mesmo criptógrafo que criou a cifra Bifid. Em vez de um quadrado de Polibio 2D, utiliza um cubo de Polibio 3×3×3: as 27 células contêm as letras do alfabeto, e cada letra é identificada por três coordenadas — camada, linha e coluna. Durante a cifragem, os três fluxos de coordenadas são concatenados e reagrupados em novas tripletas que são pesquisadas no cubo. Essa fracionação tridimensional torna a cifra mais forte do que a Bifid.</p>',
                ],
                20 => [
                    'title' => 'Quando usar esta ferramenta',
                    'text'  => '<p>Use esta página para cifrar e decifrar texto com Trifid, experimentar diferentes palavras-chave e explorar como o fracionamento de coordenadas 3D aumenta a difusão em comparação com a cifra Bifid. Alfabetos suportados: inglês (25 letras, J→I, dois dígitos de preenchimento), italiano (mesma grade) e espanhol (27 letras incluindo ñ, sem preenchimento).</p>',
                ],
            ],
            'tr' => [
                10 => [
                    'title' => 'Trifid Şifresi nasıl çalışır',
                    'text'  => '<p>Trifid Şifresi, Bifid şifresini de icat eden kriptograf Félix Delastelle tarafından yaratılmış klasik bir fraksiyonlama transpozisyon şifresidir. 2B Polybius karesi yerine 3×3×3 Polybius küpü kullanır: küpün 27 hücresi alfabe harflerini içerir ve her harf üç koordinatla — katman, satır ve sütun — tanımlanır. Şifreleme sırasında üç koordinat akışı birleştirilir ve yeni üçlülere ayrılır; bu üçlüler küpte aranarak şifreli metin üretilir. Bu üç boyutlu fraksiyonlama, şifreyi Bifid\'den daha güçlü kılar.</p>',
                ],
                20 => [
                    'title' => 'Bu araç ne zaman kullanılır',
                    'text'  => '<p>Bu sayfayı Trifid ile metin şifrelemek ve çözmek, farklı anahtar kelimeler denemek ve 3B koordinat fraksiyonlamasının Bifid\'e kıyasla yayılımı nasıl artırdığını keşfetmek için kullanın. Desteklenen alfabeler: İngilizce (25 harf, J→I, iki dolgu rakamı), İtalyanca (aynı ızgara) ve İspanyolca (ñ dahil 27 harf, dolgu gerekmez).</p>',
                ],
            ],
        ];
    }

    /**
     * Возвращает базовые примеры.
     *
     * Пример 10: шифрование HELLO/KEYWORD (en) → FFOF1.
     * Пример 20: расшифровка FFOF1/KEYWORD (en) → HELLO.
     * Пример 30: шифрование HOLA/CLAVE (es) → KACN.
     *
     * Значения шифротекста вычислены через TrifidCipherService.
     *
     * @return array<int, array{direction: string, translations: array<string, array{title: string, input: string, output: string, key: string, alphabet: string, description: string}>}>
     */
    private function examples(): array
    {
        $examples = [
            10 => ['direction' => 'encrypt', 'translations' => []],
            20 => ['direction' => 'decrypt', 'translations' => []],
            30 => ['direction' => 'encrypt', 'translations' => []],
        ];

        $titles = [
            'en' => ['enc' => 'Encrypt with Trifid',    'dec' => 'Decrypt with Trifid',    'es_enc' => 'Spanish alphabet example'],
            'ru' => ['enc' => 'Шифрование Трифид',      'dec' => 'Расшифровка Трифид',     'es_enc' => 'Пример с испанским алфавитом'],
            'de' => ['enc' => 'Trifid verschlüsseln',   'dec' => 'Trifid entschlüsseln',   'es_enc' => 'Beispiel mit spanischem Alphabet'],
            'es' => ['enc' => 'Cifrar con Trifid',      'dec' => 'Descifrar con Trifid',   'es_enc' => 'Ejemplo con alfabeto español'],
            'fr' => ['enc' => 'Chiffrer avec Trifid',   'dec' => 'Déchiffrer avec Trifid', 'es_enc' => 'Exemple avec alphabet espagnol'],
            'it' => ['enc' => 'Cifrare con Trifid',     'dec' => 'Decifrare con Trifid',   'es_enc' => 'Esempio con alfabeto spagnolo'],
            'pt' => ['enc' => 'Cifrar com Trifid',      'dec' => 'Decifrar com Trifid',    'es_enc' => 'Exemplo com alfabeto espanhol'],
            'tr' => ['enc' => 'Trifid ile şifrele',     'dec' => 'Trifid ile çöz',         'es_enc' => 'İspanyolca alfabe örneği'],
        ];

        $desc = [
            'en' => [
                'enc'    => 'Basic Trifid encryption example using English alphabet (J→I, two numeric pads).',
                'dec'    => 'Decryption using the same keyword and English alphabet.',
                'es_enc' => 'Trifid example with Spanish alphabet — 27 letters including ñ fit the 3×3×3 cube exactly.',
            ],
            'ru' => [
                'enc'    => 'Базовый пример шифрования Трифид с английским алфавитом (J→I, два цифровых заполнителя).',
                'dec'    => 'Расшифровка с тем же ключевым словом и английским алфавитом.',
                'es_enc' => 'Пример Трифид с испанским алфавитом — 27 букв, включая ñ, точно заполняют куб 3×3×3.',
            ],
            'de' => [
                'enc'    => 'Einfaches Trifid-Verschlüsselungsbeispiel mit englischem Alphabet (J→I, zwei Füllziffern).',
                'dec'    => 'Entschlüsselung mit demselben Schlüsselwort und englischem Alphabet.',
                'es_enc' => 'Trifid-Beispiel mit spanischem Alphabet — 27 Buchstaben einschließlich ñ passen genau in den 3×3×3-Würfel.',
            ],
            'es' => [
                'enc'    => 'Ejemplo básico de cifrado Trifid con alfabeto inglés (J→I, dos dígitos de relleno).',
                'dec'    => 'Descifrado con la misma palabra clave y alfabeto inglés.',
                'es_enc' => 'Ejemplo Trifid con el alfabeto español — 27 letras incluyendo ñ encajan exactamente en el cubo 3×3×3.',
            ],
            'fr' => [
                'enc'    => "Exemple de chiffrement Trifid de base avec l'alphabet anglais (J→I, deux chiffres de rembourrage).",
                'dec'    => "Déchiffrement avec le même mot-clé et l'alphabet anglais.",
                'es_enc' => "Exemple Trifid avec l'alphabet espagnol — 27 lettres dont ñ remplissent exactement le cube 3×3×3.",
            ],
            'it' => [
                'enc'    => 'Esempio di cifratura Trifid di base con alfabeto inglese (J→I, due cifre di riempimento).',
                'dec'    => 'Decifratura con la stessa parola chiave e alfabeto inglese.',
                'es_enc' => 'Esempio Trifid con alfabeto spagnolo — 27 lettere inclusa ñ riempiono esattamente il cubo 3×3×3.',
            ],
            'pt' => [
                'enc'    => 'Exemplo básico de cifra Trifid com alfabeto inglês (J→I, dois dígitos de preenchimento).',
                'dec'    => 'Decifragem com a mesma palavra-chave e alfabeto inglês.',
                'es_enc' => 'Exemplo Trifid com alfabeto espanhol — 27 letras incluindo ñ cabem exatamente no cubo 3×3×3.',
            ],
            'tr' => [
                'enc'    => 'İngilizce alfabeyle temel Trifid şifreleme örneği (J→I, iki dolgu rakamı).',
                'dec'    => 'Aynı anahtar kelime ve İngilizce alfabeyle çözme.',
                'es_enc' => 'İspanyolca alfabe ile Trifid örneği — ñ dahil 27 harf 3×3×3 küpü tam olarak doldurur.',
            ],
        ];

        foreach (array_keys($titles) as $language) {
            // Пример 10: шифрование HELLO+KEYWORD → FFOF1 (EN)
            $examples[10]['translations'][$language] = [
                'title'       => $titles[$language]['enc'],
                'input'       => 'HELLO',
                'output'      => 'FFOF1',
                'key'         => 'KEYWORD',
                'alphabet'    => 'en',
                'description' => $desc[$language]['enc'],
            ];

            // Пример 20: расшифровка FFOF1+KEYWORD → HELLO (EN)
            $examples[20]['translations'][$language] = [
                'title'       => $titles[$language]['dec'],
                'input'       => 'FFOF1',
                'output'      => 'HELLO',
                'key'         => 'KEYWORD',
                'alphabet'    => 'en',
                'description' => $desc[$language]['dec'],
            ];

            // Пример 30: шифрование HOLA+CLAVE → KACN (ES, 27 букв без заполнителей)
            $examples[30]['translations'][$language] = [
                'title'       => $titles[$language]['es_enc'],
                'input'       => 'HOLA',
                'output'      => 'KACN',
                'key'         => 'CLAVE',
                'alphabet'    => 'es',
                'description' => $desc[$language]['es_enc'],
            ];
        }

        return $examples;
    }

    /**
     * Возвращает базовые FAQ.
     *
     * @return array<int, array<string, array{question: string, answer: string}>>
     */
    private function faqs(): array
    {
        return [
            10 => $this->localizedFaq(
                'What is the Trifid Cipher?',
                'Что такое шифр Трифид?',
                'The Trifid Cipher is a classical fractionating cipher invented by Félix Delastelle. It uses a 3×3×3 Polybius cube to represent each letter as three coordinates, then mixes those coordinates across the message to achieve stronger diffusion than the Bifid cipher.',
                'Шифр Трифид — классический фракционирующий шифр, изобретённый Феликсом Делестелем. Он использует куб Полибия 3×3×3 для представления каждой буквы тремя координатами, а затем перемешивает эти координаты по всему сообщению, достигая более сильной диффузии, чем шифр Бифид.'
            ),
            20 => $this->localizedFaq(
                'How is Trifid different from Bifid?',
                'Чем Трифид отличается от Бифида?',
                'Bifid uses a 2D Polybius square giving each letter two coordinates. Trifid uses a 3D Polybius cube giving each letter three coordinates. The three-stream coordinate mixing achieves greater diffusion, making Trifid harder to cryptanalyse than Bifid.',
                'Бифид использует двумерный квадрат Полибия, давая каждой букве две координаты. Трифид использует трёхмерный куб Полибия, давая каждой букве три координаты. Перемешивание трёх потоков координат обеспечивает большую диффузию, делая Трифид труднее для криптоанализа, чем Бифид.'
            ),
            30 => $this->localizedFaq(
                'Why does the ciphertext sometimes contain digits?',
                'Почему шифротекст иногда содержит цифры?',
                'For English and Italian alphabets, two numeric pad characters (1 and 2) fill the 3×3×3 cube to exactly 27 positions. When the recombined coordinate triples map to those padding positions, digits appear in the ciphertext. They are correctly handled during decryption.',
                'Для английского и итальянского алфавитов два цифровых заполнителя (1 и 2) дополняют куб 3×3×3 до ровно 27 позиций. Когда перекомбинированные тройки координат указывают на позиции заполнителей, в шифротексте появляются цифры. При расшифровке они корректно обрабатываются.'
            ),
            40 => $this->localizedFaq(
                'Do I need the same key to decrypt?',
                'Нужен ли тот же ключ для расшифровки?',
                'Yes. You must use the same keyword and the same alphabet as during encryption. The keyword determines the order of letters inside the 3×3×3 cube.',
                'Да. Для расшифровки необходимо использовать то же ключевое слово и тот же алфавит, что и при шифровании. Ключевое слово определяет порядок букв внутри куба 3×3×3.'
            ),
        ];
    }

    /**
     * Создаёт FAQ для всех локалей с базовыми en/ru текстами.
     *
     * @return array<string, array{question: string, answer: string}>
     */
    private function localizedFaq(string $englishQuestion, string $russianQuestion, string $englishAnswer, string $russianAnswer): array
    {
        $result = [];
        foreach (['en', 'de', 'es', 'fr', 'it', 'pt', 'tr'] as $language) {
            $result[$language] = ['question' => $englishQuestion, 'answer' => $englishAnswer];
        }

        $result['ru'] = ['question' => $russianQuestion, 'answer' => $russianAnswer];

        return $result;
    }
}
