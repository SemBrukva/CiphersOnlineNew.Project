<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Виженера в категорию классических шифров.
 */
class SeedVigenereCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр vigenere и его переводы.
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
            [$categoryId, 'vigenere']
        );

        if ($cipher === false) {
            $cipherId = (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'vigenere', 'api', 50, 1, $now, $now]
            );
        } else {
            $cipherId = (int) $cipher['id'];

            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? '
                . 'WHERE id = ?',
                ['api', 50, 1, $now, $cipherId]
            );
        }

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }
    }

    /**
     * Удаляет шифр vigenere и связанные с ним переводы.
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
            [$categoryId, 'vigenere']
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
     * Возвращает переводы для шифра Виженера.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Vigenere Cipher',
                'name_short' => 'Vigenere',
                'description' => 'Encrypt and decrypt text with the Vigenere cipher using a keyword and selectable alphabet.',
                'description_stort' => 'Keyword-based Vigenere encryption and decryption.',
                'meta_title' => 'Vigenere Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Vigenere cipher online: choose alphabet, enter a keyword, encrypt or decrypt instantly.',
            ],
            'ru' => [
                'name' => 'Шифр Виженера',
                'name_short' => 'Виженер',
                'description' => 'Онлайн-инструмент для шифрования и расшифровки текста шифром Виженера по ключевому слову и выбранному алфавиту.',
                'description_stort' => 'Шифрование и расшифровка Виженера по ключевому слову.',
                'meta_title' => 'Шифр Виженера Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр Виженера онлайн: выберите алфавит, задайте ключ и получите результат мгновенно.',
            ],
            'de' => [
                'name' => 'Vigenere-Chiffre',
                'name_short' => 'Vigenere',
                'description' => 'Text mit der Vigenere-Chiffre per Schlüsselwort und wählbarem Alphabet ver- und entschlüsseln.',
                'description_stort' => 'Vigenere-Verfahren mit Schlüsselwort.',
                'meta_title' => 'Vigenere-Chiffre Online | Ciphers Online',
                'meta_description' => 'Vigenere-Chiffre online nutzen: Alphabet wählen, Schlüsselwort eingeben, direkt ver- oder entschlüsseln.',
            ],
            'es' => [
                'name' => 'Cifrado Vigenere',
                'name_short' => 'Vigenere',
                'description' => 'Cifra y descifra texto con el cifrado Vigenere usando una palabra clave y alfabeto seleccionable.',
                'description_stort' => 'Cifrado Vigenere con palabra clave.',
                'meta_title' => 'Cifrado Vigenere Online | Ciphers Online',
                'meta_description' => 'Usa Vigenere online: elige alfabeto, introduce palabra clave y cifra o descifra al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre de Vigenere',
                'name_short' => 'Vigenere',
                'description' => 'Chiffrez et déchiffrez du texte avec le chiffre de Vigenere via mot-clé et alphabet sélectionnable.',
                'description_stort' => 'Chiffrement Vigenere par mot-clé.',
                'meta_title' => 'Chiffre de Vigenere en ligne | Ciphers Online',
                'meta_description' => 'Utilisez Vigenere en ligne : choisissez l’alphabet, saisissez le mot-clé et chiffrez ou déchiffrez immédiatement.',
            ],
            'it' => [
                'name' => 'Cifrario Vigenere',
                'name_short' => 'Vigenere',
                'description' => 'Cifra e decifra testo con il cifrario Vigenere usando parola chiave e alfabeto selezionabile.',
                'description_stort' => 'Vigenere con parola chiave.',
                'meta_title' => 'Cifrario Vigenere Online | Ciphers Online',
                'meta_description' => 'Usa Vigenere online: scegli alfabeto, inserisci parola chiave e cifra o decifra subito.',
            ],
            'pt' => [
                'name' => 'Cifra de Vigenere',
                'name_short' => 'Vigenere',
                'description' => 'Criptografe e descriptografe texto com a cifra de Vigenere usando palavra-chave e alfabeto selecionável.',
                'description_stort' => 'Vigenere com palavra-chave.',
                'meta_title' => 'Cifra de Vigenere Online | Ciphers Online',
                'meta_description' => 'Use Vigenere online: escolha o alfabeto, informe a palavra-chave e cifre ou decifre na hora.',
            ],
            'tr' => [
                'name' => 'Vigenere Şifresi',
                'name_short' => 'Vigenere',
                'description' => 'Anahtar kelime ve seçilebilir alfabe ile Vigenere şifresi kullanarak metni şifreleyin ve çözün.',
                'description_stort' => 'Anahtar kelimeli Vigenere aracı.',
                'meta_title' => 'Vigenere Şifresi Online | Ciphers Online',
                'meta_description' => 'Vigenere aracını online kullanın: alfabeyi seçin, anahtar kelimeyi girin, hemen şifreleyin veya çözün.',
            ],
        ];
    }
}
