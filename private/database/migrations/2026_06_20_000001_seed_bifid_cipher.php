<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Бифид (Bifid) в категорию классических шифров.
 */
class SeedBifidCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр Bifid и его переводы.
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
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }
    }

    /**
     * Удаляет шифр Bifid и связанные с ним переводы.
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
            [(int) $category['id'], 'bifid']
        );
    }

    /**
     * Создаёт или обновляет запись шифра Bifid.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'bifid']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'bifid', 'api', 57, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 57, 1, $now, $cipherId]
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
     * Возвращает переводы для шифра Bifid.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'             => 'Bifid Cipher',
                'name_short'       => 'Bifid',
                'description'      => 'Encrypt and decrypt text with the Bifid cipher — a classical fractionating transposition cipher that combines a 5×5 Polybius square with coordinate splitting.',
                'description_stort' => 'Bifid encryption and decryption using a 5×5 Polybius square.',
                'meta_title'       => 'Bifid Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Bifid cipher online: enter a keyword, encrypt or decrypt English text instantly using coordinate fractionation.',
            ],
            'ru' => [
                'name'             => 'Шифр Бифид',
                'name_short'       => 'Бифид',
                'description'      => 'Шифрование и расшифровка текста шифром Бифид — классическим фракционирующим шифром на основе квадрата Полибия 5×5 с разбиением координат.',
                'description_stort' => 'Шифрование и расшифровка Бифид с квадратом Полибия 5×5.',
                'meta_title'       => 'Шифр Бифид Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр Бифид онлайн: введите ключевое слово и мгновенно зашифруйте или расшифруйте текст методом фракционирования координат.',
            ],
            'de' => [
                'name'             => 'Bifid-Chiffre',
                'name_short'       => 'Bifid',
                'description'      => 'Text mit der Bifid-Chiffre ver- und entschlüsseln — einem klassischen fraktionierenden Transpositions-Chiffre auf Basis eines 5×5-Polybios-Quadrats.',
                'description_stort' => 'Bifid-Verfahren mit 5×5-Polybios-Quadrat und Schlüsselwort.',
                'meta_title'       => 'Bifid-Chiffre Online | Ciphers Online',
                'meta_description' => 'Bifid-Chiffre online nutzen: Schlüsselwort eingeben, Text sofort ver- oder entschlüsseln mit Koordinatenfraktionierung.',
            ],
            'es' => [
                'name'             => 'Cifrado Bifid',
                'name_short'       => 'Bifid',
                'description'      => 'Cifra y descifra texto con el cifrado Bifid — un cifrado de transposición fraccionado clásico basado en un cuadrado de Polibio 5×5.',
                'description_stort' => 'Cifrado Bifid con cuadrado de Polibio 5×5 y palabra clave.',
                'meta_title'       => 'Cifrado Bifid Online | Ciphers Online',
                'meta_description' => 'Usa Bifid online: introduce una palabra clave y cifra o descifra texto al instante mediante fraccionamiento de coordenadas.',
            ],
            'fr' => [
                'name'             => 'Chiffre de Bifid',
                'name_short'       => 'Bifid',
                'description'      => 'Chiffrez et déchiffrez du texte avec le chiffre de Bifid — un chiffre de transposition fractionnel classique basé sur un carré de Polybe 5×5.',
                'description_stort' => 'Chiffrement Bifid avec carré de Polybe 5×5 et mot-clé.',
                'meta_title'       => 'Chiffre de Bifid en ligne | Ciphers Online',
                'meta_description' => 'Utilisez Bifid en ligne : saisissez un mot-clé et chiffrez ou déchiffrez du texte instantanément par fractionnement des coordonnées.',
            ],
            'it' => [
                'name'             => 'Cifrario Bifid',
                'name_short'       => 'Bifid',
                'description'      => 'Cifra e decifra testo con il cifrario Bifid — un cifrario di trasposizione frazionante classico basato su un quadrato di Polibio 5×5.',
                'description_stort' => 'Cifratura Bifid con quadrato di Polibio 5×5 e parola chiave.',
                'meta_title'       => 'Cifrario Bifid Online | Ciphers Online',
                'meta_description' => 'Usa Bifid online: inserisci una parola chiave e cifra o decifra testo istantaneamente tramite frazionamento delle coordinate.',
            ],
            'pt' => [
                'name'             => 'Cifra Bifid',
                'name_short'       => 'Bifid',
                'description'      => 'Cifre e decifre texto com a cifra Bifid — uma cifra de transposição fracionada clássica baseada em um quadrado de Polibio 5×5.',
                'description_stort' => 'Cifra Bifid com quadrado de Polibio 5×5 e palavra-chave.',
                'meta_title'       => 'Cifra Bifid Online | Ciphers Online',
                'meta_description' => 'Use Bifid online: informe uma palavra-chave e cifre ou decifre texto instantaneamente por fracionamento de coordenadas.',
            ],
            'tr' => [
                'name'             => 'Bifid Şifresi',
                'name_short'       => 'Bifid',
                'description'      => 'Bifid şifresiyle metin şifreleyin veya çözün — 5×5 Polybius karesine dayalı koordinat fraksiyonlaması kullanan klasik bir transpozisyon şifresi.',
                'description_stort' => 'Anahtar kelimeli 5×5 Polybius karesiyle Bifid şifreleme ve çözme.',
                'meta_title'       => 'Bifid Şifresi Online | Ciphers Online',
                'meta_description' => 'Bifid şifresini online kullanın: anahtar kelime girin ve koordinat fraksiyonlamasıyla metni anında şifreleyin ya da çözün.',
            ],
        ];
    }
}
