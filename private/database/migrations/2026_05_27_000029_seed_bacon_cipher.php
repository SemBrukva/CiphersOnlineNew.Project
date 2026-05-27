<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет шифр Бэкона в категорию классических шифров.
 */
class SeedBaconCipher extends Migration
{
    /**
     * Создаёт или обновляет шифр bacon и его переводы.
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
            [$categoryId, 'bacon']
        );

        if ($cipher === false) {
            $cipherId = (int) $this->db->insert(
                'INSERT INTO ' . Tables::CIPHERS
                . ' (category_id, alias, calculation_mode, sort_order, published, created_at, updated_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$categoryId, 'bacon', 'api', 70, 1, $now, $now]
            );
        } else {
            $cipherId = (int) $cipher['id'];

            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS
                . ' SET calculation_mode = ?, sort_order = ?, published = ?, updated_at = ? '
                . 'WHERE id = ?',
                ['api', 70, 1, $now, $cipherId]
            );
        }

        foreach ($this->translations() as $language => $translation) {
            $this->upsertTranslation($cipherId, $language, $translation, $now);
        }
    }

    /**
     * Удаляет шифр bacon и связанные с ним переводы.
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
            [$categoryId, 'bacon']
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
     * Возвращает переводы для шифра Бэкона.
     *
     * @return array<string, array{name: string, name_short: string, description: string, description_stort: string, meta_title: string, meta_description: string}>
     */
    private function translations(): array
    {
        return [
            'en' => [
                'name' => 'Bacon Cipher',
                'name_short' => 'Bacon',
                'description' => 'Encode and decode text with the Bacon cipher using five-letter A/B groups and selectable alphabet.',
                'description_stort' => 'A/B group encoding by Bacon cipher.',
                'meta_title' => 'Bacon Cipher Online | Ciphers Online',
                'meta_description' => 'Use Bacon cipher online: choose alphabet and convert text to A/B groups or decode back.',
            ],
            'ru' => [
                'name' => 'Шифр Бэкона',
                'name_short' => 'Бэкон',
                'description' => 'Онлайн-инструмент для кодирования и декодирования шифром Бэкона с группами A/B и выбором алфавита.',
                'description_stort' => 'Кодирование шифром Бэкона через группы A/B.',
                'meta_title' => 'Шифр Бэкона Онлайн | Ciphers Online',
                'meta_description' => 'Используйте шифр Бэкона онлайн: выберите алфавит и преобразуйте текст в группы A/B или обратно.',
            ],
            'de' => [
                'name' => 'Bacon-Chiffre',
                'name_short' => 'Bacon',
                'description' => 'Text mit der Bacon-Chiffre über fünfstellige A/B-Gruppen und wählbares Alphabet kodieren und dekodieren.',
                'description_stort' => 'Bacon-Kodierung mit A/B-Gruppen.',
                'meta_title' => 'Bacon-Chiffre Online | Ciphers Online',
                'meta_description' => 'Bacon-Chiffre online nutzen: Alphabet wählen, Text in A/B-Gruppen umwandeln oder zurück dekodieren.',
            ],
            'es' => [
                'name' => 'Cifrado Bacon',
                'name_short' => 'Bacon',
                'description' => 'Codifica y decodifica texto con el cifrado Bacon usando grupos A/B de cinco símbolos y alfabeto seleccionable.',
                'description_stort' => 'Codificación Bacon con grupos A/B.',
                'meta_title' => 'Cifrado Bacon Online | Ciphers Online',
                'meta_description' => 'Usa Bacon online: elige alfabeto y convierte texto a grupos A/B o decodifica al instante.',
            ],
            'fr' => [
                'name' => 'Chiffre de Bacon',
                'name_short' => 'Bacon',
                'description' => 'Encodez et décodez du texte avec le chiffre de Bacon via groupes A/B de cinq caractères et alphabet sélectionnable.',
                'description_stort' => 'Encodage Bacon en groupes A/B.',
                'meta_title' => 'Chiffre de Bacon en ligne | Ciphers Online',
                'meta_description' => 'Utilisez Bacon en ligne : choisissez l’alphabet, convertissez en groupes A/B ou décodez immédiatement.',
            ],
            'it' => [
                'name' => 'Cifrario Bacon',
                'name_short' => 'Bacon',
                'description' => 'Codifica e decodifica testo con il cifrario Bacon usando gruppi A/B di cinque simboli e alfabeto selezionabile.',
                'description_stort' => 'Codifica Bacon con gruppi A/B.',
                'meta_title' => 'Cifrario Bacon Online | Ciphers Online',
                'meta_description' => 'Usa Bacon online: scegli alfabeto, converti il testo in gruppi A/B o decodifica subito.',
            ],
            'pt' => [
                'name' => 'Cifra de Bacon',
                'name_short' => 'Bacon',
                'description' => 'Codifique e decodifique texto com a cifra de Bacon usando grupos A/B de cinco símbolos e alfabeto selecionável.',
                'description_stort' => 'Codificação Bacon com grupos A/B.',
                'meta_title' => 'Cifra de Bacon Online | Ciphers Online',
                'meta_description' => 'Use Bacon online: escolha o alfabeto e converta texto em grupos A/B ou decodifique na hora.',
            ],
            'tr' => [
                'name' => 'Bacon Şifresi',
                'name_short' => 'Bacon',
                'description' => 'Bacon şifresiyle metni beşli A/B grupları ve seçilebilir alfabe kullanarak kodlayın ve çözün.',
                'description_stort' => 'A/B gruplarıyla Bacon kodlaması.',
                'meta_title' => 'Bacon Şifresi Online | Ciphers Online',
                'meta_description' => 'Bacon aracını online kullanın: alfabeyi seçin, metni A/B gruplarına dönüştürün veya geri çözün.',
            ],
        ];
    }
}
