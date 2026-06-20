<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Трифид (Trifid) в категорию классических шифров.
 */
class SeedTrifidCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр Trifid и его переводы.
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
     * Удаляет шифр Trifid и связанные с ним переводы.
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
            [(int) $category['id'], 'trifid']
        );
    }

    /**
     * Создаёт или обновляет запись шифра Trifid.
     */
    private function upsertCipher(int $categoryId, string $now): int
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE category_id = ? AND alias = ? LIMIT 1',
            [$categoryId, 'trifid']
        );

        if ($cipher === false) {
            return (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'trifid', 'api', 58, 1, $now, $now]
            );
        }

        $cipherId = (int) $cipher['id'];
        $this->db->execute(
            'UPDATE ' . Tables::CIPHERS
            . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? WHERE id = ?',
            ['api', 58, 1, $now, $cipherId]
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
     * Возвращает переводы для шифра Trifid.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name'              => 'Trifid Cipher',
                'name_short'        => 'Trifid',
                'description'       => 'Encrypt and decrypt text with the Trifid cipher — a classical fractionating cipher that uses a 3×3×3 Polybius cube to split each letter into three coordinates.',
                'description_stort' => 'Trifid encryption and decryption using a 3×3×3 Polybius cube.',
                'meta_title'        => 'Trifid Cipher Online | Ciphers Online',
                'meta_description'  => 'Use the Trifid cipher online: enter a keyword, encrypt or decrypt text instantly using a 3×3×3 Polybius cube and coordinate fractionation.',
            ],
            'ru' => [
                'name'              => 'Шифр Трифид',
                'name_short'        => 'Трифид',
                'description'       => 'Шифрование и расшифровка текста шифром Трифид — классическим фракционирующим шифром на основе куба Полибия 3×3×3, разбивающего каждую букву на три координаты.',
                'description_stort' => 'Шифрование и расшифровка Трифид с кубом Полибия 3×3×3.',
                'meta_title'        => 'Шифр Трифид Онлайн | Ciphers Online',
                'meta_description'  => 'Используйте шифр Трифид онлайн: введите ключевое слово и мгновенно зашифруйте или расшифруйте текст с помощью куба Полибия 3×3×3.',
            ],
            'de' => [
                'name'              => 'Trifid-Chiffre',
                'name_short'        => 'Trifid',
                'description'       => 'Text mit der Trifid-Chiffre ver- und entschlüsseln — einem klassischen fraktionierenden Chiffre auf Basis eines 3×3×3-Polybios-Würfels, der jeden Buchstaben in drei Koordinaten zerlegt.',
                'description_stort' => 'Trifid-Verfahren mit 3×3×3-Polybios-Würfel und Schlüsselwort.',
                'meta_title'        => 'Trifid-Chiffre Online | Ciphers Online',
                'meta_description'  => 'Trifid-Chiffre online nutzen: Schlüsselwort eingeben, Text sofort ver- oder entschlüsseln mit 3×3×3-Polybios-Würfel.',
            ],
            'es' => [
                'name'              => 'Cifrado Trifid',
                'name_short'        => 'Trifid',
                'description'       => 'Cifra y descifra texto con el cifrado Trifid — un cifrado de transposición fraccionado clásico basado en un cubo de Polibio 3×3×3 que divide cada letra en tres coordenadas.',
                'description_stort' => 'Cifrado Trifid con cubo de Polibio 3×3×3 y palabra clave.',
                'meta_title'        => 'Cifrado Trifid Online | Ciphers Online',
                'meta_description'  => 'Usa Trifid online: introduce una palabra clave y cifra o descifra texto al instante mediante un cubo de Polibio 3×3×3.',
            ],
            'fr' => [
                'name'              => 'Chiffre de Trifid',
                'name_short'        => 'Trifid',
                'description'       => 'Chiffrez et déchiffrez du texte avec le chiffre de Trifid — un chiffre de transposition fractionnel classique basé sur un cube de Polybe 3×3×3 qui décompose chaque lettre en trois coordonnées.',
                'description_stort' => 'Chiffrement Trifid avec cube de Polybe 3×3×3 et mot-clé.',
                'meta_title'        => 'Chiffre de Trifid en ligne | Ciphers Online',
                'meta_description'  => 'Utilisez Trifid en ligne : saisissez un mot-clé et chiffrez ou déchiffrez du texte instantanément avec un cube de Polybe 3×3×3.',
            ],
            'it' => [
                'name'              => 'Cifrario Trifid',
                'name_short'        => 'Trifid',
                'description'       => 'Cifra e decifra testo con il cifrario Trifid — un cifrario di trasposizione frazionante classico basato su un cubo di Polibio 3×3×3 che scompone ogni lettera in tre coordinate.',
                'description_stort' => 'Cifratura Trifid con cubo di Polibio 3×3×3 e parola chiave.',
                'meta_title'        => 'Cifrario Trifid Online | Ciphers Online',
                'meta_description'  => 'Usa Trifid online: inserisci una parola chiave e cifra o decifra testo istantaneamente tramite un cubo di Polibio 3×3×3.',
            ],
            'pt' => [
                'name'              => 'Cifra Trifid',
                'name_short'        => 'Trifid',
                'description'       => 'Cifre e decifre texto com a cifra Trifid — uma cifra de transposição fracionada clássica baseada em um cubo de Polibio 3×3×3 que divide cada letra em três coordenadas.',
                'description_stort' => 'Cifra Trifid com cubo de Polibio 3×3×3 e palavra-chave.',
                'meta_title'        => 'Cifra Trifid Online | Ciphers Online',
                'meta_description'  => 'Use Trifid online: informe uma palavra-chave e cifre ou decifre texto instantaneamente com um cubo de Polibio 3×3×3.',
            ],
            'tr' => [
                'name'              => 'Trifid Şifresi',
                'name_short'        => 'Trifid',
                'description'       => 'Trifid şifresiyle metin şifreleyin veya çözün — her harfi üç koordinata bölen 3×3×3 Polybius küpüne dayalı klasik bir fraksiyonlama şifresi.',
                'description_stort' => 'Anahtar kelimeli 3×3×3 Polybius küpüyle Trifid şifreleme ve çözme.',
                'meta_title'        => 'Trifid Şifresi Online | Ciphers Online',
                'meta_description'  => 'Trifid şifresini online kullanın: anahtar kelime girin ve 3×3×3 Polybius küpüyle metni anında şifreleyin ya da çözün.',
            ],
        ];
    }
}
