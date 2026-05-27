<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Атбаш в категорию классических шифров.
 */
class SeedAtbashCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр atbash и его переводы.
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
            [$categoryId, 'atbash']
        );

        if ($cipher === false) {
            $cipherId = (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'atbash', 'api', 80, 1, $now, $now]
            );
        } else {
            $cipherId = (int) $cipher['id'];
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
                ['api', 80, 1, $now, $cipherId]
            );
        }

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }
    }

    /**
     * Удаляет шифр atbash и связанные с ним переводы.
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
            [$categoryId, 'atbash']
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
     * Возвращает переводы для шифра Атбаш.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Atbash Cipher',
                'name_short' => 'Atbash',
                'description' => 'Encrypt and decrypt text with the Atbash cipher by mirroring letters across the selected alphabet.',
                'description_stort' => 'Alphabet mirroring with Atbash.',
                'meta_title' => 'Atbash Cipher Online | Ciphers Online',
                'meta_description' => 'Use Atbash cipher online: choose alphabet and transform text instantly in both directions.',
            ],
            'ru' => [
                'name' => 'Шифр Атбаш',
                'name_short' => 'Атбаш',
                'description' => 'Онлайн-инструмент шифра Атбаш: отражение букв относительно конца выбранного алфавита.',
                'description_stort' => 'Зеркальное преобразование букв по Атбашу.',
                'meta_title' => 'Шифр Атбаш Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр Атбаш онлайн: выберите алфавит и мгновенно преобразуйте текст в обе стороны.',
            ],
            'de' => [
                'name' => 'Atbash-Chiffre',
                'name_short' => 'Atbash',
                'description' => 'Text mit der Atbash-Chiffre durch Spiegelung der Buchstaben im gewählten Alphabet ver- und entschlüsseln.',
                'description_stort' => 'Alphabet-Spiegelung mit Atbash.',
                'meta_title' => 'Atbash-Chiffre Online | Ciphers Online',
                'meta_description' => 'Atbash-Chiffre online nutzen: Alphabet wählen und Text sofort in beide Richtungen umwandeln.',
            ],
            'es' => [
                'name' => 'Cifrado Atbash',
                'name_short' => 'Atbash',
                'description' => 'Cifra y descifra texto con Atbash reflejando letras dentro del alfabeto seleccionado.',
                'description_stort' => 'Reflejo de alfabeto con Atbash.',
                'meta_title' => 'Cifrado Atbash Online | Ciphers Online',
                'meta_description' => 'Usa Atbash online: elige alfabeto y transforma texto al instante en ambos sentidos.',
            ],
            'fr' => [
                'name' => 'Chiffre Atbash',
                'name_short' => 'Atbash',
                'description' => 'Chiffrez et déchiffrez du texte avec Atbash en reflétant les lettres dans l’alphabet sélectionné.',
                'description_stort' => 'Miroir alphabétique Atbash.',
                'meta_title' => 'Chiffre Atbash en ligne | Ciphers Online',
                'meta_description' => 'Utilisez Atbash en ligne : choisissez l’alphabet et transformez le texte immédiatement dans les deux sens.',
            ],
            'it' => [
                'name' => 'Cifrario Atbash',
                'name_short' => 'Atbash',
                'description' => 'Cifra e decifra testo con Atbash riflettendo le lettere nell’alfabeto selezionato.',
                'description_stort' => 'Riflesso alfabetico Atbash.',
                'meta_title' => 'Cifrario Atbash Online | Ciphers Online',
                'meta_description' => 'Usa Atbash online: scegli alfabeto e trasforma il testo subito in entrambe le direzioni.',
            ],
            'pt' => [
                'name' => 'Cifra Atbash',
                'name_short' => 'Atbash',
                'description' => 'Criptografe e descriptografe texto com Atbash espelhando letras no alfabeto selecionado.',
                'description_stort' => 'Espelhamento alfabético Atbash.',
                'meta_title' => 'Cifra Atbash Online | Ciphers Online',
                'meta_description' => 'Use Atbash online: escolha o alfabeto e transforme o texto instantaneamente em ambos os sentidos.',
            ],
            'tr' => [
                'name' => 'Atbash Şifresi',
                'name_short' => 'Atbash',
                'description' => 'Atbash ile metni seçilen alfabede harfleri ayna mantığıyla eşleyerek şifreleyin ve çözün.',
                'description_stort' => 'Atbash ile alfabetik aynalama.',
                'meta_title' => 'Atbash Şifresi Online | Ciphers Online',
                'meta_description' => 'Atbash aracını online kullanın: alfabeyi seçin ve metni anında iki yönde dönüştürün.',
            ],
        ];
    }
}
