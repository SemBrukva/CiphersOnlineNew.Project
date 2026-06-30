<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет блок «Таблица английского алфавита» к шифру «Пляшущие человечки».
 */
class SeedDancingMenAlphabetBlock extends Migration
{
    /** Координаты концов рук: 0 = вниз, 1 = горизонтально, 2 = вверх. */
    private const LEFT_ARM_PTS  = [[9, 24], [6, 17], [9, 10]];
    private const RIGHT_ARM_PTS = [[21, 24], [24, 17], [21, 10]];

    /** Координаты концов ног: 0 = вместе, 1 = лев., 2 = прав., 3 = обе. */
    private const LEFT_LEG_PTS  = [[11, 42], [7, 42], [11, 42], [7, 42]];
    private const RIGHT_LEG_PTS = [[19, 42], [19, 42], [23, 42], [23, 42]];

    /** Английский алфавит: буква → [левая рука, правая рука, ноги]. */
    private const ALPHABET_EN = [
        'A' => [0, 0, 0], 'B' => [0, 0, 1], 'C' => [0, 0, 2], 'D' => [0, 0, 3],
        'E' => [0, 1, 0], 'F' => [0, 1, 1], 'G' => [0, 1, 2], 'H' => [0, 1, 3],
        'I' => [0, 2, 0], 'J' => [0, 2, 1], 'K' => [0, 2, 2], 'L' => [0, 2, 3],
        'M' => [1, 0, 0], 'N' => [1, 0, 1], 'O' => [1, 0, 2], 'P' => [1, 0, 3],
        'Q' => [1, 1, 0], 'R' => [1, 1, 1], 'S' => [1, 1, 2], 'T' => [1, 1, 3],
        'U' => [1, 2, 0], 'V' => [1, 2, 1], 'W' => [1, 2, 2], 'X' => [1, 2, 3],
        'Y' => [2, 0, 0], 'Z' => [2, 0, 1],
    ];

    /**
     * Добавляет блок с таблицей английского алфавита.
     */
    public function up(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['dancing-men']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $now      = date('Y-m-d H:i:s');
        $blockId  = $this->upsertBlock($cipherId, 20, $now);
        $html     = $this->buildAlphabetHtml();

        $titles = [
            'en' => 'English Alphabet Reference',
            'ru' => 'Таблица английского алфавита',
            'de' => 'Englisches Alphabet – Übersicht',
            'es' => 'Referencia del alfabeto inglés',
            'fr' => 'Référence de l\'alphabet anglais',
            'it' => 'Riferimento all\'alfabeto inglese',
            'pt' => 'Referência do alfabeto inglês',
            'tr' => 'İngilizce Alfabe Referansı',
        ];

        foreach ($titles as $language => $title) {
            $this->upsertBlockTranslation($blockId, $language, $title, $html, $now);
        }
    }

    /**
     * Удаляет блок таблицы алфавита.
     */
    public function down(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['dancing-men']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];

        $block = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, 20]
        );

        if ($block === false) {
            return;
        }

        $blockId = (int) $block['id'];
        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ?',
            [$blockId]
        );
        $this->db->execute(
            'DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE id = ?',
            [$blockId]
        );
    }

    /**
     * Создаёт или обновляет запись блока.
     */
    private function upsertBlock(int $cipherId, int $sortOrder, string $now): int
    {
        $row = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [$cipherId, $sortOrder]
        );

        if ($row !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_BLOCKS . ' SET published = 1, updated_at = ? WHERE id = ?',
                [$now, (int) $row['id']]
            );
            return (int) $row['id'];
        }

        return (int) $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS . ' (app_id, sort_order, published, created_at, updated_at) VALUES (?, ?, 1, ?, ?)',
            [$cipherId, $sortOrder, $now, $now]
        );
    }

    /**
     * Создаёт или обновляет перевод блока.
     */
    private function upsertBlockTranslation(int $blockId, string $language, string $title, string $text, string $now): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ? AND language = ? LIMIT 1',
            [$blockId, $language]
        );

        if ($existing !== false) {
            $this->db->execute(
                'UPDATE ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS
                . ' SET `title` = ?, `text` = ?, updated_at = ? WHERE id = ?',
                [$title, $text, $now, (int) $existing['id']]
            );
            return;
        }

        $this->db->insert(
            'INSERT INTO ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS
            . ' (`block_id`, `language`, `title`, `text`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?)',
            [$blockId, $language, $title, $text, $now, $now]
        );
    }

    /**
     * Генерирует HTML с сеткой фигурок для 26 букв английского алфавита.
     */
    private function buildAlphabetHtml(): string
    {
        $cells = '';

        foreach (self::ALPHABET_EN as $letter => [$la, $ra, $legs]) {
            [$lax, $lay] = self::LEFT_ARM_PTS[$la];
            [$rax, $ray] = self::RIGHT_ARM_PTS[$ra];
            [$llx, $lly] = self::LEFT_LEG_PTS[$legs];
            [$rlx, $rly] = self::RIGHT_LEG_PTS[$legs];

            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 48">'
                 . '<circle cx="15" cy="6" r="4.5" fill="none" stroke="currentColor" stroke-width="1.8"/>'
                 . '<line x1="15" y1="10.5" x2="15" y2="28" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>'
                 . '<line x1="15" y1="17" x2="' . $lax . '" y2="' . $lay . '" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>'
                 . '<line x1="15" y1="17" x2="' . $rax . '" y2="' . $ray . '" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>'
                 . '<line x1="15" y1="28" x2="' . $llx . '" y2="' . $lly . '" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>'
                 . '<line x1="15" y1="28" x2="' . $rlx . '" y2="' . $rly . '" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>'
                 . '</svg>';

            $cells .= '<div class="dm-ref-cell">' . $svg . '<span class="dm-ref-letter">' . $letter . '</span></div>';
        }

        return '<div class="dm-ref-grid">' . $cells . '</div>';
    }
}
