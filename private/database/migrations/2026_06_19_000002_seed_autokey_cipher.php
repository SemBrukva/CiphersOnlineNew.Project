<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Autokey в категорию классических шифров.
 */
class SeedAutokeyCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр autokey и его переводы.
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
        $cipherId = $this->upsertCipher($categoryId, $now);

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }
    }

    /**
     * Удаляет шифр autokey и связанные с ним переводы.
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
            'DELETE FROM ' . Tables::CIPHERS
            . ' WHERE category_id = ? AND alias = ?',
            [(int) $category['id'], 'autokey']
        );
    }

    /**
     * Создаёт или обновляет запись шифра Autokey.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'autokey']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'autokey', 'api', 55, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? '
            . 'WHERE id = ?',
            ['api', 55, 1, $now, $cipherId]
        );

        return $cipherId;
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

    /**
     * Возвращает переводы для шифра Autokey.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Autokey Cipher',
                'name_short' => 'Autokey',
                'description' => 'Encrypt and decrypt text with the Autokey cipher using an initial keyword and selectable alphabet.',
                'description_stort' => 'Autokey encryption and decryption with an initial keyword.',
                'meta_title' => 'Autokey Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Autokey cipher online: choose alphabet, enter an initial keyword, encrypt or decrypt instantly.',
            ],
            'ru' => [
                'name' => 'Шифр Autokey',
                'name_short' => 'Autokey',
                'description' => 'Онлайн-инструмент для шифрования и расшифровки текста шифром Autokey с начальным ключевым словом и выбором алфавита.',
                'description_stort' => 'Шифрование и расшифровка Autokey по начальному ключу.',
                'meta_title' => 'Шифр Autokey Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр Autokey онлайн: выберите алфавит, задайте начальный ключ и получите результат мгновенно.',
            ],
            'de' => [
                'name' => 'Autokey-Chiffre',
                'name_short' => 'Autokey',
                'description' => 'Text mit der Autokey-Chiffre per Startschlüsselwort und wählbarem Alphabet ver- und entschlüsseln.',
                'description_stort' => 'Autokey-Verfahren mit Startschlüsselwort.',
                'meta_title' => 'Autokey-Chiffre Online | Ciphers Online',
                'meta_description' => 'Autokey-Chiffre online nutzen: Alphabet wählen, Startschlüssel eingeben, direkt ver- oder entschlüsseln.',
            ],
            'es' => [
                'name' => 'Cifrado Autokey',
                'name_short' => 'Autokey',
                'description' => 'Cifra y descifra texto con el cifrado Autokey usando una palabra clave inicial y alfabeto seleccionable.',
                'description_stort' => 'Cifrado Autokey con palabra clave inicial.',
                'meta_title' => 'Cifrado Autokey Online | Ciphers Online',
                'meta_description' => 'Usa Autokey online: elige alfabeto, introduce una clave inicial y cifra o descifra al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre Autokey',
                'name_short' => 'Autokey',
                'description' => 'Chiffrez et déchiffrez du texte avec le chiffre Autokey via mot-clé initial et alphabet sélectionnable.',
                'description_stort' => 'Chiffrement Autokey par mot-clé initial.',
                'meta_title' => 'Chiffre Autokey en ligne | Ciphers Online',
                'meta_description' => 'Utilisez Autokey en ligne : choisissez l’alphabet, saisissez la clé initiale et chiffrez ou déchiffrez immédiatement.',
            ],
            'it' => [
                'name' => 'Cifrario Autokey',
                'name_short' => 'Autokey',
                'description' => 'Cifra e decifra testo con il cifrario Autokey usando una parola chiave iniziale e un alfabeto selezionabile.',
                'description_stort' => 'Cifratura Autokey con parola chiave iniziale.',
                'meta_title' => 'Cifrario Autokey Online | Ciphers Online',
                'meta_description' => 'Usa Autokey online: scegli l’alfabeto, inserisci la chiave iniziale e cifra o decifra subito.',
            ],
            'pt' => [
                'name' => 'Cifra Autokey',
                'name_short' => 'Autokey',
                'description' => 'Cifre e decifre texto com a cifra Autokey usando uma palavra-chave inicial e alfabeto selecionável.',
                'description_stort' => 'Cifra Autokey com palavra-chave inicial.',
                'meta_title' => 'Cifra Autokey Online | Ciphers Online',
                'meta_description' => 'Use Autokey online: escolha o alfabeto, informe a chave inicial e cifre ou decifre instantaneamente.',
            ],
            'tr' => [
                'name' => 'Autokey Şifresi',
                'name_short' => 'Autokey',
                'description' => 'Başlangıç anahtar sözcüğü ve seçilebilir alfabe ile Autokey şifresini kullanarak metni şifreleyin veya çözün.',
                'description_stort' => 'Başlangıç anahtarıyla Autokey şifreleme ve çözme.',
                'meta_title' => 'Autokey Şifresi Online | Ciphers Online',
                'meta_description' => 'Autokey aracını online kullanın: alfabe seçin, başlangıç anahtarını girin ve anında şifreleyin ya da çözün.',
            ],
        ];
    }
}
