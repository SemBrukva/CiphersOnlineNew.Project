<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Бофора в категорию классических шифров.
 */
class SeedBeaufortCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр beaufort и его переводы.
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
            [$categoryId, 'beaufort']
        );

        if ($cipher === false) {
            $cipherId = (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'beaufort', 'api', 30, 1, $now, $now]
            );
        } else {
            $cipherId = (int) $cipher['id'];

            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? '
                . 'WHERE id = ?',
                ['api', 30, 1, $now, $cipherId]
            );
        }

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }
    }

    /**
     * Удаляет шифр beaufort и связанные с ним переводы.
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
            [$categoryId, 'beaufort']
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
     * Возвращает переводы для шифра Бофора.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Beaufort Cipher',
                'name_short' => 'Beaufort',
                'description' => 'Encrypt and decrypt text with the Beaufort cipher using a keyword and selectable alphabet.',
                'description_stort' => 'Keyword-based Beaufort encryption and decryption.',
                'meta_title' => 'Beaufort Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Beaufort cipher online: choose alphabet, enter keyword, encrypt or decrypt instantly.',
            ],
            'ru' => [
                'name' => 'Шифр Бофора',
                'name_short' => 'Бофор',
                'description' => 'Онлайн-инструмент для шифрования и расшифровки текста шифром Бофора с ключевым словом и выбором алфавита.',
                'description_stort' => 'Шифрование и расшифровка Бофора по ключу.',
                'meta_title' => 'Шифр Бофора Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр Бофора онлайн: выберите алфавит, задайте ключевое слово и получите результат мгновенно.',
            ],
            'de' => [
                'name' => 'Beaufort-Chiffre',
                'name_short' => 'Beaufort',
                'description' => 'Text mit der Beaufort-Chiffre per Schlüsselwort und wählbarem Alphabet ver- und entschlüsseln.',
                'description_stort' => 'Beaufort-Verfahren mit Schlüsselwort.',
                'meta_title' => 'Beaufort-Chiffre Online | Ciphers Online',
                'meta_description' => 'Beaufort-Chiffre online nutzen: Alphabet wählen, Schlüsselwort eingeben, direkt ver- oder entschlüsseln.',
            ],
            'es' => [
                'name' => 'Cifrado Beaufort',
                'name_short' => 'Beaufort',
                'description' => 'Cifra y descifra texto con el cifrado Beaufort usando una palabra clave y alfabeto seleccionable.',
                'description_stort' => 'Cifrado Beaufort con palabra clave.',
                'meta_title' => 'Cifrado Beaufort Online | Ciphers Online',
                'meta_description' => 'Usa Beaufort online: elige alfabeto, introduce palabra clave y cifra o descifra al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre de Beaufort',
                'name_short' => 'Beaufort',
                'description' => 'Chiffrez et déchiffrez du texte avec le chiffre de Beaufort via mot-clé et alphabet sélectionnable.',
                'description_stort' => 'Chiffrement Beaufort par mot-clé.',
                'meta_title' => 'Chiffre de Beaufort en ligne | Ciphers Online',
                'meta_description' => 'Utilisez Beaufort en ligne : choisissez l’alphabet, saisissez le mot-clé et chiffrez ou déchiffrez immédiatement.',
            ],
            'it' => [
                'name' => 'Cifrario Beaufort',
                'name_short' => 'Beaufort',
                'description' => 'Cifra e decifra testo con il cifrario Beaufort usando parola chiave e alfabeto selezionabile.',
                'description_stort' => 'Beaufort con parola chiave.',
                'meta_title' => 'Cifrario Beaufort Online | Ciphers Online',
                'meta_description' => 'Usa Beaufort online: scegli alfabeto, inserisci parola chiave e cifra o decifra subito.',
            ],
            'pt' => [
                'name' => 'Cifra de Beaufort',
                'name_short' => 'Beaufort',
                'description' => 'Criptografe e descriptografe texto com a cifra de Beaufort usando palavra-chave e alfabeto selecionável.',
                'description_stort' => 'Beaufort com palavra-chave.',
                'meta_title' => 'Cifra de Beaufort Online | Ciphers Online',
                'meta_description' => 'Use Beaufort online: escolha o alfabeto, informe a palavra-chave e cifre ou decifre na hora.',
            ],
            'tr' => [
                'name' => 'Beaufort Şifresi',
                'name_short' => 'Beaufort',
                'description' => 'Anahtar kelime ve seçilebilir alfabe ile Beaufort şifresi kullanarak metni şifreleyin ve çözün.',
                'description_stort' => 'Anahtar kelimeli Beaufort aracı.',
                'meta_title' => 'Beaufort Şifresi Online | Ciphers Online',
                'meta_description' => 'Beaufort aracını online kullanın: alfabeyi seçin, anahtar kelimeyi girin, hemen şifreleyin veya çözün.',
            ],
        ];
    }
}
