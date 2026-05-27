<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр A1Z26 в категорию классических шифров.
 */
class SeedA1z26Cipher extends Migration
{
    /**
     * Создаёт или обновляет шифр a1z26 и его переводы.
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
        $now = date('Y-m-d H:i:s');

        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'a1z26']
        );

        if ($cipher === false) {
            $cipherId = (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'a1z26', 'api', 90, 1, $now, $now]
            );
        } else {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                ['api', 90, 1, $now, $cipherId]
            );
        }

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }
    }

    /**
     * Удаляет шифр a1z26 и связанные с ним переводы.
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

        $categoryId = (int) $category['id'];
        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ?',
            [$categoryId, 'a1z26']
        );
    }

    /**
     * Создаёт или обновляет перевод шифра.
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
     * Возвращает переводы для шифра A1Z26.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'A1Z26 Cipher',
                'name_short' => 'A1Z26',
                'description' => 'Convert letters to their alphabet positions and back (A1Z26) with selectable alphabet and delimiter.',
                'description_stort' => 'Letter-position conversion (A1Z26).',
                'meta_title' => 'A1Z26 Cipher Online | Ciphers Online',
                'meta_description' => 'Use A1Z26 online: encode text as numbers or decode numbers back to letters.',
            ],
            'ru' => [
                'name' => 'Шифр A1Z26',
                'name_short' => 'A1Z26',
                'description' => 'Преобразование букв в номера алфавита и обратно (A1Z26) с выбором алфавита и разделителя.',
                'description_stort' => 'Преобразование позиций букв A1Z26.',
                'meta_title' => 'Шифр A1Z26 Онлайн | Ciphers Online',
                'meta_description' => 'Используйте A1Z26 онлайн: кодируйте текст числами или декодируйте числа обратно в буквы.',
            ],
            'de' => [
                'name' => 'A1Z26-Chiffre',
                'name_short' => 'A1Z26',
                'description' => 'Buchstaben in Alphabetpositionen und zurück umwandeln (A1Z26) mit wählbarem Alphabet und Trennzeichen.',
                'description_stort' => 'A1Z26-Buchstabenpositionsumwandlung.',
                'meta_title' => 'A1Z26-Chiffre Online | Ciphers Online',
                'meta_description' => 'A1Z26 online nutzen: Text in Zahlen kodieren oder Zahlen zurück in Buchstaben dekodieren.',
            ],
            'es' => [
                'name' => 'Cifrado A1Z26',
                'name_short' => 'A1Z26',
                'description' => 'Convierte letras a posiciones del alfabeto y viceversa (A1Z26) con alfabeto y delimitador seleccionables.',
                'description_stort' => 'Conversión de posiciones A1Z26.',
                'meta_title' => 'Cifrado A1Z26 Online | Ciphers Online',
                'meta_description' => 'Usa A1Z26 online: codifica texto como números o decodifica números a letras.',
            ],
            'fr' => [
                'name' => 'Chiffre A1Z26',
                'name_short' => 'A1Z26',
                'description' => 'Convertissez les lettres en positions alphabétiques et inversement (A1Z26) avec alphabet et séparateur sélectionnables.',
                'description_stort' => 'Conversion de positions A1Z26.',
                'meta_title' => 'Chiffre A1Z26 en ligne | Ciphers Online',
                'meta_description' => 'Utilisez A1Z26 en ligne : encodez le texte en nombres ou décodez les nombres en lettres.',
            ],
            'it' => [
                'name' => 'Cifrario A1Z26',
                'name_short' => 'A1Z26',
                'description' => 'Converte lettere in posizioni alfabetiche e viceversa (A1Z26) con alfabeto e delimitatore selezionabili.',
                'description_stort' => 'Conversione posizionale A1Z26.',
                'meta_title' => 'Cifrario A1Z26 Online | Ciphers Online',
                'meta_description' => 'Usa A1Z26 online: codifica testo in numeri o decodifica numeri in lettere.',
            ],
            'pt' => [
                'name' => 'Cifra A1Z26',
                'name_short' => 'A1Z26',
                'description' => 'Converta letras em posições do alfabeto e vice-versa (A1Z26) com alfabeto e delimitador selecionáveis.',
                'description_stort' => 'Conversão de posições A1Z26.',
                'meta_title' => 'Cifra A1Z26 Online | Ciphers Online',
                'meta_description' => 'Use A1Z26 online: codifique texto em números ou decodifique números em letras.',
            ],
            'tr' => [
                'name' => 'A1Z26 Şifresi',
                'name_short' => 'A1Z26',
                'description' => 'Harfleri alfabe pozisyonlarına ve geri dönüştürün (A1Z26); alfabe ve ayırıcı seçebilirsiniz.',
                'description_stort' => 'A1Z26 harf-pozisyon dönüşümü.',
                'meta_title' => 'A1Z26 Şifresi Online | Ciphers Online',
                'meta_description' => 'A1Z26 aracını online kullanın: metni sayı olarak kodlayın veya sayıları harflere geri çözün.',
            ],
        ];
    }
}
