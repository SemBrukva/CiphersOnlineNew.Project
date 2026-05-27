<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Вернама в категорию классических шифров.
 */
class SeedVernamCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр vernam и его переводы.
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
            [$categoryId, 'vernam']
        );

        if ($cipher === false) {
            $cipherId = (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'vernam', 'api', 60, 1, $now, $now]
            );
        } else {
            $cipherId = (int) $cipher['id'];

            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? '
                . 'WHERE id = ?',
                ['api', 60, 1, $now, $cipherId]
            );
        }

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }
    }

    /**
     * Удаляет шифр vernam и связанные с ним переводы.
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
            [$categoryId, 'vernam']
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
     * Возвращает переводы для шифра Вернама.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Vernam Cipher',
                'name_short' => 'Vernam',
                'description' => 'Encrypt and decrypt text with the Vernam cipher using a key. Encrypted output is returned in Base64 format.',
                'description_stort' => 'Byte-wise XOR Vernam cipher with Base64 output.',
                'meta_title' => 'Vernam Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Vernam cipher online: enter text and key to encrypt or decrypt instantly.',
            ],
            'ru' => [
                'name' => 'Шифр Вернама',
                'name_short' => 'Вернам',
                'description' => 'Онлайн-инструмент шифра Вернама: побайтовый XOR по ключу с выдачей шифротекста в формате Base64.',
                'description_stort' => 'Побайтовый XOR-шифр Вернама с Base64-результатом.',
                'meta_title' => 'Шифр Вернама Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр Вернама онлайн: введите текст и ключ для мгновенного шифрования или расшифровки.',
            ],
            'de' => [
                'name' => 'Vernam-Chiffre',
                'name_short' => 'Vernam',
                'description' => 'Text mit der Vernam-Chiffre per Schlüssel ver- und entschlüsseln. Die Ausgabe beim Verschlüsseln erfolgt als Base64.',
                'description_stort' => 'Byteweiser XOR-Vernam mit Base64-Ausgabe.',
                'meta_title' => 'Vernam-Chiffre Online | Ciphers Online',
                'meta_description' => 'Vernam-Chiffre online nutzen: Text und Schlüssel eingeben, sofort ver- oder entschlüsseln.',
            ],
            'es' => [
                'name' => 'Cifrado Vernam',
                'name_short' => 'Vernam',
                'description' => 'Cifra y descifra texto con el cifrado Vernam usando una clave. La salida cifrada se devuelve en Base64.',
                'description_stort' => 'Vernam XOR por bytes con salida Base64.',
                'meta_title' => 'Cifrado Vernam Online | Ciphers Online',
                'meta_description' => 'Usa Vernam online: introduce texto y clave para cifrar o descifrar al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre de Vernam',
                'name_short' => 'Vernam',
                'description' => 'Chiffrez et déchiffrez du texte avec le chiffre de Vernam via une clé. Le résultat chiffré est renvoyé en Base64.',
                'description_stort' => 'Vernam XOR octet par octet avec sortie Base64.',
                'meta_title' => 'Chiffre de Vernam en ligne | Ciphers Online',
                'meta_description' => 'Utilisez Vernam en ligne : saisissez texte et clé pour chiffrer ou déchiffrer immédiatement.',
            ],
            'it' => [
                'name' => 'Cifrario Vernam',
                'name_short' => 'Vernam',
                'description' => 'Cifra e decifra testo con il cifrario Vernam usando una chiave. L’output cifrato viene restituito in Base64.',
                'description_stort' => 'Vernam XOR byte per byte con output Base64.',
                'meta_title' => 'Cifrario Vernam Online | Ciphers Online',
                'meta_description' => 'Usa Vernam online: inserisci testo e chiave per cifrare o decifrare subito.',
            ],
            'pt' => [
                'name' => 'Cifra de Vernam',
                'name_short' => 'Vernam',
                'description' => 'Criptografe e descriptografe texto com a cifra de Vernam usando uma chave. O resultado cifrado é retornado em Base64.',
                'description_stort' => 'Vernam XOR byte a byte com saída Base64.',
                'meta_title' => 'Cifra de Vernam Online | Ciphers Online',
                'meta_description' => 'Use Vernam online: informe texto e chave para cifrar ou decifrar na hora.',
            ],
            'tr' => [
                'name' => 'Vernam Şifresi',
                'name_short' => 'Vernam',
                'description' => 'Vernam şifresiyle metni anahtar kullanarak şifreleyin ve çözün. Şifreleme çıktısı Base64 olarak döner.',
                'description_stort' => 'Base64 çıktılı bayt düzeyinde XOR Vernam aracı.',
                'meta_title' => 'Vernam Şifresi Online | Ciphers Online',
                'meta_description' => 'Vernam aracını online kullanın: metin ve anahtar girerek anında şifreleyin veya çözün.',
            ],
        ];
    }
}
