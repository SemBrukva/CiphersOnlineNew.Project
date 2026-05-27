<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Плейфера в категорию классических шифров.
 */
class SeedPlayfairCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр playfair и его переводы.
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
            [$categoryId, 'playfair']
        );

        if ($cipher === false) {
            $cipherId = (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'playfair', 'api', 20, 1, $now, $now]
            );
        } else {
            $cipherId = (int) $cipher['id'];

            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? '
                . 'WHERE id = ?',
                ['api', 20, 1, $now, $cipherId]
            );
        }

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }
    }

    /**
     * Удаляет шифр playfair и связанные с ним переводы.
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
            'DELETE FROM ' . Tables::CIPHERS
            . ' WHERE category_id = ? AND alias = ?',
            [$categoryId, 'playfair']
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
            'SELECT id FROM ' . Tables::CIPHERS_TRANSLATIONS
            . ' WHERE app_id = ? AND language = ? LIMIT 1',
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
     * Возвращает переводы для шифра Плейфера.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Playfair Cipher',
                'name_short' => 'Playfair',
                'description' => 'Encrypt and decrypt text with the Playfair cipher using a keyword and selectable alphabet.',
                'description_stort' => 'Keyword-based Playfair encryption and decryption.',
                'meta_title' => 'Playfair Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Playfair cipher online: choose alphabet, enter keyword, encrypt or decrypt instantly.',
            ],
            'ru' => [
                'name' => 'Шифр Плейфера',
                'name_short' => 'Плейфер',
                'description' => 'Онлайн-инструмент для шифрования и расшифровки текста шифром Плейфера с ключевым словом и выбором алфавита.',
                'description_stort' => 'Шифрование и расшифровка Плейфера по ключу.',
                'meta_title' => 'Шифр Плейфера Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр Плейфера онлайн: выберите алфавит, задайте ключевое слово и получите результат мгновенно.',
            ],
            'de' => [
                'name' => 'Playfair-Chiffre',
                'name_short' => 'Playfair',
                'description' => 'Text mit der Playfair-Chiffre per Schlüsselwort und wählbarem Alphabet ver- und entschlüsseln.',
                'description_stort' => 'Playfair-Verfahren mit Schlüsselwort.',
                'meta_title' => 'Playfair-Chiffre Online | Ciphers Online',
                'meta_description' => 'Playfair-Chiffre online nutzen: Alphabet wählen, Schlüsselwort eingeben, direkt ver- oder entschlüsseln.',
            ],
            'es' => [
                'name' => 'Cifrado Playfair',
                'name_short' => 'Playfair',
                'description' => 'Cifra y descifra texto con el cifrado Playfair usando una palabra clave y alfabeto seleccionable.',
                'description_stort' => 'Cifrado Playfair con palabra clave.',
                'meta_title' => 'Cifrado Playfair Online | Ciphers Online',
                'meta_description' => 'Usa Playfair online: elige alfabeto, introduce palabra clave y cifra o descifra al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre de Playfair',
                'name_short' => 'Playfair',
                'description' => 'Chiffrez et déchiffrez du texte avec le chiffre de Playfair via mot-clé et alphabet sélectionnable.',
                'description_stort' => 'Chiffrement Playfair par mot-clé.',
                'meta_title' => 'Chiffre de Playfair en ligne | Ciphers Online',
                'meta_description' => 'Utilisez Playfair en ligne : choisissez l’alphabet, saisissez le mot-clé et chiffrez ou déchiffrez immédiatement.',
            ],
            'it' => [
                'name' => 'Cifrario Playfair',
                'name_short' => 'Playfair',
                'description' => 'Cifra e decifra testo con il cifrario Playfair usando parola chiave e alfabeto selezionabile.',
                'description_stort' => 'Playfair con parola chiave.',
                'meta_title' => 'Cifrario Playfair Online | Ciphers Online',
                'meta_description' => 'Usa Playfair online: scegli alfabeto, inserisci parola chiave e cifra o decifra subito.',
            ],
            'pt' => [
                'name' => 'Cifra de Playfair',
                'name_short' => 'Playfair',
                'description' => 'Criptografe e descriptografe texto com a cifra de Playfair usando palavra-chave e alfabeto selecionável.',
                'description_stort' => 'Playfair com palavra-chave.',
                'meta_title' => 'Cifra de Playfair Online | Ciphers Online',
                'meta_description' => 'Use Playfair online: escolha o alfabeto, informe a palavra-chave e cifre ou decifre na hora.',
            ],
            'tr' => [
                'name' => 'Playfair Şifresi',
                'name_short' => 'Playfair',
                'description' => 'Anahtar kelime ve seçilebilir alfabe ile Playfair şifresi kullanarak metni şifreleyin ve çözün.',
                'description_stort' => 'Anahtar kelimeli Playfair aracı.',
                'meta_title' => 'Playfair Şifresi Online | Ciphers Online',
                'meta_description' => 'Playfair aracını online kullanın: alfabeyi seçin, anahtar kelimeyi girin, hemen şifreleyin veya çözün.',
            ],
        ];
    }
}
