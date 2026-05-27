<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Гронсфельда в категорию классических шифров.
 */
class SeedGronsfeldCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр gronsfeld и его переводы.
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
            [$categoryId, 'gronsfeld']
        );

        if ($cipher === false) {
            $cipherId = (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'gronsfeld', 'api', 40, 1, $now, $now]
            );
        } else {
            $cipherId = (int) $cipher['id'];

            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? '
                . 'WHERE id = ?',
                ['api', 40, 1, $now, $cipherId]
            );
        }

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }
    }

    /**
     * Удаляет шифр gronsfeld и связанные с ним переводы.
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
            [$categoryId, 'gronsfeld']
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
     * Возвращает переводы для шифра Гронсфельда.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Gronsfeld Cipher',
                'name_short' => 'Gronsfeld',
                'description' => 'Encrypt and decrypt text with the Gronsfeld cipher using a numeric key and selectable alphabet.',
                'description_stort' => 'Numeric-key Gronsfeld encryption and decryption.',
                'meta_title' => 'Gronsfeld Cipher Online | Ciphers Online',
                'meta_description' => 'Use the Gronsfeld cipher online: choose alphabet, enter numeric key, encrypt or decrypt instantly.',
            ],
            'ru' => [
                'name' => 'Шифр Гронсфельда',
                'name_short' => 'Гронсфельд',
                'description' => 'Онлайн-инструмент для шифрования и расшифровки текста шифром Гронсфельда с числовым ключом и выбором алфавита.',
                'description_stort' => 'Шифрование и расшифровка Гронсфельда по числовому ключу.',
                'meta_title' => 'Шифр Гронсфельда Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр Гронсфельда онлайн: выберите алфавит, задайте числовой ключ и получите результат мгновенно.',
            ],
            'de' => [
                'name' => 'Gronsfeld-Chiffre',
                'name_short' => 'Gronsfeld',
                'description' => 'Text mit der Gronsfeld-Chiffre per numerischem Schlüssel und wählbarem Alphabet ver- und entschlüsseln.',
                'description_stort' => 'Gronsfeld-Verfahren mit Zahlenschlüssel.',
                'meta_title' => 'Gronsfeld-Chiffre Online | Ciphers Online',
                'meta_description' => 'Gronsfeld-Chiffre online nutzen: Alphabet wählen, Zahlenschlüssel eingeben, direkt ver- oder entschlüsseln.',
            ],
            'es' => [
                'name' => 'Cifrado Gronsfeld',
                'name_short' => 'Gronsfeld',
                'description' => 'Cifra y descifra texto con el cifrado Gronsfeld usando una clave numérica y alfabeto seleccionable.',
                'description_stort' => 'Cifrado Gronsfeld con clave numérica.',
                'meta_title' => 'Cifrado Gronsfeld Online | Ciphers Online',
                'meta_description' => 'Usa Gronsfeld online: elige alfabeto, introduce clave numérica y cifra o descifra al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre de Gronsfeld',
                'name_short' => 'Gronsfeld',
                'description' => 'Chiffrez et déchiffrez du texte avec le chiffre de Gronsfeld via clé numérique et alphabet sélectionnable.',
                'description_stort' => 'Chiffrement Gronsfeld par clé numérique.',
                'meta_title' => 'Chiffre de Gronsfeld en ligne | Ciphers Online',
                'meta_description' => 'Utilisez Gronsfeld en ligne : choisissez l’alphabet, saisissez la clé numérique et chiffrez ou déchiffrez immédiatement.',
            ],
            'it' => [
                'name' => 'Cifrario Gronsfeld',
                'name_short' => 'Gronsfeld',
                'description' => 'Cifra e decifra testo con il cifrario Gronsfeld usando chiave numerica e alfabeto selezionabile.',
                'description_stort' => 'Gronsfeld con chiave numerica.',
                'meta_title' => 'Cifrario Gronsfeld Online | Ciphers Online',
                'meta_description' => 'Usa Gronsfeld online: scegli alfabeto, inserisci chiave numerica e cifra o decifra subito.',
            ],
            'pt' => [
                'name' => 'Cifra de Gronsfeld',
                'name_short' => 'Gronsfeld',
                'description' => 'Criptografe e descriptografe texto com a cifra de Gronsfeld usando chave numérica e alfabeto selecionável.',
                'description_stort' => 'Gronsfeld com chave numérica.',
                'meta_title' => 'Cifra de Gronsfeld Online | Ciphers Online',
                'meta_description' => 'Use Gronsfeld online: escolha o alfabeto, informe a chave numérica e cifre ou decifre na hora.',
            ],
            'tr' => [
                'name' => 'Gronsfeld Şifresi',
                'name_short' => 'Gronsfeld',
                'description' => 'Sayısal anahtar ve seçilebilir alfabe ile Gronsfeld şifresi kullanarak metni şifreleyin ve çözün.',
                'description_stort' => 'Sayısal anahtarlı Gronsfeld aracı.',
                'meta_title' => 'Gronsfeld Şifresi Online | Ciphers Online',
                'meta_description' => 'Gronsfeld aracını online kullanın: alfabeyi seçin, sayısal anahtarı girin, hemen şifreleyin veya çözün.',
            ],
        ];
    }
}
