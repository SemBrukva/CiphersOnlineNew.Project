<?php

declare(strict_types=1);

use App\Database\Migration;
use App\Database\Tables;

/**
 * Добавляет блок «Таблица алфавита» к шифру масонов (Pigpen) — по сетке символов
 * для каждого из трёх вариантов (стандартный, чередующийся, розенкрейцерский).
 */
class SeedPigpenAlphabetBlock extends Migration
{
    /** Грани ячейки решётки 3×3 по индексу 0..8: [верх, право, низ, лево]. */
    private const GRID_EDGES = [
        [0, 1, 1, 0], [0, 1, 1, 1], [0, 0, 1, 1],
        [1, 1, 1, 0], [1, 1, 1, 1], [1, 0, 1, 1],
        [1, 1, 0, 0], [1, 1, 0, 1], [1, 0, 0, 1],
    ];

    /** Позиция точки внутри клина «икса» по ориентации. */
    private const X_DOT_POS = ['u' => [15, 8], 'd' => [15, 22], 'l' => [8, 15], 'r' => [22, 15]];

    /** Ориентации клиньев «икса» в порядке заполнения. */
    private const X_ORDER = ['u', 'l', 'r', 'd'];

    /** Английский алфавит (26 букв). */
    private const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Добавляет блок с таблицами символов всех вариантов.
     */
    public function up(): void
    {
        $cipher = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS . ' WHERE alias = ? LIMIT 1',
            ['pigpen']
        );

        if ($cipher === false) {
            return;
        }

        $cipherId = (int) $cipher['id'];
        $now      = date('Y-m-d H:i:s');
        $blockId  = $this->upsertBlock($cipherId, 20, $now);

        $titles = [
            'en' => 'Pigpen Alphabet Reference',
            'ru' => 'Таблица алфавита шифра масонов',
            'de' => 'Pigpen-Alphabet – Übersicht',
            'es' => 'Referencia del alfabeto Pigpen',
            'fr' => 'Référence de l\'alphabet Pigpen',
            'it' => 'Riferimento all\'alfabeto Pigpen',
            'pt' => 'Referência do alfabeto Pigpen',
            'tr' => 'Pigpen Alfabe Referansı',
        ];

        $variantLabels = [
            'en' => ['standard' => 'Standard (Masonic)',     'variant' => 'Variant (grid & cross)',  'rosicrucian' => 'Rosicrucian'],
            'ru' => ['standard' => 'Стандартный (масонский)', 'variant' => 'Вариант (решётка и крест)', 'rosicrucian' => 'Розенкрейцерский'],
            'de' => ['standard' => 'Standard (Freimaurer)',   'variant' => 'Variante (Gitter & Kreuz)', 'rosicrucian' => 'Rosenkreuzer'],
            'es' => ['standard' => 'Estándar (masónico)',     'variant' => 'Variante (rejilla y cruz)', 'rosicrucian' => 'Rosacruz'],
            'fr' => ['standard' => 'Standard (maçonnique)',   'variant' => 'Variante (grille et croix)', 'rosicrucian' => 'Rose-Croix'],
            'it' => ['standard' => 'Standard (massonico)',    'variant' => 'Variante (griglia e croce)', 'rosicrucian' => 'Rosacroce'],
            'pt' => ['standard' => 'Padrão (maçônico)',       'variant' => 'Variante (grade e cruz)',   'rosicrucian' => 'Rosa-cruz'],
            'tr' => ['standard' => 'Standart (Mason)',        'variant' => 'Varyant (ızgara ve çapraz)', 'rosicrucian' => 'Gül-Haç'],
        ];

        foreach ($titles as $language => $title) {
            $html = $this->buildHtml($variantLabels[$language]);
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
            ['pigpen']
        );

        if ($cipher === false) {
            return;
        }

        $block = $this->db->fetch(
            'SELECT id FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE app_id = ? AND sort_order = ? LIMIT 1',
            [(int) $cipher['id'], 20]
        );

        if ($block === false) {
            return;
        }

        $blockId = (int) $block['id'];
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS_TRANSLATIONS . ' WHERE block_id = ?', [$blockId]);
        $this->db->execute('DELETE FROM ' . Tables::CIPHERS_BLOCKS . ' WHERE id = ?', [$blockId]);
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
     * Собирает HTML со всеми тремя вариантами.
     *
     * @param array{standard: string, variant: string, rosicrucian: string} $labels
     */
    private function buildHtml(array $labels): string
    {
        $html = '';
        foreach (['standard', 'variant', 'rosicrucian'] as $variant) {
            $html .= '<div class="pp-ref-section">'
                . '<h3 class="pp-ref-section-title">' . htmlspecialchars($labels[$variant], ENT_QUOTES) . '</h3>'
                . $this->buildVariantGrid($variant)
                . '</div>';
        }

        return $html;
    }

    /**
     * Строит сетку из 26 ячеек для указанного варианта.
     */
    private function buildVariantGrid(string $variant): string
    {
        $map   = $this->variantMap($variant);
        $cells = '';

        foreach (str_split(self::LETTERS) as $letter) {
            $cells .= '<div class="pp-ref-cell">'
                . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30">' . $this->glyphSvg($map[$letter]) . '</svg>'
                . '<span class="pp-ref-letter">' . $letter . '</span>'
                . '</div>';
        }

        return '<div class="pp-ref-grid">' . $cells . '</div>';
    }

    /**
     * Возвращает таблицу буква → дескриптор символа [k, s, d] для варианта.
     *
     * @return array<string, array{0: string, 1: int|string, 2: string}>
     */
    private function variantMap(string $variant): array
    {
        $letters = str_split(self::LETTERS);
        $map = [];

        if ($variant === 'rosicrucian') {
            $dots = ['l', 'c', 'r'];
            foreach ($letters as $i => $letter) {
                $map[$letter] = ['g', intdiv($i, 3), $dots[$i % 3]];
            }

            return $map;
        }

        if ($variant === 'variant') {
            for ($i = 0; $i < 9; $i++) {
                $map[$letters[$i]] = ['g', $i, 'none'];
            }
            for ($i = 0; $i < 4; $i++) {
                $map[$letters[9 + $i]] = ['x', self::X_ORDER[$i], 'none'];
            }
            for ($i = 0; $i < 9; $i++) {
                $map[$letters[13 + $i]] = ['g', $i, 'c'];
            }
            for ($i = 0; $i < 4; $i++) {
                $map[$letters[22 + $i]] = ['x', self::X_ORDER[$i], 'c'];
            }

            return $map;
        }

        // standard
        for ($i = 0; $i < 9; $i++) {
            $map[$letters[$i]] = ['g', $i, 'none'];
        }
        for ($i = 0; $i < 9; $i++) {
            $map[$letters[9 + $i]] = ['g', $i, 'c'];
        }
        for ($i = 0; $i < 4; $i++) {
            $map[$letters[18 + $i]] = ['x', self::X_ORDER[$i], 'none'];
        }
        for ($i = 0; $i < 4; $i++) {
            $map[$letters[22 + $i]] = ['x', self::X_ORDER[$i], 'c'];
        }

        return $map;
    }

    /**
     * Строит внутренний SVG-код одного символа.
     *
     * @param array{0: string, 1: int|string, 2: string} $glyph
     */
    private function glyphSvg(array $glyph): string
    {
        [$kind, $shape, $dot] = $glyph;

        $line = static fn (int $x1, int $y1, int $x2, int $y2): string =>
            '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2
            . '" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>';
        $circle = static fn (int $x, int $y): string =>
            '<circle cx="' . $x . '" cy="' . $y . '" r="2.4" fill="currentColor"/>';

        $out = '';

        if ($kind === 'g') {
            [$t, $r, $b, $l] = self::GRID_EDGES[$shape] ?? self::GRID_EDGES[0];
            if ($t) {
                $out .= $line(3, 3, 27, 3);
            }
            if ($r) {
                $out .= $line(27, 3, 27, 27);
            }
            if ($b) {
                $out .= $line(3, 27, 27, 27);
            }
            if ($l) {
                $out .= $line(3, 3, 3, 27);
            }
            if ($dot !== 'none') {
                $dx = $dot === 'l' ? 9 : ($dot === 'r' ? 21 : 15);
                $out .= $circle($dx, 15);
            }

            return $out;
        }

        // kind === 'x'
        $out .= match ($shape) {
            'u' => $line(15, 15, 3, 3) . $line(15, 15, 27, 3),
            'd' => $line(15, 15, 3, 27) . $line(15, 15, 27, 27),
            'l' => $line(15, 15, 3, 3) . $line(15, 15, 3, 27),
            default => $line(15, 15, 27, 3) . $line(15, 15, 27, 27),
        };
        if ($dot === 'c') {
            [$dx, $dy] = self::X_DOT_POS[$shape] ?? [15, 15];
            $out .= $circle($dx, $dy);
        }

        return $out;
    }
}
